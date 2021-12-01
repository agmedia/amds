<?php
class ModelExtensionModuleOrderReviews extends Model {

	public function sendMail($data = array()) {
		$store_data = $this->getCurrentStore($data['store_id']);
		$this->load->config('isenselabs/orderreviews');
		$this->load->model('setting/setting');

		$mailToUser = new Vendor\iSenseLabs\OrderReviews\Mail($this->config->get('config_mail_engine'));
        $mailToUser->parameter = $this->config->get('config_mail_parameter');
        $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
        $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
        $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mailToUser->setTo($data['email']);
		$mailToUser->setFrom($store_data['store_email']);
		$mailToUser->setSender(html_entity_decode($store_data['name'], ENT_QUOTES, 'UTF-8'));
		$mailToUser->setSubject(html_entity_decode($data['subject'], ENT_QUOTES, 'UTF-8'));
		$mailToUser->setHtml($data['message']);

		if(!$this->checkDbTable('orderreviews_setting')){
			$moduleSettings = $this->model_setting_setting->getSetting('orderreviews', $data['store_id']);
		} else {
			$moduleSettings = $this->getSetting('orderreviews', $data['store_id']);
		}

		if(isset($moduleSettings['orderreviews']['BCC']) && $moduleSettings['orderreviews']['BCC'] == 'yes') {
			$mailToUser->setBcc($store_data['store_email']);
		}

		$mailToUser->send();

		if ($mailToUser)
			return true;
		else
			return false;
	}

	public function getOrders($orderID, $dayLimit, $dateType = 'date_modified',$store_id) {
		$query =  $this->db->query("
			SELECT * FROM `" . DB_PREFIX . "order`
				WHERE `order_status_id`=" . (int)$orderID . "
				AND store_id = " . (int)$store_id . "
				AND DATE(`" . $dateType . "`) = '" . date("Y-m-d ", strtotime('-' . $dayLimit . ' days')) . "'
				AND order_id NOT IN (SELECT order_id FROM `" . DB_PREFIX . "orderreviews_mail_log`)"
		);

		return $query->rows;
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT DISTINCTROW product_id, name, order_id FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' ORDER BY product_id");
		return $query->rows;
	}

