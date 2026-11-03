<?php
class ControllerStartupGoogleSitemap extends Controller {
	public function index() {
		if (!isset($this->request->get['_route_']) || !is_string($this->request->get['_route_'])) {
			return;
		}

		$path = $this->request->get['_route_'];
		$page = '1';
		if ($path === 'site-map.xml' || $path === 'sitemap.xml') {
			$type = 'index';
		} elseif ($path === 'site-map-pages.xml') {
			$type = 'pages';
		} elseif (preg_match('/^site-map-products-([1-9][0-9]*)\.xml$/D', $path, $match)) {
			$type = 'products';
			$page = $match[1];
		} else {
			return;
		}

		// Resolve reserved paths before the existing SEO controller, including
		// installations where OpenCart serves a generated copy of that controller.
		$this->request->get['route'] = 'extension/feed/google_sitemap';
		$this->request->get['type'] = $type;
		$this->request->get['page'] = $page;
		unset($this->request->get['_route_']);
	}
}
