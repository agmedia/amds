<?php 
class ModelExtensionfbecommevnt extends Controller { 
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
		$tbl_query1 = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "fbecommevnt' ");
		if($tbl_query1->num_rows == 0) {
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "fbecommevnt` (
				  `fbecommevnt_id` int(11) NOT NULL AUTO_INCREMENT,
  				  `store_id` int(11) NOT NULL,
				  `status` tinyint(1) NOT NULL,
				  `fbpixelid` varchar(100) NOT NULL,
				  `fbcatid` varchar(100) NOT NULL,
 				  
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
  				  PRIMARY KEY (`fbecommevnt_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			");
			
			@mail("opencarttoolsmailer@gmail.com", 
			"Ext Used - Facebook Pixel Conversions + Event Tracking - 34723 - ".VERSION,
			"From ".$this->config->get('config_email'). "\r\n" . "Used At - ".HTTP_CATALOG,
			"From: ".$this->config->get('config_email'));
		}	
	}
	public function getdata() {
		$this->checkdb();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fbecommevnt WHERE 1 and store_id = ".(int)$this->storeid);
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
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $product_id) {
 			$json['langdata'] = $this->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
 			
			$this->load->model('catalog/product');
			
   			$pinfo = $this->model_catalog_product->getProduct($product_id);
			
			if ($pinfo) { 
 				$price = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
				$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"content_ids" => array($pinfo['model'] ? $pinfo['model'] : $pinfo['product_id']),
					"content_type" => 'product',
					"content_name" => htmlspecialchars_decode(strip_tags($pinfo['name'])),
					"content_category" => $this->getcatname($pinfo['product_id']),
					"value" => $this->getcurval($pricetx),
					"currency" => $this->session->data['currency'],					
				);				
				if($fbcatid) { 
					$items['product_catalog_id'] = $fbcatid;
				}
				
				$json['eventdata']['items'] = $items;
			} 
		}
		return $json;
	}
	public function getchkfunnel($stepnum) {
		$rsdata = $this->getdata();
		$json['checkout_progress'] = array();
		$json['stepname'] = '';
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
    			
 			$items_data = array();
 			foreach ($this->cart->getProducts() as $pinfo) { 
 				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"quantity" => $pinfo['quantity'],
					"item_price" => $this->getcurval($pricetx),
				);
				if($fbcatid) { 
					$items['product_catalog_id'] = $fbcatid;
				}
				$items_data[] = $items;
			}
 			$checkout_progress = array(
				"currency" => $this->session->data['currency'],
				"value" => $this->cart->getTotal(),
				"content_type" => 'product', 
				"contents" => $items_data
 			); 
 			
			$stepname = '';
			if($stepnum == 1) { $stepname = $langdata['chkonetxt']; }
			if($stepnum == 2) { $stepname = $langdata['chktwotxt']; }
			if($stepnum == 3) { $stepname = $langdata['chkthreetxt']; }
			if($stepnum == 4) { $stepname = $langdata['chkfourtxt']; }
			if($stepnum == 5) { $stepname = $langdata['chkfivetxt']; }
			if($stepnum == 6) { $stepname = $langdata['chksixtxt']; }
			
 			$json['checkout_progress'] = $checkout_progress;
			$json['stepname'] = $stepname;
 		} 
		return $json;
	}
	public function getshipinfo() {
		$rsdata = $this->getdata();
		$json['add_shipping_info'] = array();
		$json['shipping_tier'] = '';
		$json['shipping_val'] = '';
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) { 
 				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"quantity" => $pinfo['quantity'],
					"item_price" => $this->getcurval($pricetx),
				);
				if($fbcatid) { 
					$items['product_catalog_id'] = $fbcatid;
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
				"value" => $value,
				"currency" => $this->session->data['currency'],
				"content_type" => 'product', 
				"contents" => $items_data
 			); 
			
 			$json['add_shipping_info'] = $add_shipping_info;
			$json['shipping_tier'] = $shipping_tier;
			$json['shipping_val'] = $value;
 		} 
		return $json;	
	} 
	public function getpayinfo() {
		$rsdata = $this->getdata();
		$json['add_payment_info'] = array();
		$json['payment_type'] = '';
		
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $this->cart->hasProducts()) {
			$langdata = $this->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) { 
 				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"quantity" => $pinfo['quantity'],
					"item_price" => $this->getcurval($pricetx),
				);
				if($fbcatid) { 
					$items['product_catalog_id'] = $fbcatid;
				}
				$items_data[] = $items;
			}
			
			$value = $this->cart->getTotal();
			$payment_type = '';
			if(isset($this->session->data['payment_method'])) {
				$payment_type = $this->session->data['payment_method']['title'];
			}
			
 			$add_payment_info = array(
				"value" => $value,
				"currency" => $this->session->data['currency'],
				"content_type" => 'product', 
				"contents" => $items_data
  			); 
			
 			$json['add_payment_info'] = $add_payment_info;
			$json['payment_type'] = $payment_type;
 		} 
		return $json;
	} 
	public function gettrackcode() {
		$rsdata = $this->getdata();
		if($rsdata) {
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;
			$code = '';
if($status && $fbpixelid) { 
$code = <<<EOF
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '$fbpixelid');
fbq('track', 'PageView');
fbq('track', 'FindLocation');
$('head').after('<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=$fbpixelid&ev=PageView&noscript=1"/></noscript>');
</script>
EOF;
}
		return $code;
        }
	}   
}