	public function getAccountOrders($orderID, $dayLimit, $dateType = 'date_modified', $store_id) {
		$order_data = array();

		$query =  $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order`
			WHERE `order_status_id`=".$orderID." AND DATE(`". $dateType ."`) <= '".date("Y-m-d ",strtotime('-'.$dayLimit.' days'))."' AND store_id =".$store_id);

		foreach ($query->rows as $result) {
			$order_data[] = $result['order_id'];
		}

		return $order_data;
	}

	public function loadLanguage($directory, $filename) {

		$default = 'en-gb/'.$this->config->get('orderreviews_path');

		$data = array();

		$file = DIR_LANGUAGE . $directory . '/'. $filename.'.php';

		if (file_exists($file)) {
			$_ = array();

			require($file);
			 $data = array_merge($data, $_);
			return $data;
		}

		$file = DIR_LANGUAGE . $default .'.php';

		if (file_exists($file)) {
			$_ = array();
			require($file);
			$data = array_merge($data, $_);
			return $data;
		} else {
			trigger_error('Error: Could not load language ' . $filename . '!');
		//	exit();
		}
	}

	// Coupons
	public function generateuniquerandomcouponcode() {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$couponCode = '';
		for ($i = 0; $i < 10; $i++) {
			$couponCode .= $characters[rand(0, strlen($characters) - 1)];
		}
		if($this->isUniqueCode($couponCode)) {
			return $couponCode;
		} else {
			return $this->generateuniquerandomcouponcode();
		}
	}

	public function isUniqueCode($randomCode) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code='".$this->db->escape($randomCode)."'");
				if($query->num_rows == 0) {
					return true;
							} else {
					return false;
				}
	}

	public function addCoupon($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['shipping'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

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
	}

	private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTP_SERVER;
        } else {
            $storeURL = HTTPS_SERVER;
        }
        return $storeURL;
    }

	public function getStore($store_id) {
        if($store_id && $store_id != 0) {
            $store = $this->getStoreData($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

	private function getStoreData($store_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . (int)$store_id . "'");

		return $query->row;
	}

	public function fetchForm($filename) {
		$data = array();
		$file = DIR_APPLICATION.'view/theme/'. $filename;

		if (file_exists($file)) {
			extract($data);
			ob_start();
			include($file);
			$content = ob_get_clean();
			return $content;
		} else {
			trigger_error('Error: Could not load template ' . $file . '!');
			exit();
		}
	}

	private $moduleName = 'orderreviews';
	private $moduleModel = 'model_module_orderreviews';

	public function sendReviewMail($order_id, $order_status_id) {

		$this->config->load('isenselabs/orderreviews');
		$this->load->model('setting/setting');
		$this->load->model('setting/store');
		$this->load->model('checkout/order');
		$this->load->model('localisation/language');

		$order = $this->model_checkout_order->getOrder($order_id);
		$stores = array_merge(array(0 => $this->getStore(0)), $this->model_setting_store->getStores());

		foreach ($stores as $store) {
			if ($order['store_id'] != $store['store_id']) {
				continue;
			}

			if(!$this->checkDbTable('orderreviews_setting')){
				$setting = $this->model_setting_setting->getSetting($this->moduleName, $store['store_id']);
			} else {
				$setting = $this->getSetting($this->moduleName, $store['store_id']);
			}
			$moduleData = isset($setting[$this->moduleName]) ? $setting[$this->moduleName] : array();

			if (!empty($moduleData['Enabled']) && $moduleData['Enabled'] == 'yes' && isset($moduleData['ReviewMail'])) {
				foreach ($moduleData['ReviewMail'] as $reviewmail) {
					if ($reviewmail['Enabled']=='yes' && $reviewmail['Delay']=='0' && $reviewmail['OrderStatusID']==$order_status_id) {

						if (!(($reviewmail['CustomerGroupID'] == 'send_all') || ($reviewmail['CustomerGroupID'] != 'send_all' && $reviewmail['CustomerGroupID']==$order['customer_group_id']))) {
							break;
						}

						$OrderLanguage = $this->model_localisation_language->getLanguage($order['language_id']);

						$LangVars = $this->loadLanguage($OrderLanguage['code'].'/extension/module','orderreviews');
						$OrderProducts = $this->getOrderProducts($order['order_id']);

						$query_reviewed = $this->db->query("SELECT DISTINCT product_id FROM " . DB_PREFIX . "orderreviews_log as ol LEFT JOIN " . DB_PREFIX . "order as o ON ol.order_id = o.order_id LEFT JOIN " . DB_PREFIX . "order_product as op ON ol.order_id = op.order_id WHERE o.email = '" . $order['email'] . "' ORDER BY product_id");

						$reviewedProducts = array();

						foreach ($query_reviewed->rows as $rows){
							$reviewedProducts[]=$rows['product_id'];

						}

						$currentProducts = array();

						foreach($OrderProducts as $products ){
							if(!in_array($products['product_id'],$reviewedProducts)){
								$currentProducts[] = $products;
							}
						}

						if(!empty($currentProducts)) {
							$Products = '';
							$ProductIDs = '';
							if (sizeof($currentProducts)==1) {
								$Products = '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $currentProducts[0]['product_id'].'">'.$currentProducts[0]['name'].'</a>';

								$ProductIDs = $currentProducts[0]['product_id'];
							} else {
								for ($i=0; $i<sizeof($currentProducts); $i++) {
									if (($i+1) == sizeof($currentProducts)) {
										$Products .= ' '.$LangVars['text_and'].' ';
									}  else if (($i+1) < sizeof($currentProducts) && ($i>0)) {
										$Products .= ', ';
									}
									$Products .= '<a href="'.$store['url'].'index.php?route=product/product&amp;product_id=' . $currentProducts[$i]['product_id'].'">'.$currentProducts[$i]['name'].'</a>';
									$ProductIDs .= $currentProducts[$i]['product_id'];

									if (!(($i+1) == sizeof($currentProducts)))
											$ProductIDs .= '_';
								}
							}

							$subject_original = array('{first_name}','{last_name}', '{order_id}');
							$subject_replace = array($order['firstname'], $order['lastname'], $order_id);
							$Subject = str_replace($subject_original, $subject_replace, $reviewmail['Subject'][$order['language_id']]);
							$Message = html_entity_decode($reviewmail['Message'][$order['language_id']]);
							$FirstName = $order['firstname'];
							$LastName = $order['lastname'];
							$Email = $order['email'];

							$SubmitLink = $store['url'].'index.php?route='.$this->config->get('orderreviews_path').'/sendReview';
							$params = 'order_id='.$order['order_id'].'&reviewmail_id='.$reviewmail['id'].'&store_id='.$store['store_id'];
							$ReveiewMailLink = $store['url'].'index.php?route='.$this->config->get('orderreviews_path').'/sendReview&params='.base64_encode($params);

							//$MainFormData = $this->fetchForm('default/template/module/orderreviews_review_email_form.twig');
							$ProductFormData = $this->fetchForm('default/template/'.$this->config->get('orderreviews_path').'/orderreviews_product_form_include.twig');
							$ProductsViews = "";


							if ($reviewmail['ReviewType'] == 'per_purchase') {
								$tempVar = '';
								$old = array("{number}","{pr_name}","{pr_id}","{image}");
								$new = array('0','','0',NULL);
								$tempVar = str_replace($old, $new, $ProductFormData);
								$ProductsViews .= $tempVar;
							} else if ($reviewmail['ReviewType'] == 'per_product') {
								if (sizeof($currentProducts)>0) {
										for ($i=0; $i<sizeof($currentProducts); $i++) {
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


							if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){

								$MainFormData = $this->fetchForm('default/template/'.$this->config->get('orderreviews_path').'/orderreviews_review_email_form.twig');

								$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}","{reviewmail_link_href}","{link_button_text}","{store_url}","{star_1}","{star_2}","{star_3}","{star_4}","{star_5}");
								$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews, $ReveiewMailLink,$LangVars['text_leave_review'],$store['url'],$LangVars['star_1'],$LangVars['star_2'],$LangVars['star_3'],$LangVars['star_4'],$LangVars['star_5']);

							}else{
								$privacyCheckbox = $this->privacyCheckbox($moduleData['PrivacyPolicy']);
								$MainFormData = $this->fetchForm('default/template/'.$this->config->get('orderreviews_path').'/orderreviews_product_form_main.twig');

								$form_pattern = array("{submit_link}","{first_name}", "{last_name}", "{customer_id}", "{text_submit}", "{text_review}", "{privacy_aggreement}", "{product_id}", "{order_id}", "{customer_name}", "{reviewmail_id}", "{email}","{product_info}");
								$form_replacements = array($SubmitLink, $FirstName, $LastName, base64_encode($order['customer_id']), $LangVars['text_submit'], $LangVars['text_review'], $privacyCheckbox, $ProductIDs, base64_encode($order['order_id']), $FirstName.' '.$LastName, $reviewmail['id'], base64_encode($Email), $ProductsViews);

							}

							$ReviewForm = str_replace($form_pattern, $form_replacements, $MainFormData);

							if(!empty($moduleData['EmailType']) && $moduleData['EmailType'] == 'link'){
								$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}','{reviewmail_link}');
								$replacements = array($FirstName, $LastName, $ReviewForm, $Products, $order['order_id'],$LangVars['link_replacement']);
							}else {
								$patterns = array('{first_name}', '{last_name}', '{review_form}', '{order_products}', '{order_id}', '{reviewmail_link}', '{reviewmail_link_href}');
								$replacements = array($FirstName, $LastName, $ReviewForm, $Products,$order['order_id'], $LangVars['text_reviewmail_link'], $ReveiewMailLink);
							}

							$HTMLMail = str_replace($patterns, $replacements, $Message);

							$newMail = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
								<html xmlns="http://www.w3.org/1999/xhtml">
								<head>
									<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
									<title>OrderReviews</title>
								</head>
								<body>'.$HTMLMail.'</body></html>';

							$MailData = array(
								'email' =>  $Email,
								'message' => $newMail,
								'subject' => $Subject,
								'store_name' => $store['name'],
								'store_id' => $store['store_id']);

							if (sizeof($currentProducts)>0) {
								$emailResult = $this->sendMail($MailData);
								if  (isset($moduleData['LOG']) && $moduleData['LOG'] == 'yes') {
									$OrderDate = date('Y-m-d H:i:s', time());
									$this->db->query("INSERT INTO `" . DB_PREFIX . "orderreviews_mail_log` SET `date`='".$this->db->escape($OrderDate)."', `store_id`='".$this->db->escape($store['store_id'])."', `order_id`='".$this->db->escape($order['order_id'])."'");
								}
							}
						}
					}
				}
			}
		}

	}

	private function getCurrentStore($store_id) {
        if($store_id && $store_id != 0) {
            $store = $this->getStore($store_id);
            $store['store_email'] = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `key`= 'config_email' AND `store_id`=".$this->db->escape($store_id))->row['value'];
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
            $store['store_email'] = $this->config->get('config_email');
        }
        return $store;
    }
	public function updateReviewLog($log_id,$discount_code)
	{
		$this->db->query("UPDATE `" . DB_PREFIX . "orderreviews_log` SET `review_coupon` = '".$discount_code."' WHERE log_id = ".(int)$log_id);
	}
	public function addReviewLog($order_id,$data,$store_id,$review_id) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "orderreviews_log SET order_id = '" . $this->db->escape($order_id) . "', review_id = '" . $this->db->escape($review_id) . "', customer_name = '" . $this->db->escape($data['name']) . "', review_product_id = '" . (int)($data['product_id']) . "', review_rating = '" . (int)($data['rating']) . "', store_id = '" . (int)($store_id) . "', privacy_policy = '" . (int)$data['privacy_policy'] . "', date_created = NOW()");

		if ($data['privacy_policy'] && is_file(DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR . 'gdpr.php')) {
        	$order = $this->db->query("SELECT email FROM `" . DB_PREFIX . "order` WHERE order_id = '" . $this->db->escape($order_id) . "'");
            $this->load->library('gdpr');
            $this->gdpr->newOptin($this->config->get('config_account_id'), $order->row['email'], 'OrderReviews');
        }
	}

	public function checkReviewLog($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "orderreviews_log` WHERE order_id='".$this->db->escape($order_id)."'");
			if($query->num_rows > 0) {
				return true;
			} else {
				return false;
			}
	}

