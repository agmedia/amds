<?php

namespace {
	@ini_set('pcre.jit', '0');

	define('KLAVIYO_TEST_ROOT', dirname(dirname(__DIR__)));
	define('DB_PREFIX', 'oc_');
	define('KLAV_KEY', 'test-private-key');
	define('KLAV_ID', 'test-list');
	define('KLAVIYO_API_URL', 'https://klaviyo.test.invalid');

	// Never let a developer or deployment API key leak into this local harness.
	// Preserve it for the parent process while tests deliberately exercise the
	// environment-over-legacy-constant precedence with synthetic values only.
	$klaviyo_test_original_environment_key = getenv('KLAV_KEY');
	putenv('KLAV_KEY');
	register_shutdown_function(function () use ($klaviyo_test_original_environment_key) {
		if ($klaviyo_test_original_environment_key === false) {
			putenv('KLAV_KEY');
		} else {
			putenv('KLAV_KEY=' . $klaviyo_test_original_environment_key);
		}
	});

	// The production client is exercised with namespaced cURL doubles, so the
	// harness also works on a PHP CLI build that does not have ext-curl loaded.
	$curl_constants = array(
		'CURLOPT_CUSTOMREQUEST' => 10036,
		'CURLOPT_RETURNTRANSFER' => 19913,
		'CURLOPT_HTTPHEADER' => 10023,
		'CURLOPT_CONNECTTIMEOUT' => 78,
		'CURLOPT_TIMEOUT' => 13,
		'CURLOPT_POSTFIELDS' => 10015,
		'CURLINFO_HTTP_CODE' => 2097154
	);

	foreach ($curl_constants as $name => $value) {
		if (!defined($name)) {
			define($name, $value);
		}
	}

	class KlaviyoRegressionFailure extends \Exception {}

	function regression_assert($condition, $message) {
		if (!$condition) {
			throw new KlaviyoRegressionFailure($message);
		}
	}

	function regression_assert_same($expected, $actual, $message) {
		if ($expected !== $actual) {
			throw new KlaviyoRegressionFailure(
				$message . '; expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
			);
		}
	}

	function regression_profile_response($email, $consent, $suppressions = array(), $list_suppressions = array()) {
		return array(
			'data' => array(
				array(
					'type' => 'profile',
					'id' => 'profile-123',
					'attributes' => array(
						'email' => $email,
						'subscriptions' => array(
							'email' => array(
								'marketing' => array(
									'consent' => $consent,
									'suppression' => $suppressions,
									'list_suppressions' => $list_suppressions
								)
							)
						)
					)
				)
			)
		);
	}

	function regression_list_membership_response($is_member, $email = 'person@example.com') {
		if (!$is_member) {
			return array('data' => array());
		}

		return array(
			'data' => array(
				array(
					'type' => 'profile',
					'id' => 'profile-123',
					'attributes' => array('email' => $email)
				)
			)
		);
	}

	function regression_list_opt_in_response($process) {
		return array(
			'data' => array(
				'type' => 'list',
				'id' => 'test-list',
				'attributes' => array('opt_in_process' => $process)
			)
		);
	}

	function regression_load_source($relative_path, $namespace) {
		$path = KLAVIYO_TEST_ROOT . '/' . ltrim($relative_path, '/');
		$source = file_get_contents($path);

		if ($source === false) {
			throw new KlaviyoRegressionFailure('Unable to read source file: ' . $path);
		}

		$source = preg_replace('/^\s*<\?php\s*/', '', $source, 1);
		eval('namespace ' . $namespace . ';' . PHP_EOL . $source);
	}
}

namespace KlaviyoRegression {
	class CurlHandleMock {
		public $url;
		public $options = array();
		public $status = 0;
		public $error = '';

		public function __construct($url) {
			$this->url = $url;
		}
	}

	class HttpMock {
		public static $responses = array();
		public static $requests = array();

		public static function reset() {
			self::$responses = array();
			self::$requests = array();
		}

		public static function enqueue($status, $body, $callback = null, $error = '') {
			self::$responses[] = array(
				'status' => (int)$status,
				'body' => $body,
				'callback' => $callback,
				'error' => $error
			);
		}

		public static function enqueueSingleOptIn() {
			self::enqueue(200, \regression_list_opt_in_response('single_opt_in'));
		}

		public static function execute($handle) {
			if (!self::$responses) {
				throw new \KlaviyoRegressionFailure('Unexpected HTTP request: ' . $handle->url);
			}

			$response = array_shift(self::$responses);
			$method = isset($handle->options[CURLOPT_CUSTOMREQUEST]) ? $handle->options[CURLOPT_CUSTOMREQUEST] : 'GET';
			$headers = isset($handle->options[CURLOPT_HTTPHEADER]) ? $handle->options[CURLOPT_HTTPHEADER] : array();
			$encoded_payload = isset($handle->options[CURLOPT_POSTFIELDS]) ? $handle->options[CURLOPT_POSTFIELDS] : null;
			$payload = $encoded_payload !== null ? json_decode($encoded_payload, true) : null;

			self::$requests[] = array(
				'method' => $method,
				'url' => $handle->url,
				'headers' => $headers,
				'payload' => $payload
			);

			if (is_callable($response['callback'])) {
				call_user_func($response['callback'], $handle, self::$requests[count(self::$requests) - 1]);
			}

			$handle->status = $response['status'];
			$handle->error = $response['error'];

			return is_string($response['body']) ? $response['body'] : json_encode($response['body']);
		}
	}

	function function_exists($name) {
		if ($name === 'curl_init') {
			return true;
		}

		return \function_exists($name);
	}

