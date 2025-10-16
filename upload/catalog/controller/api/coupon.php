<?php
class ControllerApiCoupon extends Controller {
    public function index() {
        $this->load->language('api/coupon');

        // Delete past coupon in case there is an error
        unset($this->session->data['coupon']);

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/total/coupon');

            if (isset($this->request->post['coupon'])) {
                $coupon = $this->request->post['coupon'];
            } else {
                $coupon = '';
            }

            $coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

            if ($coupon_info) {
                $this->session->data['coupon'] = $this->request->post['coupon'];

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->language->get('error_coupon');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /** Robustno dohvaćanje Authorization headera + fallback varijanti */
    private function getAuthHeader() {
        // standard
        if (!empty($this->request->server['HTTP_AUTHORIZATION'])) {
            return $this->request->server['HTTP_AUTHORIZATION'];
        }
        // neke Apache/Nginx konfiguracije
        if (!empty($this->request->server['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $this->request->server['REDIRECT_HTTP_AUTHORIZATION'];
        }
        // fallback: getallheaders
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach (['Authorization','authorization','X-Webhook-Token','x-webhook-token'] as $k) {
                if (!empty($headers[$k])) {
                    // ako koristimo X-Webhook-Token, normaliziraj na Bearer format
                    if (stripos($k, 'webhook-token') !== false) {
                        return 'Bearer ' . $headers[$k];
                    }
                    return $headers[$k];
                }
            }
        }
        // krajnji fallback: ?token= u query stringu (korisno za brzi test)
        if (!empty($this->request->get['token'])) {
            return 'Bearer ' . $this->request->get['token'];
        }
        return '';
    }

    public function issue() {
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
            return;
        }

        $auth = $this->getAuthHeader();
        if ($auth !== 'Bearer Bakanal40#') { // <-- promijeni vrijednost po potrebi
            $this->response->addHeader('HTTP/1.1 401 Unauthorized');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => 'unauthorized']));
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $email    = isset($input['email']) ? trim($input['email']) : '';
        $prefix   = isset($input['prefix']) ? trim($input['prefix']) : 'WELCOME-';
        $discount = isset($input['discount']) ? (int)$input['discount'] : 10;

        if (!$email) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => 'email required']));
            return;
        }

        // Osiguraj unique indeks nad coupon.code (izvršit će se jednom; kasnije će TRY/CATCH ignorirati)
        $this->ensureUniqueIndex();

        // Generiraj unikatni kod (s prefiksom)
        $code = $this->generateUniqueCode($prefix, 6);

        // Upis u OpenCart kupon tablicu
        $this->db->query("INSERT INTO `" . DB_PREFIX . "coupon` SET
			`name` = 'Welcome coupon',
			`code` = '" . $this->db->escape($code) . "',
			`discount` = '" . (int)$discount . "',
			`type` = 'P',
			`total` = '0',
			`logged` = '0',
			`shipping` = '0',
			`date_start` = '0000-00-00',
    `date_end` = '0000-00-00',
			`uses_total` = '1',
			`uses_customer` = '1',
			`status` = '1',
			`date_added` = NOW()");

        // Upis koda u Klaviyo profil (custom property), npr. person.welcome_code
        $ok = $this->upsertKlaviyoProfile($email, ['welcome_code' => $code]);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'email' => $email,
            'code'  => $code,
            'klaviyo_update' => $ok ? 'ok' : 'failed'
        ]));
    }

    /** Generira unikatni kod i provjerava kolizije u bazi */
    private function generateUniqueCode($prefix, $len = 6) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // bez O/0/I/1
        $max = strlen($chars) - 1;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < $len; $i++) {
                $suffix .= $chars[random_int(0, $max)];
            }
            $code = $prefix . $suffix;

            $q = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "'");
            if (!$q->num_rows) {
                return $code;
            }
        }
        throw new \RuntimeException('Could not generate unique code');
    }

    /** Doda UNIQUE indeks nad `coupon`.`code` (izvršava se jednom; idempotentno) */
    private function ensureUniqueIndex() {
        try {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "coupon` ADD UNIQUE KEY `uniq_coupon_code` (`code`)");
        } catch (\Exception $e) {
            // već postoji ili nema ovlasti – ignoriraj
        }
    }

    /** Upis/izmjena profila u Klaviyo (subscribe + custom properties) */
    private function upsertKlaviyoProfile($email, $props = []) {
        // provjere da su konstante definirane
        if (!defined('KLAV_KEY') || !defined('KLAV_ID')) {
            $this->log->write('[klaviyo] KLAV_KEY or KLAV_ID not defined');
            return false;
        }

        $payload = [
            'api_key'  => KLAV_KEY,
            'profiles' => [[
                'email'      => $email,
                'properties' => $props
            ]]
        ];

        $ch = curl_init('https://a.klaviyo.com/api/v2/list/' . KLAV_ID . '/subscribe');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $result = curl_exec($ch);
        $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->log->write('[klaviyo] curl error: ' . $err);
            return false;
        }
        if ($http < 200 || $http >= 300) {
            $this->log->write('[klaviyo] http ' . $http . ' response: ' . $result);
            return false;
        }
        return true;
    }
}
