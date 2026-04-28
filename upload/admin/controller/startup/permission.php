<?php
class ControllerStartupPermission extends Controller {
	public function index() {
		if (isset($this->request->get['route'])) {
			if ($this->isLuceedSyncRoute($this->request->get['route']) && $this->hasValidLuceedCronKey()) {
				return;
			}

			$route = '';

			$part = explode('/', $this->request->get['route']);

			if (isset($part[0])) {
				$route .= $part[0];
			}

			if (isset($part[1])) {
				$route .= '/' . $part[1];
			}

			// If a 3rd part is found we need to check if its under one of the extension folders.
			$extension = array(
				'extension/advertise',
				'extension/dashboard',
				'extension/analytics',
				'extension/captcha',
				'extension/extension',
				'extension/feed',
				'extension/fraud',
				'extension/module',
				'extension/payment',
				'extension/shipping',
				'extension/theme',
				'extension/total',
				'extension/report'
			);

			if (isset($part[2]) && in_array($route, $extension)) {
				$route .= '/' . $part[2];
			}

			// We want to ingore some pages from having its permission checked.
			$ignore = array(
				'common/dashboard',
				'common/login',
				'common/logout',
				'common/forgotten',
				'common/reset',
				'error/not_found',
				'error/permission'
			);

			if (!in_array($route, $ignore) && !$this->user->hasPermission('access', $route)) {
				return new Action('error/permission');
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
}
