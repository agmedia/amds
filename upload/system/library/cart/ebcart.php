<?php
namespace Cart;
Class Ebcart{
	private $data = array();
	
	public function __construct($registry){
		$this->config = $registry->get('config');
		$this->customer = $registry->get('customer');
		$this->session = $registry->get('session');
		$this->db = $registry->get('db');
		$this->request = $registry->get('request');
		
		//echo $this->session->data['currency']; die();
		
		if ($this->customer->getId()) {
			// We want to change the session ID on all the old items in the customers cart
			$this->db->query("UPDATE " . DB_PREFIX . "ebabandonedcart SET session_id = '" . $this->db->escape($this->session->getId()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");
			
			$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
			
			foreach($cart_query->rows as $cart){
				$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart WHERE ebabandonedcart_id = '" . (int)$cart['ebabandonedcart_id'] . "'");
				$carts_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart_description WHERE ebabandonedcart_id = '" . (int)$cart['ebabandonedcart_id'] . "'");
				foreach ($carts_query->rows as $ecart){
				 $this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_description WHERE ebabandonedcart_description_id = '" . (int)$ecart['ebabandonedcart_description_id'] . "'");
				 $this->addcart($ecart['product_id'], $ecart['quantity'], json_decode($ecart['option']), $ecart['recurring_id']);
				}
			}
			
		}else{
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
			if($query->row){
				$this->addinfo($query->row['ebabandonedcart_id']);
			}else{
				$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
				foreach ($cart_query->rows as $cart) {
					// The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
					$this->addcart($cart['product_id'], $cart['quantity'], json_decode($cart['option']), $cart['recurring_id']);
				}
			}
		}
	}
	
	
	public function addinfo($ebabandonedcart_id){
		$order_info=array();
		if(isset($this->session->data['order_id'])){
		 $order_info = $this->db->query("SELECT * FROM `".DB_PREFIX."order` WHERE order_id = '".(int)$this->session->data['order_id']."'")->row;
		}
		
		if(isset($this->session->data['guest']['firstname'])){
			$firstname = $this->session->data['guest']['firstname'];
		}elseif(isset($order_info['firstname'])){
			$firstname = $order_info['firstname'];
		}else{
			$firstname = $this->customer->getFirstName();
		}

		
		if(isset($this->session->data['guest']['lastname'])){
			$lastname = $this->session->data['guest']['lastname'];
		}elseif(isset($order_info['lastname'])){
			$lastname = $order_info['lastname'];
		}else{
			$lastname = $this->customer->getLastName();
		}

		if(isset($this->session->data['guest']['email'])){
			$email = $this->session->data['guest']['email'];

		}elseif(isset($order_info['email'])){
			$email = $order_info['email'];
		}else{
			$email = $this->customer->getEmail();
		}
		
		if(isset($this->session->data['guest']['telephone'])){
			$telephone = $this->session->data['guest']['telephone'];
		}elseif(isset($order_info['telephone'])){
			$telephone = $order_info['telephone'];
		}else{
			$telephone = $this->customer->getTelephone();
		}
		
		$this->db->query("UPDATE ".DB_PREFIX."ebabandonedcart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', store_id = '".$this->config->get('config_store_id')."', language_id = '".$this->config->get('config_language_id')."', firstname = '".$this->db->escape($firstname)."', lastname = '".$this->db->escape($lastname)."',email = '".$this->db->escape($email)."', telephone = '".$this->db->escape($telephone)."', currency  = '".$this->session->data['currency']."', ip = '".$this->db->escape($this->request->server['REMOTE_ADDR'])."' WHERE ebabandonedcart_id = '".(int)$ebabandonedcart_id."'");
	}
	
	public function addcart($product_id, $quantity = 1, $option = array(), $recurring_id = 0){
		if(isset($this->session->data['guest']['firstname'])){
			$firstname = $this->session->data['guest']['firstname'];
		}else{
			$firstname = $this->customer->getFirstName();
		}
		
		if(isset($this->session->data['guest']['lastname'])){
			$lastname = $this->session->data['guest']['lastname'];
		}else{
			$lastname = $this->customer->getLastName();
		}
		
		if(isset($this->session->data['guest']['email'])){
			$email = $this->session->data['guest']['email'];
		}else{
			$email = $this->customer->getEmail();
		}
		
		if(isset($this->session->data['guest']['telephone'])){
			$telephone = $this->session->data['guest']['telephone'];
		}else{
			$telephone = $this->customer->getTelephone();
		}
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
		
		if(!$query->row){
			$this->db->query("INSERT INTO ".DB_PREFIX."ebabandonedcart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', store_id = '".$this->config->get('config_store_id')."', language_id = '".$this->config->get('config_language_id')."', firstname = '".$this->db->escape($firstname)."', lastname = '".$this->db->escape($lastname)."',email = '".$this->db->escape($email)."', telephone = '".$this->db->escape($telephone)."', currency  = '".$this->session->data['currency']."', ip = '".$this->db->escape($this->request->server['REMOTE_ADDR'])."', date_added = NOW()");
			
			$ebabandonedcart_id = $this->db->getLastId();
			$this->session->data['ebabandonedcart_id'] = $ebabandonedcart_id;
		}else{
			$ebabandonedcart_id = $query->row['ebabandonedcart_id'];
			$this->session->data['ebabandonedcart_id'] = $query->row['ebabandonedcart_id'];
		}
		
		
		$query3 = $this->db->query("SELECT cart_id FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
		
		if(!empty($query3->row['cart_id'])){
			$cart_id = $query3->row['cart_id'];
		}else{
			$cart_id = 0;
		}
		
		$query2 = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "ebabandonedcart_description WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND ebabandonedcart_id = '".(int)$ebabandonedcart_id."' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
		if(!$query2->row['total']){
			$this->db->query("INSERT INTO ".DB_PREFIX."ebabandonedcart_description SET ebabandonedcart_id = '".(int)$ebabandonedcart_id."', api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', date_added = NOW(), cart_id = '".(int)$cart_id."'");
		}else{
			$this->db->query("UPDATE " . DB_PREFIX . "ebabandonedcart_description SET quantity = (quantity + " . (int)$quantity . ")  WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND ebabandonedcart_id = '" . (int)$ebabandonedcart_id . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
		}
	}
	
	public function removeCart($cart_id){
		$query = $this->db->query("SELECT ebabandonedcart_id FROM " . DB_PREFIX . "ebabandonedcart_description WHERE cart_id = '" . (int)$cart_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "ebabandonedcart_description WHERE cart_id = '" . (int)$cart_id . "'");
		if(!empty($query->row['ebabandonedcart_id'])){
			$query2 = $this->db->query("SELECT ebabandonedcart_id FROM " . DB_PREFIX . "ebabandonedcart_description WHERE ebabandonedcart_id = '" . (int)$query->row['ebabandonedcart_id'] . "'");
			if(empty($query2->row['ebabandonedcart_id'])){
				$this->db->query("DELETE FROM ".DB_PREFIX."ebabandonedcart WHERE ebabandonedcart_id = '" . (int)$query->row['ebabandonedcart_id'] . "'");
				if(isset($this->session->data['ebabandonedcart_id'])){
				  unset($this->session->data['ebabandonedcart_id']);
				}
			}
		}
	}
	
	public function updatecart($cart_id, $quantity) {
		$this->db->query("UPDATE " . DB_PREFIX . "ebabandonedcart_description SET quantity = '" . (int)$quantity . "' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "'");
	}
	
	public function vistpagehistory(){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "ebabandonedcart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
		if(!empty($query->row['ebabandonedcart_id'])){
			if($this->request->server['HTTPS']){
			  $actual_link = 'https://'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			} else {
			  $actual_link = 'http://'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			}
			
			$query1 = $this->db->query("SELECT * FROM ".DB_PREFIX."ebabandonedcart_visit_history WHERE ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."' AND link LIKE '%".$this->db->escape($actual_link)."%'");
			if($query1->row){
				$this->db->query("UPDATE ".DB_PREFIX."ebabandonedcart_visit_history SET total_count = (total_count + 1), date_added = NOW(), time_added = NOW() WHERE ebabandonedcart_visit_history_id = '".(int)$query1->row['ebabandonedcart_visit_history_id']."'");
			}else{
				$this->db->query("INSERT INTO ".DB_PREFIX."ebabandonedcart_visit_history SET ebabandonedcart_id = '".(int)$query->row['ebabandonedcart_id']."', link = '".$this->db->escape($actual_link)."', total_count = 1, date_added = NOW(), time_added = NOW()");
			}
		}
	}
}
?>