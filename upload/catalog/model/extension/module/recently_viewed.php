<?php
class ModelExtensionModuleRecentlyViewed extends Model {

	public function getRecentlyViewedProducts($customer_id, $limit, $current_product_id){
		$query = $this->db->query("SELECT product_id FROM `".DB_PREFIX."customer_product_viewed` WHERE `customer_id`='".(int)$customer_id."' AND product_id<>'".(int)$current_product_id."' ORDER BY viewed_on DESC LIMIT ".$limit);
		return $query->rows;
	}
	
	public function setRecentlyViewedProducts($customer_id, $product_id, $viewed_on = '') {
		if($viewed_on == ''){
			$viewed_on = date("Y-m-d H:i:s");
		}
		$query = $this->db->query("SELECT product_id FROM `".DB_PREFIX."customer_product_viewed` WHERE `customer_id`='".(int)$customer_id."' AND product_id='".(int)$product_id."' LIMIT 1");
		if($query->num_rows){
			$this->db->query("UPDATE `".DB_PREFIX."customer_product_viewed` SET viewed_on='".date("Y-m-d H:i:s")."' WHERE `customer_id`='".(int)$customer_id."' AND product_id='".(int)$product_id."'");
		} else {
			$this->db->query("INSERT INTO `".DB_PREFIX."customer_product_viewed` SET `customer_id`='".(int)$customer_id."', product_id='".(int)$product_id."', viewed_on='".date("Y-m-d H:i:s")."'");
		}
		return true;
	}
	
	public function isEnabled(){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'module' AND `code` = 'recently_viewed'");
		return $query->num_rows? true : false;
	}
}