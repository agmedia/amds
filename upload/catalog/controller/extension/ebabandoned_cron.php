<?php
class ControllerExtensionEbabandonedCron extends Controller {
	public function index() {
		//echo date('-1 Hour', strtotime(date('Y-m-d H:i:s a'))); die();
		$this->load->model('setting/setting');
		$this->load->model('extension/ebcart');
		$this->load->model('extension/ebemail_template');
		
		if(isset($this->request->get['store_id'])){
			$store_id = $this->request->get['store_id'];
		}else{
			$store_id = $this->config->get('config_store_id');
		}
		
		if(isset($this->request->get['template_id'])){
			$template_id = $this->request->get['template_id'];
		}else{
			$template_id = 0;
		}
		
		if(isset($this->request->get['vistor_type'])){
			$vistor_type = $this->request->get['vistor_type'];
		}else{
			$vistor_type = 'all';
		}
		
		if(isset($this->request->get['condition'])){
			$condition = $this->request->get['condition'];
		}else{
			$condition = 'unnotified';
		}
		
		$store_info = $this->model_setting_setting->getSetting('config', $store_id);
		if($store_info){
			$store_email = isset($store_info['config_email']) ? $store_info['config_email'] : $this->config->get('config_email');
			
				switch($vistor_type){
				 case 'registered':
				 $vistor = 1; 
				 break;
				 case 'guest':
				 $vistor = 2; 
				 break;
				 case 'all':
				 $vistor = 0; 
				 break;
				 default:
				 $vistor = 0;
				}
				
				$notify=null;
				switch($condition){
				 case 'unnotified':
				 $notify = 0; 
				 break;
				 case 'notified':
				 $notify = 1; 
				 break;
				}
				
				$filter_data=array(
				  'filter_store_id'		=> $store_id,
				  'filter_vistor'		=> $vistor,
				  'filter_notify'		=> $notify,
				);
				 
				$ebcarts = $this->model_extension_ebcart->getebcarts($filter_data);
				$customers=array();
				 foreach($ebcarts as $ebcart):
				    if((int)$ebcart['ebabandonedcart_id']):
					$ebinfo = $this->model_extension_ebcart->getebcart($ebcart['ebabandonedcart_id']);	
					if(!empty($ebinfo['email'])){
					  $customers[]=array(
					    'ebabandonedcart_id' => $ebcart['ebabandonedcart_id'],
						'firstname'			 => $ebinfo['firstname'],
						'lastname'		=> $ebinfo['lastname'],
						'email'			=> $ebinfo['email'],
						'telephone'		=> $ebinfo['telephone'],
						'language_id'	=> $ebinfo['language_id'],
						'currency'		=> $ebinfo['currency'],
						'products'		=> $this->model_extension_ebcart->getebcartproducts($ebcart['ebabandonedcart_id']),
					  );
					}
					endif;
				 endforeach;
				 
				 
				
				$coupon_settings = $this->model_extension_ebemail_template->getEmailCoupon($template_id);
				foreach($customers as $email):
				$coupon_data=array();
				if($coupon_settings['coupon_status']):
					if($coupon_settings['coupon_contion']){
						foreach($email['products'] as $cprod):
							$cartproducts[]=$cprod['product_id'];
						endforeach;
					}else{
						$cartproducts=array();
					}
					
					$coupon_data=array(
						'coupon_name' 			=> $coupon_settings['coupon_name'],
						'coupon_type' 			=> $coupon_settings['coupon_type'], 	
						'coupon_discount' 		=> $coupon_settings['coupon_discount'],
						'coupon_total' 			=> $coupon_settings['coupon_total'],
						'coupon_product' 		=> $cartproducts,
						'coupon_category' 		=> (!empty($coupon_settings['coupon_category']) ? $coupon_settings['coupon_category'] : array()),
						'coupon_vaild' 			=> $coupon_settings['coupon_vaild'],
						'coupon_uses_total' 	=> $coupon_settings['coupon_uses_total'],
						'coupon_uses_customer'  => $coupon_settings['coupon_uses_customer'],
					);
				endif;
				
					$coupon_info=array();
					if($coupon_data):
						$coupon_id = $this->SetCoupon($coupon_data,$email);
						$coupon_info = $this->getCoupon($coupon_id);
						if($coupon_info):
							$this->db->query("INSERT INTO " . DB_PREFIX . "ebcart_coupon SET email = '". $this->db->escape($email['email']) ."', coupon_id = '". (int)$coupon_info['coupon_id'] ."'");
						endif;
					endif;
					
					$templateinfo = $this->model_extension_ebemail_template->getEmailTemplateDatabylanguage($this->request->get['template_id'],$email['language_id']);
					
					$templatesubject = (isset($templateinfo['subject']) ? $templateinfo['subject'] : '');
					
					$templatedata = (isset($templateinfo['description']) ? $templateinfo['description'] : '');
					
					
					$find = array(
						'{logo}',
						'{Store_name}',
						'{Store_address}',					
						'{Store_email}',					
						'{Store_telephone}',
						'{store_url}',					
						'{firstname}',				
						'{lastname}',				
						'{email}',					
						'{telephone}',							
						'{cart_products}',
						'{coupon}',			
						'{discount}',
						'{currency}',						
						'{total_amount}',						
						'{date_end}',						
					);
					
					$replace = array(
						'logo'					=> '<img src="' . HTTP_SERVER . 'image/'. $store_info['config_logo'] .'" title="'. $store_info['config_name'] .'" alt="'. $store_info['config_name'] .'" />',
						'store'					=> $store_info['config_name'],
						'store_adddress'		=> $store_info['config_address'],
						'store_email'			=> $store_info['config_email'],
						'store_telephone'		=> $store_info['config_telephone'],
						'store_url'				=> HTTP_SERVER,
						'firstname'				=> $email['firstname'],
						'lastname'				=> $email['lastname'],
						'email'					=> $email['email'],
						'telephone'				=> $email['telephone'],
						'cart_products'			=> $this->getcartproducts($email['products'],$email['ebabandonedcart_id']),
						'coupon'				=> (!empty($coupon_info['code']) ? $coupon_info['code'] : ''),
						'discount'				=> (!empty($coupon_info['discount']) ? number_format(round($coupon_info['discount']), 0) : ''),
						'currency'				=> (($this->currency->getSymbolRight($email['currency'])) ? $this->currency->getSymbolRight($email['currency']) : $this->currency->getSymbolLeft($email['currency'])),
						'total_amount'=>  (!empty($coupon_info['total']) ? number_format($coupon_info['total'], 2) : ''),
						'date_end'		=> (!empty($coupon_info['date_end']) ? $coupon_info['date_end'] : ''),
					);
					
					
					 $subject = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $templatesubject))));
				
					 $message = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $templatedata))));
					 
					 if($this->config->get('module_recover_carts_protocol')=='sendgrid'){
						require DIR_SYSTEM.'library/email_api/vendor/autoload.php';
						$sendername  = html_entity_decode($store_info['config_name'], ENT_QUOTES, 'UTF-8');
						$senderid 	 = $store_email;
						$sg_username = $this->config->get('module_recover_carts_username');
						$sg_password = $this->config->get('module_recover_carts_password');
						
						$sendgrid = new SendGrid($sg_username, $sg_password);
			
						$mail = new SendGrid\Email();
						$mail->addTo(trim($email['email']));
						$mail->setFromName($sendername)->setFrom($senderid)->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'))->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
						$sendgrid->send($mail);
					}else{
						$mail = new Mail($this->config->get('config_mail_engine'));
						$mail->parameter = $this->config->get('config_mail_parameter');
						$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
						$mail->smtp_username = $this->config->get('config_mail_smtp_username');
						$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
						$mail->smtp_port = $this->config->get('config_mail_smtp_port');
						$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

						$mail->setTo($email['email']);
						$mail->setFrom($store_email);
						$mail->setSender(html_entity_decode($store_info['config_name'], ENT_QUOTES, 'UTF-8'));
						$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
						$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
						$mail->send();
					}
					$this->model_extension_ebcart->updatenotifystatus($email['ebabandonedcart_id'],$email['email']);
					echo "EMAIL SENT";
				endforeach;
		}
	}
	
	public function getcartproducts($products,$ebabandonedcart_id){
		//CART PRODUCTS EMAIL TEMPLETES
		$this->language->load('extension/recover_carts');
		$this->load->model('extension/ebcart');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');
		
		$data['products'] = $products;
		
		$data['carttotal'] = $this->model_extension_ebcart->getebcarttotalprice($ebabandonedcart_id);
		
		return $this->load->view('default/template/extension/ebcart/email_products', $data);

	}
	
	public function SetCoupon($data,$email_data){
			//SET COUPON
			$coupon_vaild = $data['coupon_vaild'];
			$coupon_name = (isset($data['coupon_name'])) ? $data['coupon_name'].' ['.$email_data['email'].']' : '' ;
			$coupon_discount = $data['coupon_discount'];
			$coupon_type = $data['coupon_type'];
			$coupon_total = $data['coupon_total'];
			$coupon_uses_total = $data['coupon_uses_total'];
			$coupon_uses_customer = $data['coupon_uses_customer'];
			
			$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$code = "";
			for($i = 0; $i < 10; $i++){
				$code .= $chars[mt_rand(0, strlen($chars)-1)];
			}
			
			$today_date = date('Y-m-d');
			
			$enddate = date('Y-m-d',strtotime("+". $coupon_vaild ."day", strtotime($today_date)));
			
			$coupon_exists = $this->getCouponByCode($code);
			
			// check exists coupon code
			if(!$coupon_exists) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($coupon_name) . "', code = '" . $this->db->escape($code) . "', discount = '" . (float)$coupon_discount . "', total = '" . (float)$coupon_total . "', type = '" . $this->db->escape($coupon_type) . "', date_start = NOW(), date_end = '" . $this->db->escape($enddate) . "', uses_total = '" . (int)$coupon_uses_total . "', uses_customer = '" . (int)$coupon_uses_customer . "', status = 1, date_added = NOW()");

				$coupon_id = $this->db->getLastId();

				if (isset($data['coupon_product'])) {
					foreach ($data['coupon_product'] as $product_id) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "coupon_product SET coupon_id = '" . (int)$coupon_id . "', product_id = '" . (int)$product_id . "'");
					}
				}

				if (isset($data['coupon_category'])) {
					foreach ($data['coupon_category'] as $category_id) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "coupon_category SET coupon_id = '" . (int)$coupon_id . "', category_id = '" . (int)$category_id . "'");
					}
				}
		
				return $coupon_id;
			}else{
				$this->SetCoupon();
			}
	}
	
	public function getCouponByCode($code){
		//GET COUPON CODE BY CODE DETAILS
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($code) . "'");

		return $query->row;
	}
	
	public function getCoupon($coupon_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE coupon_id = '" . (int)$coupon_id . "' AND status = 1");

		return $query->row;
	}
}