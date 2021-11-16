<?php
class ModelExtensionEbcart extends Model {
	
	public function deleteOrders($order_id){
		$this->db->query("DELETE FROM ".DB_PREFIX."eborder WHERE order_id = '".(int)$order_id."'");
	}
	
	public function getTotalOrders(){
		$sql = "SELECT COUNT(*) as total FROM `". DB_PREFIX ."eborder` e LEFT JOIN `".DB_PREFIX."order` o ON (e.order_id = o.order_id) WHERE e.eborder_id > 0";
		
		if(!empty($data['filter_from']) && !empty($data['filter_to'])){
			$sql .= " AND DATE(e.add_date) BETWEEN DATE('" . $this->db->escape($data['filter_from']) . "') AND DATE('" . $this->db->escape($data['filter_to']) . "')";
		}
		
		if(isset($data['filter_store_id'])){
			$sql .=" AND o.store_id = '".(int)$data['filter_store_id']."'";	
		}
		
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function getCompleteOrders($data){
		$sql = "SELECT * FROM `".DB_PREFIX."eborder` e LEFT JOIN `".DB_PREFIX."order` o ON(e.order_id = o.order_id) WHERE e.eborder_id > 0";
		
		if(!empty($data['filter_from']) && !empty($data['filter_to'])){
			$sql .= " AND DATE(e.add_date) BETWEEN DATE('" . $this->db->escape($data['filter_from']) . "') AND DATE('" . $this->db->escape($data['filter_to']) . "')";
		}
		
		if(isset($data['filter_store_id'])){
			$sql .=" AND o.store_id = '".(int)$data['filter_store_id']."'";	
		}
		
		$sql .=" Order by e.add_date DESC";
		
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
		
		$this->load->model('sale/order');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		
		$orders=array();
		foreach($query->rows as $row){
			$order_info = $this->model_sale_order->getOrder($row['order_id']);
			if($order_info):
			$orderproducts=array();
			$products = $this->model_sale_order->getOrderProducts($order_info['order_id']);
			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($order_info['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
				}
				
				$product_info = $this->model_catalog_product->getProduct($product['product_id']);
				
				if (is_file(DIR_IMAGE . $product_info['image'])) {
				  $image = $this->model_tool_image->resize($product_info['image'], 100, 100);
				} else {
				  $image = $this->model_tool_image->resize('no_image.png', 100, 100);
			    }

				$orderproducts[] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'image'			   => $image,
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], true)
				);
			}
			
			
			$orders[]=array(
				 'order_id'  	 	=> $order_info['order_id'],
				 'firstname' 	 	=> $order_info['firstname'],
				 'visitor'        	=> ($order_info['customer_id'] ? '<span class="btn-sm btn-success">'. $this->language->get('text_registered').'</span>' : '<span class="btn-sm btn-success">'. $this->language->get("text_guest").'</span>'),
				 'lastname'  	 	=> $order_info['lastname'],
				 'email'  	 	 	=> $order_info['email'],
				 'telephone'  	 	=> $order_info['telephone'],
				 'currency'  	    => $order_info['currency_code'],
				 'language_code'  	=> $order_info['language_code'],
				 'total'  			=> $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
				 'date_added'  		=> date($this->language->get('datetime_format'),strtotime($order_info['date_added'])),
				 'store'  			=> $order_info['store_name'],
				 'products'			=> $orderproducts,
				 'ip'				=> $order_info['ip'],
				 'ip_href'			=> 'http://whatismyipaddress.com/ip/'. $order_info['ip'],
				 'href'				=> $this->url->link('sale/order','&user_token='.$this->session->data['user_token'],'SSL'),
			);
			endif;
		}
		return $orders;
	}
	
	public function updatenotifystatus($ebabandonedcart_id,$email){
		$this->db->query("DELETE FROM ".DB_PREFIX."ebnotify_history WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
		
		$this->db->query("INSERT INTO ".DB_PREFIX."ebnotify_history SET ebabandonedcart_id = '".(int)$ebabandonedcart_id."', email = '".$this->db->escape($email)."', date_added = NOW()");
		
		$this->db->query("UPDATE ".DB_PREFIX."ebabandonedcart SET notify_status = 1 WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
	}
	
	public function deleteebcart($ebabandonedcart_id){
		$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
		$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart_description WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
	}
	
	public function getunkownrecordclean(){
		$sql ="SELECT COUNT(*) as total FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id > 0";
		
		$sql .=" AND (firstname = '' AND email = '')";
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function getexpirecoupons(){
		$sql = "SELECT count(*) as total FROM " . DB_PREFIX . "ebcart_coupon cc LEFT JOIN  " . DB_PREFIX . "coupon cp ON(cp.coupon_id=cc.coupon_id) WHERE cp.coupon_id=cc.coupon_id";
		
		$sql .=" AND cp.date_end < NOW()";
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function deleteexpirecoupons(){
		$sql = "SELECT * FROM " . DB_PREFIX . "ebcart_coupon cc LEFT JOIN  " . DB_PREFIX . "coupon cp ON(cp.coupon_id=cc.coupon_id) WHERE cp.coupon_id=cc.coupon_id";
		
		$sql .=" AND cp.date_end < NOW()";
		
		$query = $this->db->query($sql);
		
		foreach($query->rows as $row){
			$this->db->query("DELETE FROM ".DB_PREFIX."ebcart_coupon WHERE coupon_id = ".(int)$row['coupon_id']."");
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_category WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_history WHERE coupon_id = '" . (int)$row['coupon_id'] . "'");
		}
	}
	
	public function deleteunkownrecordclean(){
		$sql ="DELETE FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id > 0";
		
		$sql .=" AND (firstname = '' AND email = '')";
		
		$query = $this->db->query($sql);

	}
	
	public function getebcarts($data=array()){
		$sql = "SELECT *,(select name from ".DB_PREFIX."store WHERE store_id = e.store_id) as name FROM ".DB_PREFIX."ebabandonedcart e WHERE e.store_id = ".(int)$data['filter_store_id']."";
		
		if(!empty($data['filter_from']) && !empty($data['filter_to'])){
			$sql .= " AND DATE(e.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_from']) . "') AND DATE('" . $this->db->escape($data['filter_to']) . "')";
		}
		
		if(isset($data['filter_unknown']) && $data['filter_unknown']==2){
			$sql .=" AND (e.email != '' OR e.telephone != '')";
		}
	
		
		if(!empty($data['filter_vistor']) && $data['filter_vistor']==1){
			$sql .= " AND e.customer_id !=0";
		}
		
		if(!empty($data['filter_vistor']) && $data['filter_vistor']==2){
			$sql .= " AND e.customer_id = 0";
		}
		
		if(isset($data['filter_notify']) && !is_null($data['filter_notify'])){
			$sql .= " AND e.notify_status = '".(int)$data['filter_notify']."'";
		}
		
		$sql .=" ORDER By e.date_added DESC";
		
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
	
	
	public function getTotalbcarts($data=array()){
		$sql = "SELECT COUNT(*) as total FROM ".DB_PREFIX."ebabandonedcart e WHERE e.store_id = ".(int)$data['filter_store_id']."";
		
		if(!empty($data['filter_from']) && !empty($data['filter_to'])){
			$sql .= " AND DATE(e.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_from']) . "') AND DATE('" . $this->db->escape($data['filter_to']) . "')";
		}
		
		if(isset($data['filter_unknown']) && $data['filter_unknown']==2){
			$sql .=" AND (e.email != '' OR e.telephone != '')";
		}
		
		if(!empty($data['filter_vistor']) && $data['filter_vistor']==1){
			$sql .= " AND e.customer_id !=0";
		}
		
		if(!empty($data['filter_vistor']) && $data['filter_vistor']==2){
			$sql .= " AND e.customer_id = 0";
		}
		
		if(isset($data['filter_notify']) && !is_null($data['filter_notify'])){
			$sql .= " AND e.notify_status = '".(int)$data['filter_notify']."'";
		}
		
		$sql .=" ORDER By e.date_added DESC";
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function getebcart($ebabandonedcart_id){
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart  WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
		
		return $query->row;
	}
	
	public function getlastvisit($ebabandonedcart_id){
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart_visit_history WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."' ORDER BY date_added DESC LIMIT 0,1");
		return $query->row;
	}
	
	public function getlastvisithistory($ebabandonedcart_id){
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart_visit_history WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."' ORDER BY date_added DESC");
		return $query->rows;
	}
	
	public function getvistorhistory(){
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart_visit_history ORDER BY date_added DESC LIMIT 0,10");
		return $query->rows;
	}
	
	public function getebcartproducts($ebabandonedcart_id){
		$ebcartinfo = $this->getebcart($ebabandonedcart_id);
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT eb.*,pd.name,p.model,p.price,p.image,p.tax_class_id FROM ".DB_PREFIX."ebabandonedcart_description eb LEFT JOIN ".DB_PREFIX."product p ON(eb.product_id = p.product_id) LEFT JOIN ".DB_PREFIX."product_description pd ON(p.product_id = pd.product_id) WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."' AND pd.language_id = '".(int)$this->config->get('config_language_id')."'");
		$products_data=array();
		
		foreach($query->rows as $row){
			$option_data = array();
			$option_price = 0;
			   foreach(json_decode($row['option']) as $product_option_id => $value){
					$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$row['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($option_query->num_rows) {
						if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
							$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
							
							if ($option_value_query->num_rows){
								$option_data[] = array(
									'name'                    => $option_query->row['name'],
									'value'                   => $option_value_query->row['name'],
								);
								
								if($option_value_query->row['price_prefix'] == '+') {
									$option_price += $option_value_query->row['price'];
								} elseif ($option_value_query->row['price_prefix'] == '-') {
									$option_price -= $option_value_query->row['price'];
								}
							}
						} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
							foreach ($value as $product_option_value_id) {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
									if ($option_value_query->num_rows) {
										if($option_value_query->row['price_prefix'] == '+') {
											$option_price += $option_value_query->row['price'];
										} elseif ($option_value_query->row['price_prefix'] == '-'){
											$option_price -= $option_value_query->row['price'];
										}
									
										$option_data[] = array(
										'name'                    => $option_query->row['name'],
										'value'                   => $option_value_query->row['name'],
									    );
								}
							}
						} elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
							$option_data[] = array(
								'name'                    => $option_query->row['name'],
								'value'                   => $value,
							);
						}
					}
				}
				
				if (is_file(DIR_IMAGE . $row['image'])) {
				  $image = $this->model_tool_image->resize($row['image'], 100, 100);
				} else {
				  $image = $this->model_tool_image->resize('no_image.png', 100, 100);
			    }
				
				$price = $row['price'];
				
				$product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$row['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");
				if ($product_special_query->num_rows) {
					$price = $product_special_query->row['price'];
				}
				
				$products_data[]=array(
				 'product_id'	=> $row['product_id'],
				 'quantity'		=> $row['quantity'],
				 'model'		=> $row['model'],
				 'image'      	=> $image,
				 'name'			=> $row['name'],
				 'price'		=> $this->currency->format($this->tax->calculate(($price + $option_price), $row['tax_class_id'], $this->config->get('config_tax')),$ebcartinfo['currency']),
				 'total'        => $this->currency->format($this->tax->calculate(($price + $option_price), $row['tax_class_id'], $this->config->get('config_tax')) * $row['quantity'],$ebcartinfo['currency']),
				 'total2'		=> $this->tax->calculate(($price + $option_price), $row['tax_class_id'], $this->config->get('config_tax')) * $row['quantity'],
				 'option_data'  => $option_data,
				 'href'			=> HTTP_CATALOG.'index.php?route=product/product&product_id='.$row['product_id'],
			  );
		}
		return $products_data;
	}
	
	
	public function getebcarttotalprice($ebabandonedcart_id){
		$ebcartinfo = $this->getebcart($ebabandonedcart_id);
		$this->load->model('tool/image');
		$query = $this->db->query("SELECT eb.*,pd.name,p.model,p.price,p.image,p.tax_class_id FROM ".DB_PREFIX."ebabandonedcart_description eb LEFT JOIN ".DB_PREFIX."product p ON(eb.product_id = p.product_id) LEFT JOIN ".DB_PREFIX."product_description pd ON(p.product_id = pd.product_id) WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."' AND pd.language_id = '".(int)$this->config->get('config_language_id')."'");
		$total = 0;
		foreach($query->rows as $row){
			$option_data = array();
			$option_price = 0;
			   foreach(json_decode($row['option']) as $product_option_id => $value){
					$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$row['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($option_query->num_rows) {
						if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
							$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");	
								
								if($option_value_query->row){
									if($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-') {
										$option_price -= $option_value_query->row['price'];
									}							
								}

						} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
							foreach ($value as $product_option_value_id) {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
								if($option_value_query->row){
									if($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-'){
										$option_price -= $option_value_query->row['price'];
									}
								}
							}
						}
					}
				}
				
				$price = $row['price'];
				
				$product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$row['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");
				if ($product_special_query->num_rows) {
					$price = $product_special_query->row['price'];
				}
				
				$total += $this->tax->calculate(($price + $option_price), $row['tax_class_id'], $this->config->get('config_tax')) * $row['quantity'];
		}
		
		return $this->currency->format($total,$ebcartinfo['currency']);
	}
}