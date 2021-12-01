<?php 
class ModelExtensionModuleOrderReviews extends Model {
	
  	public function install() {
		$query = $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_log`(
			`log_id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL DEFAULT '0',
			`review_id` int(11) NOT NULL DEFAULT '0',
			`customer_name` varchar(255) DEFAULT NULL,
			`review_product_id` int(11) DEFAULT NULL,
			`review_rating` int(1) NOT NULL,
			`review_coupon` varchar(255) DEFAULT NULL,
			`store_id` int(1) NOT NULL,
	 	 	`privacy_policy` INT(11) NULL DEFAULT '0',
			`date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`log_id`));
			");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_mail_log` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`date` DATETIME NULL DEFAULT NULL,  
			`store_id` INT(11) NOT NULL DEFAULT 0,  
			`order_id` INT(11) NOT NULL,
			 PRIMARY KEY (`id`))");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_setting` (
			`setting_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`code` varchar(128) NOT NULL,
			`key` varchar(128) NOT NULL,
			`value` longtext NOT NULL,
			`serialized` tinyint(1) NOT NULL,
			PRIMARY KEY (`setting_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_mail` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`mail_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`code` varchar(128) NOT NULL,
			`key` varchar(128) NOT NULL,
			`value` longtext NOT NULL,
			`serialized` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
		$this->checkTables();
  	}

	public function checkTables()
  	{
  		// v3.8.9: add 'privacy_policy' column if not exist
		$check_privacy_policy_column = $this->db->query("SHOW COLUMNS FROM `".DB_PREFIX."orderreviews_log` LIKE 'privacy_policy' ");
		if (!$check_privacy_policy_column->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "orderreviews_log` ADD `privacy_policy` INT(11) NULL DEFAULT '0' AFTER `store_id`;");
		}
  	}
  
  	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."orderreviews_log");
		$this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."orderreviews_mail_log");
		$this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."orderreviews_setting");
		$this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."orderreviews_mail");
  	}

	public function editSetting($code, $data, $store_id = 0) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "orderreviews_setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = 'orderreviews'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "orderreviews_mail` WHERE store_id = '" . (int)$store_id . "' AND `code` = 'orderreviewsMailTemplate'");

		foreach ($data as $key => $value) {
			if ($key == 'orderreviewsMailTemplate') {
				foreach ($value['ReviewMail'] as $values) {
					if (!is_array($values)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_mail SET mail_id = '" . $values['id'] . "', store_id = '" . (int)$store_id . "', `code` = 'orderreviewsMailTemplate', `key` = 'ReviewMail', `value` = '" . $this->db->escape($values) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_mail SET mail_id = '" . $values['id'] . "', store_id = '" . (int)$store_id . "', `code` = 'orderreviewsMailTemplate', `key` = 'ReviewMail', `value` = '" . $this->db->escape(json_encode($values, true)) . "', serialized = '1'");
					}
				}
			} else {
				if (!is_array($value)) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_setting SET store_id = '" . (int)$store_id . "', `code` = 'orderreviews', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_setting SET store_id = '" . (int)$store_id . "', `code` = 'orderreviews', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
				}
			}
		}
	}

	public function getSetting($code, $store_id = 0) {
		$setting_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "orderreviews_setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

		foreach ($query->rows as $result) {
			if (!$result['serialized']) {
				$setting_data[$result['key']] = $result['value'];
			} else {
				$setting_data[$result['key']] = json_decode($result['value'], true);
			}
		}

		$mailTemplates = array();
		$mailQuery = $this->db->query("SELECT * FROM " . DB_PREFIX . "orderreviews_mail WHERE store_id = '" . (int)$store_id . "' AND `code` = 'orderreviewsMailTemplate' ORDER BY mail_id ASC");

		foreach ($mailQuery->rows as $result) {
			if (!$result['serialized']) {
				$mailTemplates[$result['mail_id']] = $result['value'];
			} else {
				$mailTemplates[$result['mail_id']] = json_decode($result['value'], true);
			}
		}

		$setting_data[$code]['ReviewMail'] = $mailTemplates;

		return $setting_data;
	}

	public function getUsedCouponDetails($coupon_id)
	{
		$used_coupon = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE coupon_id = ".(int)$coupon_id);
		return $used_coupon->row;
	}

	public function getTotalUsedCoupons($store_id=0) {
		$total_coupons = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE `name` LIKE '%OrderReviews%'");
		$total_coupons_ids = array();		
		foreach ($total_coupons->rows as $coupon) {
			$total_coupons_ids[] = $coupon['coupon_id'];			
		}

		$comma_separated_ids = implode(', ', $total_coupons_ids);		
		$total_used = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "coupon_history WHERE coupon_id IN (".$this->db->escape($comma_separated_ids).")");
		return $total_used->row['count'];
	}
	
	public function getUsedCoupons($page=1, $limit=1, $store=0, $sort="coupon_id", $order="DESC"){
		
		if ($page) {
			$start = ($page - 1) * $limit;
		}
		$sent_ids = array();
		$sent_coupons = $this->getAllGeneratedCoupons($page, $limit, $store, $sort, $order);
		foreach ($sent_coupons as $coupon) {
			$sent_ids[] = $coupon['coupon_id'];
		}

		$comma_separated_ids = implode(', ', $sent_ids);
		
		$all_used = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon_history WHERE coupon_id IN (".$this->db->escape($comma_separated_ids).")");

		return $all_used->rows;
	}

	public function getAllGeneratedCoupons($page=1, $limit=8, $store=0, $sort="coupon_id", $order="DESC") {
		if ($page) {
			$start = ($page - 1) * $limit;
		}
		$query = $this->db->query("SELECT c.*, (SELECT count(*) FROM " . DB_PREFIX . "coupon_history WHERE coupon_id=c.coupon_id) as used FROM " . DB_PREFIX . "coupon c WHERE c.`name` LIKE '%OrderReviews%' ORDER BY c.`coupon_id` DESC LIMIT ".$start.", ".$limit);
		return $query->rows;
	}

	public function getTotalCoupons() {
		$query = $this->db->query("SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "coupon` WHERE `name` LIKE '%OrderReviews%'");
		return $query->row['count']; 
	}

	public function getAllReviews($page=1, $limit=8, $store=0, $sort="log_id", $order="DESC") {	
		if ($page) {
			$start = ($page - 1) * $limit;
		}
		$query =  $this->db->query("SELECT * FROM `" . DB_PREFIX . "orderreviews_log`
			WHERE `store_id`='".$store."'
			ORDER BY `log_id` DESC
			LIMIT ".$start.", ".$limit);
		return $query->rows; 
	}

	public function getTotalReviews($store=0){
		$query = $this->db->query("SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "orderreviews_log` WHERE `store_id`=".$store);	

		return $query->row['count']; 
	}

	public function update(){
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "orderreviews_log` ADD `review_id` int(11) NOT NULL AFTER `order_id`");

		$queries = $this->db->query("SELECT r.review_id as rev_id, log_id, date_added, date_created, DATE_ADD(date_created, INTERVAL 5 SECOND) as date_to, DATE_SUB(date_created, INTERVAL 5 SECOND) as date_from, product_id, review_product_id FROM " . DB_PREFIX . "orderreviews_log ol LEFT JOIN " . DB_PREFIX . "review r ON (r.product_id = ol.review_product_id)");

		$reviews = $queries->rows;

		foreach ($reviews as $review) {
			if ($review['date_added'] >= $review['date_from'] && $review['date_added'] <= $review['date_to']) {
				$this->db->query("UPDATE `" . DB_PREFIX . "orderreviews_log` SET `review_id` = '".$review['rev_id']."' WHERE `review_product_id` = '".$review['product_id']."' AND `log_id` = '".$review['log_id']."'");
			}
		}
	}

	public function updateMailId() {
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "orderreviews_mail` ADD `mail_id` int(11) NOT NULL AFTER `id`");
	}

	public function deleteReview($review_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "orderreviews_log WHERE review_id = '" . (int)$review_id . "'");
	}

	public function getProductsInIDArray($products = array()) {
		if(!empty($products)) {
			return $this->db->query('SELECT p.product_id, p.image, pd.name  FROM `' . DB_PREFIX .'product` p ' . 
				'LEFT JOIN `'  . DB_PREFIX . 'product_description` AS pd ON (pd.language_id = ' . $this->config->get('config_language_id') . ' AND p.product_id = pd.product_id) ' . 
				((count($products) == 1) ?
					'WHERE p.product_id = ' . $products[0] :
					'WHERE p.product_id IN (' . implode(',', $products) . ')'
				)
			)->rows;
		} else {
			return array();
		}
	}

	public function getCategoriesByID($categories = array()) {
		if(!empty($categories)) {
			return $this->db->query('SELECT c.category_id, name FROM ' . DB_PREFIX . 'category c ' . 
				'JOIN '  . DB_PREFIX . 'category_description AS cd on c.category_id=cd.category_id ' . 
				((count($categories) == 1) ?
					'WHERE c.category_id = ' . $categories[0] :
					'WHERE c.category_id IN (' . implode(',', $categories) . ')'
				) . 
				' AND language_id = ' . $this->config->get('config_language_id')
			)->rows;
		} else {
			return NULL;
		}
	}

	public function checkDbColumn($table, $column) {
      // return TRUE if exist, FALSE if column is not exist at table
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table ."` LIKE '" . $column . "'");
		return ($result->num_rows) ? TRUE : FALSE;
	}

	public function checkDbTable($table) {
		// return TRUE if exist, FALSE if table is not exist
		$result = $this->db->query("SHOW TABLES LIKE '". DB_PREFIX . $table . "'");
		return ($result->num_rows != '1') ? FALSE : TRUE;
	}

	public function update392(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_mail_log` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`date` DATETIME NULL DEFAULT NULL,  
			`store_id` INT(11) NOT NULL DEFAULT 0,  
			`order_id` INT(11) NOT NULL,
			 PRIMARY KEY (`id`))");
	}
	
	public function viewLogs($page=1, $limit=8, $store=0,$sort="id", $order="DESC") {	
		if ($page) {
			$start = ($page - 1) * $limit;
		}
		$query =  $this->db->query("SELECT * FROM `" . DB_PREFIX . "orderreviews_mail_log`
			WHERE `store_id`='".$store."'
			ORDER BY `id` DESC
			LIMIT ".$start.", ".$limit);
			
		return $query->rows; 
	}

	public function getTotalLog($store=0){
		$query = $this->db->query("SELECT COUNT(*) as `count`  FROM `" . DB_PREFIX . "orderreviews_mail_log` WHERE `store_id`=".$store);
		
		return $query->row['count']; 
	}

	public function deleteLogEntry($data, $store_id)
    {	
    	$sql="DELETE FROM `".DB_PREFIX."orderreviews_mail_log` WHERE id in (".implode(",",$data).") AND store_id = '" . (int)$store_id . "'";  
    	$this->db->query($sql);  	
    }

	public function update3101($settings){
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_setting` (
			`setting_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`code` varchar(128) NOT NULL,
			`key` varchar(128) NOT NULL,
			`value` longtext NOT NULL,
			`serialized` tinyint(1) NOT NULL,
			PRIMARY KEY (`setting_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "orderreviews_mail` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`mail_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`code` varchar(128) NOT NULL,
			`key` varchar(128) NOT NULL,
			`value` longtext NOT NULL,
			`serialized` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
		if (!empty($settings)) {
			foreach ($settings as $store_id => $data) {
				if ($data == 'ReviewMail') {
					foreach ($data['ReviewMail'] as $values) {
						if (!is_array($values)) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_mail SET mail_id = '" . $values['id'] . "', store_id = '" . (int)$store_id . "', `code` = 'orderreviewsMailTemplate', `key` = 'ReviewMail', `value` = '" . $this->db->escape($values) . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_mail SET mail_id = '" . $values['id'] . "', store_id = '" . (int)$store_id . "', `code` = 'orderreviewsMailTemplate', `key` = 'ReviewMail', `value` = '" . $this->db->escape(json_encode($values, true)) . "', serialized = '1'");
						}
					}
				} else {
					if (!is_array($data)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_setting SET store_id = '" . (int)$store_id . "', `code` = 'orderreviews', `key` = 'orderreviews', `value` = '" . $this->db->escape($data) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_setting SET store_id = '" . (int)$store_id . "', `code` = 'orderreviews', `key` = 'orderreviews', `value` = '" . $this->db->escape(json_encode($data, true)) . "', serialized = '1'");
					}
				}
			}
		}
	}
}
