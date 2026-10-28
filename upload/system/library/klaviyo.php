<?php

class Klaviyo {
	const DEFAULT_API_URL = 'https://a.klaviyo.com';
	const DEFAULT_API_REVISION = '2026-07-15';

	private $config;
	private $db;
	private $log;
	private $last_error = '';

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->db = $registry->get('db');
		$this->log = $registry->get('log');
	}

	public function getLastError() {
		return $this->last_error;
	}

	public function upsertProfile($email, $properties = array(), $attributes = array()) {
		$email = $this->normalizeEmail($email);

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		$profile_attributes = array('email' => $email);

		foreach (array('first_name', 'last_name', 'phone_number', 'external_id') as $key) {
			if (isset($attributes[$key]) && $attributes[$key] !== '') {
				$profile_attributes[$key] = $attributes[$key];
			}
		}

		if ($properties) {
			$profile_attributes['properties'] = $properties;
		}

		return $this->request('POST', '/api/profile-import', array(
			'data' => array(
				'type' => 'profile',
				'attributes' => $profile_attributes
			)
		));
	}

	public function getProfileByEmail($email) {
		$email = $this->normalizeEmail($email);

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		$filter_email = str_replace(array('\\', '"'), array('\\\\', '\\"'), $email);
		$query = http_build_query(array(
			'filter' => 'equals(email,"' . $filter_email . '")',
			'additional-fields[profile]' => 'subscriptions',
			'fields[profile]' => 'email,properties,subscriptions'
		), '', '&', PHP_QUERY_RFC3986);

		$result = $this->request('GET', '/api/profiles?' . $query);

		if ($result['success']) {
			$result['profile'] = !empty($result['data']['data'][0]) ? $result['data']['data'][0] : null;
		}

		return $result;
	}

	public function getProfileEventByProperty($profile_id, $metric_name, $property_name, $property_value, $additional_properties = array(), $since = '') {
		$profile_id = trim((string)$profile_id);
		$metric_name = trim((string)$metric_name);
		$property_name = trim((string)$property_name);

		if ($profile_id === '' || $metric_name === '' || $property_name === '' || !is_array($additional_properties)) {
			return $this->failure('invalid_event_lookup');
		}

		$expected_properties = $additional_properties;
		$expected_properties[$property_name] = $property_value;
		$filter_profile_id = str_replace(array('\\', '"'), array('\\\\', '\\"'), $profile_id);
		$filter = 'equals(profile_id,"' . $filter_profile_id . '")';

		if ($since !== '') {
			$since_timestamp = strtotime($since);

			if ($since_timestamp === false) {
				return $this->failure('invalid_event_lookup_since');
			}

			$filter = 'and(' . $filter . ',greater-or-equal(datetime,' . gmdate('Y-m-d\TH:i:s\Z', $since_timestamp) . '))';
		}

		$query_parameters = array(
			'filter' => $filter,
			'include' => 'metric',
			'fields[event]' => 'event_properties',
			'fields[metric]' => 'name',
			'page[size]' => 200,
			'sort' => '-datetime'
		);
		$result = null;

		for ($page = 0; $page < 5; $page++) {
			$query = http_build_query($query_parameters, '', '&', PHP_QUERY_RFC3986);
			$result = $this->request('GET', '/api/events?' . $query);

			if (!$result['success']) {
				return $result;
			}

			$metric_ids = array();

			if (!empty($result['data']['included']) && is_array($result['data']['included'])) {
				foreach ($result['data']['included'] as $included) {
					if (isset($included['type'], $included['id'], $included['attributes']['name']) && $included['type'] === 'metric' && $included['attributes']['name'] === $metric_name) {
						$metric_ids[(string)$included['id']] = true;
					}
				}
			}

			if ($metric_ids && !empty($result['data']['data']) && is_array($result['data']['data'])) {
				foreach ($result['data']['data'] as $event) {
					$event_metric_id = isset($event['relationships']['metric']['data']['id']) ? (string)$event['relationships']['metric']['data']['id'] : '';
					$event_properties = isset($event['attributes']['event_properties']) && is_array($event['attributes']['event_properties']) ? $event['attributes']['event_properties'] : array();

					if (!isset($metric_ids[$event_metric_id])) {
						continue;
					}

					$properties_match = true;

					foreach ($expected_properties as $expected_name => $expected_value) {
						if (!array_key_exists($expected_name, $event_properties) || (string)$event_properties[$expected_name] !== (string)$expected_value) {
							$properties_match = false;
							break;
						}
					}

					if ($properties_match) {
						$result['event_exists'] = true;
						return $result;
					}
				}
			}

			$next = isset($result['data']['links']['next']) ? $result['data']['links']['next'] : '';

			if (!$next) {
				$result['event_exists'] = false;
				return $result;
			}

			$next_query = parse_url($next, PHP_URL_QUERY);
			$next_parameters = array();
			parse_str((string)$next_query, $next_parameters);

			if (empty($next_parameters['page']['cursor'])) {
				return $this->failure('invalid_event_pagination');
			}

			$query_parameters['page[cursor]'] = $next_parameters['page']['cursor'];
		}

		return $this->failure('event_lookup_page_limit');
	}

	public function subscribeProfile($email) {
		$email = $this->normalizeEmail($email);
		$list_id = $this->getListId();

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		if ($list_id === '') {
			return $this->failure('missing_list_id');
		}

		return $this->request('POST', '/api/profile-subscription-bulk-create-jobs', array(
			'data' => array(
				'type' => 'profile-subscription-bulk-create-job',
				'attributes' => array(
					'profiles' => array(
						'data' => array(
							array(
								'type' => 'profile',
								'attributes' => array(
									'email' => $email,
									'subscriptions' => array(
										'email' => array(
											'marketing' => array(
												'consent' => 'SUBSCRIBED'
											)
										)
									)
								)
							)
						)
					)
				),
				'relationships' => array(
					'list' => array(
						'data' => array(
							'type' => 'list',
							'id' => $list_id
						)
					)
				)
			)
		), false);
	}

	public function unsubscribeProfile($email) {
		$email = $this->normalizeEmail($email);
		$list_id = $this->getListId();

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		if ($list_id === '') {
			return $this->failure('missing_list_id');
		}

		return $this->request('POST', '/api/profile-subscription-bulk-delete-jobs', array(
			'data' => array(
				'type' => 'profile-subscription-bulk-delete-job',
				'attributes' => array(
					'profiles' => array(
						'data' => array(
							array(
								'type' => 'profile',
								'attributes' => array(
									'email' => $email,
									'subscriptions' => array(
										'email' => array(
											'marketing' => array(
												'consent' => 'UNSUBSCRIBED'
											)
										)
									)
								)
							)
						)
					)
				),
				'relationships' => array(
					'list' => array(
						'data' => array(
							'type' => 'list',
							'id' => $list_id
						)
					)
				)
			)
		), false);
	}

	public function getProfileListMembership($email) {
		$email = $this->normalizeEmail($email);
		$list_id = $this->getListId();

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		if ($list_id === '') {
			return $this->failure('missing_list_id');
		}

		$filter_email = str_replace(array('\\', '"'), array('\\\\', '\\"'), $email);
		$query = http_build_query(array(
			'filter' => 'equals(email,"' . $filter_email . '")',
			'additional-fields[profile]' => 'subscriptions',
			'fields[profile]' => 'email,subscriptions',
			'page[size]' => 1
		), '', '&', PHP_QUERY_RFC3986);

		$result = $this->request('GET', '/api/lists/' . rawurlencode($list_id) . '/profiles?' . $query);

		if ($result['success']) {
			$result['is_member'] = !empty($result['data']['data'][0]);
		}

		return $result;
	}

	public function getListOptInProcess() {
		$list_id = $this->getListId();

		if ($list_id === '') {
			return $this->failure('missing_list_id');
		}

		$query = http_build_query(array(
			'fields[list]' => 'opt_in_process'
		), '', '&', PHP_QUERY_RFC3986);
		$result = $this->request('GET', '/api/lists/' . rawurlencode($list_id) . '?' . $query);

		if ($result['success']) {
			if (empty($result['data']['data']['attributes']['opt_in_process'])) {
				return $this->failure('invalid_list_opt_in_process');
			}

			$result['opt_in_process'] = $result['data']['data']['attributes']['opt_in_process'];
		}

		return $result;
	}

	public function getNewsletterConsentState($email) {
		$profile_result = $this->getProfileByEmail($email);

		if (!$profile_result['success']) {
			return $profile_result;
		}

		if (empty($profile_result['profile'])) {
			$profile_result['profile_exists'] = false;
			$profile_result['is_member'] = false;
			$profile_result['is_subscribed'] = false;
			$profile_result['is_unsubscribed'] = true;
			return $profile_result;
		}

		$marketing = isset($profile_result['profile']['attributes']['subscriptions']['email']['marketing']) && is_array($profile_result['profile']['attributes']['subscriptions']['email']['marketing']) ? $profile_result['profile']['attributes']['subscriptions']['email']['marketing'] : null;

		if ($marketing === null || !isset($marketing['consent'])) {
			return $this->failure('invalid_profile_subscription_state');
		}

		$membership_result = $this->getProfileListMembership($email);

		if (!$membership_result['success']) {
			return $membership_result;
		}

		$consent = (string)$marketing['consent'];
		$profile_result['profile_exists'] = true;
		$profile_result['is_member'] = !empty($membership_result['is_member']);
		$profile_result['is_subscribed'] = $consent === 'SUBSCRIBED' && !$this->hasResubscribableSuppression($marketing);
		$profile_result['is_unsubscribed'] = $consent === 'UNSUBSCRIBED';

		return $profile_result;
	}

	public function queueNewsletterConsent($email, $subscribed) {
		$email = $this->normalizeEmail($email);

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		$email_hash = hash('sha256', $email);
		$state_token = bin2hex(random_bytes(16));
		$desired_subscribed = $subscribed ? 1 : 0;

		$this->db->query("INSERT INTO `" . DB_PREFIX . "klaviyo_consent_queue` SET `email_hash` = '" . $this->db->escape($email_hash) . "', `email` = '" . $this->db->escape($email) . "', `desired_subscribed` = '" . $desired_subscribed . "', `state_token` = '" . $this->db->escape($state_token) . "', `attempts` = '0', `next_attempt_at` = NOW(), `date_added` = NOW(), `date_modified` = NOW() ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `desired_subscribed` = VALUES(`desired_subscribed`), `state_token` = VALUES(`state_token`), `attempts` = IF(`in_flight_token` IS NULL, '0', `attempts`), `next_attempt_at` = IF(`in_flight_token` IS NULL, NOW(), `next_attempt_at`), `last_attempt_at` = IF(`in_flight_token` IS NULL, NULL, `last_attempt_at`), `last_http_status` = IF(`in_flight_token` IS NULL, NULL, `last_http_status`), `last_error` = IF(`in_flight_token` IS NULL, NULL, `last_error`), `date_modified` = NOW()");

		$this->last_error = '';

		return array(
			'success' => true,
			'status' => 0,
			'data' => array(),
			'error' => '',
			'retryable' => false,
			'queued' => true
		);
	}

	public function processConsentQueue($limit = 50, $time_limit_seconds = 50) {
		$limit = max(1, min(100, (int)$limit));
		$time_limit_seconds = max(5, min(55, (int)$time_limit_seconds));
		$deadline = microtime(true) + $time_limit_seconds;
		$summary = array(
			'locked' => false,
			'processed' => 0,
			'submitted' => 0,
			'confirmed' => 0,
			'waiting' => 0,
			'succeeded' => 0,
			'failed' => 0
		);
		$lock_name = 'amds_klaviyo_consent_worker';
		$lock_query = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock_name) . "', 0) AS acquired");

		if (empty($lock_query->row) || (int)$lock_query->row['acquired'] !== 1) {
			$summary['locked'] = true;
			return $summary;
		}

		try {
			$queue = $this->db->query("SELECT * FROM `" . DB_PREFIX . "klaviyo_consent_queue` WHERE next_attempt_at <= NOW() ORDER BY next_attempt_at ASC, klaviyo_consent_queue_id ASC LIMIT " . $limit);

			if ($queue->rows) {
				$list = $this->getListOptInProcess();

				if (!$list['success']) {
					$this->log->write('[klaviyo] consent worker list preflight failed: error=' . $list['error']);
					throw new \RuntimeException('klaviyo_list_lookup_failed');
				}

				if ($list['opt_in_process'] !== 'single_opt_in') {
					$this->log->write('[klaviyo] consent worker requires a single_opt_in list');
					throw new \RuntimeException('klaviyo_consent_worker_requires_single_opt_in');
				}
			}

			foreach ($queue->rows as $job) {
				if (microtime(true) >= $deadline) {
					break;
				}

				$summary['processed']++;

				if (!empty($job['in_flight_token'])) {
					$this->verifyInFlightConsent($job, $summary);
				} else {
					$this->processQueuedConsent($job, $summary);
				}
			}
		} finally {
			try {
				$this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock_name) . "')");
			} catch (\Exception $e) {
				$this->log->write('[klaviyo] could not release consent worker lock');
			}
		}

		return $summary;
	}

	private function processQueuedConsent($job, &$summary) {
		$membership = $this->getNewsletterConsentState($job['email']);

		if (!$membership['success']) {
			$this->recordConsentStateFailure($job, $membership);
			$summary['failed']++;
			return;
		}

		$desired_subscribed = !empty($job['desired_subscribed']);

		$state_matches = $desired_subscribed ? (!empty($membership['is_subscribed']) && !empty($membership['is_member'])) : (!empty($membership['is_unsubscribed']) && empty($membership['is_member']));

		if ($state_matches) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "klaviyo_consent_queue` WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND state_token = '" . $this->db->escape($job['state_token']) . "' AND in_flight_token IS NULL");

			if ($this->db->countAffected()) {
				$summary['confirmed']++;
				$summary['succeeded']++;
			}

			return;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET in_flight_subscribed = '" . ($desired_subscribed ? '1' : '0') . "', in_flight_token = '" . $this->db->escape($job['state_token']) . "', submitted_at = NOW(), next_attempt_at = DATE_ADD(NOW(), INTERVAL 30 SECOND), last_attempt_at = NOW(), last_http_status = NULL, last_error = NULL, date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND state_token = '" . $this->db->escape($job['state_token']) . "' AND in_flight_token IS NULL");

		if (!$this->db->countAffected()) {
			return;
		}

		if ($desired_subscribed) {
			$result = $this->subscribeProfile($job['email']);
		} else {
			$result = $this->unsubscribeProfile($job['email']);
		}

		if ($result['success']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET last_http_status = '" . (int)$result['status'] . "', last_error = NULL, date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND in_flight_token = '" . $this->db->escape($job['state_token']) . "'");
			$summary['submitted']++;
			return;
		}

		$uncertain = !empty($result['retryable']) && (int)$result['status'] !== 429;
		$this->recordConsentOperationFailure($job, $job['state_token'], $result, $uncertain);
		$summary['failed']++;
	}

	private function verifyInFlightConsent($job, &$summary) {
		$membership = $this->getNewsletterConsentState($job['email']);

		if (!$membership['success']) {
			$this->recordConsentOperationFailure($job, $job['in_flight_token'], $membership, true);
			$summary['failed']++;
			return;
		}

		$expected_subscribed = !empty($job['in_flight_subscribed']);
		$state_matches = $expected_subscribed ? (!empty($membership['is_subscribed']) && !empty($membership['is_member'])) : (!empty($membership['is_unsubscribed']) && empty($membership['is_member']));

		if ($state_matches) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "klaviyo_consent_queue` WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND state_token = '" . $this->db->escape($job['in_flight_token']) . "' AND in_flight_token = '" . $this->db->escape($job['in_flight_token']) . "'");

			if (!$this->db->countAffected()) {
				$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET in_flight_subscribed = NULL, in_flight_token = NULL, submitted_at = NULL, attempts = '0', next_attempt_at = NOW(), last_attempt_at = NULL, last_http_status = NULL, last_error = NULL, date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND in_flight_token = '" . $this->db->escape($job['in_flight_token']) . "'");
			}

			$summary['confirmed']++;
			$summary['succeeded']++;
			return;
		}

		$submitted_at = !empty($job['submitted_at']) ? strtotime($job['submitted_at']) : false;

		if ($submitted_at === false || $submitted_at <= time() - 1800) {
			if ((bool)$job['desired_subscribed'] !== $expected_subscribed) {
				$this->recordSupersededConsentWait($job);
				$summary['waiting']++;
				return;
			}

			$timeout = array(
				'status' => 0,
				'error' => 'verification_timeout'
			);
			$this->recordConsentOperationFailure($job, $job['in_flight_token'], $timeout, false);
			$summary['failed']++;
			return;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET next_attempt_at = DATE_ADD(NOW(), INTERVAL 30 SECOND), last_attempt_at = NOW(), last_error = 'awaiting_confirmation', date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND in_flight_token = '" . $this->db->escape($job['in_flight_token']) . "'");
		$summary['waiting']++;
	}

	private function recordConsentStateFailure($job, $result) {
		$attempts = (int)$job['attempts'] + 1;
		$delay = $this->getConsentRetryDelay($attempts);
		$error = substr((string)$result['error'], 0, 160);

		$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET attempts = '" . $attempts . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL " . $delay . " SECOND), last_attempt_at = NOW(), last_http_status = '" . (int)$result['status'] . "', last_error = '" . $this->db->escape($error) . "', date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND state_token = '" . $this->db->escape($job['state_token']) . "' AND in_flight_token IS NULL");
	}

	private function recordConsentOperationFailure($job, $operation_token, $result, $keep_in_flight) {
		$attempts = (int)$job['attempts'] + 1;
		$delay = $this->getConsentRetryDelay($attempts);
		$error = substr((string)$result['error'], 0, 160);
		$clear_operation = $keep_in_flight ? '' : "in_flight_subscribed = NULL, in_flight_token = NULL, submitted_at = NULL, ";

		$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET " . $clear_operation . "attempts = '" . $attempts . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL " . $delay . " SECOND), last_attempt_at = NOW(), last_http_status = '" . (int)$result['status'] . "', last_error = '" . $this->db->escape($error) . "', date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND in_flight_token = '" . $this->db->escape($operation_token) . "'");
	}

	private function recordSupersededConsentWait($job) {
		$attempts = min((int)$job['attempts'] + 1, 65535);

		$this->db->query("UPDATE `" . DB_PREFIX . "klaviyo_consent_queue` SET attempts = '" . $attempts . "', next_attempt_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), last_attempt_at = NOW(), last_error = 'superseded_operation_unconfirmed', date_modified = NOW() WHERE klaviyo_consent_queue_id = '" . (int)$job['klaviyo_consent_queue_id'] . "' AND in_flight_token = '" . $this->db->escape($job['in_flight_token']) . "'");
	}

	public function createEvent($metric_name, $email, $properties, $profile_properties, $unique_id) {
		$email = $this->normalizeEmail($email);
		$metric_name = trim((string)$metric_name);
		$unique_id = trim((string)$unique_id);

		if (!$this->isValidEmail($email)) {
			return $this->failure('invalid_email');
		}

		if ($metric_name === '' || $unique_id === '') {
			return $this->failure('invalid_event');
		}

		$profile_attributes = array('email' => $email);

		if ($profile_properties) {
			$profile_attributes['properties'] = $profile_properties;
		}

		return $this->request('POST', '/api/events', array(
			'data' => array(
				'type' => 'event',
				'attributes' => array(
					'time' => gmdate('c'),
					'unique_id' => $unique_id,
					'properties' => $properties,
					'metric' => array(
						'data' => array(
							'type' => 'metric',
							'attributes' => array(
								'name' => $metric_name
							)
						)
					),
					'profile' => array(
						'data' => array(
							'type' => 'profile',
							'attributes' => $profile_attributes
						)
					)
				)
			)
		));
	}

	private function request($method, $path, $payload = null, $retry_immediately = true) {
		$api_key = $this->getApiKey();

		if ($api_key === '') {
			return $this->failure('missing_api_key');
		}

		if (!function_exists('curl_init')) {
			return $this->failure('curl_unavailable');
		}

		$url = rtrim($this->getApiUrl(), '/') . $path;
		$headers = array(
			'Authorization: Klaviyo-API-Key ' . $api_key,
			'Accept: application/vnd.api+json',
			'Content-Type: application/vnd.api+json',
			'Revision: ' . $this->getApiRevision()
		);

		$encoded_payload = null;

		if ($payload !== null) {
			$encoded_payload = json_encode($payload);

			if ($encoded_payload === false) {
				return $this->failure('json_encode_failed');
			}
		}

		$result = null;

		$max_attempts = $retry_immediately ? 2 : 1;

		for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
			$ch = curl_init($url);
			$options = array(
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_CONNECTTIMEOUT => 2,
				CURLOPT_TIMEOUT => 5
			);

			if ($encoded_payload !== null) {
				$options[CURLOPT_POSTFIELDS] = $encoded_payload;
			}

			curl_setopt_array($ch, $options);

			$body = curl_exec($ch);
			$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curl_error = curl_error($ch);
			curl_close($ch);

			$decoded = is_string($body) && $body !== '' ? json_decode($body, true) : array();
			$success = $curl_error === '' && $status >= 200 && $status < 300;
			$error = $success ? '' : $this->extractError($decoded, $curl_error, $status);
			$retryable = $curl_error !== '' || $status === 408 || $status === 429 || $status >= 500;

			$result = array(
				'success' => $success,
				'status' => $status,
				'data' => is_array($decoded) ? $decoded : array(),
				'error' => $error,
				'retryable' => $retryable
			);

			if ($success || !$retryable || $attempt === $max_attempts - 1) {
				break;
			}

			usleep(250000);
		}

		if (!$result['success']) {
			$this->last_error = $result['error'];
			$this->log->write('[klaviyo] request failed: method=' . $method . ' path=' . strtok($path, '?') . ' status=' . $result['status'] . ' error=' . $result['error']);
		} else {
			$this->last_error = '';
		}

		return $result;
	}

	private function extractError($decoded, $curl_error, $status) {
		if ($curl_error !== '') {
			return 'transport_error';
		}

		if (is_array($decoded) && !empty($decoded['errors'][0])) {
			$error = $decoded['errors'][0];
			$title = isset($error['title']) ? $error['title'] : 'api_error';
			$code = isset($error['code']) ? $error['code'] : '';
			$value = trim($title . ($code !== '' ? ':' . $code : ''));

			return substr(preg_replace('/[^a-zA-Z0-9_.: -]/', '', $value), 0, 160);
		}

		return 'http_' . (int)$status;
	}

	private function failure($error) {
		$this->last_error = $error;

		return array(
			'success' => false,
			'status' => 0,
			'data' => array(),
			'error' => $error,
			'retryable' => false
		);
	}

	private function getConsentRetryDelay($attempts) {
		$delays = array(60, 300, 900, 3600, 21600, 86400);
		$index = min(max(1, (int)$attempts), count($delays)) - 1;

		return $delays[$index];
	}

	private function hasResubscribableSuppression($marketing) {
		$resubscribe_reasons = array('UNSUBSCRIBE', 'SPAM_REPORT', 'SPAM_COMPLAINT', 'USER_SUPPRESSED', 'USER_INITIATED');

		if (!empty($marketing['suppression']) && is_array($marketing['suppression'])) {
			foreach ($marketing['suppression'] as $suppression) {
				$reason = isset($suppression['reason']) ? $suppression['reason'] : '';

				if (in_array($reason, $resubscribe_reasons, true)) {
					return true;
				}
			}
		}

		if (!empty($marketing['list_suppressions']) && is_array($marketing['list_suppressions'])) {
			$list_id = $this->getListId();

			foreach ($marketing['list_suppressions'] as $suppression) {
				$reason = isset($suppression['reason']) ? $suppression['reason'] : '';

				if (isset($suppression['list_id']) && (string)$suppression['list_id'] === $list_id && in_array($reason, $resubscribe_reasons, true)) {
					return true;
				}
			}
		}

		return false;
	}

	private function normalizeEmail($email) {
		return strtolower(trim((string)$email));
	}

	private function isValidEmail($email) {
		return strlen($email) <= 96 && filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	private function getApiKey() {
		// Environment configuration is the deployment override. This matters when
		// an older KLAV_KEY constant is still present in a protected config file.
		$environment_key = getenv('KLAV_KEY');

		if ($environment_key !== false && trim((string)$environment_key) !== '') {
			return trim((string)$environment_key);
		}

		if (defined('KLAV_KEY')) {
			$api_key = trim((string)KLAV_KEY);

			if ($api_key !== '') {
				return $api_key;
			}
		}

		return trim((string)$this->config->get('klaviyo_private_api_key'));
	}

	private function getListId() {
		if (defined('KLAV_ID')) {
			$list_id = trim((string)KLAV_ID);

			if ($list_id !== '') {
				return $list_id;
			}
		}

		$environment_list_id = getenv('KLAV_ID');

		if ($environment_list_id !== false && trim((string)$environment_list_id) !== '') {
			return trim((string)$environment_list_id);
		}

		return trim((string)$this->config->get('klaviyo_list_id'));
	}

	private function getApiRevision() {
		if (defined('KLAVIYO_API_REVISION') && KLAVIYO_API_REVISION) {
			return KLAVIYO_API_REVISION;
		}

		$environment_revision = getenv('KLAVIYO_API_REVISION');

		if ($environment_revision !== false && trim((string)$environment_revision) !== '') {
			return trim((string)$environment_revision);
		}

		return self::DEFAULT_API_REVISION;
	}

	private function getApiUrl() {
		if (defined('KLAVIYO_API_URL') && KLAVIYO_API_URL) {
			return KLAVIYO_API_URL;
		}

		return self::DEFAULT_API_URL;
	}
}