	function curl_init($url = null) {
		return new CurlHandleMock($url);
	}

	function curl_setopt_array($handle, $options) {
		$handle->options = $options;
		return true;
	}

	function curl_exec($handle) {
		return HttpMock::execute($handle);
	}

	function curl_getinfo($handle, $option = 0) {
		if ($option === CURLINFO_HTTP_CODE) {
			return $handle->status;
		}

		return array('http_code' => $handle->status);
	}

	function curl_error($handle) {
		return $handle->error;
	}

	function curl_close($handle) {
		return true;
	}

	class FakeConfig {
		public function get($key) {
			return '';
		}
	}

	class FakeLog {
		public $messages = array();

		public function write($message) {
			$this->messages[] = $message;
		}
	}

	class FakeRegistry {
		private $values;

		public function __construct($db = null) {
			$this->values = array(
				'config' => new FakeConfig(),
				'db' => $db,
				'log' => new FakeLog()
			);
		}

		public function get($key) {
			return isset($this->values[$key]) ? $this->values[$key] : null;
		}
	}

	class FakeDbResult {
		public $num_rows = 0;
		public $row = array();
		public $rows = array();

		public function __construct($rows = array()) {
			$this->rows = array_values($rows);
			$this->num_rows = count($this->rows);
			$this->row = $this->num_rows ? $this->rows[0] : array();
		}
	}

	class ConsentQueueFakeDb {
		public $rows = array();
		private $last_id = 0;
		private $affected = 0;

		public function escape($value) {
			return addslashes($value);
		}

		public function countAffected() {
			return $this->affected;
		}

		public function getLastId() {
			return $this->last_id;
		}

		public function firstRow() {
			if (!$this->rows) {
				return null;
			}

			$rows = array_values($this->rows);
			return $rows[0];
		}

		public function query($sql) {
			$this->affected = 0;

			if (strpos($sql, 'SELECT GET_LOCK(') === 0) {
				return new FakeDbResult(array(array('acquired' => 1)));
			}

			if (strpos($sql, 'SELECT RELEASE_LOCK(') === 0) {
				return new FakeDbResult();
			}

			if (strpos($sql, 'INSERT INTO `oc_klaviyo_consent_queue`') === 0) {
				return $this->upsertConsent($sql);
			}

			if (strpos($sql, 'SELECT * FROM `oc_klaviyo_consent_queue`') === 0) {
				ksort($this->rows);
				return new FakeDbResult(array_values($this->rows));
			}

			if (strpos($sql, 'DELETE FROM `oc_klaviyo_consent_queue`') === 0) {
				return $this->deleteConsent($sql);
			}

			if (strpos($sql, 'UPDATE `oc_klaviyo_consent_queue` SET in_flight_subscribed = NULL') === 0) {
				return $this->clearInFlight($sql);
			}

			if (strpos($sql, 'UPDATE `oc_klaviyo_consent_queue` SET in_flight_subscribed =') === 0) {
				return $this->markInFlight($sql);
			}

			if (strpos($sql, 'UPDATE `oc_klaviyo_consent_queue` SET last_http_status =') === 0) {
				return $this->recordSubmission($sql);
			}

			if (strpos($sql, 'UPDATE `oc_klaviyo_consent_queue` SET attempts =') === 0 && strpos($sql, "last_error = 'superseded_operation_unconfirmed'") !== false) {
				return $this->recordSupersededWait($sql);
			}

			throw new \KlaviyoRegressionFailure('Unhandled consent queue SQL: ' . $sql);
		}

		private function upsertConsent($sql) {
			$email_hash = $this->capture("/`email_hash` = '([^']+)'/", $sql, 'email_hash');
			$email = stripslashes($this->capture("/`email` = '([^']+)'/", $sql, 'email'));
			$desired = (int)$this->capture("/`desired_subscribed` = '([01])'/", $sql, 'desired_subscribed');
			$state_token = $this->capture("/`state_token` = '([^']+)'/", $sql, 'state_token');
			$existing_id = null;

			foreach ($this->rows as $id => $row) {
				if ($row['email_hash'] === $email_hash) {
					$existing_id = $id;
					break;
				}
			}

			if ($existing_id === null) {
				$this->last_id++;
				$this->rows[$this->last_id] = array(
					'klaviyo_consent_queue_id' => $this->last_id,
					'email_hash' => $email_hash,
					'email' => $email,
					'desired_subscribed' => $desired,
					'state_token' => $state_token,
					'in_flight_subscribed' => null,
					'in_flight_token' => null,
					'submitted_at' => null,
					'attempts' => 0,
					'next_attempt_at' => 'due',
					'last_attempt_at' => null,
					'last_http_status' => null,
					'last_error' => null
				);
			} else {
				$row =& $this->rows[$existing_id];
				$row['email'] = $email;
				$row['desired_subscribed'] = $desired;
				$row['state_token'] = $state_token;

				if ($row['in_flight_token'] === null) {
					$row['attempts'] = 0;
					$row['next_attempt_at'] = 'due';
					$row['last_attempt_at'] = null;
					$row['last_http_status'] = null;
					$row['last_error'] = null;
				}

				unset($row);
			}

			$this->affected = 1;
			return new FakeDbResult();
		}

