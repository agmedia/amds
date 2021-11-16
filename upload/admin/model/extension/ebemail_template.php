<?php
class ModelExtensionEbemailTemplate extends Model {
	
	public function CreateTables() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart_email_template` ( `abandonedcart_email_template_id` int(11) NOT NULL AUTO_INCREMENT, `sort_order` int(3) NOT NULL DEFAULT '0',`status` tinyint(1) NOT NULL DEFAULT '1', PRIMARY KEY (`abandonedcart_email_template_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart_email_template_description` ( `abandonedcart_email_template_id` int(11) NOT NULL, `language_id` int(11) NOT NULL, `title` varchar(64) NOT NULL, `subject` varchar(255) NOT NULL, `description` text NOT NULL, PRIMARY KEY (`abandonedcart_email_template_id`,`language_id`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		
	
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart_email_coupon` ( `abandonedcart_email_coupon_id` int(11) NOT NULL AUTO_INCREMENT, `abandonedcart_email_template_id` int(11) NOT NULL, `coupon_name` varchar(255) NOT NULL, `coupon_type` varchar(255) NOT NULL,`coupon_discount` decimal(15,4) NOT NULL,`coupon_total` decimal(15,4) NOT NULL,`coupon_product` text NOT NULL,`coupon_category` text NOT NULL,`coupon_vaild` int(11) NOT NULL,`coupon_uses_total` int(11) NOT NULL,`coupon_uses_customer` int(11) NOT NULL,`coupon_status` tinyint(4)  NOT NULL,`coupon_contion` tinyint(4)  NOT NULL, PRIMARY KEY (`abandonedcart_email_coupon_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7");
		
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebcart_coupon` ( `ebcart_coupon_id` int(11) NOT NULL AUTO_INCREMENT, `email` varchar(255) NOT NULL, `coupon_id` int(11) NOT NULL, PRIMARY KEY (`ebcart_coupon_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
		
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart` ( `ebabandonedcart_id` int(11) NOT NULL AUTO_INCREMENT, `customer_id` int(11) NOT NULL,`store_id` int(11) NOT NULL,`language_id` int(11) NOT NULL,`currency` varchar(255) NOT NULL,`firstname` varchar(255) NOT NULL,`lastname` varchar(255) NOT NULL,`email` varchar(255) NOT NULL,`telephone` varchar(255) NOT NULL, `notify_status` tinyint(4) NOT NULL,`session_id` text NOT NULL,`api_id` text NOT NULL,`ip` text NOT NULL,`date_added` datetime NOT NULL,`date_modifed` datetime NOT NULL, PRIMARY KEY (`ebabandonedcart_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart_description` ( `ebabandonedcart_description_id` int(11) NOT NULL AUTO_INCREMENT, `ebabandonedcart_id` int(11) NOT NULL,`product_id` int(11) NOT NULL,`option` text NOT NULL,`quantity` int(11) NOT NULL,`api_id` text NOT NULL,`cart_id` int(11) NOT NULL,`session_id` text NOT NULL,`recurring_id` int(11) NOT NULL,`date_added` datetime NOT NULL, PRIMARY KEY (`ebabandonedcart_description_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebnotify_history` ( `ebnotify_history_id` int(11) NOT NULL AUTO_INCREMENT, `ebabandonedcart_id` int(11) NOT NULL,`email` varchar(255) NOT NULL,`date_added` datetime NOT NULL, PRIMARY KEY (`ebnotify_history_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "eborder` ( `eborder_id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL,`add_date` datetime NOT NULL, PRIMARY KEY (`eborder_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ebabandonedcart_visit_history` ( `ebabandonedcart_visit_history_id` int(11) NOT NULL AUTO_INCREMENT, `ebabandonedcart_id` int(11) NOT NULL,`link` text NOT NULL,`total_count` int(11) NOT NULL,`date_added` datetime NOT NULL, `time_added` time NOT NULL, PRIMARY KEY (`ebabandonedcart_visit_history_id`) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24");
	}
	
	public function addEmailTemplate($data) {
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "ebabandonedcart_email_template SET sort_order = '" . (int)$data['sort_order'] . "'");

		$abandonedcart_email_template_id = $this->db->getLastId();

		foreach ($data['abandonedcart_email_template_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ebabandonedcart_email_template_description SET abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', subject = '" . $this->db->escape($value['subject']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		
		if(!empty($data['coupon_product'])){
			$coupon_product = json_encode($data['coupon_product']); 
		}else{
			$coupon_product = '';
		}
		
		if(!empty($data['coupon_category'])){
			$coupon_category = json_encode($data['coupon_category']); 
		}else{
			$coupon_category = '';
		}
		
		$this->db->query("INSERT INTO ".DB_PREFIX."ebabandonedcart_email_coupon SET abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "', coupon_name = '".$this->db->escape($data['coupon_name'])."', coupon_type  = '".$this->db->escape($data['coupon_type'])."',coupon_discount  = '".$this->db->escape($data['coupon_discount'])."',coupon_total  = '".$this->db->escape($data['coupon_total'])."', coupon_product = '".$coupon_product."', coupon_category = '".$coupon_category."', coupon_vaild = '".(int)$data['coupon_vaild']."',coupon_uses_total = '".(int)$data['coupon_uses_total']."',coupon_contion = '".(int)$data['coupon_contion']."', coupon_uses_customer = '".(int)$data['coupon_uses_customer']."',coupon_status = '".(int)$data['coupon_status']."'");

		return $abandonedcart_email_template_id;
	}

	public function editEmailTemplate($abandonedcart_email_template_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "ebabandonedcart_email_template SET sort_order = '" . (int)$data['sort_order'] . "' WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_email_template_description WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		foreach ($data['abandonedcart_email_template_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "ebabandonedcart_email_template_description SET abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', subject = '" . $this->db->escape($value['subject']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_email_coupon WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");
		
		if(!empty($data['coupon_product'])){
			$coupon_product = json_encode($data['coupon_product']); 
		}else{
			$coupon_product = '';
		}
		
		if(!empty($data['coupon_category'])){
			$coupon_category = json_encode($data['coupon_category']); 
		}else{
			$coupon_category = '';
		}
		
		$this->db->query("INSERT INTO ".DB_PREFIX."ebabandonedcart_email_coupon SET abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "', coupon_name = '".$this->db->escape($data['coupon_name'])."', coupon_type  = '".$this->db->escape($data['coupon_type'])."',coupon_discount  = '".$this->db->escape($data['coupon_discount'])."',coupon_total  = '".$this->db->escape($data['coupon_total'])."',coupon_product = '".$coupon_product."', coupon_category = '".$coupon_category."', coupon_vaild = '".(int)$data['coupon_vaild']."',coupon_uses_total = '".(int)$data['coupon_uses_total']."',coupon_contion = '".(int)$data['coupon_contion']."', coupon_uses_customer = '".(int)$data['coupon_uses_customer']."',coupon_status = '".(int)$data['coupon_status']."'");
	}

	public function deleteEmailTemplate($abandonedcart_email_template_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_email_template WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_email_template_description WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");
	}

	public function getEmailTemplate($abandonedcart_email_template_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_template WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		return $query->row;
	}
	
	public function getEmailCoupon($abandonedcart_email_template_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_coupon WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		return $query->row;
	}
	
	public function getEmailTemplateData($abandonedcart_email_template_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_template et LEFT JOIN " . DB_PREFIX . "ebabandonedcart_email_template_description etd ON (et.abandonedcart_email_template_id = etd.abandonedcart_email_template_id) WHERE et.abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "' AND etd.language_id = '". (int)$this->config->get('config_language_id') ."'");

		return $query->row;
	}
	
	public function getEmailTemplateDatabylanguage($abandonedcart_email_template_id,$language_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_template et LEFT JOIN " . DB_PREFIX . "ebabandonedcart_email_template_description etd ON (et.abandonedcart_email_template_id = etd.abandonedcart_email_template_id) WHERE et.abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "' AND etd.language_id = '". (int)$language_id ."'");

		return $query->row;
	}

	public function getEmailTemplates($data = array()) {

		$sql = "SELECT * FROM " . DB_PREFIX . "ebabandonedcart_email_template i LEFT JOIN " . DB_PREFIX . "ebabandonedcart_email_template_description id ON (i.abandonedcart_email_template_id = id.abandonedcart_email_template_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND i.status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = array(
			'id.title',
			'i.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY id.title";
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

	public function getEmailTemplateDescriptions($abandonedcart_email_template_id) {
		$abandonedcart_email_template_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart_email_template_description WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		foreach ($query->rows as $result) {
			$abandonedcart_email_template_description_data[$result['language_id']] = array(
				'title'            => $result['title'],
				'subject'     		 => $result['subject'],
				'description'      => $result['description'],
			);
		}

		return $abandonedcart_email_template_description_data;
	}

	public function getTotalEmailTemplates() {
		$this->CreateTables();
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ebabandonedcart_email_template");

		return $query->row['total'];
	}
	
	
	public function getCustomerCoupons($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "ebcart_coupon cc LEFT JOIN  " . DB_PREFIX . "coupon cp ON(cp.coupon_id=cc.coupon_id) WHERE cp.coupon_id=cc.coupon_id";
		
		//WHERE 
		$implode = array();
		
		if (!empty($data['filter_email'])) {
			$implode[] = "cc.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
		}

		if ($implode) {
			$sql .= " AND " . implode(" AND ", $implode);
		}

		if(!empty($data['filter_unused_coupons'])) {
			$sql .= " AND cc.coupon_id NOT IN (SELECT coupon_id from ". DB_PREFIX ."coupon_history)";
		}else{
			$sql .= " AND cc.coupon_id IN (SELECT coupon_id from ". DB_PREFIX ."coupon_history)";
		}
		
		$sort_data = array(
			'c.email',
			'cp.date_start',
			'cp.date_end',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY cp.date_start";
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
	
	public function getTotalCustomerCoupons($data = array()) {
		$sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "ebcart_coupon cc LEFT JOIN  " . DB_PREFIX . "coupon cp ON(cp.coupon_id=cc.coupon_id) WHERE cp.coupon_id=cc.coupon_id";
		
		if(!empty($data['filter_unused_coupons'])) {
			$sql .= " AND cc.coupon_id NOT IN (SELECT coupon_id from ". DB_PREFIX ."coupon_history)";
		}else{
			$sql .= " AND cc.coupon_id IN (SELECT coupon_id from ". DB_PREFIX ."coupon_history)";
		}
				
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
}