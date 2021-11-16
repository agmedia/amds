<?php
class ModelExtensionEbemailTemplate extends Model {
	
	public function getEmailCoupon($abandonedcart_email_template_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_coupon WHERE abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "'");

		return $query->row;
	}
	
	public function getEmailTemplateDatabylanguage($abandonedcart_email_template_id,$language_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "ebabandonedcart_email_template et LEFT JOIN " . DB_PREFIX . "ebabandonedcart_email_template_description etd ON (et.abandonedcart_email_template_id = etd.abandonedcart_email_template_id) WHERE et.abandonedcart_email_template_id = '" . (int)$abandonedcart_email_template_id . "' AND etd.language_id = '". (int)$language_id ."'");

		return $query->row;
	}
}