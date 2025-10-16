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

    private function getAuthHeader() {
        $auth = $this->request->server['HTTP_AUTHORIZATION'] ?? '';
        if (!$auth && function_exists('getallheaders')) {
            $headers = getallheaders();
            if (!empty($headers['Authorization'])) {
                $auth = $headers['Authorization'];
            } elseif (!empty($headers['authorization'])) {
                $auth = $headers['authorization'];
            }
        }
        return $auth;
    }

    public function issue() {
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
            return;
        }
        $auth = $this->getAuthHeader();
        if ($auth !== 'Bearer Bakanal40#') {
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

        $this->ensureUniqueIndex(); // bolje prije generiranja/inserta
        $code = $this->generateUniqueCode($prefix, 6);

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

        $ok = $this->upsertKlaviyoProfile($email, ['welcome_code' => $code]);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'email' => $email,
            'code'  => $code,
            'klaviyo_update' => $ok ? 'ok' : 'failed'
        ]));
    }

    // generateUniqueCode() i ensureUniqueIndex() ostaju

    private function upsertKlaviyoProfile($email, $props = []) {
        $payload = [
            'api_key' => KLAV_KEY, // MORA biti definirano u config.php
            'profiles' => [[
                'email' => $email,
                'properties' => $props
            ]]
        ];
        $list_id = KLAV_ID;        // MORA biti definirano u config.php

        $ch = curl_init('https://a.klaviyo.com/api/v2/list/' . $list_id . '/subscribe');
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
