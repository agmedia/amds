<?php
class ControllerExtensionModuleBirthdayReminder extends Controller
{
	private $data = array();
	private $moduleName;
	private $moduleNameSmall;
	private $moduleVersion;
	private $modulePath;
	private $moduleModel;
	private $callModel;
	private $extensionLink;

	public function __construct($registry) {
		parent::__construct($registry);
		$this->config->load('isenselabs/birthdayreminder');

		$module = 'birthdayreminder_';
		$this->moduleName       = $this->config->get('birthdayreminder_moduleName');
		$this->moduleNameSmall 	= $this->config->get($module.'moduleNameSmall');
		$this->moduleVersion 	= $this->config->get($module.'moduleVersion');
		$this->modulePath 		= $this->config->get($module.'modulePath');
		$this->callModel 		= $this->config->get($module.'callModel');
		$this->extensionLink 	= $this->url->link($this->config->get($module.'extensionLink'), 'user_token=' . $this->session->data['user_token'].$this->config->get($module.'extensionLink_type'), 'SSL');

		// Load Language
        $this->language = $this->load->language($this->modulePath, $this->moduleNameSmall);
        $this->language = $this->language[$this->moduleNameSmall];
        $this->data = $this->language->all();

		//Load Model
		$this->load->model($this->modulePath );
		//Model Instance
		$this->moduleModel 		= $this->{$this->callModel};


		//MODULE
		$this->document->addScript('view/javascript/summernote/summernote.min.js');
		$this->document->addStyle('view/javascript/summernote/summernote.css');	
		$this->document->addScript('view/javascript/birthdayreminder/underscore.min.js');
		$this->document->addScript('view/javascript/birthdayreminder/calendar.js');
		$this->document->addScript('view/javascript/birthdayreminder/jquery-ui.min.js');
		$this->document->addScript('view/javascript/birthdayreminder/timepicker.js');
		$this->document->addStyle('view/stylesheet/birthdayreminder/birthdayreminder_calendar.css');
		$this->document->addStyle('view/stylesheet/birthdayreminder/birthdayreminder.css');
		$this->document->addStyle('view/stylesheet/birthdayreminder/jquery-ui.min.css');
		$this->document->addStyle('view/stylesheet/birthdayreminder/jquery-ui.theme.min.css');


		$this->load->model('setting/setting');
		$this->load->model('localisation/language');

		$this->data['modulePath'] = $this->modulePath;
		$this->data['moduleName']        = $this->moduleName;
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		
	}


	public function index() {
		$this->data['error_warning'] = '';
		$this->data['user_token'] = $this->session->data['user_token'];
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
				$this->request->post['BirthdayReminder']['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
			}
			if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
				$this->request->post['BirthdayReminder']['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
			}

			$this->model_setting_setting->editSetting('BirthdayReminder', $this->request->post);			

			$this->session->data['success'] = $this->language->get('text_success');
			if (!empty($_GET['activate'])) {
				$this->session->data['success'] = $this->language->get('text_success_activation');
			}
			$selectedTab = (empty($this->request->post['selectedTab'])) ? 0 : $this->request->post['selectedTab'];
			// Upgrade table not needed in version > 3.6
			// $this->upgradeTable();

			$this->moduleModel->removeEventHandlers();
			if ($this->request->post[$this->moduleName]['Enabled'] == 'yes'){
                $this->model_setting_setting->editSetting('module_'.strtolower($this->moduleName), array('module_'.strtolower($this->moduleName).'_status' => 1));
                $this->moduleModel->setupEventHandlers();
            } else {
                $this->model_setting_setting->editSetting('module_'.strtolower($this->moduleName), array('module_'.strtolower($this->moduleName).'_status' => 0));
            }
			$this->response->redirect($this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'] . '&tab=' . $selectedTab, 'SSL'));
		}

		
		$this->data['currency'] = $this->config->get('config_currency');

		$this->data['heading_title'] .= " " . $this->moduleVersion;

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}
		
		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs']   = array();
		$this->data['breadcrumbs'][] = array(
			'text' => $this->registry->get('language')->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
			);

