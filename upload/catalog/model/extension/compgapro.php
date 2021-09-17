<?php 
class ModelExtensioncompgapro extends Controller { 
   	private $langid = 0;
	private $storeid = 0;
	private $storename = '';
	private $custgrpid = 0;
	public function __construct($registry) {
		parent::__construct($registry);
		$this->langid = (int)$this->config->get('config_language_id');
		$this->storeid = (int)$this->config->get('config_store_id');
		$this->storename = $this->config->get('config_meta_title');
		$this->custgrpid = (int)$this->config->get('config_customer_group_id');
		ini_set("serialize_precision", -1);
 	}
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
	public function getdata() {
		$this->checkdb();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "compgapro WHERE 1 and store_id = ".(int)$this->storeid);
		return $query->row;
	}
	public function getcurval($taxprc) {
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$taxprc = $this->currency->format($taxprc, $this->session->data['currency'], false, false);
		} else {
			$taxprc = $this->currency->format($taxprc, '', false, false);
		}	
		return round($taxprc,2);
	}
	public function getcatname($product_id) {
		$query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description cd INNER JOIN " . DB_PREFIX . "product_to_category pc ON pc.category_id = cd.category_id WHERE 1 AND pc.product_id = '".$product_id."' AND cd.language_id = '".$this->langid."' limit 1");
		return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
	}
	public function getbrandname($product_id) {
		$query = $this->db->query("SELECT name from " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "product p on m.manufacturer_id = p.manufacturer_id WHERE 1 AND p.product_id = ".$product_id);
		return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
	}
	public function getorderproduct($order_id) {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' ");
 		return $query->rows;
	}
	public function getordertax($order_id) {
 		$tax_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'tax'");
		if (isset($tax_query->row['value']) && $tax_query->row['value']) {
			return round($tax_query->row['value'],2);
		} 
		return 0;
	}	
	public function getordershipping($order_id) {
 		$tax_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'shipping'");
		if (isset($tax_query->row['value']) && $tax_query->row['value']) {
			return round($tax_query->row['value'],2);
		} 
		return 0;
	}
	public function getlangdata($rsdata) {
		$eventdata = array();
		
		$atctxt = json_decode($rsdata['atctxt'],true);
		$eventdata['atctxt'] = $atctxt[$this->langid];
		$atwtxt = json_decode($rsdata['atwtxt'],true);
		$eventdata['atwtxt'] = $atwtxt[$this->langid];
		$atcmtxt = json_decode($rsdata['atcmtxt'],true);
		$eventdata['atcmtxt'] = $atcmtxt[$this->langid];
		
		$rmctxt = json_decode($rsdata['rmctxt'],true);
		$eventdata['rmctxt'] = $rmctxt[$this->langid];
		
		$logntxt = json_decode($rsdata['logntxt'],true);
		$eventdata['logntxt'] = $logntxt[$this->langid];
		$regtxt = json_decode($rsdata['regtxt'],true);
		$eventdata['regtxt'] = $regtxt[$this->langid];
		
		$chkonetxt = json_decode($rsdata['chkonetxt'],true);
		$eventdata['chkonetxt'] = $chkonetxt[$this->langid];
		$chktwotxt = json_decode($rsdata['chktwotxt'],true);
		$eventdata['chktwotxt'] = $chktwotxt[$this->langid];
		$chkthreetxt = json_decode($rsdata['chkthreetxt'],true);
		$eventdata['chkthreetxt'] = $chkthreetxt[$this->langid];
		$chkfourtxt = json_decode($rsdata['chkfourtxt'],true);
		$eventdata['chkfourtxt'] = $chkfourtxt[$this->langid];
		$chkfivetxt = json_decode($rsdata['chkfivetxt'],true);
		$eventdata['chkfivetxt'] = $chkfivetxt[$this->langid];
		$chksixtxt = json_decode($rsdata['chksixtxt'],true);
		$eventdata['chksixtxt'] = $chksixtxt[$this->langid];
		
		return $eventdata;
	}
	public function getevent($product_id) {
		$rsdata = $this->getdata();
		$json['eventdata'] = array();
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $product_id) {
 			$json['langdata'] = $this->getlangdata($rsdata);
 			
			$this->load->model('catalog/product');
			
			$items = array();
  			$pinfo = $this->model_catalog_product->getProduct($product_id);
 			
			if ($pinfo) { 
 				$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['minimum'],
 					"item_category" => $this->getcatname($pinfo['product_id']),
					"item_brand" => $this->getbrandname($pinfo['product_id']),
					"category" => $this->getcatname($pinfo['product_id']),
					"brand" => $this->getbrandname($pinfo['product_id']),
				);				
				if($pinfo['special']) { 
					$specialtx = $this->tax->calculate($pinfo['special'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
					$items['discount'] = $this->getcurval($pricetx - $specialtx);
					$json['eventdata']['value'] = $this->getcurval($specialtx);
				}
				
				$json['eventdata']['currency'] = $this->session->data['currency'];
				$json['eventdata']['event_category'] = 'ecommerce';
				$json['eventdata']['event_label'] = $pinfo['name'];
				$json['eventdata']['items'] = $items;
			} 
 		}
		return $json;
	}
	public function getchkfunnel($stepnum) {
		$rsdata = $this->getdata();
		
		$json['checkout_progress'] = array();
		$json['checkout_option'] = array();
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) { 
				$counter += 1;
				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['minimum'],
					"item_category" => $this->getcatname($pinfo['product_id']),
					"item_brand" => $this->getbrandname($pinfo['product_id']),
					"category" => $this->getcatname($pinfo['product_id']),
					"brand" => $this->getbrandname($pinfo['product_id']),
					"index" => $counter,
				);
				if (isset($this->session->data['coupon'])) {
					$items['coupon'] = $this->session->data['coupon'];
				}
				$items_data[] = $items;
			}
 			$checkout_progress = array(
				"currency" => $this->session->data['currency'],
				"value" => $this->cart->getTotal(),
				"items" => $items_data,
 			); 
			if (isset($this->session->data['coupon'])) {
				$checkout_progress['coupon'] = $this->session->data['coupon'];
			}
			
			$stepname = '';
			if($stepnum == 1) { $stepname = $langdata['chkonetxt']; }
			if($stepnum == 2) { $stepname = $langdata['chktwotxt']; }
			if($stepnum == 3) { $stepname = $langdata['chkthreetxt']; }
			if($stepnum == 4) { $stepname = $langdata['chkfourtxt']; }
			if($stepnum == 5) { $stepname = $langdata['chkfivetxt']; }
			if($stepnum == 6) { $stepname = $langdata['chksixtxt']; }
			
			$checkout_option = array(
				"checkout_step" => $stepnum,
				"checkout_option" => $stepname,
				"value" => $this->cart->getTotal(),
			);
			
			$checkout_progress['event_category'] = 'ecommerce';
			$checkout_progress['event_label'] = $stepname;
			
			$checkout_option['event_category'] = 'ecommerce';
			$checkout_option['event_label'] = $stepname;
 			
			$json['checkout_progress'] = $checkout_progress;
			$json['checkout_option'] = $checkout_option;
 		} 
		return $json;
	}
	public function getshipinfo() {
		$rsdata = $this->getdata();
		$json['add_shipping_info'] = array();
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) { 
				$counter += 1;
				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['minimum'],
					"item_category" => $this->getcatname($pinfo['product_id']),
					"item_brand" => $this->getbrandname($pinfo['product_id']),
					"category" => $this->getcatname($pinfo['product_id']),
					"brand" => $this->getbrandname($pinfo['product_id']),
					"index" => $counter,
				);
				if (isset($this->session->data['coupon'])) {
					$items['coupon'] = $this->session->data['coupon'];
				}
				$items_data[] = $items;
			}
			
			$value = $this->cart->getTotal();
			$shipping_tier = '';
			if(isset($this->session->data['shipping_method'])) {
				$value = $this->session->data['shipping_method']['cost'];
				$shipping_tier = $this->session->data['shipping_method']['title'];
			}
			
 			$add_shipping_info = array(
				"currency" => $this->session->data['currency'],
				"value" => $value,
				"shipping_tier" => $shipping_tier,
				"items" => $items_data,
 			); 
			if (isset($this->session->data['coupon'])) {
				$add_shipping_info['coupon'] = $this->session->data['coupon'];
			}
			
			$add_shipping_info['event_category'] = 'ecommerce';
			$add_shipping_info['event_label'] = $shipping_tier;
			
			$json['add_shipping_info'] = $add_shipping_info;
 		} 
		return $json;	
	} 
	public function getpayinfo() {
		$rsdata = $this->getdata();
		$json['add_payment_info'] = array();
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) { 
				$counter += 1;
				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['minimum'],
					"item_category" => $this->getcatname($pinfo['product_id']),
					"item_brand" => $this->getbrandname($pinfo['product_id']),
					"category" => $this->getcatname($pinfo['product_id']),
					"brand" => $this->getbrandname($pinfo['product_id']),
					"index" => $counter,
				);
				if (isset($this->session->data['coupon'])) {
					$items['coupon'] = $this->session->data['coupon'];
				}
				$items_data[] = $items;
			}
			
			$value = $this->cart->getTotal();
			$payment_type = '';
			if(isset($this->session->data['payment_method'])) {
				$payment_type = $this->session->data['payment_method']['title'];
			}
			
 			$add_payment_info = array(
				"currency" => $this->session->data['currency'],
				"value" => $value,
				"payment_type" => $payment_type,
				"items" => $items_data,
 			); 
			if (isset($this->session->data['coupon'])) {
				$add_payment_info['coupon'] = $this->session->data['coupon'];
			}
			
			$add_payment_info['event_category'] = 'ecommerce';
			$add_payment_info['event_label'] = $payment_type;
			
			$json['add_payment_info'] = $add_payment_info;
 		} 
		return $json;
	} 
	public function gettrackcode() {
		$rsdata = $this->getdata();
		if($rsdata) {
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$gaid = $rsdata['gaid'] ? $rsdata['gaid'] : false;
			$gafid = $rsdata['gafid'] ? $rsdata['gafid'] : false;
			$adwstatus = $rsdata['adwstatus'] ? $rsdata['adwstatus'] : false;
			$adwid = $rsdata['adwid'] ? $rsdata['adwid'] : false;
			$adwlbl = $rsdata['adwlbl'] ? $rsdata['adwlbl'] : false;
			$gacode = '';
			$adwcode = '';
if($rsdata['status'] == 1 && $rsdata['gaid']) { 
$gacode = <<<EOF
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=$gaid"></script>
<script>
var gafid = '$gafid';
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '$gaid');
if(gafid) {
gtag('config', '$gafid');
}
</script>
EOF;
}

if($rsdata['adwstatus'] && $rsdata['adwid']) { 
$adwcode = <<<EOF
<!-- Global site tag (gtag.js) - Google AdWords -->
<script async src="https://www.googletagmanager.com/gtag/js?id=$adwid"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '$adwid');
</script>
EOF;
}

return $gacode . $adwcode;
		}
	}   
}