<?php
class ControllerApiCoupon extends Controller {
	const WELCOME_COUPON_NAME = 'Welcome coupon';
	const WELCOME_COUPON_PREFIX = 'WELCOME-';
	const WELCOME_COUPON_DISCOUNT = 10;
	const WELCOME_EVENT_NAME = 'Welcome Coupon Ready';

	public function index() {
		$this->load->language('api/coupon');

		unset($this->session->data['coupon']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/total/coupon');

			$coupon = isset($this->request->post['coupon']) ? $this->request->post['coupon'] : '';
			$coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

			if ($coupon_info) {
				$this->session->data['coupon'] = $coupon;
				$json['success'] = $this->language->get('text_success');
			} else {
				$json['error'] = $this->language->get('error_coupon');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function issue() {
		if (!isset($this->request->server['REQUEST_METHOD']) || $this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->jsonResponse(405, array('error' => 'method_not_allowed'));
			return;
		}

		$configured_webhook_token = $this->getConfiguredWebhookToken();

		if ($configured_webhook_token === '') {
			$this->log->write('[welcome_coupon] webhook token is not configured');
			$this->jsonResponse(503, array('error' => 'webhook_not_configured'));
			return;
		}

		if (!$this->isWebhookAuthorized($configured_webhook_token)) {
			$this->jsonResponse(401, array('error' => 'unauthorized'));
			return;
		}

		$raw_input = file_get_contents('php://input');
		$input = json_decode($raw_input, true);

		if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
			$this->jsonResponse(400, array('error' => 'invalid_json'));
			return;
		}

		$email = isset($input['email']) ? strtolower(trim((string)$input['email'])) : '';

		if (strlen($email) > 96 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->jsonResponse(400, array('error' => 'invalid_email'));
			return;
		}

		$email_hash = hash('sha256', $email);
		$this->load->library('klaviyo');
		$legacy_code = '';

		try {
			$mapping_exists = $this->hasWelcomeCouponMapping($email_hash);
		} catch (\Exception $e) {
			$this->log->write('[welcome_coupon] mapping lookup failed: email_hash=' . $email_hash);
			$this->jsonResponse(500, array('error' => 'coupon_persistence_failed'));
			return;
		}

		if (!$mapping_exists) {
			$profile_result = $this->klaviyo->getProfileByEmail($email);

			if (!$profile_result['success']) {
				$status = !empty($profile_result['retryable']) ? 503 : 502;
				$this->jsonResponse($status, array('error' => 'klaviyo_profile_lookup_failed'));
				return;
			}

			if (!empty($profile_result['profile']['attributes']['properties']['welcome_code'])) {
				$candidate = $profile_result['profile']['attributes']['properties']['welcome_code'];

				if ($this->isWelcomeCode($candidate)) {
					$legacy_code = $candidate;
				}
			}
		}

		try {
			$welcome_coupon = $this->getOrCreateWelcomeCoupon($email_hash, $legacy_code);
		} catch (\Exception $e) {
			if ($e->getMessage() === 'welcome_coupon_revoked') {
				$this->log->write('[welcome_coupon] mapped coupon is no longer available: email_hash=' . $email_hash);
				$this->jsonResponse(410, array('error' => 'coupon_unavailable'));
				return;
			}

			$this->log->write('[welcome_coupon] database operation failed: email_hash=' . $email_hash);
			$this->jsonResponse(500, array('error' => 'coupon_persistence_failed'));
			return;
		}

		if (!empty($welcome_coupon['event_sent_at'])) {
			$this->welcomeCouponResponse($welcome_coupon);
			return;
		}

		$event_unique_id = 'amds-welcome-coupon-v1-' . $welcome_coupon['code'];

		// Klaviyo accepts events asynchronously. On retries, confirm that the
		// previously accepted event really reached the profile before returning 2xx.
		if ($mapping_exists) {
			$confirmation = $this->getWelcomeEventConfirmation($email, $welcome_coupon['code'], $event_unique_id, $welcome_coupon['date_added']);

			if (!$confirmation['success']) {
				$this->recordEventFailure($welcome_coupon['welcome_coupon_id'], $confirmation['error']);
				$status = !empty($confirmation['retryable']) ? 503 : 502;
				$this->jsonResponse($status, array('error' => 'klaviyo_event_confirmation_failed'));
				return;
			}

			if (!empty($confirmation['event_exists'])) {
				$this->recordEventSuccess($welcome_coupon['welcome_coupon_id']);
				$this->welcomeCouponResponse($welcome_coupon);
				return;
			}
		}

		$event_result = $this->klaviyo->createEvent(
			self::WELCOME_EVENT_NAME,
			$email,
			array(
				'welcome_code' => $welcome_coupon['code'],
				'discount_percentage' => self::WELCOME_COUPON_DISCOUNT,
				'source_event_id' => $event_unique_id
			),
			array(
				'welcome_code' => $welcome_coupon['code']
			),
			$event_unique_id
		);

		if (!$event_result['success']) {
			$this->recordEventFailure($welcome_coupon['welcome_coupon_id'], $event_result['error']);
			$status = !empty($event_result['retryable']) ? 503 : 502;
			$this->jsonResponse($status, array(
				'error' => 'klaviyo_event_failed',
				'code' => $welcome_coupon['code'],
				'created' => $welcome_coupon['created']
			));
			return;
		}

		// A 202 only means accepted for asynchronous processing. A retry with the
		// same stable unique_id is safe; return 503 until GET /events confirms it.
		$this->recordEventFailure($welcome_coupon['welcome_coupon_id'], 'awaiting_confirmation');
		$this->jsonResponse(503, array(
			'error' => 'klaviyo_event_pending',
			'code' => $welcome_coupon['code'],
			'created' => $welcome_coupon['created']
		));
	}

	private function hasWelcomeCouponMapping($email_hash) {
		$query = $this->db->query("SELECT welcome_coupon_id FROM `" . DB_PREFIX . "welcome_coupon` WHERE email_hash = '" . $this->db->escape($email_hash) . "' LIMIT 1");

		return (bool)$query->num_rows;
	}

	private function getOrCreateWelcomeCoupon($email_hash, $legacy_code = '') {
		$lock_name = 'amds_welcome_' . substr($email_hash, 0, 40);
		$lock_query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock_name) . "', 5) AS acquired");

		if (empty($lock_query->row) || (int)$lock_query->row['acquired'] !== 1) {
			throw new \RuntimeException('welcome_coupon_lock_unavailable');
		}

		try {
			$existing = $this->db->query("SELECT wc.welcome_coupon_id, wc.coupon_id, wc.code, wc.event_sent_at, wc.date_added, c.coupon_id AS existing_coupon_id FROM `" . DB_PREFIX . "welcome_coupon` wc LEFT JOIN `" . DB_PREFIX . "coupon` c ON (c.coupon_id = wc.coupon_id AND c.code = wc.code) WHERE wc.email_hash = '" . $this->db->escape($email_hash) . "' LIMIT 1");

			if ($existing->num_rows && !empty($existing->row['existing_coupon_id'])) {

				return array(
					'welcome_coupon_id' => (int)$existing->row['welcome_coupon_id'],
					'coupon_id' => (int)$existing->row['coupon_id'],
					'code' => $existing->row['code'],
					'event_sent_at' => $existing->row['event_sent_at'],
					'date_added' => $existing->row['date_added'],
					'created' => false
				);
			}

			if ($existing->num_rows) {
				if (!empty($existing->row['coupon_id'])) {
					throw new \RuntimeException('welcome_coupon_revoked');
				}

				$code = $existing->row['code'];

				if (!$this->isWelcomeCode($code)) {
					throw new \RuntimeException('welcome_coupon_mapping_invalid');
				}

				$coupon_query = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");
				$coupon_id = $coupon_query->num_rows ? (int)$coupon_query->row['coupon_id'] : 0;
				$created = false;

				if (!$coupon_id) {
					$coupon_id = $this->createCoupon($code);
					$created = true;
				}

				$this->db->query("UPDATE `" . DB_PREFIX . "welcome_coupon` SET coupon_id = '" . $coupon_id . "', event_sent_at = NULL, last_error = NULL, date_modified = NOW() WHERE welcome_coupon_id = '" . (int)$existing->row['welcome_coupon_id'] . "'");

				return array(
					'welcome_coupon_id' => (int)$existing->row['welcome_coupon_id'],
					'coupon_id' => $coupon_id,
					'code' => $code,
					'event_sent_at' => null,
					'date_added' => $existing->row['date_added'],
					'created' => $created
				);
			}

			$code = $this->isWelcomeCode($legacy_code) ? $legacy_code : $this->generateUniqueCode();
			// Reserve the code first: the stock OpenCart coupon table may be MyISAM,
			// so a retry must be able to repair an interrupted coupon insert/update.
			$date_added = date('Y-m-d H:i:s');
			$this->db->query("INSERT INTO `" . DB_PREFIX . "welcome_coupon` SET `email_hash` = '" . $this->db->escape($email_hash) . "', `coupon_id` = NULL, `code` = '" . $this->db->escape($code) . "', `date_added` = '" . $this->db->escape($date_added) . "', `date_modified` = NOW()");
			$welcome_coupon_id = (int)$this->db->getLastId();
			$coupon_query = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");
			$coupon_id = $coupon_query->num_rows ? (int)$coupon_query->row['coupon_id'] : 0;
			$created = false;

			if (!$coupon_id) {
				$coupon_id = $this->createCoupon($code);
				$created = true;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "welcome_coupon` SET coupon_id = '" . $coupon_id . "', date_modified = NOW() WHERE welcome_coupon_id = '" . $welcome_coupon_id . "'");

			return array(
				'welcome_coupon_id' => $welcome_coupon_id,
				'coupon_id' => $coupon_id,
				'code' => $code,
				'event_sent_at' => null,
				'date_added' => $date_added,
				'created' => $created
			);
		} finally {
			try {
				$this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock_name) . "')");
			} catch (\Exception $e) {
				$this->log->write('[welcome_coupon] could not release database lock: email_hash=' . $email_hash);
			}
		}
	}

	private function createCoupon($code) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon` SET `name` = '" . $this->db->escape(self::WELCOME_COUPON_NAME) . "', `code` = '" . $this->db->escape($code) . "', `discount` = '" . (int)self::WELCOME_COUPON_DISCOUNT . "', `type` = 'P', `total` = '0', `logged` = '0', `shipping` = '0', `date_start` = '0000-00-00', `date_end` = '0000-00-00', `uses_total` = '1', `uses_customer` = '1', `status` = '1', `date_added` = NOW()");

		return (int)$this->db->getLastId();
	}

	private function isWelcomeCode($code) {
		return is_string($code) && preg_match('/^WELCOME-[A-HJ-NP-Z2-9]{6,12}$/', $code);
	}

	private function generateUniqueCode() {
		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$max = strlen($characters) - 1;

		for ($attempt = 0; $attempt < 10; $attempt++) {
			$suffix = '';

			for ($index = 0; $index < 12; $index++) {
				$suffix .= $characters[random_int(0, $max)];
			}

			$code = self::WELCOME_COUPON_PREFIX . $suffix;
			$query = $this->db->query("SELECT coupon_id FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");

			if (!$query->num_rows) {
				return $code;
			}
		}

		throw new \RuntimeException('welcome_coupon_code_unavailable');
	}

	private function recordEventSuccess($welcome_coupon_id) {
		try {
			$this->db->query("UPDATE `" . DB_PREFIX . "welcome_coupon` SET event_sent_at = COALESCE(event_sent_at, NOW()), last_error = NULL, date_modified = NOW() WHERE welcome_coupon_id = '" . (int)$welcome_coupon_id . "'");
		} catch (\Exception $e) {
			$this->log->write('[welcome_coupon] could not record event success: id=' . (int)$welcome_coupon_id);
		}
	}

	private function recordEventFailure($welcome_coupon_id, $error) {
		try {
			$this->db->query("UPDATE `" . DB_PREFIX . "welcome_coupon` SET last_error = '" . $this->db->escape(substr((string)$error, 0, 160)) . "', date_modified = NOW() WHERE welcome_coupon_id = '" . (int)$welcome_coupon_id . "'");
		} catch (\Exception $e) {
			$this->log->write('[welcome_coupon] could not record event failure: id=' . (int)$welcome_coupon_id);
		}
	}

	private function getWelcomeEventConfirmation($email, $code, $event_unique_id, $coupon_date_added) {
		$profile_result = $this->klaviyo->getProfileByEmail($email);

		if (!$profile_result['success']) {
			return $profile_result;
		}

		if (empty($profile_result['profile']['id'])) {
			$profile_result['event_exists'] = false;
			return $profile_result;
		}

		return $this->klaviyo->getProfileEventByProperty(
			$profile_result['profile']['id'],
			self::WELCOME_EVENT_NAME,
			'welcome_code',
			$code,
			array('source_event_id' => $event_unique_id),
			date('c', strtotime($coupon_date_added . ' -1 hour'))
		);
	}

	private function welcomeCouponResponse($welcome_coupon) {
		$this->jsonResponse(200, array(
			'code' => $welcome_coupon['code'],
			'created' => $welcome_coupon['created'],
			'klaviyo_update' => 'confirmed'
		));
	}

	private function isWebhookAuthorized($configured_token) {
		$provided_token = $this->getWebhookToken();

		if ($provided_token === '') {
			return false;
		}

		return hash_equals($configured_token, $provided_token);
	}

	private function getWebhookToken() {
		if (!empty($this->request->server['HTTP_X_WEBHOOK_TOKEN'])) {
			return trim((string)$this->request->server['HTTP_X_WEBHOOK_TOKEN']);
		}

		$authorization = '';

		if (!empty($this->request->server['HTTP_AUTHORIZATION'])) {
			$authorization = $this->request->server['HTTP_AUTHORIZATION'];
		} elseif (!empty($this->request->server['REDIRECT_HTTP_AUTHORIZATION'])) {
			$authorization = $this->request->server['REDIRECT_HTTP_AUTHORIZATION'];
		}

		if ($authorization !== '' && stripos($authorization, 'Bearer ') === 0) {
			return trim(substr($authorization, 7));
		}

		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if (strcasecmp($name, 'X-Webhook-Token') === 0) {
					return trim((string)$value);
				}

				if (strcasecmp($name, 'Authorization') === 0 && stripos($value, 'Bearer ') === 0) {
					return trim(substr($value, 7));
				}
			}
		}

		return '';
	}

	private function getConfiguredWebhookToken() {
		if (defined('WELCOME_COUPON_WEBHOOK_TOKEN')) {
			$token = trim((string)WELCOME_COUPON_WEBHOOK_TOKEN);

			if ($token !== '') {
				return $token;
			}
		}

		$environment_token = getenv('WELCOME_COUPON_WEBHOOK_TOKEN');

		if ($environment_token !== false && trim((string)$environment_token) !== '') {
			return trim((string)$environment_token);
		}

		if (defined('OC_ENV') && isset(OC_ENV['security']['welcome_coupon_webhook_token'])) {
			$token = trim((string)OC_ENV['security']['welcome_coupon_webhook_token']);

			if ($token !== '') {
				return $token;
			}
		}

		return trim((string)$this->config->get('welcome_coupon_webhook_token'));
	}

	private function jsonResponse($status, $payload) {
		$status_text = array(
			200 => 'OK',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			410 => 'Gone',
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable'
		);

		$text = isset($status_text[$status]) ? $status_text[$status] : 'Error';
		$this->response->addHeader('HTTP/1.1 ' . (int)$status . ' ' . $text);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Cache-Control: no-store');
		$this->response->setOutput(json_encode($payload));
	}
}