		$this->data['breadcrumbs'][] = array(
			'text' => $this->registry->get('language')->get('text_module'),
			'href' => $this->extensionLink,
			'separator' => ' :: '
			);
		
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
			);

		$this->data['action']        = $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL');
		$this->data['cancel']        = $this->extensionLink;

		if (isset($this->request->post['BirthdayReminder'])) {
			foreach ($this->request->post['BirthdayReminder'] as $key => $value) {
				$this->data['data']['BirthdayReminder'][$key] = $this->request->post['BirthdayReminder'][$key];
			}
		} else {
			$configValue                            = $this->config->get('BirthdayReminder');
			$this->data['data']['BirthdayReminder'] = $configValue;
		}

		if(VERSION < '2.1.0.1') {
			$this->load->model('sale/custom_field');
			$this->data['custom_fields'] = $this->model_sale_custom_field->getCustomFields();
		} else {
			$this->load->model('customer/custom_field');
			$this->data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();
		}
		
		
		
		$this->data['config_email'] = $this->config->get('config_email');
		$this->data['languages']              = $this->model_localisation_language->getLanguages();

		foreach($this->data['languages'] as &$language) {
			$language['flag_url'] = version_compare(VERSION, '2.2.0.0', "<") ? 'view/image/flags/' . $language['image'] : 'language/' . $language['code'] . '/' . $language['code'] . '.png';
		}

		$this->data['default_date_format'] = $this->language->get('date_format_short');
		$this->data['data'] = $this->model_setting_setting->getSetting('BirthdayReminder');
		$this->data['header'] = $this->load->controller('common/header');
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['footer'] = $this->load->controller('common/footer');

		

		if (isset($this->data['data'][$this->moduleName])) {
            // Module Unifier
            $this->data['moduleData'] = $this->data['data'][$this->moduleName];
            // Module Unifier
        }

		if (empty($this->data['moduleData']['LicensedOn'])) {
        	$hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;
            $this->data['hostname'] = (strstr($hostname, 'http://') === false) ? 'http://' . $hostname : $hostname;
        	$this->data['domHostname'] 	= base64_encode($this->data['hostname']);
        	$this->data['b64'] = base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Vfc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=');
        } else {
        	$this->data['cHRpbWl6YXRpb24ef4fe'] = base64_encode(json_encode($this->data['data'][$this->moduleName]['License']));
            $this->data['dateExpires'] = date("F j, Y", strtotime($this->data['data'][$this->moduleName]['License']['licenseExpireDate']));
        }
        $this->data['supportTicketURL'] = 'https://isenselabs.com/tickets/open/' . base64_encode('Support Request').'/'.base64_encode('386').'/'. base64_encode($_SERVER['SERVER_NAME']);


		//Tabs
        $this->data['tab_controlpanel'] = $this->load->view($this->modulePath.'/tab_settings', $this->data);
        $this->data['tab_calendar'] = $this->load->view($this->modulePath.'/tab_calendar', $this->data);
        $this->data['tab_template'] = $this->load->view($this->modulePath.'/tab_template', $this->data);
        $this->data['tab_support'] = $this->load->view($this->modulePath.'/tab_support', $this->data);

		$this->response->setOutput($this->load->view($this->modulePath, $this->data));
	}
	
	public function getBirthdays() {
		
		$this->load->model('setting/setting');

		if(VERSION < '2.1.0.1') {
			$this->load->model('sale/customer');
		} else {
			$this->load->model('customer/customer');
		}

		$this->load->model($this->modulePath);

		$this->data['data']= $this->model_setting_setting->getSetting('BirthdayReminder');		

		if(VERSION < '2.1.0.1') {
			$this->data['customers'] = $this->model_sale_customer->getCustomers();
		} else {
			$this->data['customers'] = $this->model_customer_customer->getCustomers();
		}

		$json["success" ] = '1'; 
		$events= array();
		foreach($this->data['customers'] as $customerBirthday) {

			$birthDayCustomField = json_decode($customerBirthday['custom_field'], true);
			if(!empty($this->data['data']['BirthdayReminder']['custom_field']) && !empty($birthDayCustomField[$this->data['data']['BirthdayReminder']['custom_field']])) {
				$customerBirthday['birthday_date'] = $birthDayCustomField[$this->data['data']['BirthdayReminder']['custom_field']];
			}
			
			if(!empty($customerBirthday['birthday_date']) && $customerBirthday['birthday_date'] > 0) {
				$birthdayTimestamp = $this->convertDateToMiliseconds($customerBirthday['birthday_date']);
				
				$infoFromDB = $this->moduleModel->getInfoFromTheDB();
				

				if(!empty($infoFromDB)) {
					foreach($infoFromDB as $dbrow) {

						if($dbrow['email'] == $customerBirthday['email'] && !empty($dbrow['last_gift_date'])) {							
							if ($this->isDateInThisYear($dbrow['last_gift_date'])) {
								$class = ' fa fa-check';
							} else {
								$class = ' fa fa-birthday-cake';
							}
						}  else {
							$class = ' fa fa-birthday-cake';
						}

					}					
				} else {
					$class = ' fa fa-birthday-cake';
				}

				$events[] = array(
					"id" => $customerBirthday['customer_id'], 
					"title" => $customerBirthday['firstname'] . ' ' . $customerBirthday['lastname'], 
					"url" => $this->url->link($this->modulePath."/sendGift","user_token=".$this->session->data['user_token']."&customer_id=".$customerBirthday['customer_id'],"SSL"),
					"class" => $class, 
					"start" => $birthdayTimestamp, 
					"end" => $birthdayTimestamp + 2);  
			}

		}	

		$guestBirthdays = $this->moduleModel->getGuestBirthdays();

		foreach($guestBirthdays as $guestBirthday) {
			if(!empty($guestBirthday['birthday']) && $guestBirthday['birthday'] > 0) { 
				$birthdayTimestamp = $this->convertDateToMiliseconds($guestBirthday['birthday']);
				if ($this->isDateInThisYear($guestBirthday['last_gift_date'])) {
					$class = ' fa fa-check';
				} else {
					$class = ' fa fa-birthday-cake';
				}

				$events[] = array(
					"id" => $guestBirthday['customer_id'], 
					"title" => 'Guest ' . $guestBirthday['email'], 
					"url"=> HTTP_SERVER.'index.php?route='.$this->modulePath.'/sendGift&user_token=' . $this->session->data['user_token'].'&customer_email='.urlencode($guestBirthday['email']),
					"class" => $class, 
					"start" => $birthdayTimestamp, 
					"end" => $birthdayTimestamp + 2
				);
			}
		}
		$json["result"] = $events;
		$this->response->setOutput(json_encode($json));	
	}
	
	public function sendGift() {
		
		$this->load->model($this->modulePath);
		$this->load->model('setting/setting');
		$this->load->language($this->modulePath);	
		$this->data['data'] = $this->model_setting_setting->getSetting('BirthdayReminder');

		if(isset($this->request->get['customer_id'])) {
			if(VERSION < '2.1.0.1') {
				$this->load->model('sale/customer');
				$this->data['customerInfo'] = $this->model_sale_customer->getCustomer($this->request->get['customer_id']);
			} else {
				$this->load->model('customer/customer');
				$this->data['customerInfo'] = $this->model_customer_customer->getCustomer($this->request->get['customer_id']);
			}

			if(VERSION < '2.1.0.1') {
				$birthDayCustomField = unserialize($this->data['customerInfo']['custom_field']);
				$this->data['customerInfo']['birthday_date'] = $birthDayCustomField[$this->data['data']['BirthdayReminder']['custom_field']];
			} else {
				$birthDayCustomField = json_decode($this->data['customerInfo']['custom_field']);
				$this->data['customerInfo']['birthday_date'] = $birthDayCustomField->{$this->data['data']['BirthdayReminder']['custom_field']};
			}

			$this->data['customerInfo']['last_gift_date'] = $this->moduleModel->isAlreadySendGift($this->request->get['customer_id'],$this->data['customerInfo']['email']);

			$infoFromBRTable = $this->moduleModel->getInfoForCustomerFromBRTable($this->request->get['customer_id']);
			if(!empty($infoFromBRTable['language_id'])) {
				$this->data['customerInfo']['language_id'] = $infoFromBRTable['language_id'];
			} else {
				$this->load->model('localisation/language');
				$languages  = $this->model_localisation_language->getLanguages();
				$first_lang = reset($languages);
				$this->data['customerInfo']['language_id'] = $first_lang['language_id'];
			}

		} else if (!empty($this->request->get['customer_email'])) {
			$this->data['customerInfo'] = $this->moduleModel->getGuestCustomerInfo($this->request->get['customer_email']);
		}
		
		$languageVariables =  array('user_email', 'default_message', 'subject_text', 'default_subject', 'total_amount', 'discount_code_text');
		foreach($languageVariables as $languageVariable) { 
			$this->data[$languageVariable] =  $this->language->get($languageVariable);	
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') ) {
			$this->moduleModel->sendGiftByMail($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			
			$json= $this->request->post;
			$json=json_encode($json);
			$this->response->setOutput($json);
			return;
		}
		$this->data['fixed_amount'] = $this->language->get('fixed_amount');
		$this->data['discount_text'] = $this->language->get('discount_text');
		$this->data['type_of_discount'] = $this->language->get('type_of_discount');
		$this->data['percentage_text'] = $this->language->get('percentage_text');
		$this->data['send_gift_button'] = $this->language->get('send_gift_button');
		$this->data['currency'] = $this->config->get('config_currency');
		$this->data['discount_code'] = $this->generateUniqueRandomVoucherCode();
		$this->data['user_token'] = $this->session->data['user_token'];

		$customerInfo = $this->data['customerInfo'];

		if(!empty($customerInfo['birthday_date'])) {;
            if (strpos($customerInfo['birthday_date'],'/') !== false) {
            	$this->data['customer_birthday_date'] = floor((time()-strtotime(str_replace('/', '-', $customerInfo['birthday_date'])))/(365*60*60*24)) ;
            } else {
                $this->data['customer_birthday_date'] = floor((time()-strtotime($customerInfo['birthday_date']))/(365*60*60*24)) ;
            }
        } else {
        	$this->data['customer_birthday_date'] = "";
        }

		$this->template = $this->modulePath.'/giftForm';	

		$this->response->setOutput($this->load->view($this->modulePath.'/giftForm', $this->data));
	}
	
	private function validateBirthdayGift() {
		
		$this->load->language($this->modulePath);

		if(strlen($this->request->post['subject']) < 4 || strlen($this->request->post['subject']) > 128 ) {
			$this->error['subject'] = $this->language->get('error_subject');
		}
		
		if (strlen($$this->request->post['message']) < 4  && strlen($$this->request->post['message']) > 2000) {
			$this->error['message'] = $this->language->get('error_message');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	private function validate() {
		if (!$this->user->hasPermission('modify', $this->modulePath)) {
			$this->error = true;
		}
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	private function addZero($number) {
		
		if($number < 10){
			return '0' . $number;
		}
		else {
			return $number;
		}	
	}
	
	public function cronJob() {
		if (function_exists('shell_exec') && trim(shell_exec('echo EXEC')) == 'EXEC') {
			$phpPath = str_replace(PHP_EOL, '', shell_exec("which php"));
        } else {
            $phpPath = 'php';
        }
        
        if (empty($phpPath)) {
            $phpPath = 'php';
        }
		
		$this->load->model($this->modulePath);
		$this->load->model('setting/setting');
		$this->load->language($this->modulePath);
		
		
		if(!function_exists('exec')){ 	
			$admin_command = "exec() function is not allowed";
		}
		if(!function_exists('shell_exec')){ 	
			$customer_command = "shell_exec() function is not allowed";
		}
		
		$cronCommands = explode(PHP_EOL, shell_exec('crontab -l'));
		
		if(!empty($cronCommands)) {
			
			foreach($cronCommands as $cronCommand){
				if(strpos($cronCommand, $phpPath. ' ' . DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder/notifyadmin.php')) {
					$admin_command = $cronCommand;
					$admin_cron = explode(' ', $cronCommand);  
				}
				if(strpos($cronCommand, $phpPath. ' ' . DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder/notifycustomer.php')){
					$customer_command = $cronCommand;
					$customer_cron = explode(' ', $cronCommand);
				}
			}
		}
		if(!empty($admin_cron)) {
			$this->data['admin_time'] = $this->addZero((int)$admin_cron[1]) . ':' . $this->addZero((int)$admin_cron[0]);
		} else {
			$this->data['admin_time'] = '12:00';
		}
		if(!empty($admin_cron)) {
			$this->data['customer_time'] = $this->addZero((int)$customer_cron[1]) . ':' . $this->addZero((int)$customer_cron[0]);
		} else {
			$this->data['customer_time'] = '12:00';
		}
		
		if(!empty($admin_cron) && $admin_cron[4] != '*' && (int)$admin_cron[4] > - 1 &&  (int)$admin_cron[4] < 7 ) {
			$this->data['week_day'] = $admin_cron[4];
			$this->data['admin_notification'] = 'W';  
		} else {
			$this->data['admin_notification'] = 'D';
			$this->data['week_day'] = '';
		}
		
		$setting = $this->model_setting_setting->getSetting('br_customer_notification');
		if(isset($setting['br_customer_notification']))
			$setting = $setting['br_customer_notification'];
		if(!empty($setting['customer_notification'])){
			$this->data['customer_notification'] = $setting['customer_notification'];
		} else {
			$this->data['customer_notification'] = '';
		}
		if(!empty($setting['days_before_birthday'])) {
			$this->data['days_before_birthday'] = (int)$setting['days_before_birthday'];
		} else {
			$this->data['days_before_birthday'] = 3;
		}
		
		if(!empty($admin_command)){
			$this->data['admin_command'] = $admin_command;
		} else {
			$this->data['admin_command'] = '';	
		}
		if(!empty($customer_command )) {
			$this->data['customer_command'] = $customer_command;
		} else {
			$this->data['customer_command'] ='';
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			if(!$this->setCronJob($this->request->post)){
				echo 'Insufficient file permissions in folder ' . DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder!';
			}
			exit;
		}
		
		$languageVariables = array(
			'cron_job_text', 
			'cron_admin_notifications_title',
			'cron_select_admin_period',
			'cron_select_customer_period',
			'cron_customer_options_title',
			'cron_select_options_title',
			'cron_current_crons',
			'cron_week_day',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
			'cron_time',
			'cron_days_before',
			'cron_successfully_changed',
			'error_warning',
			'every_week',
			'every_day',
			'start_cron_button',
			'clear_cron_button',
			'before_birthday',
			'the_day_of_birthday');	
		foreach($languageVariables as $languageVariable) {
			$this->data[$languageVariable] = $this->language->get($languageVariable);
		}
		
		$this->response->setOutput($this->load->view($this->modulePath.'/cronForm', $this->data));
	}

	private function setCronJob($data) {
		if (function_exists('shell_exec') && trim(shell_exec('echo EXEC')) == 'EXEC') {
			$phpPath = str_replace(PHP_EOL, '', shell_exec("which php"));
        } else {
            $phpPath = 'php';
        }
        
        if (empty($phpPath)) {
            $phpPath = 'php';
        }

		$this->load->model('setting/setting');
		$new_cron_jobs = '';

		if(isset($this->request->post['admin_notification'])) {
			$cronMinute = substr($data['admin_time'], 3, 2) ? (int)substr($data['admin_time'], 3, 2) : '*';
			$cronHour   = substr($data['admin_time'], 0, 2) ? (int)substr($data['admin_time'], 0, 2) : '*';
			
			if($data['admin_notification'] == 'W') {	
				$cronTime = $cronMinute . ' ' . $cronHour . ' * * ' . (int)$data["week_day"];				
			}
			else {
				$cronTime =  $cronMinute . ' ' . $cronHour. ' * * *';
			}
			if(isset($cronTime)){
				$new_cron_jobs .= $cronTime .' '. $phpPath. ' ' . DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder/notifyadmin.php'. PHP_EOL;
			}
		}
		
		if(isset($data['customer_notification'])) {

			$cronMinute = substr($data['customer_time'], 3, 2) ? (int)substr($data['customer_time'],3,2) : '*';
			$cronHour   = substr($data['customer_time'], 0, 2) ? (int)substr($data['customer_time'],0,2) : '*';
			$new_cron_jobs .=  $cronMinute . ' ' . $cronHour . ' * * *  ' . $phpPath. ' ' . DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder/notifycustomer.php'. PHP_EOL;
			$newSetting['br_customer_notification'] = array(
				'customer_notification' => $data['customer_notification'], 
				'days_before_birthday' => $data['days_before_birthday'], 
				'admin_notification' => $data['admin_notification']);
			
			$this->model_setting_setting->editSetting('br_customer_notification', $newSetting);
			
		}

		if($this->editCron($new_cron_jobs)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function stopCron() {
		$this->editCron();	
	}
	
	private function editCron($new_commands = '') {
		if (function_exists('shell_exec') && trim(shell_exec('echo EXEC')) == 'EXEC') {
			$phpPath = str_replace(PHP_EOL, '', shell_exec("which php"));
        } else {
            $phpPath = 'php';
        }
        
        if (empty($phpPath)) {
            $phpPath = 'php';
        }
		
		$cronFolder = DIR_SYSTEM.'library/vendor/isenselabs/birthdayreminder/';
		//backup crontab
		$currentCronJobs = shell_exec('crontab -l');

		$cron_commands_backup = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $currentCronJobs);
		$cron_commands_backup =  explode(PHP_EOL, $cron_commands_backup);
		if(!empty($cron_commands_backup)) {
			foreach($cron_commands_backup as $key => $command) {
					if(strpos($command, $phpPath .' '. $cronFolder.'notifyadmin.php') || strpos($command,$phpPath.' '. $cronFolder . 'notifycustomer.php')){
					unset($cron_commands_backup[$key]);
				}
			}
			$cron_commands_backup = implode(PHP_EOL, $cron_commands_backup);
		}
		//delete crontab	
		shell_exec('crontab -r');
		//update with new commands 
		if(file_put_contents($cronFolder . 'cron.txt', $cron_commands_backup . PHP_EOL . $new_commands)) {
			shell_exec('crontab '. $cronFolder . 'cron.txt');	
			return true;
		} else {
			return false;
		}
	}
	
	private function convertDateToMiliseconds($date) {
		$date_month = date("F",strtotime($date));
		$date_day = date("d",strtotime($date));
		$date = "$date_month $date_day, ".date('Y');
		return strtotime($date) * 1000;
	}
	 
	
	private function isDateInThisYear($date) {
		$date_year = date("Y",strtotime($date));
		return $date_year == date("Y");
	}
	
	public function generateUniqueRandomVoucherCode() {
		$this->load->model($this->modulePath);
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$couponCode = '';
		for ($i = 0; $i < 10; $i++) {	
			$couponCode .= $characters[rand(0, strlen($characters) - 1)]; 
		}
		if($this->moduleModel->isUniqueCode($couponCode)) {	
			return $couponCode;
		} else {	
			return $this->generateUniqueRandomVoucherCode();
		}
	}

	public function install() {
		$this->load->model($this->modulePath);
		$this->moduleModel->install();
	}
	
	public function uninstall() {
		$this->load->model($this->modulePath);
		$this->moduleModel->uninstall();
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', $this->modulePath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
	

	private function upgradeTable() {
		$columns = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "customer_birthday`");

		$found_guest_mail = false;			
		foreach ($columns->rows as $column) {
			if ($column['Field'] == 'guest_email'){} $found_guest_mail = true;
		}
		
		if ($found_guest_mail) {
			$this->load->model($this->modulePath);

			if(VERSION < '2.1.0.1') {
				$this->load->model('sale/customer');
				$this->load->model('sale/custom_field');
			} else {
				$this->load->model('customer/customer');
				$this->load->model('customer/custom_field');
			}

			$this->load->model('setting/setting');	
			$settings= $this->model_setting_setting->getSetting('BirthdayReminder');
			if(!empty($settings['BirthdayReminder']['custom_field'])) {

				$data = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_birthday`");
				$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "customer_birthday`");
				
				$this->moduleModel->install();
				
				foreach($data->rows as $customer) {
					$tempCust = array();

					if(VERSION < '2.1.0.1') {
						$tempCust = $this->model_sale_customer->getCustomer($customer['customer_id']);
					} else {
						$tempCust = $this->model_customer_customer->getCustomer($customer['customer_id']);
					}
					
					if($tempCust) {
						$custom_fields = unserialize($tempCust['custom_field']);
						if(empty($custom_fields[$settings['BirthdayReminder']['custom_field']])) {
							$custom_fields[$settings['BirthdayReminder']['custom_field']] = $customer['birthday_date'];
							$tempCust['custom_field'] = $custom_fields;
							
							$tempCust['password']="";
							unset($tempCust['address']);
							if(VERSION < '2.1.0.1') {
								$this->model_sale_customer->editCustomer($customer['customer_id'], $tempCust);
							} else {
								$this->model_customer_customer->editCustomer($customer['customer_id'], $tempCust);
							}
						}
						if(empty($customer['guest_email'])) {							
							$email = $tempCust['email'];
						} else {
							$email = $customer['guest_email'];
						}

						if(empty($customer['last_gift_date'])) {
							$last_gift_date = "NULL";
						} else {
							$last_gift_date = $customer['last_gift_date'];
						}

						if(empty($customer['birthday_last_edited'])) {
							$birthday_last_edited = "NULL";
						} else {
							$birthday_last_edited = $customer['birthday_last_edited'];
						}

						$updateQuery = "INSERT INTO `" . DB_PREFIX . "customer_birthday` (email, customer_id, last_gift_date, birthday_last_edited)
			              VALUES('".$email."','".(int)$customer['customer_id']."', '".$last_gift_date."', '".$birthday_last_edited."')";
						$this->db->query($updateQuery);
					}
				}
			}
		
		}
	}
}