		private function deleteConsent($sql) {
			$id = (int)$this->capture("/klaviyo_consent_queue_id = '([0-9]+)'/", $sql, 'queue id');
			$state_token = $this->capture("/state_token = '([^']+)'/", $sql, 'delete state token');

			if (!isset($this->rows[$id]) || $this->rows[$id]['state_token'] !== $state_token) {
				return new FakeDbResult();
			}

			if (strpos($sql, 'in_flight_token IS NULL') !== false) {
				if ($this->rows[$id]['in_flight_token'] !== null) {
					return new FakeDbResult();
				}
			} else {
				$in_flight_token = $this->capture("/in_flight_token = '([^']+)'/", $sql, 'delete in-flight token');

				if ($this->rows[$id]['in_flight_token'] !== $in_flight_token) {
					return new FakeDbResult();
				}
			}

			unset($this->rows[$id]);
			$this->affected = 1;
			return new FakeDbResult();
		}

		private function markInFlight($sql) {
			$id = (int)$this->capture("/klaviyo_consent_queue_id = '([0-9]+)'/", $sql, 'queue id');
			$desired = (int)$this->capture("/in_flight_subscribed = '([01])'/", $sql, 'in-flight desired state');
			$in_flight_token = $this->capture("/in_flight_token = '([^']+)'/", $sql, 'in-flight token');
			$state_token = $this->capture("/state_token = '([^']+)'/", $sql, 'state token');

			if (!isset($this->rows[$id]) || $this->rows[$id]['state_token'] !== $state_token || $this->rows[$id]['in_flight_token'] !== null) {
				return new FakeDbResult();
			}

			$this->rows[$id]['in_flight_subscribed'] = $desired;
			$this->rows[$id]['in_flight_token'] = $in_flight_token;
			$this->rows[$id]['submitted_at'] = date('Y-m-d H:i:s');
			$this->rows[$id]['next_attempt_at'] = 'due';
			$this->rows[$id]['last_attempt_at'] = date('Y-m-d H:i:s');
			$this->rows[$id]['last_http_status'] = null;
			$this->rows[$id]['last_error'] = null;
			$this->affected = 1;
			return new FakeDbResult();
		}

		private function recordSubmission($sql) {
			$id = (int)$this->capture("/klaviyo_consent_queue_id = '([0-9]+)'/", $sql, 'queue id');
			$status = (int)$this->capture("/last_http_status = '([0-9]+)'/", $sql, 'HTTP status');
			$in_flight_token = $this->capture("/in_flight_token = '([^']+)'/", $sql, 'in-flight token');

			if (isset($this->rows[$id]) && $this->rows[$id]['in_flight_token'] === $in_flight_token) {
				$this->rows[$id]['last_http_status'] = $status;
				$this->rows[$id]['last_error'] = null;
				$this->affected = 1;
			}

			return new FakeDbResult();
		}

		private function clearInFlight($sql) {
			$id = (int)$this->capture("/klaviyo_consent_queue_id = '([0-9]+)'/", $sql, 'queue id');
			$in_flight_token = $this->capture("/in_flight_token = '([^']+)'/", $sql, 'in-flight token');

			if (isset($this->rows[$id]) && $this->rows[$id]['in_flight_token'] === $in_flight_token) {
				$this->rows[$id]['in_flight_subscribed'] = null;
				$this->rows[$id]['in_flight_token'] = null;
				$this->rows[$id]['submitted_at'] = null;
				$this->rows[$id]['attempts'] = 0;
				$this->rows[$id]['next_attempt_at'] = 'due';
				$this->rows[$id]['last_attempt_at'] = null;
				$this->rows[$id]['last_http_status'] = null;
				$this->rows[$id]['last_error'] = null;
				$this->affected = 1;
			}

			return new FakeDbResult();
		}

		private function recordSupersededWait($sql) {
			$id = (int)$this->capture("/klaviyo_consent_queue_id = '([0-9]+)'/", $sql, 'queue id');
			$attempts = (int)$this->capture("/attempts = '([0-9]+)'/", $sql, 'attempt count');
			$in_flight_token = $this->capture("/in_flight_token = '([^']+)'/", $sql, 'in-flight token');

			if (isset($this->rows[$id]) && $this->rows[$id]['in_flight_token'] === $in_flight_token) {
				$this->rows[$id]['attempts'] = $attempts;
				$this->rows[$id]['next_attempt_at'] = 'later';
				$this->rows[$id]['last_attempt_at'] = date('Y-m-d H:i:s');
				$this->rows[$id]['last_error'] = 'superseded_operation_unconfirmed';
				$this->affected = 1;
			}

			return new FakeDbResult();
		}

		private function capture($pattern, $subject, $field) {
			if (!preg_match($pattern, $subject, $matches)) {
				throw new \KlaviyoRegressionFailure('Could not parse ' . $field . ' from SQL: ' . $subject);
			}

			return stripslashes($matches[1]);
		}
	}

	\regression_load_source('upload/system/library/klaviyo.php', 'KlaviyoRegression');
}

namespace CouponRegression {
	class Controller {
		public $db;
		public $log;
	}

	class FakeLog {
		public $messages = array();

		public function write($message) {
			$this->messages[] = $message;
		}
	}

	class FakeDbResult {
		public $num_rows = 0;
		public $row = array();
		public $rows = array();

		public function __construct($rows = array()) {
			$this->rows = array_values($rows);
			$this->num_rows = count($this->rows);
			$this->row = $this->num_rows ? $this->rows[0] : array();
		}
	}

	class CouponFakeDb {
		public $mappings = array();
		public $coupons = array();
		public $coupon_inserts = 0;
		public $lock_releases = 0;
		private $last_id = 100;

		public function escape($value) {
			return addslashes($value);
		}

		public function getLastId() {
			return $this->last_id;
		}

