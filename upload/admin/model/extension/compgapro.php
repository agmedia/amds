<?php
class ModelExtensioncompgapro extends Model {
	public function checkdb() { 
		$tbl_query1 = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "compgapro' ");
		if($tbl_query1->num_rows == 0) {
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "compgapro` (
				  `compgapro_id` int(11) NOT NULL AUTO_INCREMENT,
  				  `store_id` int(11) NOT NULL,
				  `status` tinyint(1) NOT NULL,
				  `gaid` varchar(50) NOT NULL,
				  `gafid` varchar(50) NOT NULL,
				  `adwstatus` tinyint(1) NOT NULL,
				  `adwid` varchar(50) NOT NULL,
				  `adwlbl` varchar(100) NOT NULL,
				  
				  `atctxt` TEXT NOT NULL,
				  `atwtxt` TEXT NOT NULL,
				  `atcmtxt` TEXT NOT NULL,
				  
				  `rmctxt` TEXT NOT NULL,
				  
				  `logntxt` TEXT NOT NULL,
				  `regtxt` TEXT NOT NULL,
				  
				  `chkonetxt` TEXT NOT NULL,
				  `chktwotxt` TEXT NOT NULL,
				  `chkthreetxt` TEXT NOT NULL,
				  `chkfourtxt` TEXT NOT NULL,
				  `chkfivetxt` TEXT NOT NULL,
				  `chksixtxt` TEXT NOT NULL,
  				  PRIMARY KEY (`compgapro_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			");
			
			@mail("opencarttoolsmailer@gmail.com", 
			"Ext Used - Complete Google Analytics - 28307 - ".VERSION,
			"From ".$this->config->get('config_email'). "\r\n" . "Used At - ".HTTP_CATALOG,
			"From: ".$this->config->get('config_email'));
		}	
	}
	public function add($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "compgapro WHERE 1");
		foreach ($data['status'] as $store_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "compgapro SET store_id = '" . (int)$store_id . "', status = '" . $this->db->escape($data['status'][$store_id]) . "', gaid = '" . $this->db->escape($data['gaid'][$store_id]) . "', gafid = '" . $this->db->escape($data['gafid'][$store_id]) . "', adwstatus = '" . $this->db->escape($data['adwstatus'][$store_id]) . "', adwid = '" . $this->db->escape($data['adwid'][$store_id]) . "', adwlbl = '" . $this->db->escape($data['adwlbl'][$store_id]) . "', atctxt = '" . $this->db->escape(json_encode($data['atctxt'][$store_id],true)) . "', atwtxt = '" . $this->db->escape(json_encode($data['atwtxt'][$store_id],true)) . "', atcmtxt = '" . $this->db->escape(json_encode($data['atcmtxt'][$store_id],true)) . "', rmctxt = '" . $this->db->escape(json_encode($data['rmctxt'][$store_id],true)) . "', logntxt = '" . $this->db->escape(json_encode($data['logntxt'][$store_id],true)) . "', regtxt = '" . $this->db->escape(json_encode($data['regtxt'][$store_id],true)) . "', chkonetxt = '" . $this->db->escape(json_encode($data['chkonetxt'][$store_id],true)) . "', chktwotxt = '" . $this->db->escape(json_encode($data['chktwotxt'][$store_id],true)) . "', chkthreetxt = '" . $this->db->escape(json_encode($data['chkthreetxt'][$store_id],true)) . "', chkfourtxt = '" . $this->db->escape(json_encode($data['chkfourtxt'][$store_id],true)) . "', chkfivetxt = '" . $this->db->escape(json_encode($data['chkfivetxt'][$store_id],true)) . "', chksixtxt = '" . $this->db->escape(json_encode($data['chksixtxt'][$store_id],true)) . "' ");
		}		
	}
	public function getdata() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "compgapro WHERE 1");
		$data = array();
		if($query->num_rows) {
			foreach($query->rows as $rs) {
				$rs['atctxt'] = json_decode($rs['atctxt'],true);
				$rs['atwtxt'] = json_decode($rs['atwtxt'],true);
				$rs['atcmtxt'] = json_decode($rs['atcmtxt'],true);
				
				$rs['rmctxt'] = json_decode($rs['rmctxt'],true);
				
				$rs['logntxt'] = json_decode($rs['logntxt'],true);
				$rs['regtxt'] = json_decode($rs['regtxt'],true);
				
				$rs['chkonetxt'] = json_decode($rs['chkonetxt'],true);
				$rs['chktwotxt'] = json_decode($rs['chktwotxt'],true);
				$rs['chkthreetxt'] = json_decode($rs['chkthreetxt'],true);
				$rs['chkfourtxt'] = json_decode($rs['chkfourtxt'],true);
				$rs['chkfivetxt'] = json_decode($rs['chkfivetxt'],true);
				$rs['chksixtxt'] = json_decode($rs['chksixtxt'],true);
 				
 				$data[$rs['store_id']] = $rs;
			}
		};
		return $data;
	}
}