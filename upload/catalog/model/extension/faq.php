<?php
class ModelExtensionFaq extends Model {
	
	public function getfaq($faq_id){
	  $query = $this->db->query("SELECT f.*,fd.*,(SELECT fcategory_id FROM ".DB_PREFIX."faq_2_category frc WHERE f.faq_id = frc.faq_id LIMIT 0,1) as fcategory_id FROM ".DB_PREFIX."faq f LEFT JOIN ".DB_PREFIX."faq_description fd ON(f.faq_id = fd.faq_id) WHERE fd.language_id = '".(int)$this->config->get('config_language_id')."' AND f.status = 1 AND f.faq_id = '".(int)$faq_id."'");
	  return $query->row;
	}
	
	public function getfCategory($category_id){
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "fcategory c LEFT JOIN " . DB_PREFIX . "fcategory_description cd ON (c.fcategory_id = cd.fcategory_id) LEFT JOIN " . DB_PREFIX . "fcategory_to_store c2s ON (c.fcategory_id = c2s.fcategory_id) WHERE c.fcategory_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}
	
	public function getfcategoies(){
	  $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory c LEFT JOIN " . DB_PREFIX . "fcategory_description cd ON (c.fcategory_id = cd.fcategory_id) LEFT JOIN " . DB_PREFIX . "fcategory_to_store c2s ON (c.fcategory_id = c2s.fcategory_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");
	  
	  return $query->rows;
	}

	public function getfaqs($data){
		$sql = "SELECT f.*,fd.*,(SELECT fcategory_id FROM ".DB_PREFIX."faq_2_category frc WHERE f.faq_id = frc.faq_id LIMIT 0,1) as fcategory_id FROM ".DB_PREFIX."faq f LEFT JOIN ".DB_PREFIX."faq_description fd ON(f.faq_id = fd.faq_id) ";
		
		if(!empty($data['filter_fcategory_id'])){
			$sql .=" LEFT JOIN ".DB_PREFIX."faq_2_category f2c ON(f.faq_id = f2c.faq_id)";
		}
		
		$sql .=" WHERE fd.language_id = '".(int)$this->config->get('config_language_id')."' AND f.status = 1";
		
		if(!empty($data['filter_fcategory_id'])){
		  $sql .=" AND f2c.fcategory_id = '".(int)$data['filter_fcategory_id']."'";
		}
		
		if(!empty($data['filter_name'])){
		   $sql .=" AND (fd.name LIKE '%".$this->db->escape($data['filter_name'])."%' OR fd.description LIKE '%".$this->db->escape($data['filter_name'])."%')";
		}
		
		$sql .= " GROUP BY f.faq_id ORDER BY f.sort_order ASC";
		
		return $this->db->query($sql)->rows;
	}
// 06 august 2018 //
	public function getFaqcategories($data) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory f LEFT JOIN " . DB_PREFIX . "fcategory_description fd ON (f.fcategory_id = fd.fcategory_id) WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND f.fcategory_id<>0");

		return $query->rows;
	}

	public function getfcateg($fcategory_id){
	  $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory c LEFT JOIN " . DB_PREFIX . "fcategory_description cd ON (c.fcategory_id = cd.fcategory_id) LEFT JOIN " . DB_PREFIX . "fcategory_to_store c2s ON (c.fcategory_id = c2s.fcategory_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' AND c.fcategory_id='".$fcategory_id."' ORDER BY c.sort_order, LCASE(cd.name)");
	  
	  return $query->rows;
	}

///new code sep 2019 /// 
	public function getTmdFaqCategory($product_id){
	  $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fcategory_to_product WHERE product_id = '" . (int)$product_id . "'");
	  
	  return $query->rows;
	}

	public function getTmdfcategoies($fcategory_id){

	   $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq c LEFT JOIN " . DB_PREFIX . "faq_description cd ON (c.faq_id = cd.faq_id) LEFT JOIN " . DB_PREFIX . "faq_2_category c2s ON (c.faq_id = c2s.faq_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1' AND c2s.fcategory_id='".$fcategory_id."' ORDER BY c.sort_order, LCASE(cd.name)");
	  return $query->rows;
	}
///new code sep 2019 /// 
// 06 august 2018 //
}