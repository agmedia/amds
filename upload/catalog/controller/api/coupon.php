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

    public function issue() {
        // 1) Sigurnost: provjera metode i tokena
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        $auth = $this->request->server['HTTP_AUTHORIZATION'] ?? '';
        if ($auth !== 'Bearer Bakanal40#') {
            $this->response->addHeader('HTTP/1.1 401 Unauthorized');
            return;
        }

        // 2) JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $email   = isset($input['email']) ? trim($input['email']) : '';
        $prefix  = isset($input['prefix']) ? trim($input['prefix']) : 'WELCOME-';
        $discount = isset($input['discount']) ? (int)$input['discount'] : 10;

        if (!$email) {
            $this->response->addHeader('HTTP/1.1 400 Bad Request');
            $this->response->setOutput(json_encode(['error' => 'email required']));
            return;
        }

        // 3) Helperi
        $code = $this->generateUniqueCode($prefix, 6);
        $this->ensureUniqueIndex(); // kreira UNIQUE indeks nad oc_coupon.code ako ne postoji

        // 4) Insert kupona u OC
        $this->db->query("INSERT INTO `" . DB_PREFIX . "coupon` SET
            `name` = 'Welcome coupon',
            `code` = '" . $this->db->escape($code) . "',
            `discount` = '" . (int)$discount . "',
            `type` = 'P',
            `total` = '0',
            `logged` = '0',
            `shipping` = '0',
            `date_start` = NULL,
            `date_end` = NULL,
            `uses_total` = '1',
            `uses_customer` = '1',
            `status` = '1',
            `date_added` = NOW()");

        // 5) Upis koda u Klaviyo profil (custom property)
        $ok = $this->upsertKlaviyoProfile($email, ['welcome_code' => $code]);

        // 6) Odgovor
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'email' => $email,
            'code'  => $code,
            'klaviyo_update' => $ok ? 'ok' : 'failed'
        ]));
    }

    private function generateUniqueCode($prefix, $len = 6) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($chars) - 1;

        // retry na slučaj kolizije (unique indeks)
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < $len; $i++) {
                $suffix .= $chars[random_int(0, $max)];
            }
            $code = $prefix . $suffix;

            $q = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "'");
            if (!$q->num_rows) return $code;
        }
        throw new \RuntimeException('Could not generate unique code');
    }

    private function ensureUniqueIndex() {
        // Kreiraj UNIQUE indeks nad code (izvedi jednom; benigno je i ako prođe više puta pa ga zaštiti TRY/CATCHom)
        try {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "coupon` ADD UNIQUE KEY `uniq_coupon_code` (`code`)");
        } catch (\Exception $e) {
            // već postoji – ignoriraj
        }
    }

    private function upsertKlaviyoProfile($email, $props = []) {
        // Najjednostavnije: upis kroz List Subscribe s custom properties (server-side).
        // Upiše na listu (ako već nije) i ažurira profile properties.
        $payload = [
            'api_key' => KLAV_KEY,
            'profiles' => [[
                'email' => $email,
                'properties' => $props
            ]]
        ];

        $list_id = KLAV_ID;
        $ch = curl_init('https://a.klaviyo.com/api/v2/list/' . $list_id . '/subscribe');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->log->write('[klaviyo] error: ' . $err);
            return false;
        }
        return true;
    }


}
