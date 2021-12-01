<?php
class ControllerExtensionModuleOrderReviews extends Controller {
	
	private $data = array();
	private $error = array();
	private $version;
	private $module_path;
	private $extensions_link;
	private $language_variables;
	private $moduleModel;
	private $moduleName;
	private $call_model;
	/**
	 * OrderReviews Controller Constructor
	 * initialize necessary dependencies from the OpenCart framework.
	 */
	public function __construct($registry){
		parent::__construct($registry);
		$this->load->config('isenselabs/orderreviews');
		$this->moduleName = $this->config->get('orderreviews_name');
		$this->call_model = $this->config->get('orderreviews_model');
		$this->module_path = $this->config->get('orderreviews_path');
			
		$this->load->model($this->module_path);
		$this->moduleModel = $this->{$this->call_model};
    	$this->language_variables = $this->load->language($this->module_path);

    	//Loading framework models
	 	$this->load->model('setting/store');
		$this->load->model('setting/setting');
        $this->load->model('localisation/language');       

		$this->data['module_path']     = $this->module_path;		
		$this->data['moduleName']      = $this->moduleName;
		$this->data['moduleNameSmall'] = $this->moduleName;	    
	}

    public function sendEmails()  {
		$stores = array_merge(array(0 => $this->moduleModel->getStore(0)), $this->model_setting_store->getStores());
		
		foreach ($stores as $store) {
			if(!$this->moduleModel->checkDbTable('orderreviews_setting')){
				$setting = $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
			} else {
				$setting = $this->moduleModel->getSetting($this->moduleName, $store['store_id']);
			}

			$moduleData = isset($setting[$this->moduleName]) ? $setting[$this->moduleName] : array();

			if (!empty($moduleData['Enabled']) && $moduleData['Enabled'] == 'yes' && isset($moduleData['ReviewMail'])) {
				$counter = 0;
				$OrderProducts = array();
				
				foreach ($moduleData['ReviewMail'] as $reviewmail) {
					if ($reviewmail['Enabled']=='yes') {
						
						$orders = $this->moduleModel->getOrders($reviewmail['OrderStatusID'], $reviewmail['Delay'], $reviewmail['DateType'],$store['store_id']);

						foreach ($orders as $order) {
							if ($order['store_id'] != $store['store_id']) {
								continue;
							}

							if (!(($reviewmail['CustomerGroupID'] == 'send_all') || ($reviewmail['CustomerGroupID'] != 'send_all' && $reviewmail['CustomerGroupID']==$order['customer_group_id']))) {
								break;	
							}

							if ($this->moduleModel->checkReviewLog($order['order_id'])) {
								continue;	
							}
							
							$OrderLanguage = $this->model_localisation_language->getLanguage($order['language_id']);
							$LangVars =  $this->moduleModel->loadLanguage($OrderLanguage['code'].'/extension/module','orderreviews');
							$VarOrderProducts =  $this->moduleModel->getOrderProducts($order['order_id']);

							$query_reviewed = $this->db->query("SELECT DISTINCT product_id FROM " . DB_PREFIX . "orderreviews_log as ol LEFT JOIN `" . DB_PREFIX . "order` as o ON ol.order_id = o.order_id LEFT JOIN " . DB_PREFIX . "order_product as op ON ol.order_id = op.order_id WHERE o.email = '" . $order['email'] . "' ORDER BY product_id");

							$reviewedProducts = array();

							foreach ($query_reviewed->rows as $rows){
								$reviewedProducts[]=$rows['product_id'];
							}

							$OrderProducts = array();

							foreach($VarOrderProducts as $products ){
								if(!in_array($products['product_id'],$reviewedProducts)){
									$OrderProducts[] = $products;
								}
							}

							$Products = '';
							$ProductIDs = '';
							if (sizeof($OrderProducts)==1) {
								$Products = '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[0]['product_id'].'">'.$OrderProducts[0]['name'].'</a>';
								$ProductIDs = $OrderProducts[0]['product_id'];
							} else {
								for ($i=0; $i<sizeof($OrderProducts); $i++) {
									if (($i+1) == sizeof($OrderProducts)) {
										$Products .= ' '.$LangVars['text_and'].' ';
									}  else if (($i+1) < sizeof($OrderProducts) && ($i>0)) {
										$Products .= ', ';	
									}
									$Products .= '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[$i]['product_id'].'">'.$OrderProducts[$i]['name'].'</a>';
									$ProductIDs .= $OrderProducts[$i]['product_id'];
									
									if (!(($i+1) == sizeof($OrderProducts)))
											$ProductIDs .= '_';
								}
							}
							$subject_original = array('{first_name}','{last_name}', '{order_id}');
							$subject_replace = array($order['firstname'], $order['lastname'], $order['order_id']);
							$Subject = str_replace($subject_original, $subject_replace, $reviewmail['Subject'][$order['language_id']]);
							$Message = html_entity_decode($reviewmail['Message'][$order['language_id']]);
							$FirstName = $order['firstname'];
							$LastName = $order['lastname'];
							$Email = $order['email'];
							$SubmitLink = $store['url'].'index.php?route='.$this->module_path;
							$params = 'order_id='.$order['order_id'].'&reviewmail_id='.$reviewmail['id'].'&store_id='.$store['store_id'];
							$ReveiewMailLink = $store['url'].'index.php?route='.$this->module_path.'/sendReview&params='.base64_encode($params);
							
							$ProductFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_include.twig');			
							$ProductsViews = "";
							
							
							if ($reviewmail['ReviewType'] == 'per_purchase') {
								$tempVar = '';
								$old = array("{number}","{pr_name}","{pr_id}","{image}");
								$new = array('0','','0',NULL);
								$tempVar = str_replace($old, $new, $ProductFormData);
								$ProductsViews .= $tempVar;
							} else if ($reviewmail['ReviewType'] == 'per_product') { 
								if (sizeof($OrderProducts)>0) {
									for ($i=0; $i<sizeof($OrderProducts); $i++) {
										$tempVar = '';
										if($reviewmail['DisplayImages'] == 'yes'){										
											$this->load->model('catalog/product');
											$product_info = $this->model_catalog_product->getProduct($OrderProducts[$i]['product_id']);
											$this->load->model('tool/image');
											if ($product_info['image']) {
												$image = $this->model_tool_image->resize($product_info['image'], 200, 200);
											} else { 
										 	 	$image = false;
									 	 	}

											$old = array("{number}","{pr_name}","{pr_id}","{image}");
											$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',"<img src='".$image."' />");
										} else {
											$old = array("{number}","{pr_name}","{pr_id}","{image}");
											$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',NULL);									
										}
										
										$tempVar = str_replace($old, $new, $ProductFormData);
										$ProductsViews .= $tempVar;
									}
								}
							}
							
							if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){
								
								$MainFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_review_email_form.twig');
															
								$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}","{reviewmail_link_href}","{link_button_text}","{store_url}","{star_1}","{star_2}","{star_3}","{star_4}","{star_5}");
								$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews, $ReveiewMailLink,$LangVars['text_leave_review'],$store['url'],$LangVars['star_1'],$LangVars['star_2'],$LangVars['star_3'],$LangVars['star_4'],$LangVars['star_5']);
							}else{
								$privacyCheckbox = $this->moduleModel->privacyCheckbox($moduleData['PrivacyPolicy']);
								$MainFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_main.twig');
								$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{privacy_aggreement}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}");				
								$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $privacyCheckbox, $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews);	
							}

							$ReviewForm = str_replace($form_pattern, $form_replacements, $MainFormData);
							
							if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){
								$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}','{reviewmail_link}');
								$replacements = array($FirstName, $LastName, $ReviewForm, $Products, base64_encode($order['order_id']),$LangVars['link_replacement']);	
							}else {
								$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}', '{reviewmail_link}', '{reviewmail_link_href}');
								$replacements = array($FirstName, $LastName, $ReviewForm, $Products, base64_encode($order['order_id']), $LangVars['text_reviewmail_link'], $ReveiewMailLink);		
							}
							
							$HTMLMail = str_replace($patterns, $replacements, $Message);

							$newMail = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
								<html xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
									<title>Monthly Newsletter</title>
								</head>
								<body>'.$HTMLMail.'</body></html>';

							$MailData = array(
								'email' =>  $Email,
								'message' => $newMail, 
								'subject' => $Subject,
								'store_name' => $store['name'],
								'store_id' => $store['store_id']
							);
							if (sizeof($OrderProducts)>0) {
								$emailResult = $this->moduleModel->sendMail($MailData);
								$counter++;
								if  (isset($moduleData['LOG']) && $moduleData['LOG'] == 'yes') {
									$OrderDate = date('Y-m-d H:i:s', time());
									$this->db->query("INSERT INTO `" . DB_PREFIX . "orderreviews_mail_log` SET `date`='".$this->db->escape($OrderDate)."', `store_id`='".$this->db->escape($store['store_id'])."', `order_id`='".$this->db->escape($order['order_id'])."'");
								}
							}
						}
					}
				}
				
				$result = "Cron was executed successfully! A total of <strong>".$counter."</strong> emails were sent to the customers.<br />";
	
				if (isset($moduleData['CronNotification']) && $moduleData['CronNotification']=='yes' && sizeof($OrderProducts)>0) {
					
					$mailToUser = new Vendor\iSenseLabs\OrderReviews\Mail($this->config->get('config_mail_engine'));
			        $mailToUser->parameter = $this->config->get('config_mail_parameter');
			        $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			        $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
			        $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			        $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
			        $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
					$mailToUser->setTo($this->config->get('config_email'));
					$mailToUser->setFrom($this->config->get('config_email'));
					$mailToUser->setSender($this->config->get('config_name'));
					$mailToUser->setSubject(html_entity_decode('OrderReviews Cron Task', ENT_QUOTES, 'UTF-8'));
					$mailToUser->setHtml($result);
					$mailToUser->send(); 
				} else {
					echo $result;	
				}	
			}
		}
    }
	
	public function sendEmailAgain()  {
		$stores = array_merge(array(0 => $this->moduleModel->getStore(0)), $this->model_setting_store->getStores());

		foreach ($stores as $store) {
			if(!$this->moduleModel->checkDbTable('orderreviews_setting')){
				$setting = $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
			} else {
				$setting = $this->moduleModel->getSetting($this->moduleName, $store['store_id']);
			}

			$moduleData = isset($setting[$this->moduleName]) ? $setting[$this->moduleName] : array();

			if (!empty($moduleData['Enabled']) && $moduleData['Enabled'] == 'yes' && isset($moduleData['ReviewMail'])) {
				$counter = 0;
				foreach ($moduleData['ReviewMail'] as $reviewmail) {
					if ($reviewmail['Enabled']=='yes') {

						$orders = $this->moduleModel->getOrders($reviewmail['OrderStatusID'], $reviewmail['Delay'], $reviewmail['DateType'],$store['store_id']);

						foreach ($orders as $order) {
							if (($order['order_id']) == ((int)base64_decode($this->request->get['order_id']))){

								if ($order['store_id'] != $store['store_id']) {
									continue;
								}

								if (!(($reviewmail['CustomerGroupID'] == 'send_all') || ($reviewmail['CustomerGroupID'] != 'send_all' && $reviewmail['CustomerGroupID']==$order['customer_group_id']))) {
									break;
								}

								if ($this->moduleModel->checkReviewLog($order['order_id'])) {
									continue;
								}

								$OrderLanguage = $this->model_localisation_language->getLanguage($order['language_id']);
								$LangVars =  $this->moduleModel->loadLanguage($OrderLanguage['code'].'/extension/module','orderreviews');
								$VarOrderProducts =  $this->moduleModel->getOrderProducts($order['order_id']);

								$query_reviewed = $this->db->query("SELECT DISTINCT product_id FROM " . DB_PREFIX . "orderreviews_log as ol LEFT JOIN `" . DB_PREFIX . "order` as o ON ol.order_id = o.order_id LEFT JOIN " . DB_PREFIX . "order_product as op ON ol.order_id = op.order_id WHERE o.email = '" . $order['email'] . "' ORDER BY product_id");

								$reviewedProducts = array();

								foreach ($query_reviewed->rows as $rows){
									$reviewedProducts[]=$rows['product_id'];
								}

								$OrderProducts = array();
								
								foreach($VarOrderProducts as $products ){
									if(!in_array($products['product_id'],$reviewedProducts)){
										$OrderProducts[] = $products;
									}
								}

								$Products = '';
								$ProductIDs = '';
								if (sizeof($OrderProducts)==1) {
									$Products = '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[0]['product_id'].'">'.$OrderProducts[0]['name'].'</a>';
									$ProductIDs = $OrderProducts[0]['product_id'];
								} else {
									for ($i=0; $i<sizeof($OrderProducts); $i++) {
										if (($i+1) == sizeof($OrderProducts)) {
											$Products .= ' '.$LangVars['text_and'].' ';
										}  else if (($i+1) < sizeof($OrderProducts) && ($i>0)) {
											$Products .= ', ';
										}
										$Products .= '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[$i]['product_id'].'">'.$OrderProducts[$i]['name'].'</a>';
										$ProductIDs .= $OrderProducts[$i]['product_id'];

										if (!(($i+1) == sizeof($OrderProducts)))
												$ProductIDs .= '_';
									}
								}
								$subject_original = array('{first_name}','{last_name}', '{order_id}');
								$subject_replace = array($order['firstname'], $order['lastname'], $order['order_id']);
								$Subject = str_replace($subject_original, $subject_replace, $reviewmail['Subject'][$order['language_id']]);
								$Message = html_entity_decode($reviewmail['Message'][$order['language_id']]);
								$FirstName = $order['firstname'];
								$LastName = $order['lastname'];
								$Email = $order['email'];
								$SubmitLink = $store['url'].'index.php?route='.$this->module_path;
								$params = 'order_id='.$order['order_id'].'&reviewmail_id='.$reviewmail['id'].'&store_id='.$store['store_id'];
								$ReveiewMailLink = $store['url'].'index.php?route='.$this->module_path.'/sendReview&params='.base64_encode($params);

								$ProductFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_include.twig');
								$ProductsViews = "";


								if ($reviewmail['ReviewType'] == 'per_purchase') {
									$tempVar = '';
									$old = array("{number}","{pr_name}","{pr_id}","{image}");
									$new = array('0','','0',NULL);
									$tempVar = str_replace($old, $new, $ProductFormData);
									$ProductsViews .= $tempVar;
								} else if ($reviewmail['ReviewType'] == 'per_product') {
									if (sizeof($OrderProducts)>0) {
										for ($i=0; $i<sizeof($OrderProducts); $i++) {
											$tempVar = '';
											if($reviewmail['DisplayImages'] == 'yes'){
												$this->load->model('catalog/product');
												$product_info = $this->model_catalog_product->getProduct($OrderProducts[$i]['product_id']);
												$this->load->model('tool/image');
												if ($product_info['image']) {
													$image = $this->model_tool_image->resize($product_info['image'], 200, 200);
												} else {
											 	 	$image = false;
										 	 	}

												$old = array("{number}","{pr_name}","{pr_id}","{image}");
												$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',"<img src='".$image."' />");
											} else {
												$old = array("{number}","{pr_name}","{pr_id}","{image}");
												$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',NULL);
											}

											$tempVar = str_replace($old, $new, $ProductFormData);
											$ProductsViews .= $tempVar;
										}
									}
								}

								if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){

									$MainFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_review_email_form.twig');

									$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}","{reviewmail_link_href}","{link_button_text}","{store_url}","{star_1}","{star_2}","{star_3}","{star_4}","{star_5}");
									$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews, $ReveiewMailLink,$LangVars['text_leave_review'],$store['url'],$LangVars['star_1'],$LangVars['star_2'],$LangVars['star_3'],$LangVars['star_4'],$LangVars['star_5']);
								}else{
									$privacyCheckbox = $this->moduleModel->privacyCheckbox($moduleData['PrivacyPolicy']);
									$MainFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_main.twig');
									$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{privacy_aggreement}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}");
									$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $privacyCheckbox, $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews);
								}

								$ReviewForm = str_replace($form_pattern, $form_replacements, $MainFormData);

								if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){
									$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}','{reviewmail_link}');
									$replacements = array($FirstName, $LastName, $ReviewForm, $Products, base64_encode($order['order_id']),$LangVars['link_replacement']);
								}else {
									$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}', '{reviewmail_link}', '{reviewmail_link_href}');
									$replacements = array($FirstName, $LastName, $ReviewForm, $Products, base64_encode($order['order_id']), $LangVars['text_reviewmail_link'], $ReveiewMailLink);
								}

								$HTMLMail = str_replace($patterns, $replacements, $Message);

								$newMail = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
									<html xmlns="http://www.w3.org/1999/xhtml">
									<head>
										<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
										<title>Monthly Newsletter</title>
									</head>
									<body>'.$HTMLMail.'</body></html>';

								$MailData = array(
									'email' =>  $Email,
									'message' => $newMail,
									'subject' => $Subject,
									'store_name' => $store['name'],
									'store_id' => $store['store_id']
								);
								if (sizeof($OrderProducts)>0) {
									$emailResult = $this->moduleModel->sendMail($MailData);
									$counter++;
								}

							}
						}
					}
				}
				$data['success_again'] = $this->language->get('resend_mail');
				$data['continue'] = $this->url->link('common/home');
				$data['breadcrumbs'] = array();

				$data['breadcrumbs'][] = array(
					'href'      => $this->url->link('common/home'),
					'text'      => $this->language->get('text_home'),
					'separator' => false
				);
				$link =  "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
				$escaped_link = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
				$data['breadcrumbs'][] = array(
					'href'      => $escaped_link,
					'text'      => $this->language->get('button_send_again'),
					'separator' => false
				);

				$this->document->setTitle($this->language->get('button_send_again'));

				$data['heading_title'] 			= $this->language->get('button_send_again');
				$data['button_continue'] 		= $this->language->get('button_continue');
				$data['continue'] 				= $this->url->link('common/home');

				$data['column_left'] 			= $this->load->controller('common/column_left');
				$data['column_right'] 			= $this->load->controller('common/column_right');
				$data['content_top'] 			= $this->load->controller('common/content_top');
				$data['content_bottom'] 		= $this->load->controller('common/content_bottom');
				$data['footer']					= $this->load->controller('common/footer');
				$data['header'] 				= $this->load->controller('common/header');

				$result =  $this->response->setOutput($this->load->view($this->config->get('orderreviews_path').'/orderreviews_send_again', $data));


				echo $result;

			}
		}
}
	
	public function sendReview()  {
		$this->language->load('product/product');
		$this->load->model('catalog/review');
		$this->load->model('checkout/order');
		$this->load->language($this->module_path);
		
		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_send_again'] = $this->language->get('button_send_again');
		$data['history'] = isset($this->request->server['HTTP_REFERER']) ? $this->request->server['HTTP_REFERER'] : '';
		$data['duplicate'] = false;
		$error = array();
		$missing_data = true;
		
		if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['orderreviews'])) {
			$missing_data = false;
			if(!$this->moduleModel->checkDbTable('orderreviews_setting')){
				$setting = $this->model_setting_setting->getSetting($this->moduleName, $this->config->get('config_store_id'));
			} else {
				$setting = $this->moduleModel->getSetting($this->moduleName, $this->config->get('config_store_id'));
			}
			$moduleData = $setting[$this->moduleName];
			
			if (!empty($moduleData['Enabled']) && $moduleData['Enabled'] == 'yes') {

				$reviewData = $setting[$this->moduleName]['ReviewMail'][$this->request->get['reviewmail_id']];
				$couponChance = false;

				if ($reviewData['ReviewType'] == 'per_purchase') {
					if ((utf8_strlen($this->request->get['name']) < 3) || (utf8_strlen($this->request->get['name']) > 100)) {
						$error['error'] = $this->language->get('error_name');
					}
		
					if (!isset($this->request->get['orderreviews'][0]['text']) || (utf8_strlen($this->request->get['orderreviews'][0]['text']) < 10) || (utf8_strlen($this->request->get['orderreviews'][0]['text']) > 1000)) {
						$error['error'] = $this->language->get('error_text');
					}
		
					if (empty($this->request->get['orderreviews'][0]['rating'])) {
						$error['error'] = $this->language->get('error_rating');
					}

					if ($moduleData['PrivacyPolicy'] && empty($this->request->get['privacy_policy'])) {
						$error['error'] = $this->language->get('text_privacy_error');
					}
					
					if (isset($this->request->get['order_id']) && ($this->moduleModel->checkReviewLog(base64_decode($this->request->get['order_id'])))) {
						$error['error'] = $this->language->get('error_duplicate');
						$data['duplicate'] = true;
					}
					$log_ids = array();
					
					if (!isset($error['error'])) {
						$products = explode('_', $this->request->get['product_ids']);
						foreach ($products as $product_id) {
							$data = array(
							'name' => $this->request->get['name'],
							'customer_id' => base64_decode($this->request->get['customer_id']),
							'product_id' => $product_id,
							'text' => $this->request->get['orderreviews'][0]['text'],
							'rating' => $this->request->get['orderreviews'][0]['rating'],
							'AutoApproved' => $moduleData['AutoApproved'] ? $moduleData['AutoApproved'] : 'no',
							'AutoApprovedStar' => $moduleData['AutoApprovedStar'] ? $moduleData['AutoApprovedStar'] : 0,
							'privacy_policy' => !empty($this->request->get['privacy_policy']) ? $this->request->get['privacy_policy'] : 0
							);
							$review_id = $this->moduleModel->addReview($product_id, $data);
							$this->moduleModel->addReviewLog(base64_decode($this->request->get['order_id']),$data,$this->config->get('config_store_id'),$review_id);
							$log_ids[] = $this->db->getLastId();
						}

						$data['success'] = $this->language->get('successfull_review');
						$data['button_continue'] = $this->language->get('button_continue');
						$data['continue'] = $this->url->link('common/home');
						$couponChance = true;
					} else {
						$data['errors'] = $this->language->get('text_errors');
						$data['errorsArray'] = $error;
					}
				} else if ($reviewData['ReviewType'] == 'per_product') { 
					if (isset($this->request->get['order_id']) && ($this->moduleModel->checkReviewLog(base64_decode($this->request->get['order_id'])))) {
						$error['error'] = $this->language->get('error_duplicate');
						$data['duplicate'] = true;
						$data['errors'] = $this->language->get('text_errors');
						$data['errorsArray'] = $error;
					} else {
						$products = explode('_', $this->request->get['product_ids']);
						$checker = false;
						foreach ($products as $product_id) {
							if (!empty($this->request->get['orderreviews'][$product_id]['text']) && !empty($this->request->get['orderreviews'][$product_id]['rating'])){ 
								$data = array(
								'name' => $this->request->get['name'],
								'customer_id' => base64_decode($this->request->get['customer_id']),
								'product_id' => $product_id,
								'text' => $this->request->get['orderreviews'][$product_id]['text'],
								'rating' => $this->request->get['orderreviews'][$product_id]['rating'],
								'AutoApproved' => $moduleData['AutoApproved'] ? $moduleData['AutoApproved'] : 'no',
								'AutoApprovedStar' => $moduleData['AutoApprovedStar'] ? $moduleData['AutoApprovedStar'] : 0,
								'privacy_policy' => !empty($this->request->get['privacy_policy']) ? $this->request->get['privacy_policy'] : 0

								);
								$review_id = $this->moduleModel->addReview($product_id, $data);
								$this->moduleModel->addReviewLog(base64_decode($this->request->get['order_id']),$data,$this->config->get('config_store_id'),$review_id);
								$log_ids[] = $this->db->getLastId();
								$checker = true;
							}
						}
						if (!$checker) {
							$error['error'] = $this->language->get('no_reviews');
							$data['errors'] = $this->language->get('text_errors');
							$data['errorsArray'] = $error;
						} else {
							$data['success'] = $this->language->get('successfull_review');
							$data['button_continue'] = $this->language->get('button_continue');
							$data['continue'] = $this->url->link('common/home');
							$couponChance = true;
						}
					}
				}
				
				if ($couponChance) {
					if ($reviewData['DiscountType']!='N') {

						$order_data = $this->model_checkout_order->getOrder(base64_decode($this->request->get['order_id']));

						if(empty($reviewData['products'])) {
							$reviewData['products'] = NULL;
							$discountProducts = '';
						}
						if(empty($reviewData['categories'])) {
							$reviewData['categories'] = NULL;
							$discountCategories = '';
						}

						$DiscountCode			= $this->moduleModel->generateuniquerandomcouponcode();
						$TimeEnd				=  time() + $reviewData['DiscountValidity'] * 24 * 60 * 60;
						$CouponData				= array('name' => 'OrderReviews Coupon [' . $this->request->get['name'] . ']',
						'code'					=> $DiscountCode, 
						'discount'				=> $reviewData['Discount'],
						'type'					=> $reviewData['DiscountType'],
						'total'					=> $reviewData['TotalAmount'],
						'logged'				=> '0',
						'shipping'				=> '0',
						'date_start'			=> date('Y-m-d', time()),
						'date_end'				=> date('Y-m-d', $TimeEnd),
						'uses_total'			=> '1',
						'uses_customer'			=> '1',
						'coupon_product'		=> $reviewData['products'],
						'coupon_category'		=> $reviewData['categories'],
						'status'				=> '1');
						$this->moduleModel->addCoupon($CouponData);
						foreach ($log_ids as $id) {
							$this->moduleModel->updateReviewLog($id,$DiscountCode);
						}

						if ($reviewData['products']) {
							$dataDiscountProducts = $this->moduleModel->getProductsInIDArray($reviewData['products']);
							
							if (sizeof($dataDiscountProducts)==1) {
								$discountProducts = '<a href="'.$order_data['store_url'].'index.php?route=product/product&amp;product_id=' . $dataDiscountProducts[0]['product_id'].'">'.$dataDiscountProducts[0]['name'].'</a>';
							} else {
								for ($i=0; $i<sizeof($dataDiscountProducts); $i++) {
									if (($i+1) == sizeof($dataDiscountProducts)) {
										$discountProducts .= ' '.$this->language->get('text_and').' ';
									}  else if (($i+1) < sizeof($dataDiscountProducts) && ($i>0)) {
										$discountProducts .= ', ';	
									}
									$discountProducts = '<a href="'.$order_data['store_url'].'index.php?route=product/product&amp;product_id=' . $dataDiscountProducts[$i]['product_id'].'">'.$dataDiscountProducts[$i]['name'].'</a>';
								}
							}
						}

						if ($reviewData['categories']) {
							$dataDiscountCategories = $this->moduleModel->getCategoriesByID($reviewData['categories']);
							
							if (sizeof($dataDiscountCategories)==1) {
								$discountCategories = '<a href="'.$order_data['store_url'].'index.php?route=product/category&amp;path=' . $dataDiscountCategories[0]['category_id'].'">'.$dataDiscountCategories[0]['name'].'</a>';
							} else {
								for ($i=0; $i<sizeof($dataDiscountCategories); $i++) {
									if (($i+1) == sizeof($dataDiscountCategories)) {
										$discountCategories .= ' '.$this->language->get('text_and').' ';
									}  else if (($i+1) < sizeof($dataDiscountCategories) && ($i>0)) {
										$discountCategories .= ', ';	
									}
									$discountCategories = '<a href="'.$order_data['store_url'].'index.php?route=product/category&amp;path=' . $dataDiscountCategories[$i]['category_id'].'">'.$dataDiscountCategories[$i]['name'].'</a>';
								}
							}
						}
						
						if ($reviewData['products'] || $reviewData['categories']) {
							$discount_text = $this->language->get('text_discount_product_category');
						} else {
							$discount_text = $this->language->get('text_discount');
						}

						$discount_value = ($reviewData['DiscountType']=='F') ? $this->currency->format($reviewData['Discount'],$this->session->data['currency']) : $reviewData['Discount'].'%';
						$total_amount = $this->currency->format($reviewData['TotalAmount'],$this->session->data['currency']);
						$patterns = array('{discount_code}', '{discount_value}', '{total_amount}', '{date_end}', '{product_discount}', '{category_discount}');
						$replacements = array($DiscountCode, $discount_value, $total_amount, date($reviewData['DateFormat'], $TimeEnd), $discountProducts, $discountCategories);
						$data['discount_text'] = str_replace($patterns, $replacements, $discount_text);
						
						if (isset($reviewData['DiscountMailEnabled']) && $reviewData['DiscountMailEnabled']=='yes' && isset($reviewData['MessageDiscount'][$this->config->get('config_language_id')]) && isset($reviewData['SubjectDiscount'][$this->config->get('config_language_id')]) ) {
							$Email = base64_decode($this->request->get['email']);
							
							$subject_discount_original = array('{first_name}','{last_name}', '{order_id}');
							$subject_discount_replace = array($this->request->get['fname'], $this->request->get['lname'],base64_decode($this->request->get['order_id']));
							$Subject = str_replace($subject_discount_original, $subject_discount_replace, $reviewData['SubjectDiscount'][$this->config->get('config_language_id')]);
							
							//$Subject = $reviewData['SubjectDiscount'][$this->config->get('config_language_id')];
							$Message = html_entity_decode($reviewData['MessageDiscount'][$this->config->get('config_language_id')]);
							$patterns1 = array('{first_name}', '{last_name}', '{discount_code}', '{discount_value}', '{total_amount}', '{date_end}', '{product_discount}', '{category_discount}');
							$replacements1 = array($this->request->get['fname'], $this->request->get['lname'], $DiscountCode, $discount_value, $total_amount, date($reviewData['DateFormat'], $TimeEnd), $discountProducts, $discountCategories);
							$HTMLMail = str_replace($patterns1, $replacements1, $Message);

							$Mail = array(
								'email' =>  $Email,
								'message' => $HTMLMail, 
								'subject' => $Subject,
								'store_name' => $this->config->get('config_name'),
								'store_id' => $order_data['store_id']
							);
	
							$emailResult = $this->moduleModel->sendMail($Mail);
						}
					}	
				} 
			}
		}
		if (isset($this->request->get['params'])) {
			$data['start_rating'] = isset($this->request->get['startRating']) ? $this->request->get['startRating'] : '5';
			parse_str(base64_decode($this->request->get['params']),$decoded_data);
			unset($this->request->get['params']);
			$this->request->get = array_merge($this->request->get, $decoded_data);
			
			if (isset($this->request->get['order_id']) && ($this->moduleModel->checkReviewLog($this->request->get['order_id']))) {
				$error['error'] = $this->language->get('error_duplicate');
				$data['errors'] = $this->language->get('text_errors');
				$data['errorsArray'] = $error;
				$data['duplicate'] = true;
			} elseif (isset($this->request->get['order_id']) && isset($this->request->get['reviewmail_id']) && isset($this->request->get['store_id'])) {
				$missing_data = false;
				$this->load->model('checkout/order');
				
				$order_id			= $this->request->get['order_id'];
				$order				= $this->model_checkout_order->getOrder($order_id);
				$store				= $this->moduleModel->getStore($this->request->get['store_id']);
				
				if(!$this->moduleModel->checkDbTable('orderreviews_setting')){
					$setting = $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
				} else {
					$setting = $this->moduleModel->getSetting($this->moduleName, $store['store_id']);
				}

				$moduleData			= isset($setting[$this->moduleName]) ? $setting[$this->moduleName] : array();
				$reviewmail_id 		= $this->request->get['reviewmail_id'];
				$reviewmail			= isset ($moduleData['ReviewMail'][$reviewmail_id]) ? $moduleData['ReviewMail'][$reviewmail_id] : array();			
				
				if (!empty($reviewmail)) {
					$OrderLanguage = $this->model_localisation_language->getLanguage($order['language_id']);
					$LangVars = $this->moduleModel->loadLanguage($OrderLanguage['code'].'/extension/module','orderreviews');
					$VarOrderProducts =  $this->moduleModel->getOrderProducts($order['order_id']);

					$query_reviewed = $this->db->query("SELECT DISTINCT product_id FROM " . DB_PREFIX . "orderreviews_log as ol LEFT JOIN `" . DB_PREFIX . "order` as o ON ol.order_id = o.order_id LEFT JOIN " . DB_PREFIX . "order_product as op ON ol.order_id = op.order_id WHERE o.email = '" . $order['email'] . "' ORDER BY product_id");

					$reviewedProducts = array();

					foreach ($query_reviewed->rows as $rows){
						$reviewedProducts[]=$rows['product_id'];
					}

					$OrderProducts = array();

					foreach($VarOrderProducts as $products ){
						if(!in_array($products['product_id'],$reviewedProducts)){
							$OrderProducts[] = $products;
						}
					}

					$Products = '';
					$ProductIDs = '';
					if (sizeof($OrderProducts)==1) {
						$Products = '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[0]['product_id'].'">'.$OrderProducts[0]['name'].'</a>';
						$ProductIDs = $OrderProducts[0]['product_id'];
					} else {
						for ($i=0; $i<sizeof($OrderProducts); $i++) {
							if (($i+1) == sizeof($OrderProducts)) {
								$Products .= ' '.$LangVars['text_and'].' ';
							}  else if (($i+1) < sizeof($OrderProducts) && ($i>0)) {
								$Products .= ', ';	
							}
							$Products .= '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $OrderProducts[$i]['product_id'].'">'.$OrderProducts[$i]['name'].'</a>';
							$ProductIDs .= $OrderProducts[$i]['product_id'];
							
							if (!(($i+1) == sizeof($OrderProducts)))
									$ProductIDs .= '_';
						}
					}
					
					$subject_original = array('{first_name}','{last_name}', '{order_id}');
					$subject_replace = array($order['firstname'], $order['lastname'], $order['order_id']);
					$Subject = str_replace($subject_original, $subject_replace, $reviewmail['Subject'][$order['language_id']]);
					$Message = html_entity_decode($reviewmail['Message'][$order['language_id']]);
					$FirstName = $order['firstname'];
					$LastName = $order['lastname'];
					$Email = $order['email'];
					$SubmitLink = $store['url'].'index.php?route='.$this->module_path.'/sendReview';

					$MainFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_main.twig');
					$ProductFormData = $this->moduleModel->fetchForm('default/template/'.$this->module_path.'/orderreviews_product_form_include.twig');			
					$ProductsViews = "";
					
					if ($reviewmail['ReviewType'] == 'per_purchase') {
						$tempVar = '';
						$old = array("{number}","{pr_name}","{pr_id}","{image}");
						$new = array('0','','0',NULL);
						$tempVar = str_replace($old, $new, $ProductFormData);
						$ProductsViews .= $tempVar;
					} else if ($reviewmail['ReviewType'] == 'per_product') { 
						if (sizeof($OrderProducts)>0) {
							for ($i=0; $i<sizeof($OrderProducts); $i++) {
								$tempVar = '';
								if($reviewmail['DisplayImages'] == 'yes'){										
									$this->load->model('catalog/product');
									$product_info = $this->model_catalog_product->getProduct($OrderProducts[$i]['product_id']);
									$this->load->model('tool/image');
									if ($product_info['image']) { $image = $this->model_tool_image->resize($product_info['image'], 200, 200); } else { $image = false; }										
									$old = array("{number}","{pr_name}","{pr_id}","{image}");
									$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',"<img src='".$image."' />");
								} else {
									$old = array("{number}","{pr_name}","{pr_id}","{image}");
												$new = array($OrderProducts[$i]['product_id'],$OrderProducts[$i]['name'].':',$OrderProducts[$i]['product_id'].'<br/>',NULL);											
								}
								$tempVar = str_replace($old, $new, $ProductFormData);
								$ProductsViews .= $tempVar;
							}
						}
					}
					
					$privacyCheckbox = $this->moduleModel->privacyCheckbox($moduleData['PrivacyPolicy']);
					$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{privacy_aggreement}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}");
					
					$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $privacyCheckbox, $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews);
					
					$ReviewForm = str_replace($form_pattern, $form_replacements, $MainFormData);
					
					$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}', '{reviewmail_link}');
					$replacements = array($FirstName, $LastName, $ReviewForm, $Products, $order['order_id'], '');
					$HTMLMail = str_replace($patterns, $replacements, $Message);
					
					$data['FormData'] = $HTMLMail;
				}
			} else if ($missing_data) {
				$data['FormData']	= '<div class="warning">'.$this->language->get('error_form').'</div>';
			}
		}
		
		$data['ReveiewMailLink'] = $this->url->link($this->module_path.'/sendReview', 'order_id='.base64_encode($this->request->get['order_id']).'&reviewmail_id='.$this->request->get['reviewmail_id'].'&store_id='.$this->config->get('config_store_id'), true);		
		$data['ReveiewMailLinkNotDec'] = $this->url->link($this->module_path.'/sendEmailAgain', 'order_id='.($this->request->get['order_id']), true);
		$data['breadcrumbs'] = array(); 

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/home'),
			'text'      => $this->language->get('text_home'),
			'separator' => false
		);
		$link =  "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$escaped_link = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
		$data['breadcrumbs'][] = array(
			'href'      => $escaped_link,
			'text'      => $this->language->get('heading_title'),
			'separator' => false
		);
		
		$data['heading_title'] 			= $this->language->get('heading_title');
		$data['button_continue'] 		= $this->language->get('button_continue');
		$data['continue'] 				= $this->url->link('common/home');
		$data['text_privacy_error'] 	= $this->language->get('text_privacy_error');

		$data['column_left'] 			= $this->load->controller('common/column_left');
		$data['column_right'] 			= $this->load->controller('common/column_right');
		$data['content_top'] 			= $this->load->controller('common/content_top');
		$data['content_bottom'] 		= $this->load->controller('common/content_bottom');
		$data['footer']					= $this->load->controller('common/footer');
		$data['header'] 				= $this->load->controller('common/header');
	
		// if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/product/orderreviews_success.twig')) {
		// 	$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/module/orderreviews_success.twig', $data));
		// } else {
		// 	$this->response->setOutput($this->load->view('default/template/module/orderreviews_success.twig', $data));
		// }


	  	$this->response->setOutput($this->load->view($this->config->get('orderreviews_path').'/orderreviews_success', $data));
	}

	/**
	 * Handle getOrder after event so we can insert customer group id in the returned data
	 *
	 * @param      <type>  $route  The route
	 * @param      <type>  $data   The data
	 */
	public function handleGetOrderAfter($route, &$data, &$output)
	{
		if (!empty($data) && !empty($output)) {
			$customer_group_id = $this->moduleModel->getOrderCustomerGroup($data[0]);
			$output['customer_group_id'] = $customer_group_id;
		}
	}

	/**
	 * Handle addOrderHistory after event and send the review mail
	 *
	 * @param      <type>  $route  The route
	 * @param      <type>  $data   The data
	 */
	public function handleAddOrderHistory($route, &$data)
	{
		if (!empty($data)) {
			list($order_id,$order_status_id) = $data;
			$this->moduleModel->sendReviewMail($order_id, $order_status_id);
		}
	}

	public function viewOrderHistoryAfter($route, &$data, &$output)
	{
		$orders		= $data['orders'];
		$search		= array();
		$replace	= array();

		foreach ($orders as $order) {
			if ($order['sendReviews'] == true) {
				$search[] = '<a href="'.$order['view'].'" data-toggle="tooltip" title="'.$data['button_view'].'" class="btn btn-info"><i class="fa fa-eye"></i></a>';
				$replace[] = '<a href="'.$order['ReveiewMailLink'].'" data-toggle="tooltip" title="'.$data['text_leave_review'].'" class="btn btn-primary"><i class="fa fa-pencil-square-o"></i></a> <a href="'.$order['view'].'" data-toggle="tooltip" title="'.$data['button_view'].'" class="btn btn-info"><i class="fa fa-eye"></i></a>';
			}
		}
		$output = str_replace($search, $replace, $output);
	}
}