	public function getOrderCustomerGroup($order_id)
	{
		if ($order_id) {
			$query = $this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "order` WHERE order_id = ".(int)$order_id);
			return $query->num_rows ? $query->row['customer_group_id'] : 0 ;
		}
	}

	public function privacyCheckbox($status = 0) {
		$privacyCheckbox = '';

		if (!empty($status)) {
			$this->load->language('extension/module/orderreviews');
			$this->load->model('catalog/information');
			$information = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if (!empty($information['information_id'])) {
				$privacyCheckbox = '<label id="ORPrivacyPolicy" style="font-family:Verdana;font-size:13px;letter-spacing:0"><input type="checkbox" name="privacy_policy" value="1"> ' . sprintf($this->language->get('text_privacy_agreement'), $this->getCatalogURL() . 'index.php?route=information/information&information_id=' . $information['information_id'], $information['title']) . '</label><br><br>';
			}
		}

		return $privacyCheckbox;
	}

	public function addReview($product_id, $data) {
		if ($data['AutoApproved'] == 'yes' && ($data['rating'] >= $data['AutoApprovedStar'])) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "review SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$data['customer_id'] . "', product_id = '" . (int)$product_id . "', text = '" . $this->db->escape($data['text']) . "', rating = '" . (int)$data['rating'] . "', status = '1', date_added = NOW()");
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "review SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$data['customer_id'] . "', product_id = '" . (int)$product_id . "', text = '" . $this->db->escape($data['text']) . "', rating = '" . (int)$data['rating'] . "', date_added = NOW()");
		}

		$review_id = $this->db->getLastId();

		if (in_array('review', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/review');
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($product_id);

			$subject = sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));

			$message  = $this->language->get('text_waiting') . "\n";
			$message .= sprintf($this->language->get('text_product'), html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_reviewer'), html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_rating'), $data['rating']) . "\n";
			$message .= $this->language->get('text_review') . "\n";
			$message .= html_entity_decode($data['text'], ENT_QUOTES, 'UTF-8') . "\n\n";

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject($subject);
			$mail->setText($message);
			$mail->send();

			// Send to additional alert emails
			$emails = explode(',', $this->config->get('config_mail_alert_email'));

			foreach ($emails as $email) {
				if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->setTo($email);
					$mail->send();
				}
			}
		}

		return $review_id;
	}

	public function getProductsInIDArray($products = array()) {
		if(!empty($products)) {
			return $this->db->query('SELECT p.product_id, p.image, pd.name  FROM `' . DB_PREFIX .'product` p ' . 
				'LEFT JOIN `'  . DB_PREFIX . 'product_description` AS pd ON (pd.language_id = ' . $this->config->get('config_language_id') . ' AND p.product_id = pd.product_id) ' . 
				((count($products) == 1) ?
					'WHERE p.product_id = ' . $products[0] :
					'WHERE p.product_id IN (' . implode(',', $products) . ')'
				)
			)->rows;
		} else {
			return array();
		}
	}

	public function getCategoriesByID($categories = array()) {
		if(!empty($categories)) {
			return $this->db->query('SELECT c.category_id, name FROM ' . DB_PREFIX . 'category c ' . 
				'JOIN '  . DB_PREFIX . 'category_description AS cd on c.category_id=cd.category_id ' . 
				((count($categories) == 1) ?
					'WHERE c.category_id = ' . $categories[0] :
					'WHERE c.category_id IN (' . implode(',', $categories) . ')'
				) . 
				' AND language_id = ' . $this->config->get('config_language_id')
			)->rows;
		} else {
			return NULL;
		}
	}

	public function checkDbTable($table) {
		// return TRUE if exist, FALSE if table is not exist
		$result = $this->db->query("SHOW TABLES LIKE '". DB_PREFIX . $table . "'");
		return ($result->num_rows != '1') ? FALSE : TRUE;
	}

	public function getSetting($code, $store_id = 0) {
		$setting_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "orderreviews_setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

		foreach ($query->rows as $result) {
			if (!$result['serialized']) {
				$setting_data[$result['key']] = $result['value'];
			} else {
				$setting_data[$result['key']] = json_decode($result['value'], true);
			}
		}
		
		$mailTemplates = array();
		$mailQuery = $this->db->query("SELECT * FROM " . DB_PREFIX . "orderreviews_mail WHERE store_id = '" . (int)$store_id . "' AND `code` = 'orderreviewsMailTemplate' ORDER BY mail_id ASC");

		foreach ($mailQuery->rows as $result) {
			if (!$result['serialized']) {
				$mailTemplates[$result['mail_id']] = $result['value'];
			} else {
				$mailTemplates[$result['mail_id']] = json_decode($result['value'], true);
			}
		}

		$setting_data[$code]['ReviewMail'] = $mailTemplates;

		return $setting_data;
	}
}
