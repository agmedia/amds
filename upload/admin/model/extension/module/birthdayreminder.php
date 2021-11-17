<?php 
class ModelExtensionModuleBirthdayReminder extends Model {
	
	private $moduleName;
	private $eventGroup;
	private $modulePath;

	public function __construct($registry) {
		parent::__construct($registry);
		$this->config->load('isenselabs/birthdayreminder');
		$this->modulePath 		= $this->config->get('birthdayreminder_modulePath');
		$this->moduleName       = $this->config->get('birthdayreminder_moduleNameSmall');
		$this->eventGroup 		= $this->moduleName;
		$this->load->model('setting/event');
	}

	public function setupEventHandlers()
	{
		$this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/success/before', $this->modulePath.'/registerGuestBirthday');
		$this->model_setting_event->addEvent($this->eventGroup, 'catalog/model/account/customer/editCustomer/after', $this->modulePath.'/updateCustomerBirthday');
		$this->model_setting_event->addEvent($this->eventGroup, 'catalog/model/account/customer/addCustomer/after', $this->modulePath.'/registerUserLangToDB');	
	}

	public function removeEventHandlers()
	{

		$this->model_setting_event->deleteEventByCode($this->eventGroup);
	}

	public function getInfoForCustomerFromBRTable($customer_id) {
		$query =$this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_birthday` WHERE customer_id='".(int)$customer_id."'");
		return $query->row;
	}

	public function getInfoFromTheDB() { 
		$query =$this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_birthday` WHERE customer_id!='0'");
		return $query->rows;
	}
	
	public function getGuestBirthdays() { 
	 
		$query =$this->db->query("SELECT * FROM " . DB_PREFIX ."customer_birthday WHERE customer_id='0'"   );
		
		return $query->rows;
	}
	
	public function getCustomerBirthday($customer_id) { 
	
		$query =$this->db->query("SELECT birthday_date FROM `" . DB_PREFIX . "customer` AS c LEFT JOIN `" . DB_PREFIX . "customer_birthday` AS cb ON c.customer_id=cb.customer_id WHERE c.customer_id='".$customer_id."'") ;
		if($query->num_rows > 0){
			return $query->row['birthday_date']; 
		} else return '';
	}

	public function isAlreadySendGift($customer_id, $email) {
		$query =$this->db->query("SELECT last_gift_date FROM `" . DB_PREFIX . "customer_birthday` WHERE customer_id='".$customer_id."' AND email='".$email."'") ;
		if($query->num_rows > 0){
			return $query->row['last_gift_date']; 
		} else return '';
	}

	public function getGuestCustomerInfo($customer_email) { 
		$result = array(
			'email' => $customer_email,
			'customer_id' => 0,
			'birthday' => null,
			'firstname' => 'Guest',
			'lastname' => '',
			'last_gift_date' => null
		);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_birthday` WHERE email='".$this->db->escape($customer_email)."'");

		if (!empty($query->num_rows)) {
			$result['last_gift_date'] = $query->row['last_gift_date'];
			$result['birthday_date'] = $query->row['birthday'];
		}

		return $result;
	}
	
	public function updateCustomerBirthday($customer_id, $birthday_date) {

		$this->db->query("UPDATE `" . DB_PREFIX . "customer_birthday` SET birthday_date='" . $this->db->escape($birthday_date) ."' WHERE customer_id='" . $customer_id . "'") ;	
	}

	public function sendGiftByMail($data = array()) {
		
		$this->load->model('setting/setting');
		$this->load->model('marketing/coupon');

		if(VERSION < '2.1.0.1') {
			$this->load->model('sale/customer');
		} else {
			$this->load->model('customer/customer');
		}
		
		$setting = $this->model_setting_setting->getSetting('BirthdayReminder');

		if($setting['BirthdayReminder']['Enabled'] == 'yes') {
								
			if ($data['customer_id'] != 0) {
				if(VERSION < '2.1.0.1') {
					$customerInfo= $this->model_sale_customer->getCustomer($data['customer_id']);
				} else {
					$customerInfo= $this->model_customer_customer->getCustomer($data['customer_id']);
				}
			} else {	
				$customerInfo = array(
					'firstname' => 'Guest',
					'lastname' => ''
				);
			}

			$messageToCustomer = html_entity_decode($data['gift_message'], ENT_QUOTES, 'UTF-8');
			$wordTemplates = array("{firstname}", "{lastname}", "{voucher_code}", "{discount_value}","{total_amount}","{date_end}");
			$timeEnd =  time() + $setting['BirthdayReminder']['days_after'] * 24 * 60 * 60;
			
			$updateQuery = "INSERT INTO `" . DB_PREFIX . "customer_birthday` 
			(email, customer_id, last_gift_date)
              VALUES('".$data['to_mail']."',
              '".(int)$data['customer_id']."', 
              CURDATE()) ON DUPLICATE KEY UPDATE last_gift_date = VALUES(last_gift_date), customer_id= '".(int)$data['customer_id']."' ";

			$this->db->query($updateQuery);
			//End
			
			$words   = array($customerInfo['firstname'], $customerInfo['lastname'], $data['discount_code'],$data['discount'], $data['total_amount'],  date('Y-m-d', $timeEnd));					
			$messageToCustomer = str_replace($wordTemplates, $words, $messageToCustomer);
	
			$couponInfo  = array(
				'name'          => 'BirthdayReminder [' . $data['to_mail'].']',
				'code'          => $data['discount_code'], 
				'discount'      => $data['discount'],
				'type'          => $data['discount_type'],
				'total'         => $data['total_amount'],
				'logged'        => '1',
				'shipping'      => '0',
				'date_start'    => date('Y-m-d', time()),
				'date_end'      => date('Y-m-d', $timeEnd),
				'uses_total'    => '1',
				'uses_customer' => '1',
				'status'        => '1'
			);

			$this->model_marketing_coupon->addCoupon($couponInfo);

			$mailToUser = new Vendor\iSenseLabs\BirthdayReminder\Mail($this->config->get('config_mail_engine'));
		    $mailToUser->parameter = $this->config->get('config_mail_parameter');
		    $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		    $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
		    $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		    $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
		    $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		    if($setting['BirthdayReminder']['admin_notification'] == 'yes') {
		        $mailToUser->setBcc($this->config->get('config_email'));
		    }
	      	
	      	$mailToUser->setTo($data['to_mail']);
			$mailToUser->setFrom($this->config->get('config_email'));
			$mailToUser->setSender($this->config->get('config_email'));
			$mailToUser->setSubject(html_entity_decode($data['subject'], ENT_QUOTES, 'UTF-8'));
			$mailToUser->setHtml($messageToCustomer);
	      	$mailToUser->send(); 
		}
	}

	public function getVoucherThemes() { 
	 	return $this->db->query("SELECT * FROM " . DB_PREFIX . "voucher_theme_description")->rows;
	}
	
	public function isUniqueCode($randomCode) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code='".$this->db->escape($randomCode)."'");
			if($query->num_rows == 0) {
				return true;
						} else {
				return false;
			}	
	}

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "customer_birthday` (
			`email` varchar(100) NOT NULL DEFAULT '',
			`customer_id` int(11) DEFAULT '0',
			`birthday` date DEFAULT NULL,
			`last_gift_date` date DEFAULT NULL,
			`birthday_last_edited` date DEFAULT NULL,
			`language_id` int(1) DEFAULT NULL,
			PRIMARY KEY (`email`)
			)"
		);
	}
	
	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "customer_birthday`");
		$this->removeEventHandlers();
	}
}