		public function query($sql) {
			if (strpos($sql, 'SELECT GET_LOCK(') === 0) {
				return new FakeDbResult(array(array('acquired' => 1)));
			}

			if (strpos($sql, 'SELECT RELEASE_LOCK(') === 0) {
				$this->lock_releases++;
				return new FakeDbResult();
			}

			if (strpos($sql, 'SELECT wc.welcome_coupon_id') === 0) {
				$email_hash = $this->capture("/wc.email_hash = '([^']+)'/", $sql, 'email hash');

				if (!isset($this->mappings[$email_hash])) {
					return new FakeDbResult();
				}

				$row = $this->mappings[$email_hash];
				$row['existing_coupon_id'] = null;

				if (!empty($row['coupon_id']) && isset($this->coupons[(int)$row['coupon_id']]) && $this->coupons[(int)$row['coupon_id']]['code'] === $row['code']) {
					$row['existing_coupon_id'] = (int)$row['coupon_id'];
				}

				return new FakeDbResult(array($row));
			}

			if (strpos($sql, 'SELECT coupon_id FROM `oc_coupon` WHERE code') === 0) {
				$code = $this->capture("/code = '([^']+)'/", $sql, 'coupon code');

				foreach ($this->coupons as $coupon) {
					if ($coupon['code'] === $code) {
						return new FakeDbResult(array(array('coupon_id' => $coupon['coupon_id'])));
					}
				}

				return new FakeDbResult();
			}

			if (strpos($sql, 'INSERT INTO `oc_coupon`') === 0) {
				$code = $this->capture("/`code` = '([^']+)'/", $sql, 'inserted coupon code');
				$this->last_id++;
				$this->coupons[$this->last_id] = array(
					'coupon_id' => $this->last_id,
					'code' => $code
				);
				$this->coupon_inserts++;
				return new FakeDbResult();
			}

			if (strpos($sql, 'UPDATE `oc_welcome_coupon` SET coupon_id') === 0) {
				$coupon_id = (int)$this->capture("/coupon_id = '([0-9]+)'/", $sql, 'coupon id');
				$mapping_id = (int)$this->capture("/welcome_coupon_id = '([0-9]+)'/", $sql, 'mapping id');

				foreach ($this->mappings as &$mapping) {
					if ((int)$mapping['welcome_coupon_id'] === $mapping_id) {
						$mapping['coupon_id'] = $coupon_id;
						break;
					}
				}

				unset($mapping);
				return new FakeDbResult();
			}

			throw new \KlaviyoRegressionFailure('Unhandled coupon SQL: ' . $sql);
		}

		private function capture($pattern, $subject, $field) {
			if (!preg_match($pattern, $subject, $matches)) {
				throw new \KlaviyoRegressionFailure('Could not parse ' . $field . ' from SQL: ' . $subject);
			}

			return stripslashes($matches[1]);
		}
	}

	\regression_load_source('upload/catalog/controller/api/coupon.php', 'CouponRegression');
}

namespace {
	$tests = array();

	$tests['event confirmation requires code and source id across pagination'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$source_event_id = 'amds-welcome-coupon-v1-WELCOME-ABC234';

		// The first page has the right metric and code, but the wrong source id.
		// It must not be accepted and the next cursor must be followed.
		\KlaviyoRegression\HttpMock::enqueue(200, array(
			'data' => array(
				array(
					'type' => 'event',
					'id' => 'event-wrong-source',
					'attributes' => array('event_properties' => array(
						'welcome_code' => 'WELCOME-ABC234',
						'source_event_id' => 'some-other-source'
					)),
					'relationships' => array('metric' => array('data' => array('type' => 'metric', 'id' => 'metric-welcome')))
				)
			),
			'included' => array(
				array('type' => 'metric', 'id' => 'metric-welcome', 'attributes' => array('name' => 'Welcome Coupon Ready'))
			),
			'links' => array(
				'next' => 'https://klaviyo.test.invalid/api/events?page%5Bcursor%5D=cursor-page-2'
			)
		));
		\KlaviyoRegression\HttpMock::enqueue(200, array(
			'data' => array(
				array(
					'type' => 'event',
					'id' => 'event-match',
					'attributes' => array('event_properties' => array(
						'welcome_code' => 'WELCOME-ABC234',
						'source_event_id' => $source_event_id
					)),
					'relationships' => array('metric' => array('data' => array('type' => 'metric', 'id' => 'metric-welcome')))
				)
			),
			'included' => array(
				array('type' => 'metric', 'id' => 'metric-welcome', 'attributes' => array('name' => 'Welcome Coupon Ready'))
			),
			'links' => array('next' => null)
		));

		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry());
		$result = $client->getProfileEventByProperty(
			'profile-123',
			'Welcome Coupon Ready',
			'welcome_code',
			'WELCOME-ABC234',
			array('source_event_id' => $source_event_id),
			'2026-09-14T08:00:00+00:00'
		);

		regression_assert($result['success'], 'Event lookup request should succeed');
		regression_assert_same(true, $result['event_exists'], 'Event with both confirmation properties should be detected');
		regression_assert_same(2, count(\KlaviyoRegression\HttpMock::$requests), 'Event lookup should follow exactly one cursor');

