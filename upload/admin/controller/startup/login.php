<?php
class ControllerStartupLogin extends Controller {
	public function index() {
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';

		// User
		$this->registry->set('user', new Cart\User($this->registry));

		if ($this->isLuceedSyncRoute($route)) {
			if ($this->hasValidLuceedCronKey()) {
				return;
			}

			if (!$this->user->isLogged()) {
				$this->denyLuceedSyncAccess();
			}
		}

		$ignore = array(
			'common/login',
			'common/forgotten',
			'common/reset'
		);

		if (!$this->user->isLogged() && !in_array($route, $ignore)) {
			return new Action('common/login');
		}

		if (isset($this->request->get['route'])) {
			$ignore = array(
				'common/login',
				'common/logout',
				'common/forgotten',
				'common/reset',
				'error/not_found',
				'error/permission'
			);

			if (!in_array($route, $ignore) && (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token']))) {
				return new Action('common/login');
			}
		} else {
			if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
				return new Action('common/login');
			}
		}
	}

	private function isLuceedSyncRoute($route) {
		return in_array($route, array(
			'extension/module/luceed_sync/importProducts',
			'extension/module/luceed_sync/importActions',
			'extension/module/luceed_sync/updatePricesAndQuantities',
			'extension/module/luceed_sync/updateOrderStatuses'
		), true);
	}

	private function hasValidLuceedCronKey() {
		if (PHP_SAPI === 'cli') {
			return true;
		}

		$expected = $this->getLuceedCronKey();
		$provided = isset($this->request->get['key']) ? (string)$this->request->get['key'] : '';

		return ($expected !== '' && $provided !== '' && hash_equals($expected, $provided));
	}

	private function getLuceedCronKey() {
		if (defined('OC_ENV') && isset(OC_ENV['security']['luceed_sync_cron_key'])) {
			return trim((string)OC_ENV['security']['luceed_sync_cron_key']);
		}

		if (defined('WSPAY_CRON_KEY')) {
			return trim((string)WSPAY_CRON_KEY);
		}

		return '';
	}

	private function denyLuceedSyncAccess() {
		$protocol = isset($this->request->server['SERVER_PROTOCOL']) ? $this->request->server['SERVER_PROTOCOL'] : 'HTTP/1.1';

		$this->response->addHeader($protocol . ' 403 Forbidden');
		$this->response->setOutput('Forbidden');
		$this->response->output();

		exit;
	}
}
