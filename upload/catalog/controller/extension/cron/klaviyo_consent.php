<?php
class ControllerExtensionCronKlaviyoConsent extends Controller {
	public function index() {
		if (php_sapi_name() !== 'cli') {
			http_response_code(403);
			$this->response->addHeader('HTTP/1.1 403 Forbidden');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('error' => 'cli_only')) . PHP_EOL);
			return;
		}

		try {
			$this->load->library('klaviyo');
			$result = $this->klaviyo->processConsentQueue(50, 50);
			$GLOBALS['klaviyo_consent_cron_exit_code'] = !empty($result['failed']) ? 75 : 0;

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($result) . PHP_EOL);
		} catch (\Throwable $e) {
			$this->log->write('[klaviyo] consent worker failed');
			$GLOBALS['klaviyo_consent_cron_exit_code'] = 1;
			http_response_code(500);
			$this->response->addHeader('HTTP/1.1 500 Internal Server Error');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(array('error' => 'consent_worker_failed')) . PHP_EOL);
		}
	}
}