		$first_request = \KlaviyoRegression\HttpMock::$requests[0];
		$second_request = \KlaviyoRegression\HttpMock::$requests[1];
		regression_assert_same('GET', $first_request['method'], 'Event lookup should use GET');
		regression_assert_same('/api/events', parse_url($first_request['url'], PHP_URL_PATH), 'Event lookup endpoint');
		parse_str(parse_url($first_request['url'], PHP_URL_QUERY), $first_query);
		parse_str(parse_url($second_request['url'], PHP_URL_QUERY), $second_query);
		regression_assert_same('and(equals(profile_id,"profile-123"),greater-or-equal(datetime,2026-09-14T08:00:00Z))', $first_query['filter'], 'Profile and confirmation-time filters');
		regression_assert_same('metric', $first_query['include'], 'Metric relationship should be included');
		regression_assert_same('event_properties', $first_query['fields']['event'], 'Event lookup sparse fieldset');
		regression_assert_same('name', $first_query['fields']['metric'], 'Metric lookup sparse fieldset');
		regression_assert_same('cursor-page-2', $second_query['page']['cursor'], 'Next pagination cursor should be preserved');
	};

	$tests['create event keeps a stable unique id and payload'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		\KlaviyoRegression\HttpMock::enqueue(202, array());
		\KlaviyoRegression\HttpMock::enqueue(202, array());

		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry());
		$unique_id = 'amds-welcome-coupon-v1-WELCOME-ABC234DEF567';
		$properties = array('welcome_code' => 'WELCOME-ABC234DEF567', 'discount_percentage' => 10, 'source_event_id' => $unique_id);
		$profile_properties = array('welcome_code' => 'WELCOME-ABC234DEF567');

		$first = $client->createEvent('Welcome Coupon Ready', 'Person@Example.com', $properties, $profile_properties, $unique_id);
		$second = $client->createEvent('Welcome Coupon Ready', ' person@example.com ', $properties, $profile_properties, $unique_id);

		regression_assert($first['success'] && $second['success'], 'Both asynchronous event submissions should be accepted');
		regression_assert_same(2, count(\KlaviyoRegression\HttpMock::$requests), 'Two event attempts should be captured');

		$first_request = \KlaviyoRegression\HttpMock::$requests[0];
		$second_request = \KlaviyoRegression\HttpMock::$requests[1];
		$first_attributes = $first_request['payload']['data']['attributes'];
		$second_attributes = $second_request['payload']['data']['attributes'];

		regression_assert_same('POST', $first_request['method'], 'Create Event should use POST');
		regression_assert_same('/api/events', parse_url($first_request['url'], PHP_URL_PATH), 'Create Event endpoint');
		regression_assert_same($unique_id, $first_attributes['unique_id'], 'First event idempotency key');
		regression_assert_same($unique_id, $second_attributes['unique_id'], 'Retry event idempotency key');
		regression_assert_same($properties, $first_attributes['properties'], 'Event properties must remain stable');
		regression_assert_same($properties, $second_attributes['properties'], 'Retry properties must remain stable');
		regression_assert_same('person@example.com', $first_attributes['profile']['data']['attributes']['email'], 'Profile email should be normalized');
		regression_assert_same($profile_properties, $first_attributes['profile']['data']['attributes']['properties'], 'Profile properties payload');
		regression_assert(in_array('Authorization: Klaviyo-API-Key test-private-key', $first_request['headers'], true), 'Private API authorization header');
		regression_assert(in_array('Revision: 2026-07-15', $first_request['headers'], true), 'Pinned API revision header');
	};

	$tests['environment API key overrides the legacy constant'] = function () {
		$previous_environment_key = getenv('KLAV_KEY');
		putenv('KLAV_KEY=environment-private-key');

		try {
			\KlaviyoRegression\HttpMock::reset();
			\KlaviyoRegression\HttpMock::enqueue(202, array());
			$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry());
			$result = $client->createEvent(
				'Welcome Coupon Ready',
				'key-precedence@example.com',
				array('welcome_code' => 'WELCOME-KEY234'),
				array(),
				'amds-welcome-coupon-v1-WELCOME-KEY234'
			);

			regression_assert($result['success'], 'Synthetic environment-key request should succeed');
			$request = \KlaviyoRegression\HttpMock::$requests[0];
			regression_assert(in_array('Authorization: Klaviyo-API-Key environment-private-key', $request['headers'], true), 'Non-empty environment API key must win');
			regression_assert(!in_array('Authorization: Klaviyo-API-Key test-private-key', $request['headers'], true), 'Legacy constant must not override the environment key');
		} finally {
			if ($previous_environment_key === false) {
				putenv('KLAV_KEY');
			} else {
				putenv('KLAV_KEY=' . $previous_environment_key);
			}
		}
	};

	$tests['newsletter consent state keeps profile consent and list membership separate'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry());
		$email = 'person@example.com';

		// Global consent may already be SUBSCRIBED while the profile is not yet in
		// the target list. Both facts must remain visible to the worker.
		\KlaviyoRegression\HttpMock::enqueue(200, regression_profile_response($email, 'SUBSCRIBED'));
		\KlaviyoRegression\HttpMock::enqueue(200, regression_list_membership_response(false, $email));
		$subscribed_not_member = $client->getNewsletterConsentState($email);
		regression_assert_same(true, $subscribed_not_member['is_subscribed'], 'Global SUBSCRIBED consent should be preserved');
		regression_assert_same(false, $subscribed_not_member['is_member'], 'List membership must be checked independently');
		regression_assert_same(false, $subscribed_not_member['is_unsubscribed'], 'Subscribed profile is not globally unsubscribed');

		// Conversely, a profile may still be returned by the list endpoint while
		// its global consent has already become UNSUBSCRIBED.
		\KlaviyoRegression\HttpMock::enqueue(200, regression_profile_response($email, 'UNSUBSCRIBED'));
		\KlaviyoRegression\HttpMock::enqueue(200, regression_list_membership_response(true, $email));
		$unsubscribed_member = $client->getNewsletterConsentState($email);
		regression_assert_same(false, $unsubscribed_member['is_subscribed'], 'UNSUBSCRIBED consent must not be treated as subscribed');
		regression_assert_same(true, $unsubscribed_member['is_unsubscribed'], 'Global unsubscribe should be explicit');
		regression_assert_same(true, $unsubscribed_member['is_member'], 'Stale list membership must remain independently visible');

		// Relevant global and target-list suppressions override a nominal
		// SUBSCRIBED consent while unrelated state is left alone.
		\KlaviyoRegression\HttpMock::enqueue(200, regression_profile_response(
			$email,
			'SUBSCRIBED',
			array(array('reason' => 'SPAM_REPORT'))
		));
		\KlaviyoRegression\HttpMock::enqueue(200, regression_list_membership_response(true, $email));
		$globally_suppressed = $client->getNewsletterConsentState($email);
		regression_assert_same(false, $globally_suppressed['is_subscribed'], 'Spam suppression must block subscribed state');
		regression_assert_same(true, $globally_suppressed['is_member'], 'Suppression must not erase list membership observation');

		\KlaviyoRegression\HttpMock::enqueue(200, regression_profile_response(
			$email,
			'SUBSCRIBED',
			array(),
			array(array('list_id' => 'test-list', 'reason' => 'USER_INITIATED'))
		));
		\KlaviyoRegression\HttpMock::enqueue(200, regression_list_membership_response(true, $email));
		$list_suppressed = $client->getNewsletterConsentState($email);
		regression_assert_same(false, $list_suppressed['is_subscribed'], 'Relevant target-list suppression must block subscribed state');
		regression_assert_same(true, $list_suppressed['is_member'], 'Suppressed profile can still be a returned list member');
	};

	$tests['consent bulk posts are never immediately retried'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry());

		// Only one response is queued for each call. Any immediate retry would hit
		// the mock's unexpected-request failure and fail this regression test.
		\KlaviyoRegression\HttpMock::enqueue(503, array(
			'errors' => array(array('title' => 'Service unavailable', 'code' => 'temporary'))
		));
		$server_failure = $client->subscribeProfile('person@example.com');
		regression_assert_same(false, $server_failure['success'], 'HTTP 503 subscription should fail for deferred retry');
		regression_assert_same(true, $server_failure['retryable'], 'HTTP 503 should remain retryable by the queue');
		regression_assert_same(1, count(\KlaviyoRegression\HttpMock::$requests), 'HTTP 503 bulk POST must be attempted once');

		\KlaviyoRegression\HttpMock::enqueue(0, '', null, 'connection reset');
		$transport_failure = $client->unsubscribeProfile('person@example.com');
		regression_assert_same(false, $transport_failure['success'], 'Transport failure should be deferred to queue verification');
		regression_assert_same(true, $transport_failure['retryable'], 'Transport failure should remain retryable');
		regression_assert_same(2, count(\KlaviyoRegression\HttpMock::$requests), 'Transport-failed bulk POST must also be attempted once');
	};

	$tests['newer unsubscribe survives subscribe in flight and stale token cleanup'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$db = new \KlaviyoRegression\ConsentQueueFakeDb();
		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry($db));
		$email = 'person@example.com';
		$subscribed_profile = array(
			'data' => array(
				array(
					'type' => 'profile',
					'id' => 'profile-123',
					'attributes' => array(
						'email' => $email,
						'subscriptions' => array(
							'email' => array(
								'marketing' => array(
									'consent' => 'SUBSCRIBED',
									'suppression' => array(),
									'list_suppressions' => array()
								)
							)
						)
					)
				)
			)
		);
		$unsubscribed_profile = array(
			'data' => array(
				array(
					'type' => 'profile',
					'id' => 'profile-123',
					'attributes' => array(
						'email' => $email,
						'subscriptions' => array(
							'email' => array(
								'marketing' => array(
									'consent' => 'UNSUBSCRIBED',
									'suppression' => array(),
									'list_suppressions' => array()
								)
							)
						)
					)
				)
			)
		);
		$list_member = array(
			'data' => array(
				array(
					'type' => 'profile',
					'id' => 'profile-123',
					'attributes' => array(
						'email' => $email,
						'subscriptions' => array(
							'email' => array(
								'marketing' => array('consent' => 'SUBSCRIBED')
							)
						)
					)
				)
			)
		);

		$queued = $client->queueNewsletterConsent($email, true);
		regression_assert($queued['success'], 'Initial subscribe intent should be queued');
		$subscribe_token = $db->firstRow()['state_token'];

		// Worker preflights the list, then sees that the profile does not exist.
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, array('data' => array()));
		// While the accepted subscribe request is in flight, the user opts out.
		\KlaviyoRegression\HttpMock::enqueue(202, array(), function () use ($client, $email) {
			$result = $client->queueNewsletterConsent($email, false);
			regression_assert($result['success'], 'Concurrent unsubscribe should be queued');
		});

		$submitted_subscribe = $client->processConsentQueue(50, 50);
		$row = $db->firstRow();
		regression_assert_same(1, $submitted_subscribe['submitted'], 'Subscribe should be submitted asynchronously');
		regression_assert_same(0, $row['desired_subscribed'], 'Newer unsubscribe must become desired state');
		regression_assert($row['state_token'] !== $subscribe_token, 'Newer intent must receive a new state token');
		regression_assert_same(1, $row['in_flight_subscribed'], 'Original subscribe remains the operation being verified');
		regression_assert_same($subscribe_token, $row['in_flight_token'], 'Original operation keeps its token');
		$unsubscribe_token = $row['state_token'];

		// Klaviyo now confirms both global consent and target-list membership for
		// the old subscribe. The stale token must prevent row deletion.
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, $subscribed_profile);
		\KlaviyoRegression\HttpMock::enqueue(200, $list_member);
		$confirmed_subscribe = $client->processConsentQueue(50, 50);
		$row = $db->firstRow();
		regression_assert_same(1, $confirmed_subscribe['confirmed'], 'Old subscribe should be confirmed');
		regression_assert($row !== null, 'Stale subscribe confirmation must not delete newer unsubscribe');
		regression_assert_same($unsubscribe_token, $row['state_token'], 'Newer state token must survive stale cleanup');
		regression_assert_same(0, $row['desired_subscribed'], 'Newer unsubscribe must remain queued');
		regression_assert_same(null, $row['in_flight_token'], 'Confirmed old operation should be cleared before processing newer state');

		// Worker observes subscribed consent plus membership, then submits the
		// queued unsubscribe.
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, $subscribed_profile);
		\KlaviyoRegression\HttpMock::enqueue(200, $list_member);
		\KlaviyoRegression\HttpMock::enqueue(202, array());
		$submitted_unsubscribe = $client->processConsentQueue(50, 50);
		$row = $db->firstRow();
		regression_assert_same(1, $submitted_unsubscribe['submitted'], 'Newer unsubscribe should be submitted');
		regression_assert_same(0, $row['in_flight_subscribed'], 'Unsubscribe should be tracked as the current operation');
		regression_assert_same($unsubscribe_token, $row['in_flight_token'], 'Unsubscribe should use the latest token');

		// Final global unsubscribe plus membership=false confirmation may now
		// safely remove the row.
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, $unsubscribed_profile);
		\KlaviyoRegression\HttpMock::enqueue(200, array('data' => array()));
		$confirmed_unsubscribe = $client->processConsentQueue(50, 50);
		regression_assert_same(1, $confirmed_unsubscribe['confirmed'], 'Unsubscribe should be confirmed');
		regression_assert_same(0, count($db->rows), 'Queue row should be deleted only after latest state is confirmed');

		$post_requests = array();

		foreach (\KlaviyoRegression\HttpMock::$requests as $request) {
			if ($request['method'] === 'POST') {
				$post_requests[] = $request;
			}
		}

		regression_assert_same(2, count($post_requests), 'Exactly one subscribe and one unsubscribe operation should be submitted');
		regression_assert_same('/api/profile-subscription-bulk-create-jobs', parse_url($post_requests[0]['url'], PHP_URL_PATH), 'Subscribe endpoint');
		regression_assert_same('SUBSCRIBED', $post_requests[0]['payload']['data']['attributes']['profiles']['data'][0]['attributes']['subscriptions']['email']['marketing']['consent'], 'Subscribe consent payload');
		regression_assert_same('/api/profile-subscription-bulk-delete-jobs', parse_url($post_requests[1]['url'], PHP_URL_PATH), 'Unsubscribe endpoint');
		regression_assert_same('UNSUBSCRIBED', $post_requests[1]['payload']['data']['attributes']['profiles']['data'][0]['attributes']['subscriptions']['email']['marketing']['consent'], 'Unsubscribe consent payload');
	};

	$tests['opposite newer intent preserves a timed-out in-flight operation'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$db = new \KlaviyoRegression\ConsentQueueFakeDb();
		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry($db));
		$email = 'timeout@example.com';

		$client->queueNewsletterConsent($email, true);
		$subscribe_token = $db->firstRow()['state_token'];
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, array('data' => array()));
		\KlaviyoRegression\HttpMock::enqueue(202, array(), function () use ($client, $email) {
			$client->queueNewsletterConsent($email, false);
		});

		$submitted = $client->processConsentQueue(50, 50);
		regression_assert_same(1, $submitted['submitted'], 'Original subscribe should enter in-flight verification');
		$row = $db->firstRow();
		$row_id = (int)$row['klaviyo_consent_queue_id'];
		$unsubscribe_token = $row['state_token'];
		$db->rows[$row_id]['submitted_at'] = date('Y-m-d H:i:s', time() - 1900);
		$db->rows[$row_id]['attempts'] = 65535;

		// The old subscribe is still unconfirmed after the timeout, but the desired
		// state is now unsubscribe. The worker must keep the old operation marker;
		// clearing it would permit an opposite async POST to overtake it.
		\KlaviyoRegression\HttpMock::enqueueSingleOptIn();
		\KlaviyoRegression\HttpMock::enqueue(200, array('data' => array()));
		$timed_out = $client->processConsentQueue(50, 50);
		$row = $db->firstRow();

		regression_assert_same(1, $timed_out['waiting'], 'Superseded timed-out operation should remain waiting');
		regression_assert_same(0, $timed_out['failed'], 'Opposite newer intent should not fail-and-clear old operation');
		regression_assert_same($subscribe_token, $row['in_flight_token'], 'Timed-out subscribe token must remain in flight');
		regression_assert_same(1, $row['in_flight_subscribed'], 'Timed-out operation direction must be preserved');
		regression_assert_same($unsubscribe_token, $row['state_token'], 'Newer unsubscribe token must remain the desired state');
		regression_assert_same(0, $row['desired_subscribed'], 'Newer unsubscribe intent must remain queued');
		regression_assert_same('superseded_operation_unconfirmed', $row['last_error'], 'Worker should expose why it is still waiting');
		regression_assert_same(65535, $row['attempts'], 'Superseded wait attempts must saturate at the SMALLINT UNSIGNED limit');

		$post_count = 0;
		foreach (\KlaviyoRegression\HttpMock::$requests as $request) {
			if ($request['method'] === 'POST') {
				$post_count++;
			}
		}
		regression_assert_same(1, $post_count, 'No opposite bulk POST should be submitted before old operation confirms');
	};

	$tests['double-opt-in list is refused before consent processing'] = function () {
		\KlaviyoRegression\HttpMock::reset();
		$db = new \KlaviyoRegression\ConsentQueueFakeDb();
		$client = new \KlaviyoRegression\Klaviyo(new \KlaviyoRegression\FakeRegistry($db));
		$client->queueNewsletterConsent('double-opt-in@example.com', true);
		$original_token = $db->firstRow()['state_token'];
		\KlaviyoRegression\HttpMock::enqueue(200, regression_list_opt_in_response('double_opt_in'));
		$thrown = null;

		try {
			$client->processConsentQueue(50, 50);
		} catch (\Throwable $exception) {
			$thrown = $exception;
		}

		regression_assert($thrown instanceof \RuntimeException, 'Double-opt-in preflight should stop the worker');
		regression_assert_same('klaviyo_consent_worker_requires_single_opt_in', $thrown->getMessage(), 'Double-opt-in refusal reason');
		regression_assert_same(1, count(\KlaviyoRegression\HttpMock::$requests), 'Only list preflight should run');
		regression_assert_same('GET', \KlaviyoRegression\HttpMock::$requests[0]['method'], 'List preflight should use GET');
		regression_assert_same('/api/lists/test-list', parse_url(\KlaviyoRegression\HttpMock::$requests[0]['url'], PHP_URL_PATH), 'List preflight endpoint');
		regression_assert_same($original_token, $db->firstRow()['state_token'], 'Refused worker must leave queue state untouched');
		regression_assert_same(null, $db->firstRow()['in_flight_token'], 'Refused worker must not create an in-flight operation');
	};

	$tests['explicit Basel unsubscribe does not require a local newsletter row'] = function () {
		$path = KLAVIYO_TEST_ROOT . '/upload/catalog/controller/extension/basel/basel_features.php';
		$source = file_get_contents($path);
		regression_assert($source !== false, 'Basel features controller should be readable');

		$start = strpos($source, '// Newsletter Unsubscribe');
		$end = $start === false ? false : strpos($source, '// Contact Form Captcha', $start);
		regression_assert($start !== false && $end !== false, 'Basel unsubscribe source section should be identifiable');
		$unsubscribe_source = substr($source, $start, $end - $start);

		regression_assert(strpos($unsubscribe_source, 'model_extension_basel_newsletter->unsubscribe') !== false, 'Non-customer unsubscribe path should always queue the explicit intent');
		regression_assert(strpos($unsubscribe_source, 'checkExist') === false, 'Explicit unsubscribe must not be gated by a local newsletter row');
	};

	$tests['deleted mapped coupon is treated as revoked and never recreated'] = function () {
		$db = new \CouponRegression\CouponFakeDb();
		$controller = new \CouponRegression\ControllerApiCoupon();
		$controller->db = $db;
		$controller->log = new \CouponRegression\FakeLog();
		$email_hash = hash('sha256', 'revoked@example.com');
		$db->mappings[$email_hash] = array(
			'welcome_coupon_id' => 77,
			'coupon_id' => 42,
			'code' => 'WELCOME-ABC234',
			'event_sent_at' => null
		);

		$method = new \ReflectionMethod($controller, 'getOrCreateWelcomeCoupon');
		$method->setAccessible(true);
		$thrown = null;

		try {
			$method->invoke($controller, $email_hash);
		} catch (\ReflectionException $exception) {
			throw $exception;
		} catch (\Throwable $exception) {
			$thrown = $exception;
		}

		regression_assert($thrown instanceof \RuntimeException, 'Deleted mapped coupon should raise a revoked state');
		regression_assert_same('welcome_coupon_revoked', $thrown->getMessage(), 'Revoked state error');
		regression_assert_same(0, $db->coupon_inserts, 'Deleted mapped coupon must not be recreated');
		regression_assert_same(42, $db->mappings[$email_hash]['coupon_id'], 'Revoked mapping must remain durable');
		regression_assert_same(1, $db->lock_releases, 'Coupon lock must be released after revoked state');
	};

	$tests['incomplete coupon reservation can still be repaired'] = function () {
		$db = new \CouponRegression\CouponFakeDb();
		$controller = new \CouponRegression\ControllerApiCoupon();
		$controller->db = $db;
		$controller->log = new \CouponRegression\FakeLog();
		$email_hash = hash('sha256', 'interrupted@example.com');
		$db->mappings[$email_hash] = array(
			'welcome_coupon_id' => 88,
			'coupon_id' => null,
			'code' => 'WELCOME-DEF567',
			'event_sent_at' => null
		);

		$method = new \ReflectionMethod($controller, 'getOrCreateWelcomeCoupon');
		$method->setAccessible(true);
		$result = $method->invoke($controller, $email_hash);

		regression_assert_same('WELCOME-DEF567', $result['code'], 'Reserved code should be preserved');
		regression_assert_same(true, $result['created'], 'Interrupted reservation should create its missing coupon');
		regression_assert_same(1, $db->coupon_inserts, 'Exactly one missing coupon should be created');
		regression_assert(!empty($db->mappings[$email_hash]['coupon_id']), 'Repaired mapping should link the new coupon id');
	};

	$failures = 0;

	foreach ($tests as $name => $test) {
		try {
			call_user_func($test);
			echo '[PASS] ' . $name . PHP_EOL;
		} catch (\Throwable $exception) {
			$failures++;
			fwrite(STDERR, '[FAIL] ' . $name . ': ' . $exception->getMessage() . PHP_EOL);
		}
	}

	if ($failures) {
		fwrite(STDERR, PHP_EOL . $failures . ' regression test(s) failed.' . PHP_EOL);
		exit(1);
	}

	echo PHP_EOL . count($tests) . ' Klaviyo regression tests passed.' . PHP_EOL;
}
