<?php
class ModelExtensionMeordermanager extends Model {
	
	public function create_table(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_manager_tracking` (`order_id` int(11) NOT NULL,`order_status_id` int(11) NOT NULL,`carrier_name` varchar(255) NOT NULL,`tracking_code` varchar(255) NOT NULL,PRIMARY KEY (`order_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_manager_email_template` (`order_status_id` int(11) NOT NULL,`template` text NOT NULL,PRIMARY KEY (`order_status_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order_manager_tracking` LIKE 'tracking_url'");
		if(!$query->num_rows){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order_manager_tracking` ADD `tracking_url` varchar(255) NOT NULL AFTER `tracking_code`");
		}
	}
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$reward = 0;

			$order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

			foreach ($order_product_query->rows as $product) {
				$reward += $product['reward'];
			}
			
			$this->load->model('customer/customer');

			$affiliate_info = $this->model_customer_customer->getCustomer($order_query->row['affiliate_id']);

			if ($affiliate_info) {
				$affiliate_firstname = $affiliate_info['firstname'];
				$affiliate_lastname = $affiliate_info['lastname'];
			} else {
				$affiliate_firstname = '';
				$affiliate_lastname = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
				$language_directory = $language_info['directory'];
			} else {
				$language_code = $this->config->get('config_language');
				$language_directory = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'customer'                => $order_query->row['customer'],
				'customer_group_id'       => $order_query->row['customer_group_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'fax'                     => $order_query->row['fax'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
                'boxnow'         => $order_query->row['boxnow'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'reward'                  => $reward,
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'affiliate_firstname'     => $affiliate_firstname,
				'affiliate_lastname'      => $affiliate_lastname,
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'language_directory'      => $language_directory,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return;
		}
	}

	public function getOrders($data = array()) {
		
		$sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer,cgd.name AS customer_group, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (o.customer_group_id = cgd.customer_group_id)";
		
		if (isset($data['filter_product']) && $data['filter_product'] !== '') {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)";
		}
		
		if ((isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== '') || (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== '')) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_manager_tracking ot ON (o.order_id = ot.order_id)";
		}
		
		if (isset($data['filter_order_status']) && $data['filter_order_status'] !== '') {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);
			
			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}
		
		if (!empty($data['selected'])) {
			$sql .= " AND o.order_id IN (" . $data['selected'] . ")";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}
		
		if (!empty($data['filter_customeremail'])) {
			$sql .= " AND o.email LIKE '%" . $this->db->escape($data['filter_customeremail']) . "%'";
		}
		
		if (!empty($data['filter_customer_telephone'])) {
			$sql .= " AND o.telephone LIKE '%" . $this->db->escape($data['filter_customer_telephone']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}
		
		if (!empty($data['filter_payment_method'])) {
			$sql .= " AND o.payment_code = '" . $data['filter_payment_method'] . "'";
		}
		
		if (!empty($data['filter_shipping_method'])) {
			$sql .= " AND o.shipping_code LIKE '%" . $data['filter_shipping_method'] . "'";
		}
		
		if (!empty($data['filter_customer_group'])) {
			$sql .= " AND o.customer_group_id = '" . (int)$data['filter_customer_group'] . "'";
		}
		
		if (!empty($data['filter_store'])) {
			$sql .= " AND o.store_id = '" . (int)$data['filter_store'] . "'";
		}
		
		if (!empty($data['filter_payment_country'])) {
			$sql .= " AND o.payment_country_id = '" . (int)$data['filter_payment_country'] . "'";
		}
		
		if (!empty($data['filter_shipping_country'])) {
			$sql .= " AND o.shipping_country_id = '" . (int)$data['filter_shipping_country'] . "'";
		}
		
		if (!empty($data['filter_currency'])) {
			$sql .= " AND o.currency_id = '" . (int)$data['filter_currency'] . "'";
		}
		
		if (!empty($data['filter_shipping_zone'])) {
			$sql .= " AND o.shipping_zone_id = '" . (int)$data['filter_shipping_zone'] . "'";
		}
		
		if (!empty($data['filter_product'])) {
			$sql .= " AND op.name LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
		}
		
		if (isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== ''){
			$sql .= " AND ot.carrier_name LIKE '%" . $this->db->escape($data['filter_carrier_name']) . "%'";
		}
		
		if (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== ''){
			$sql .= " AND ot.tracking_code LIKE '%" . $this->db->escape($data['filter_tracking_code']) . "%'";
		}
		
		if (isset($data['filter_ip']) && $data['filter_ip'] !== ''){
			$sql .= " AND o.ip LIKE '%" . $this->db->escape($data['filter_ip']) . "%'";
		}
		
		$sql .= " GROUP BY o.order_id";

		$sort_data = array(
			'o.order_id',
			'o.invoice_no',
			'o.invoice_prefix',
			'o.store_id',
			'o.customer_group_id',
			'customer',
			'order_status',
			'o.date_added',
			'o.date_modified',
			'o.total',
			'o.payment_method',
			'o.shipping_method',
			'o.comment',
			'o.currency_code',
			'o.language_id',
			'o.ip',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
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

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}
	
	public function getTotalOrderProducts($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderVoucherByVoucherId($voucher_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE voucher_id = '" . (int)$voucher_id . "'");

		return $query->row;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}
	
	public function getTotalOrders($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o";
		
		if (isset($data['filter_product']) && $data['filter_product'] !== '') {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)";
		}
		
		if ((isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== '') || (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== '')) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_manager_tracking ot ON (o.order_id = ot.order_id)";
		}

		if (isset($data['filter_order_status']) && $data['filter_order_status'] !== '') {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}
		
		if (!empty($data['filter_customeremail'])) {
			$sql .= " AND o.email LIKE '%" . $this->db->escape($data['filter_customeremail']) . "%'";
		}
		
		if (!empty($data['filter_customer_telephone'])) {
			$sql .= " AND o.telephone LIKE '%" . $this->db->escape($data['filter_customer_telephone']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}
		
		if (!empty($data['filter_payment_method'])) {
			$sql .= " AND o.payment_code = '" . $data['filter_payment_method'] . "'";
		}
		
		if (!empty($data['filter_shipping_method'])) {
			$sql .= " AND o.shipping_code LIKE '%" . $data['filter_shipping_method'] . "'";
		}
		
		if (!empty($data['filter_customer_group'])) {
			$sql .= " AND o.customer_group_id = '" . (int)$data['filter_customer_group'] . "'";
		}
		
		if (!empty($data['filter_store'])) {
			$sql .= " AND o.store_id = '" . (int)$data['filter_store'] . "'";
		}
		
		if (!empty($data['filter_payment_country'])) {
			$sql .= " AND o.payment_country_id = '" . (int)$data['filter_payment_country'] . "'";
		}
		
		if (!empty($data['filter_shipping_country'])) {
			$sql .= " AND o.shipping_country_id = '" . (int)$data['filter_shipping_country'] . "'";
		}
		
		if (!empty($data['filter_currency'])) {
			$sql .= " AND o.currency_id = '" . (int)$data['filter_currency'] . "'";
		}
		
		if (!empty($data['filter_shipping_zone'])) {
			$sql .= " AND o.shipping_zone_id = '" . (int)$data['filter_shipping_zone'] . "'";
		}
		
		if (!empty($data['filter_product'])) {
			$sql .= " AND op.name LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
		}
		
		if (isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== ''){
			$sql .= " AND ot.carrier_name LIKE '%" . $this->db->escape($data['filter_carrier_name']) . "%'";
		}
		
		if (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== ''){
			$sql .= " AND ot.tracking_code LIKE '%" . $this->db->escape($data['filter_tracking_code']) . "%'";
		}
		
		if (isset($data['filter_ip']) && $data['filter_ip'] !== ''){
			$sql .= " AND o.ip LIKE '%" . $this->db->escape($data['filter_ip']) . "%'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function getTotalSumOrders($data = array()) {
		$sql = "SELECT SUM(o.total) AS total FROM `" . DB_PREFIX . "order` o";
		
		if (isset($data['filter_product']) && $data['filter_product'] !== '') {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)";
		}
		
		if ((isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== '') || (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== '')) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "order_manager_tracking ot ON (o.order_id = ot.order_id)";
		}

		if (isset($data['filter_order_status']) && $data['filter_order_status'] !== '') {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}
		
		if (!empty($data['filter_customeremail'])) {
			$sql .= " AND o.email LIKE '%" . $this->db->escape($data['filter_customeremail']) . "%'";
		}
		
		if (!empty($data['filter_customer_telephone'])) {
			$sql .= " AND o.telephone LIKE '%" . $this->db->escape($data['filter_customer_telephone']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
		}
		
		if (!empty($data['filter_payment_method'])) {
			$sql .= " AND o.payment_code = '" . $data['filter_payment_method'] . "'";
		}
		
		if (!empty($data['filter_shipping_method'])) {
			$sql .= " AND o.shipping_code LIKE '%" . $data['filter_shipping_method'] . "'";
		}
		
		if (!empty($data['filter_customer_group'])) {
			$sql .= " AND o.customer_group_id = '" . (int)$data['filter_customer_group'] . "'";
		}
		
		if (!empty($data['filter_store'])) {
			$sql .= " AND o.store_id = '" . (int)$data['filter_store'] . "'";
		}
		
		if (!empty($data['filter_payment_country'])) {
			$sql .= " AND o.payment_country_id = '" . (int)$data['filter_payment_country'] . "'";
		}
		
		if (!empty($data['filter_shipping_country'])) {
			$sql .= " AND o.shipping_country_id = '" . (int)$data['filter_shipping_country'] . "'";
		}
		
		if (!empty($data['filter_currency'])) {
			$sql .= " AND o.currency_id = '" . (int)$data['filter_currency'] . "'";
		}
		
		if (!empty($data['filter_shipping_zone'])) {
			$sql .= " AND o.shipping_zone_id = '" . (int)$data['filter_shipping_zone'] . "'";
		}
		
		if (!empty($data['filter_product'])) {
			$sql .= " AND op.name LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
		}
		
		if (isset($data['filter_carrier_name']) && $data['filter_carrier_name'] !== ''){
			$sql .= " AND ot.carrier_name LIKE '%" . $this->db->escape($data['filter_carrier_name']) . "%'";
		}
		
		if (isset($data['filter_tracking_code']) && $data['filter_tracking_code'] !== ''){
			$sql .= " AND ot.tracking_code LIKE '%" . $this->db->escape($data['filter_tracking_code']) . "%'";
		}
		
		if (isset($data['filter_ip']) && $data['filter_ip'] !== ''){
			$sql .= " AND o.ip LIKE '%" . $this->db->escape($data['filter_ip']) . "%'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalOrdersByStoreId($store_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE store_id = '" . (int)$store_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrdersByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE order_status_id = '" . (int)$order_status_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByProcessingStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_processing_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode));

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByCompleteStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_complete_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode) . "");

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByLanguageId($language_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE language_id = '" . (int)$language_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByCurrencyId($currency_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE currency_id = '" . (int)$currency_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}
	
	public function getTotalSales($data = array()) {
		$sql = "SELECT SUM(total) AS total FROM `" . DB_PREFIX . "order`";

		if (!empty($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND total = '" . (float)$data['filter_total'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function createInvoiceNo($order_id) {
		$order_info = $this->getOrder($order_id);

		if ($order_info && !$order_info['invoice_no']) {
			$query = $this->db->query("SELECT MAX(invoice_no) AS invoice_no FROM `" . DB_PREFIX . "order` WHERE invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "'");

			if ($query->row['invoice_no']) {
				$invoice_no = $query->row['invoice_no'] + 1;
			} else {
				$invoice_no = 1;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_no = '" . (int)$invoice_no . "', invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "' WHERE order_id = '" . (int)$order_id . "'");

			return $order_info['invoice_prefix'] . $invoice_no;
		}
	}

	public function getOrderHistories($order_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT oh.date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}
	
	public function getOrderexportHistories($order_id){
		$query = $this->db->query("SELECT *,os.name AS status FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added ASC ");

		return $query->rows;
	}

	public function getTotalOrderHistories($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderHistoriesByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_status_id = '" . (int)$order_status_id . "'");

		return $query->row['total'];
	}
	
	public function getEmailsByProductsOrdered($products, $start, $end) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT DISTINCT email FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0' LIMIT " . (int)$start . "," . (int)$end);

		return $query->rows;
	}

	public function getTotalEmailsByProductsOrdered($products) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT COUNT(DISTINCT email) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0'");

		return $query->row['total'];
	}
	
	public function deleteOrder($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_manager_tracking` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order_id . "'");

		// Gift Voucher
		$this->db->query("UPDATE " . DB_PREFIX . "voucher SET status = '0' WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {
		$this->load->language('extension/me_order_manager');
		$order_info = $this->getOrder($order_id);
		
		if ($order_info) {
			// Fraud Detection
			$this->load->model('customer/customer');

			$customer_info = $this->model_customer_customer->getCustomer($order_info['customer_id']);

			if ($customer_info && $customer_info['safe']) {
				$safe = true;
			} else {
				$safe = false;
			}

			// Only do the fraud check if the customer is not on the safe list and the order status is changing into the complete or process order status
			if (!$safe && !$override && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Anti-Fraud
				$this->load->model('setting/extension');

				$extensions = $this->model_setting_extension->getInstalled('fraud');

				foreach ($extensions as $extension) {
					if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
						$this->load->model('extension/fraud/' . $extension['code']);

						if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);
	
							if ($fraud_status_id) {
								$order_status_id = $fraud_status_id;
							}
						}
					}
				}
			}

			// If current order status is not processing or complete but new status is processing or complete then commence completing the order
			if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Redeem coupon, vouchers and reward points
				$order_totals = $this->getOrderTotals($order_id);

				foreach ($order_totals as $order_total) {
					if($order_total['code'] == 'coupon'){
						$fraud_status_id = $this->couponconfirm($order_info, $order_total);
					}
					if($order_total['code'] == 'voucher'){
						$fraud_status_id = $this->voucherconfirm($order_info, $order_total);
					}
					if($order_total['code'] == 'reward'){
						$fraud_status_id = $this->rewardconfirm($order_info, $order_total);
					}
				
					// If the balance on the coupon, vouchers and reward points is not enough to cover the transaction or has already been used then the fraud order status is returned.
					if (isset($fraud_status_id)) {
						$order_status_id = $fraud_status_id;
					}
				}

				// Stock subtraction
				$order_products = $this->getOrderProducts($order_id);

				foreach ($order_products as $order_product) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}
				
				// Add commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
					$this->load->model('account/customer');

					if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
						$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
					}
				}
			}

			// Update the DB with the new statuses
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
			
			$textmessage  = $this->language->get('text_update_order') . ' ' . $order_id . "\n";
			$textmessage .= $this->language->get('text_update_date_added') . ' ' . date($this->language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n\n";
			
			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");
				
			if ($order_status_query->num_rows) {
				$order_status = $order_status_query->row['name'];
				$textmessage .= $this->language->get('text_update_order_status') . "\n\n";
				$textmessage .= $order_status_query->row['name'] . "\n\n";
			}else{
				$order_status = $this->language->get('text_missing');
			}
			
			if ($order_info['customer_id']) {
				$textmessage .= $this->language->get('text_update_link') . "\n";
				$textmessage .= $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id . "\n\n";
			}
			
			$textmessage .= $this->language->get('text_update_footer');
			
			$date_added = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));
			if ($this->request->server['HTTPS']) {
				$server = HTTPS_CATALOG;
			} else {
				$server = HTTP_CATALOG;
			}
			$store_name = $order_info['store_name'];
			$store_url = $order_info['store_url'];
			$store_logo = '<img src="'. $server.'image/'.$this->config->get('config_logo') .'" />';
			
			$find = array(
				'{order_id}',
				'{firstname}',
				'{lastname}',
				'{order_status}',
				'{date_added}'
			);
			
			$replace = array(
				$order_id,
				$order_info['firstname'],
				$order_info['lastname'],
				$order_status,
				$date_added
			);
			
			if ($comment) {
				$message = str_replace($find,$replace,strip_tags($comment));
			}else{
				if($notify){
					$textmessage .= $this->language->get('text_update_comment') . "\n\n";
					$textmessage .= strip_tags($comment) . "\n\n";
					$message = $textmessage;
				}else{
					$message = '';
				}
			}

			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($message) . "', date_added = NOW()");

			// If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
			if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Restock
				$order_products = $this->getOrderProducts($order_id);

				foreach($order_products as $order_product) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}

				// Remove coupon, vouchers and reward points history
				$order_totals = $this->getOrderTotals($order_id);
				
				foreach ($order_totals as $order_total) {
					if($order_total['code'] == 'coupon'){
						$this->couponunconfirm($order_id);
					}
					if($order_total['code'] == 'voucher'){
						$this->voucherunconfirm($order_id);
					}
					if($order_total['code'] == 'reward'){
						$this->rewardunconfirm($order_id);
					}
				}

				// Remove commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id']) {
					$this->deleteTransactionByOrderId($order_id);
				}
			}
			
			if ($order_info['order_status_id'] && $order_status_id && $this->config->get('module_me_order_manager_setting_sms') && $notify) {
				$smsmessage = urlencode($message);
				$find = array(
					'{mobile}',
					'{message}'
				);
				$replace = array(
					$order_info['telephone'],
					$smsmessage
				);
				$url = str_replace($find,$replace,$this->config->get('module_me_order_manager_setting_api') && $notify);
				
				//  Initiate curl
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, false);
				// Will return the response, if false it print the response
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// Set the url
				curl_setopt($ch, CURLOPT_URL,$url);
				// Execute
				$result=curl_exec($ch);
				// Closing
				curl_close($ch);
				
				if(!$result){
				   $result =  file_get_contents($url);
				}
			}
			
			if ($order_info['order_status_id'] && $order_status_id && $notify) {
				$order_products = $this->getOrderProducts($order_id);
				$products = '<table style="border-collapse: collapse; width: 100%; border-top: 1px solid #DDDDDD; border-left: 1px solid #DDDDDD; margin-bottom: 20px;">';
				$products .= '<thead>';
				$products .= '<tr>';
				$products .= '  <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; background-color: #EFEFEF; font-weight: bold; text-align: left; padding: 7px; color: #222222;"><b>'.$this->language->get('column_product').'</b></td>';
				$products .= '  <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; background-color: #EFEFEF; font-weight: bold; text-align: left; padding: 7px; color: #222222;"><b>'.$this->language->get('column_model').'</b></td>';
				$products .= '   <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; background-color: #EFEFEF; font-weight: bold; text-align: right; padding: 7px; color: #222222;"><b>'.$this->language->get('column_quantity').'</b></td>';
				$products .= '  <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; background-color: #EFEFEF; font-weight: bold; text-align: right; padding: 7px; color: #222222;"><b>'.$this->language->get('column_price').'</b></td>';
				$products .= '  <td style="font-size: 12px; border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; background-color: #EFEFEF; font-weight: bold; text-align: right; padding: 7px; color: #222222;"><b>'.$this->language->get('column_total').'</b></td>';
				$products .= '</tr>';
			    $products .= '</thead>';
			    $products .= '<tbody>';
				foreach($order_products as $order_product) {
					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);
					$products .= '<tr>';
					$products .= '<td style="font-size: 12px;	border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;">'.$order_product['name'];
					foreach($order_options as $option) {
					$products .= '<br />';
					$products .= '<small> - '.$option['name'].': '.$option['value'].'</small>';
					}
					$products .= '</td>';
					$products .= '<td style="font-size: 12px;	border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: left; padding: 7px;">'.$order_product['model'].'</td>';
					$products .= '<td style="font-size: 12px;	border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: right; padding: 7px;">'.$order_product['quantity'].'</td>';
					$products .= '<td style="font-size: 12px;	border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: right; padding: 7px;">'.$order_product['price'].'</td>';
					$products .= '<td style="font-size: 12px;	border-right: 1px solid #DDDDDD; border-bottom: 1px solid #DDDDDD; text-align: right; padding: 7px;">'.$order_product['total'].'</td>';
					$products .= '</tr>';
				}
				$products .= '</tbody>';
				$products .= '</table>';
				$tfind = array(
					'{order_id}',
					'{firstname}',
					'{lastname}',
					'{order_status}',
					'{date_added}',
					'{store_name}',
					'{store_url}',
					'{store_logo}',
					'{comment}',
					'{products}',
					'{total_cost}'
				);
				$total_cost = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
				$treplace = array(
					$order_id,
					$order_info['firstname'],
					$order_info['lastname'],
					$order_status,
					$date_added,
					$store_name,
					$store_url,
					$store_logo,
					$message,
					$products,
					$total_cost,
				);
				
				$email_template = $this->getEmailTemplatebyid($order_status_id);
				if(!empty($email_template['template'])){
					$order_status_message = str_replace($tfind,$treplace,$email_template['template']);
				}elseif(!empty($this->config->get('module_me_order_manager_setting_bulkupdate')[$order_status_id]['template'])){
					$order_status_message = !empty($this->config->get('module_me_order_manager_setting_bulkupdate')[$order_status_id]['template']) ? str_replace($tfind,$treplace,$this->config->get('module_me_order_manager_setting_bulkupdate')[$order_status_id]['template']) : '';
				}else{
					$order_status_message = '';
				}
				
				$subject = sprintf($this->language->get('text_update_subject'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);
				
				$mail = new Mail($this->config->get('config_mail_engine'));
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
	
				$mail->setTo($order_info['email']);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
				$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
				if($order_status_message){
					$mail->setHtml(html_entity_decode($order_status_message));
				}else{
					$mail->setText($message);
				}
				$mail->send();
			}
		}
	}
	
	public function deleteTransactionByOrderId($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function couponconfirm($order_info, $order_total) {
		$code = '';

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}

		if ($code) {
			$status = true;
			
			$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND status = '1'");

			if ($coupon_query->num_rows) {
				$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);
	
				if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
					$status = false;
				}
				
				if ($order_info['customer_id']) {
					$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $order_info['customer_id']);
					
					if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
						$status = false;
					}
				}
			} else {
				$status = false;	
			}

			if ($status) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_history` SET coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', customer_id = '" . (int)$order_info['customer_id'] . "', amount = '" . (float)$order_total['value'] . "', date_added = NOW()");
			} else {
				return $this->config->get('config_fraud_status_id');
			}
		}
	}
	
	public function getTotalCouponHistoriesByCoupon($coupon) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "'");	
		
		return $query->row['total'];
	}
	
	public function getTotalCouponHistoriesByCustomerId($coupon, $customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "' AND ch.customer_id = '" . (int)$customer_id . "'");
		
		return $query->row['total'];
	}
	
	public function voucherconfirm($order_info, $order_total) {
		$code = '';

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}

		if ($code) {
			$voucher_info = $this->getVoucher($code);

			if ($voucher_info) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "voucher_history` SET voucher_id = '" . (int)$voucher_info['voucher_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', amount = '" . (float)$order_total['value'] . "', date_added = NOW()");
			} else {
				return $this->config->get('config_fraud_status_id');
			}
		}
	}
	
	public function getVoucher($code) {
		$status = true;

		$voucher_query = $this->db->query("SELECT *, vtd.name AS theme FROM " . DB_PREFIX . "voucher v LEFT JOIN " . DB_PREFIX . "voucher_theme vt ON (v.voucher_theme_id = vt.voucher_theme_id) LEFT JOIN " . DB_PREFIX . "voucher_theme_description vtd ON (vt.voucher_theme_id = vtd.voucher_theme_id) WHERE v.code = '" . $this->db->escape($code) . "' AND vtd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND v.status = '1'");

		if ($voucher_query->num_rows) {
			if ($voucher_query->row['order_id']) {
				$implode = array();

				foreach ($this->config->get('config_complete_status') as $order_status_id) {
					$implode[] = "'" . (int)$order_status_id . "'";
				}

				$order_query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$voucher_query->row['order_id'] . "' AND order_status_id IN(" . implode(",", $implode) . ")");

				if (!$order_query->num_rows) {
					$status = false;
				}

				$order_voucher_query = $this->db->query("SELECT order_voucher_id FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$voucher_query->row['order_id'] . "' AND voucher_id = '" . (int)$voucher_query->row['voucher_id'] . "'");

				if (!$order_voucher_query->num_rows) {
					$status = false;
				}
			}

			$voucher_history_query = $this->db->query("SELECT SUM(amount) AS total FROM `" . DB_PREFIX . "voucher_history` vh WHERE vh.voucher_id = '" . (int)$voucher_query->row['voucher_id'] . "' GROUP BY vh.voucher_id");

			if ($voucher_history_query->num_rows) {
				$amount = $voucher_query->row['amount'] + $voucher_history_query->row['total'];
			} else {
				$amount = $voucher_query->row['amount'];
			}

			if ($amount <= 0) {
				$status = false;
			}
		} else {
			$status = false;
		}

		if ($status) {
			return array(
				'voucher_id'       => $voucher_query->row['voucher_id'],
				'code'             => $voucher_query->row['code'],
				'from_name'        => $voucher_query->row['from_name'],
				'from_email'       => $voucher_query->row['from_email'],
				'to_name'          => $voucher_query->row['to_name'],
				'to_email'         => $voucher_query->row['to_email'],
				'voucher_theme_id' => $voucher_query->row['voucher_theme_id'],
				'theme'            => $voucher_query->row['theme'],
				'message'          => $voucher_query->row['message'],
				'image'            => $voucher_query->row['image'],
				'amount'           => $amount,
				'status'           => $voucher_query->row['status'],
				'date_added'       => $voucher_query->row['date_added']
			);
		}
	}
	
	public function rewardconfirm($order_info, $order_total) {
		$points = 0;

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$points = substr($order_total['title'], $start, $end - $start);
		}

		$this->load->model('account/customer');

		if ($this->getRewardTotal($order_info['customer_id']) >= $points) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "customer_reward SET customer_id = '" . (int)$order_info['customer_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', description = '" . $this->db->escape(sprintf($this->language->get('text_order_id'), (int)$order_info['order_id'])) . "', points = '" . (float)-$points . "', date_added = NOW()");
		} else {
			return $this->config->get('config_fraud_status_id');
		}
	}
	
	public function getRewardTotal($customer_id) {
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}
	
	public function couponunconfirm($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_history` WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function voucherunconfirm($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "voucher_history` WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function rewardunconfirm($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points < 0");
	}
	
	public function deleteApiSessionBySessonId($session_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "api_session` WHERE session_id = '" . $this->db->escape($session_id) . "'");
	}
	
	public function getOrderColumns() {
		$columns = array();
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order`");
		foreach($query->rows as $row){
			$columns[] = $row['Field'];
		}

		return $columns;
	}
	
	public function getshippingpro() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "xshippingpro`");
		
		return $query->rows;
	}
	
	public function addTracking($order_id,$order_status_id,$tracking_number,$carrier_name,$tracking_url){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_tracking` WHERE order_id = '" . (int)$order_id . "'");
		
		if($query->num_rows){
			$this->db->query("UPDATE " . DB_PREFIX . "order_manager_tracking SET tracking_code = '" . $this->db->escape($tracking_number) . "',carrier_name = '" . $this->db->escape($carrier_name) . "',tracking_url = '" . $this->db->escape($tracking_url) . "' WHERE order_id = '" . (int)$order_id . "'");
		}else{
			$this->db->query("INSERT INTO " . DB_PREFIX . "order_manager_tracking SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', tracking_code = '" . $this->db->escape($tracking_number) . "',carrier_name = '" . $this->db->escape($carrier_name) . "',tracking_url = '" . $this->db->escape($tracking_url) . "'");
		}
	}
	
	public function getTracking($order_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_tracking` WHERE order_id = '" . (int)$order_id . "'");
		
		return $query->row;
	}
	
	public function addEmailTemplate($data){
		foreach($data as $order_status_id => $template){
			if(!empty($template)){
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_email_template` WHERE order_status_id = '" . (int)$order_status_id . "'");
				if($query->num_rows){
					$this->db->query("UPDATE " . DB_PREFIX . "order_manager_email_template SET template = '" . $this->db->escape($template) . "' WHERE order_status_id = '" . (int)$order_status_id . "'");
				}else{
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_manager_email_template SET order_status_id = '" . (int)$order_status_id . "', template = '" . $this->db->escape($template) . "'");
				}
			}
		}
	}
	
	public function getEmailTemplate(){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_email_template`");
		
		return $query->rows;
	}
	
	public function getEmailTemplatebyid($order_status_id){
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_manager_email_template` WHERE order_status_id = '" . (int)$order_status_id . "'");
		
		return $query->row;
	}
}
