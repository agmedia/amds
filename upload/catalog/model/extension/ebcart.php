<?php
class ModelExtensionEbcart extends Model {
	
	public function makeorderhistory($order_id){
		$query = $this->db->query("SELECT * FROM `".DB_PREFIX."order` o LEFT JOIN ".DB_PREFIX."ebnotify_history e ON(o.email = e.email) WHERE o.order_id = '".(int)$order_id."' AND e.ebnotify_history_id > 0");
		 if($query->row){
			$this->db->query("INSERT INTO ".DB_PREFIX."eborder SET order_id = '".(int)$order_id."', add_date = NOW()");	
			
			$this->db->query("DELETE FROM ".DB_PREFIX."ebnotify_history WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."'");
			$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."'");
			$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart_description WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."'");
		 }else{
			  $query = $this->db->query("SELECT * FROM `".DB_PREFIX."order` o LEFT JOIN ".DB_PREFIX."ebabandonedcart e ON(o.email = e.email) WHERE o.order_id = '".(int)$order_id."'");
			  if($query->row){
				$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."'");
				$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart_description WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."'");
			  }
		}
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
	
	public function getebcart($ebabandonedcart_id){
		$query = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart  WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
		
		return $query->row;
	}
	
	public function getebcarts($data=array()){
		$sql = "SELECT *,(select name from ".DB_PREFIX."store WHERE store_id = e.store_id) as name FROM ".DB_PREFIX."ebabandonedcart e WHERE e.store_id = ".(int)$data['filter_store_id']."";
		
		$sql .= " AND e.date_added  <= date_sub(NOW(), interval 1 hour)";
		
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
	
	public function getebcartproducts($ebabandonedcart_id){
		$ebcartinfo = $this->getebcart($ebabandonedcart_id);
		$this->load->model('tool/image');
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
							
							if ($option_value_query->num_rows) {
								if($option_value_query->row['price_prefix'] == '+') {
									$option_price += $option_value_query->row['price'];
								} elseif ($option_value_query->row['price_prefix'] == '-') {
									$option_price -= $option_value_query->row['price'];
								}

								$option_data[] = array(
									'name'                    => $option_query->row['name'],
									'value'                   => $option_value_query->row['name'],
								);
							}
						} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
							foreach ($value as $product_option_value_id) {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
								
								if ($option_value_query->num_rows) {
									if($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-') {
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
				  $image = $this->model_tool_image->resize($row['image'], 40, 40);
				} else {
				  $image = $this->model_tool_image->resize('no_image.png', 40, 40);
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
				 'href'			=> $this->url->link('product/product','&product_id='.$row['product_id'])
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
									} elseif ($option_value_query->row['price_prefix'] == '-') {
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