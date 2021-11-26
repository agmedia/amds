<?php
class ModelExtensioncategory extends Model {
	public function addCategory($data) {
		//3 august 2018 ///
		$this->db->query("INSERT INTO " . DB_PREFIX . "fcategory SET sort_order = '" . (int)$data['sort_order'] . "', image = '" . $this->db->escape($data['image']) . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");
		//3 august 2018 ///
		$fcategory_id = $this->db->getLastId();

		foreach ($data['category_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_description SET fcategory_id = '" . (int)$fcategory_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if(isset($data['category_store'])){
			foreach($data['category_store'] as $store_id){
			  $this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_to_store SET fcategory_id = '" .(int)$fcategory_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
		
		if (isset($data['category_seo_url'])) {
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'fcategory_id=" . (int)$fcategory_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

	// new work 20 sep 2019 ///
		if (isset($data['product_id'])){
			foreach ($data['product_id'] as $product_id){
				$sql = $this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_to_product SET fcategory_id = '" . (int) $fcategory_id . "',product_id='" . $product_id . "'");
			}
		}
	// new work 20 sep 2019 ///

		return $fcategory_id;
	}

	public function editCategory($fcategory_id, $data) {
//3 august 2018 ///
		$this->db->query("UPDATE " . DB_PREFIX . "fcategory SET sort_order = '" . (int)$data['sort_order'] . "',image = '" . $this->db->escape($data['image']) . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE fcategory_id = '" . (int)$fcategory_id . "'");
//3 august 2018 ///
	
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_description WHERE fcategory_id = '" . (int)$fcategory_id . "'");

		foreach ($data['category_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_description SET fcategory_id = '" . (int)$fcategory_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_to_store WHERE fcategory_id = '" . (int)$fcategory_id . "'");
		
		if(isset($data['category_store'])){
			foreach($data['category_store'] as $store_id){
			  $this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_to_store SET fcategory_id = '" .(int)$fcategory_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

	// new work 20 sep 2019 ///
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_to_product WHERE fcategory_id = '" . (int)$fcategory_id . "'");
		if (isset($data['product_id'])){
			foreach ($data['product_id'] as $product_id){
				$sql = $this->db->query("INSERT INTO " . DB_PREFIX . "fcategory_to_product SET fcategory_id = '" . (int) $fcategory_id . "',product_id='" . $product_id . "'");
			}
		}
	// new work 20 sep 2019 ///

		
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'fcategory_id=" . (int)$fcategory_id . "'");

		if (isset($data['category_seo_url'])) {
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'fcategory_id=" . (int)$fcategory_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		$this->cache->delete('fcategory');
	}

	// new work 20 sep 2019 ///
	public function getTmdProduct($fcategory_id){
		$product_id = array();
		$sql   = "select * from " . DB_PREFIX . "fcategory_to_product where fcategory_id='" . $fcategory_id . "'";
		$query = $this->db->query($sql);
		foreach ($query->rows as $result){
			$product_id[] = $result['product_id'];
		}
		return $product_id;
	}
	// new work 20 sep 2019 ///

	public function deleteCategory($fcategory_id){
		

		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory WHERE fcategory_id = '" . (int)$fcategory_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_description WHERE fcategory_id = '" . (int)$fcategory_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_to_store WHERE fcategory_id = '" . (int)$fcategory_id . "'");
	// new work 20 sep 2019 ///
		$this->db->query("DELETE FROM " . DB_PREFIX . "fcategory_to_product WHERE fcategory_id = '" . (int)$fcategory_id . "'");
	// new work 20 sep 2019 ///
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'fcategory_id=" . (int)$fcategory_id . "'");

		$this->cache->delete('fcategory');
	}

	public function getCategory($fcategory_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory c LEFT JOIN " . DB_PREFIX . "fcategory_description cd2 ON (c.fcategory_id = cd2.fcategory_id) WHERE c.fcategory_id = '" . (int)$fcategory_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}
	
	public function getCategorySeoUrls($fcategory_id) {
		$category_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'fcategory_id=" . (int)$fcategory_id . "'");

		foreach ($query->rows as $result) {
			$category_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $category_seo_url_data;
	}

	public function getCategories($data = array()) {
		
		$sql = "SELECT * FROM ".DB_PREFIX."fcategory f LEFT JOIN ".DB_PREFIX."fcategory_description fd ON(f.fcategory_id = fd.fcategory_id) WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if(!empty($data['filter_name'])){
			$sql .= " AND fd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " GROUP BY f.fcategory_id";

		$sort_data = array(
			'name',
			'sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCategoryDescriptions($fcategory_id) {
		$fcategory_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory_description WHERE fcategory_id = '" . (int)$fcategory_id . "'");

		foreach ($query->rows as $result) {
			$fcategory_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'meta_title'       => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
			);
		}

		return $fcategory_description_data;
	}

	public function getCategoryStores ($fcategory_id) {
		$fcategory_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory_to_store WHERE fcategory_id = '" . (int)$fcategory_id . "'");

		foreach ($query->rows as $result) {
			$fcategory_store_data[] = $result['store_id'];
		}

		return $fcategory_store_data;
	}

	public function getTotalCategories() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "fcategory f LEFT JOIN ".DB_PREFIX."fcategory_description fd ON(f.fcategory_id = fd.fcategory_id) WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		

		return $query->row['total'];
	}
}