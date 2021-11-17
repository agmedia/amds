<?php 
class ModelExtensionModuleBirthdayReminder extends Model {
  public function addGuest(&$rows) {
	  if (empty($rows['firstname'])) $rows['firstname'] = 'Guest customer';
	  if (empty($rows['lastname'])) $rows['lastname'] = 'Guest customer';
  }

  public function getInfoFromTheDB($email) { 
	$query =$this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_birthday` WHERE email='".$email."'");
	return $query->row;
  }

  public function getCustomers() {
	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE custom_field IS NOT NULL");

	return $query->rows;
  }

  public function getGuestBirthdays() {    
	$query =$this->db->query("SELECT * FROM " . DB_PREFIX ."customer_birthday WHERE customer_id='0'"   );

	return $query->rows;
  }

  private function convertDateToMiliseconds($date) {
	//$date = explode('-',$date); 
	//$date = date('Y') . '-' . $date[1] . '-' . $date[2]; 
	$d = strtotime($date);
	echo "Created date is " . date("d", $d);exit;
	return strtotime($date) * 1000 ;  
  }

  public function getCustomerBirthdays() { 
	
	$this->load->model('setting/setting');
	$data['data']= $this->model_setting_setting->getSetting('BirthdayReminder');    
	$data['customers'] = $this->getCustomers();

	$birthdays = array();
	foreach($data['customers'] as $customerBirthday) {
	  if (empty($customerBirthday['custom_field'])) {
		continue;        
	  }
	  $birthDayCustomField = json_decode($customerBirthday['custom_field'],true);
	  $customerBirthday['birthday'] = $birthDayCustomField[$data['data']['BirthdayReminder']['custom_field']];
	  if(!empty($customerBirthday['birthday'])) {
		
		  $infoFromDB = array();
		  $infoFromDB = $this->getInfoFromTheDB($customerBirthday['customer_id']);
		  $customer_birthday = array_merge($infoFromDB, $customerBirthday);
		  $this->addGuest($customerBirthday);
		  $birthdays[] = $customer_birthday; 
		 
	  }
	}

	$guestBirthdays = $this->getGuestBirthdays();

	foreach($guestBirthdays as $guestBirthday) {
	  if(!empty($guestBirthday['birthday'])) {
		
		  $this->addGuest($guestBirthday);
		  $birthdays[] = $guestBirthday;  
	  }
	} 


	
	return $birthdays;
  }

  public function registerGuestBirthday($data) {
	
	$this->load->model('setting/setting'); 
	$setting = $this->model_setting_setting->getSetting('BirthdayReminder');
	if(!empty($setting['BirthdayReminder']['Enabled']) && $setting['BirthdayReminder']['Enabled'] == 'yes') {
		if(!empty($setting['BirthdayReminder']['custom_field'])) {          
		  if(is_object($data['custom_field'])) {
			$birthday = $data['custom_field']->$setting['BirthdayReminder']['custom_field'];
		  } else {
		  
			$birthday = $data['custom_field'][$setting['BirthdayReminder']['custom_field']];
		  }
		  
		  $d_quickcheckout = $this->model_setting_setting->getSetting('d_quickcheckout');
		 
		  if (!empty($d_quickcheckout['d_quickcheckout_status'])) {
			$birthday = explode('/', $birthday);
			$year = $birthday[2];
			$month = $birthday[0];
			$day = $birthday[1];
			$d_qc_date = "$year-$month-$day";
			$birthday = $d_qc_date;
		  }
		  $this->addGuestBirthday($birthday, $data['email']);
		}
	}
  }

  private function addGuestBirthday($birthday_date, $email) {   
	$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_birthday` 
		(customer_id, email, birthday)
		VALUES (
		'0', 
		'".$this->db->escape($email)."', 
		'" . $this->db->escape($birthday_date) ."')
		ON DUPLICATE KEY UPDATE birthday = '" . $this->db->escape($birthday_date) ."'"
	); 
  }
  
  public function getCustomerBirthdaysByDate($date = NULL) { 
	if(!$date) { 
	  $date = date('Y-m-d',time());
	}
	$this->load->model('setting/setting');
	$data['data']= $this->model_setting_setting->getSetting('BirthdayReminder');    
	$data['customers'] = $this->getCustomers();

	$birthdays = array();

	foreach($data['customers'] as $customerBirthday) {
	  if(VERSION < "2.1.0.1") {
		$birthDayCustomField = unserialize($customerBirthday['custom_field']);
	  } else {
		$birthDayCustomField = json_decode($customerBirthday['custom_field'],true);
	  }
		$customerBirthday['birthday'] = !empty($birthDayCustomField[$data['data']['BirthdayReminder']['custom_field']]) ? $birthDayCustomField[$data['data']['BirthdayReminder']['custom_field']] : '' ;
	  
	  
	  if(!empty($customerBirthday['birthday']) && $customerBirthday['birthday'] > 0) {
		if (strpos($customerBirthday['birthday'],'/') !== false) {
		  $birthDayInMill = strtotime(str_replace('/', '-', $customerBirthday['birthday']));
		} else {
		  $birthDayInMill = strtotime($customerBirthday['birthday']);
		}

		if(date("d", $birthDayInMill) == date("d",strtotime($date)) && date("m", $birthDayInMill) == date("m",strtotime($date)) ) {
		  $infoFromDB = array();
		  $infoFromDB = $this->getInfoFromTheDB($customerBirthday['email']);
		  $customer_birthday = array_merge($infoFromDB, $customerBirthday);
		  $this->addGuest($customerBirthday);
		  $birthdays[] = $customer_birthday;  
		}
		 
	  }
	}

	$guestBirthdays = $this->getGuestBirthdays();

	foreach($guestBirthdays as $guestBirthday) {
	  if(!empty($guestBirthday['birthday'])) {
		if (strpos($guestBirthday['birthday'],'/') !== false) {
		  $birthDayInMill = strtotime(str_replace('/', '-', $guestBirthday['birthday']));
		} else {
		  $birthDayInMill = strtotime($guestBirthday['birthday']);
		}
		
		if(date("d", $birthDayInMill) == date("d",strtotime($date)) && date("m", $birthDayInMill) == date("m",strtotime($date)) ) { 
		  $this->addGuest($guestBirthday);
		  $birthdays[] = $guestBirthday;  
		}
	  }
	} 
	
	return $birthdays;
  }
  
  public function getCustomerBirthday($customer_id) { 

	$query =$this->db->query("SELECT birthday_date FROM `" . DB_PREFIX . "customer` AS c LEFT JOIN `" . DB_PREFIX . "customer_birthday` AS cb ON c.customer_id=cb.customer_id WHERE c.customer_id='".$customer_id."'") ;
	return $query->row['birthday_date']; 
  }
  
  public function getCustomerInfo($customer_id) { 

	$query =$this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` AS c LEFT JOIN `" . DB_PREFIX . "customer_birthday` AS cb ON c.customer_id=cb.customer_id WHERE c.customer_id='".$customer_id."'") ;
	return $query->row;
  }
  
  public function sendGiftByMail($data = array()) { 
	
	$timeEnd = time() + $data['days_after'] * 24 * 60 * 60;
			
	//Version 1.1
	$this->load->model('setting/setting');
	$setting = $this->model_setting_setting->getSetting('BirthdayReminder');

	$dateEnd = date('Y-m-d', $timeEnd);

	$this->load->model('account/customer');
	$registered_customer = $this->model_account_customer->getCustomerByEmail($data['to_mail']);
	$customer_id = $data['customer_id'];

	if (!empty($registered_customer)) {
		$this->db->query("UPDATE `" . DB_PREFIX . "customer_birthday` SET `customer_id` = ".(int)$registered_customer['customer_id']." WHERE email ='".$this->db->escape($data['to_mail'])."'");
		$customer_id = $registered_customer['customer_id'];
	}

	$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_birthday` 
	  	(customer_id, email, last_gift_date)
		VALUES ('".(int)$customer_id."', 
		'".$this->db->escape($data['to_mail'])."', 
		CURDATE()) ON DUPLICATE KEY UPDATE last_gift_date = CURDATE()"
	); 
	//End
	
	$messageToCustomer = html_entity_decode($data['gift_message'], ENT_QUOTES, 'UTF-8');
	$wordTemplates = array("{firstname}", "{lastname}", "{discount_code}", "{discount_value}", "{total_amount}","{date_end}");
	$words   = array($data['firstname'], $data['lastname'], $data['discount_code'],$data['discount'], $data['total_amount'], $dateEnd);         
	$messageToCustomer = str_replace($wordTemplates, $words, $messageToCustomer);

	$couponInfo  = array(
		'name'          => 'Birthdayreminder [' . $data['to_mail'].']',
		'code'          => $data['discount_code'], 
		'discount'      => $data['discount'],
		'type'          => $data['discount_type'],
		'total'         => $data['total_amount'],
		'logged'        => '1',
		'shipping'      => '0',
		'date_start'    => date('Y-m-d', time()),
		'date_end'      =>  date('Y-m-d', $timeEnd),
		'uses_total'    => '1',
		'uses_customer' => '1',
		'status'        => '1'
	);

	$this->addCoupon($couponInfo);
		$mailToUser = new Vendor\iSenseLabs\BirthdayReminder\Mail($this->config->get('config_mail_engine'));
		$mailToUser->parameter = $this->config->get('config_mail_parameter');
		$mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
		$mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
		$mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		if($data['admin_notification'] == 'yes') {
		   $mailToUser->setBcc($this->config->get('config_email'));
		}
		$mailToUser->setTo($data['to_mail']);
		$mailToUser->setFrom($this->config->get('config_email'));
		$mailToUser->setSender($this->config->get('config_email'));
		$mailToUser->setSubject(html_entity_decode($data['subject'], ENT_QUOTES, 'UTF-8'));
		$mailToUser->setHtml($messageToCustomer);
		$mailToUser->send(); 
	}
  
  public function sendAdminNotification($customerList, $notificationType = NULL) {

	$this->load->model('setting/setting');
	$setting = $this->model_setting_setting->getSetting('BirthdayReminder');
	$birthdaysList = '';
	if($setting['BirthdayReminder']['Enabled'] == 'yes') {
	  foreach($customerList as $customer){
		if($customer['birthday'] > 0) {
		  if (strpos($customer['birthday'],'/') !== false) {
			$birthDayInMill = strtotime(str_replace('/', '-', $customer['birthday']));
		  } else {
			$birthDayInMill = strtotime($customer['birthday']);
		  }

		  $dateArray = explode('-', date("d-m-Y", $birthDayInMill));

		  $birthdaysList .= '<tr><td style="border: 1px solid #eee; padding: 10px;">' . $dateArray[0] . '.' . $dateArray[1] .  '</td><td style="border: 1px solid #eee; padding: 10px;">' . $customer['firstname'] . ' ' . $customer['lastname'] . '</td><td style="border: 1px solid #eee; padding: 10px;">' . $customer['email'] . '</td></tr>'  ;
		}
	  }

	  $table_head =  '<table style="border-collapse:collapse; width:100%;">';
	  if($notificationType == "W") {
		if(!empty($customerList)) {
		  $messageToCustomer = html_entity_decode("Birthdays this week:<br /><br />". $table_head .$birthdaysList . '</table>', ENT_QUOTES, 'UTF-8');       
		} else {
		  $messageToCustomer = html_entity_decode("There are no birthdays this week:<br /><br />". $table_head . $birthdaysList . '</table>', ENT_QUOTES, 'UTF-8');
		}
	  }
	  
	  if($notificationType == "D") {
		if(!empty($customerList)) {
		  $messageToCustomer = html_entity_decode("Birthdays today:<br /><br />". $table_head .$birthdaysList . '</table>', ENT_QUOTES, 'UTF-8');       
		} else {
		  $messageToCustomer = html_entity_decode("There are no birthdays today:<br /><br />". $table_head .$birthdaysList . '</table>', ENT_QUOTES, 'UTF-8');
		}
	  }

	  $mailToUser = new Vendor\iSenseLabs\BirthdayReminder\Mail($this->config->get('config_mail_engine'));
		$mailToUser->parameter = $this->config->get('config_mail_parameter');
		$mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
		$mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
		$mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
	
	 	$mailToUser->setTo($this->config->get('config_email'));
	  	$mailToUser->setFrom($this->config->get('config_email'));
	  	$mailToUser->setSender($this->config->get('config_email'));
	  	$mailToUser->setSubject(html_entity_decode("BirthdayReminder", ENT_QUOTES, 'UTF-8'));
	  	$mailToUser->setHtml($messageToCustomer);
	  	$mailToUser->send();  
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
  
  private function addCoupon($data) {
	$this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['shipping'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
  }

  public function getSetting($group, $store_id) {
	$data = array(); 
	
	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `group` = '" . $this->db->escape($group) . "'");
	
	foreach ($query->rows as $result) {
	  if (!$result['serialized']) {
		$data[$result['key']] = $result['value'];
	  } else {
		$data[$result['key']] = unserialize($result['value']);
	  }
	}
	return $data;
  }
}
