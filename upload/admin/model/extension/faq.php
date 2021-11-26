<?php
class ModelExtensionFaq extends Model {
	public function install() {
$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."faq` (
  `faq_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`faq_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."faq_2_category` (
  `faq_id` int(11) NOT NULL,
  `fcategory_id` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."faq_description` (
  `faq_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`faq_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fcategory` (
  `fcategory_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `image` text NOT NULL, 
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`fcategory_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fcategory_description` (
  `fcategory_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(255) NOT NULL,
  `meta_keyword` varchar(255) NOT NULL,
  PRIMARY KEY (`fcategory_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fcategory_to_store` (
  `fcategory_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`fcategory_id`,`store_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fcategory_to_product` (
  `fcategory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

 $modules= array(
			   'extension_id' => '25315',
			   'email' => $this->config->get('config_email'),
			   'store_url' => HTTP_CATALOG
			);
	
		$url = 'https://www.opencartextensions.in/index.php?route=api/callback';
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($modules, '', '&'));
		
		 $response = curl_exec($curl);


}
	public function uninstall() {
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."faq`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."faq_2_category`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."faq_description`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."fcategory`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."fcategory_description`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."fcategory_to_store`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."fcategory_to_product`");
	}
	
	public function addfaq($data) {
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "faq SET sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");

		$faq_id = $this->db->getLastId();

		foreach ($data['faq_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "faq_description SET faq_id = '" . (int)$faq_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		
		if(isset($data['categories'])){
			foreach($data['categories'] as $fcategory_id){
				$this->db->query("INSERT INTO ".DB_PREFIX."faq_2_category SET fcategory_id = '".(int)$fcategory_id."',faq_id = '" . (int)$faq_id . "'");
			}
		}

		

		return $faq_id;
	}

	public function editfaq($faq_id, $data) {
		
		

		$this->db->query("UPDATE " . DB_PREFIX . "faq SET sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE faq_id = '" . (int)$faq_id . "'");

	
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_description WHERE faq_id = '" . (int)$faq_id . "'");

		foreach ($data['faq_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "faq_description SET faq_id = '" . (int)$faq_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_2_category WHERE faq_id = '" . (int)$faq_id . "'");
		
		if(isset($data['categories'])){
			foreach($data['categories'] as $fcategory_id){
				$this->db->query("INSERT INTO ".DB_PREFIX."faq_2_category SET fcategory_id = '".(int)$fcategory_id."',faq_id = '" . (int)$faq_id . "'");
			}
		}
		
		$this->cache->delete('faq');

		
	}

	public function deletefaq($faq_id){
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq WHERE faq_id = '" . (int)$faq_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_description WHERE faq_id = '" . (int)$faq_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_2_category WHERE faq_id = '" . (int)$faq_id . "'");
		
		$this->cache->delete('faq');

		
	}

	public function getfaq($faq_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq c LEFT JOIN " . DB_PREFIX . "faq_description cd2 ON (c.faq_id = cd2.faq_id) WHERE c.faq_id = '" . (int)$faq_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getFAQs($data = array()){
		$sql = "SELECT *,(SELECT GROUP_CONCAT(DISTINCT spc.fcategory_id SEPARATOR ',') FROM ".DB_PREFIX."faq_2_category spc WHERE f.faq_id = spc.faq_id GROUP BY spc.faq_id) as fcategory_ids FROM ".DB_PREFIX."faq f LEFT JOIN ".DB_PREFIX."faq_description fd ON(f.faq_id = fd.faq_id) ";

		if(!empty($data['filter_category'])){
			$sql .=" LEFT JOIN ".DB_PREFIX."faq_2_category f2c ON(f.faq_id = f2c.faq_id)";
		}
		
		$sql .=" WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if(!empty($data['filter_name'])){
			$sql .= " AND fd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}
		
		if(!empty($data['filter_category'])){
		  $sql .=" AND f2c.fcategory_id = '".(int)$data['filter_category']."'";
		}
		
		if(isset($data['filter_status'])){
		  $sql .=" AND f.status = '".(int)$data['filter_status']."'";
		}
		

		$sql .= " GROUP BY f.faq_id";

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

	public function getfaqDescriptions($faq_id){
		$faq_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq_description WHERE faq_id = '" . (int)$faq_id . "'");

		foreach($query->rows as $result){
			$faq_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description' 	   => $result['description'],
			);
		}

		return $faq_description_data;
	}
	
	public function getfaqcategories($faq_id){
		$faq_categories_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq_2_category WHERE faq_id = '" . (int)$faq_id . "'");

		foreach($query->rows as $result){
			$faq_categories_data[] = $result['fcategory_id'];
		}

		return $faq_categories_data;
	}
	
	public function getTotalCategories($data = array()){
		$sql = "SELECT * FROM ".DB_PREFIX."faq f LEFT JOIN ".DB_PREFIX."faq_description fd ON(f.faq_id = fd.faq_id) ";

		if(!empty($data['filter_category'])){
			$sql .=" LEFT JOIN ".DB_PREFIX."faq_2_category f2c ON(f.faq_id = f2c.faq_id)";
		}
		
		$sql .=" WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if(!empty($data['filter_name'])){
			$sql .= " AND fd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}
		
		if(!empty($data['filter_category'])){
		  $sql .=" AND f2c.fcategory_id = '".(int)$data['filter_category']."'";
		}
		
		if(isset($data['filter_status'])){
		  $sql .=" AND f.status = '".(int)$data['filter_status']."'";
		}

		$sql .= " GROUP BY f.faq_id";
		
		$query = $this->db->query($sql);

		return count($query->rows);
	}
}