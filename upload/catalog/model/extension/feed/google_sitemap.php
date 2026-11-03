<?php
class ModelExtensionFeedGoogleSitemap extends Model {
	public function getTotalProducts() {
		$query = $this->db->query('SELECT COUNT(*) AS total' . $this->productQuery());
		return (int)$query->row['total'];
	}

	public function getProducts($start, $limit) {
		$query = $this->db->query('SELECT p.product_id, p.image, p.date_modified' . $this->productQuery()
			. ' ORDER BY p.product_id LIMIT ' . max(0, (int)$start) . ',' . max(1, (int)$limit));
		return $query->rows;
	}

	private function productQuery() {
		// Match the storefront's visibility rules without loading prices, stock
		// options, reviews or resizing images for every sitemap entry.
		return ' FROM ' . DB_PREFIX . 'product p'
			. ' INNER JOIN ' . DB_PREFIX . 'product_description pd ON (p.product_id = pd.product_id)'
			. ' INNER JOIN ' . DB_PREFIX . 'product_to_store p2s ON (p.product_id = p2s.product_id)'
			. " WHERE p.status = '1' AND p.quantity > '0' AND p.date_available <= NOW()"
			. " AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'"
			. " AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
	}
}
