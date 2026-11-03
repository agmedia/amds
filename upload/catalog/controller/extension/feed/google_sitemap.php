<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
	const PRODUCTS_PER_SITEMAP = 1000;

	public function index() {
		if (!$this->config->get('feed_google_sitemap_status')) {
			$this->notFound();
			return;
		}

		$type = isset($this->request->get['type']) ? $this->request->get['type'] : 'index';

		if (!in_array($type, array('index', 'pages', 'products'), true)) {
			$this->notFound();
			return;
		}

		$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

		if ($type === 'pages') {
			$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
			$output .= $this->pageUrl('common/home');
			$output .= $this->pageUrl('information/contact');

			$this->load->model('catalog/category');
			$output .= $this->getCategories(0);

			$this->load->model('catalog/manufacturer');
			foreach ($this->model_catalog_manufacturer->getManufacturers() as $manufacturer) {
				$output .= $this->pageUrl('product/manufacturer/info', 'manufacturer_id=' . (int)$manufacturer['manufacturer_id']);
			}

			$this->load->model('catalog/information');
			foreach ($this->model_catalog_information->getInformations() as $information) {
				$output .= $this->pageUrl('information/information', 'information_id=' . (int)$information['information_id']);
			}

			$this->load->model('extension/blog/blog');
			$output .= $this->pageUrl('extension/blog/home');
			$blog_total = (int)$this->model_extension_blog_blog->getTotalBlogs(array());
			if ($blog_total) {
				foreach ($this->model_extension_blog_blog->getBlogs(array(), 0, $blog_total) as $blog) {
					$output .= $this->pageUrl('extension/blog/blog', 'blog_id=' . (int)$blog['blog_id']);
				}
			}
			$this->load->model('extension/blog/blog_category');
			$output .= $this->getBlogCategories(0);
			$output .= '</urlset>';
		} else {
			$this->load->model('extension/feed/google_sitemap');
			$total = $this->model_extension_feed_google_sitemap->getTotalProducts();
			$pages = (int)ceil($total / self::PRODUCTS_PER_SITEMAP);

			if ($type === 'index') {
				$output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
				$output .= '<sitemap><loc>' . $this->xml($this->baseUrl() . 'site-map-pages.xml') . '</loc></sitemap>' . "\n";
				for ($page = 1; $page <= $pages; $page++) {
					$output .= '<sitemap><loc>' . $this->xml($this->baseUrl() . 'site-map-products-' . $page . '.xml') . '</loc></sitemap>' . "\n";
				}
				$output .= '</sitemapindex>';
			} else {
				$page = isset($this->request->get['page']) ? $this->request->get['page'] : '1';
				if (!is_scalar($page) || !preg_match('/^[1-9][0-9]*$/D', (string)$page) || $page > $pages) {
					$this->notFound();
					return;
				}

				$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
				$products = $this->model_extension_feed_google_sitemap->getProducts(((int)$page - 1) * self::PRODUCTS_PER_SITEMAP, self::PRODUCTS_PER_SITEMAP);
				foreach ($products as $product) {
					$output .= '<url><loc>' . $this->xml($this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true)) . '</loc>';
					// Date-only values avoid inventing a timezone for MySQL DATETIME.
					$date = DateTime::createFromFormat('!Y-m-d H:i:s', $product['date_modified']);
					if ($date && (int)$date->format('Y') > 0 && $date->format('Y-m-d H:i:s') === $product['date_modified'] && $date->getTimestamp() <= time()) {
						$output .= '<lastmod>' . $date->format('Y-m-d') . '</lastmod>';
					}
					if (!empty($product['image'])) {
						$image = implode('/', array_map('rawurlencode', explode('/', $product['image'])));
						$output .= '<image:image><image:loc>' . $this->xml($this->baseUrl() . 'image/' . $image) . '</image:loc></image:image>';
					}
					$output .= '</url>' . "\n";
				}
				$output .= '</urlset>';
			}
		}

		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
		$this->response->setOutput($output);
	}

	protected function getCategories($parent_id) {
		$output = '';
		foreach ($this->model_catalog_category->getCategories($parent_id) as $category) {
			// The category controller declares the leaf category URL as canonical.
			$output .= $this->pageUrl('product/category', 'path=' . (int)$category['category_id']);
			$output .= $this->getCategories($category['category_id']);
		}
		return $output;
	}

	private function pageUrl($route, $args = '') {
		return '<url><loc>' . $this->xml($this->url->link($route, $args, true)) . '</loc></url>' . "\n";
	}

	private function getBlogCategories($parent_id, $path = '') {
		$output = '';
		foreach ($this->model_extension_blog_blog_category->getBlogCategories($parent_id) as $category) {
			$new_path = $path === '' ? (string)$category['blog_category_id'] : $path . '_' . $category['blog_category_id'];
			$output .= $this->pageUrl('extension/blog/category', 'blogpath=' . $new_path);
			$output .= $this->getBlogCategories($category['blog_category_id'], $new_path);
		}
		return $output;
	}

	private function baseUrl() {
		return rtrim($this->config->get('config_ssl'), '/') . '/';
	}

	private function xml($value) {
		// Url::link() already HTML-escapes query separators.
		return htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
	}

	private function notFound() {
		$this->response->addHeader('HTTP/1.1 404 Not Found');
		$this->response->setOutput('');
	}
}
