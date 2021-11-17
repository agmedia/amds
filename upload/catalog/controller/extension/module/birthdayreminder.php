<?php
class ControllerExtensionModuleBirthdayReminder extends Controller
{
    
    private $moduleVersion;
    private $modulePath;
    private $moduleModel;
    private $callModel;
    private $extensionLink;
    
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->config->load('isenselabs/birthdayreminder');
        
        $module = 'birthdayreminder_';
        
        $this->moduleVersion = $this->config->get($module . 'moduleVersion');
        $this->modulePath    = $this->config->get($module . 'modulePath');
        $this->callModel     = $this->config->get($module . 'callModel');
        
        $this->load->model($this->modulePath);
        $this->language->load($this->modulePath);
        
        $this->moduleModel = $this->{$this->callModel};
    }
    
    public function registerUserLangToDB(&$route, &$args, &$output)
    {
        if ($output) {
            $this->load->model('account/customer');
            $customer_id = $output ? $output : 0;
            $customer = $this->model_account_customer->getCustomer($customer_id);
            
            if (!empty($customer)) {
                $this->load->model($this->modulePath);
                $infoFromBirthdayTable = $this->moduleModel->getInfoFromTheDB($customer['email']);

                if (empty($infoFromBirthdayTable)) {
                    $userQuery = $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_birthday` (email,customer_id,language_id) VALUES ('" . $customer['email'] . "','" . (int) $customer_id . "','" . $this->config->get('config_language_id') . "')");
                } else {
                    $this->db->query("UPDATE `" . DB_PREFIX . "customer_birthday` SET language_id = " . (int) $this->config->get('config_language_id') . " WHERE customer_id = " . (int) $customer_id);
                }
            }
        }
    }
    
    public function registerGuestBirthday(&$route,&$data)
    {
        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
            if ($order_id && !$this->customer->getId()) {
                $this->load->model('checkout/order');
                $data = $this->model_checkout_order->getOrder($order_id);
                if ($data) {
                    $this->moduleModel->registerGuestBirthday($data);
                } 
            }
        } 
    }
    
    
    
    public function updateCustomerBirthday(&$route, &$args, &$output)
    {
        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($this->customer->getId());

        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_birthday` 
            (customer_id, email, birthday_last_edited)
            VALUES('" . (int) $customer['customer_id'] . "',
            '" . $customer['email'] . "',
            NOW())
            ON DUPLICATE KEY UPDATE birthday_last_edited = NOW()");
    }
    
    public function customerCanEdit($customer_id)
    {
        $now             = time();
        $birthday_update = $this->db->query("SELECT birthday_last_edited FROM `" . DB_PREFIX . "customer_birthday` WHERE customer_id = '" . (int) $customer_id . "' AND birthday_last_edited IS NOT NULL");
        
        if (!$birthday_update->num_rows) {
            return true;
        }
        
        $then = strtotime($birthday_update->row['birthday_last_edited']);
        return ($now - $then >= 365 * 24 * 60 * 60);
    }
    
    
    
    public function sendWishes()
    {
        $this->load->model('setting/setting');
        $setting         = $this->model_setting_setting->getSetting("BirthdayReminder");
        $customerSetting = $this->model_setting_setting->getSetting("br_customer_notification");
        
        if ($setting['BirthdayReminder']['Enabled'] == "yes" && !empty($customerSetting)) {
            $customerSetting = $customerSetting['br_customer_notification'];
            
            if (isset($customerSetting['customer_notification']) && $customerSetting['customer_notification'] == 'before') {
                $customers = $this->moduleModel->getCustomerBirthdaysByDate(date('Y-m-d', time() + $customerSetting['days_before_birthday'] * 24 * 60 * 60));
            } elseif (isset($customerSetting['customer_notification']) && $customerSetting['customer_notification'] == 'on') {
                $customers = $this->moduleModel->getCustomerBirthdaysByDate();
            }
            
            if (!empty($customers)) {
                foreach ($customers as $customer) {
                    $this->load->model($this->modulePath);
                    $infoFromBRTable = $this->moduleModel->getInfoFromTheDB($customer['email']);
                    
                    if (!empty($infoFromBRTable['last_gift_date'])) {
                        $lastGiftDate = $infoFromBRTable['last_gift_date'];
                    } else if (!empty($customer['last_gift_date'])) {
                        $lastGiftDate = $customer['last_gift_date'];
                    } else {
                        $lastGiftDate = "0000-00-00";
                    }
                    
                    if (!$this->isDateInThisYear($lastGiftDate)) {
                        $discount_code = $this->generateUniqueRandomVoucherCode();
                        
                        $language_id = 0;
                        
                        if (!empty($infoFromBRTable['language_id'])) {
                            $language_id = $infoFromBRTable['language_id'];
                        } else {
                            $this->load->model('localisation/language');
                            $languages   = $this->model_localisation_language->getLanguages();
                            $first_lang  = reset($languages);
                            $language_id = $first_lang['language_id'];
                        }
                        
                        $data = array(
                            'discount_code' => $discount_code,
                            'discount' => $setting['BirthdayReminder']['discount'],
                            'discount_type' => $setting['BirthdayReminder']['discount_type'],
                            'total_amount' => $setting['BirthdayReminder']['total_amount'],
                            'customer_id' => $customer['customer_id'],
                            'subject' => $setting['BirthdayReminder']['subject'],
                            'gift_message' => $setting['BirthdayReminder']['message'][$language_id],
                            'birthday_date' => $customer['birthday'],
                            'to_mail' => $customer['email'],
                            'days_after' => $setting['BirthdayReminder']['days_after'],
                            'firstname' => $customer['firstname'],
                            'lastname' => $customer['lastname'],
                            'admin_notification' => $setting['BirthdayReminder']['admin_notification']
                        );
                        $this->moduleModel->sendGiftByMail($data);
                    }
                }
            }
        }
    }
    
    public function notifyAdmin()
    {
        $this->load->model('setting/setting');
        $this->load->model($this->modulePath);
        $setting             = $this->model_setting_setting->getSetting("BirthdayReminder");
        $notificationSetting = $this->model_setting_setting->getSetting("br_customer_notification");
        
        if ($setting['BirthdayReminder']['Enabled'] == "yes" && !empty($notificationSetting)) {
            $notificationSetting = $notificationSetting["br_customer_notification"];
            if ($notificationSetting['admin_notification'] == "D") {
                $birthdaysList = $this->moduleModel->getCustomerBirthdaysByDate(date('Y-m-d', time()));
                $this->moduleModel->sendAdminNotification($birthdaysList, $notificationSetting['admin_notification']);
            }
            
            if ($notificationSetting['admin_notification'] == "W") {
                $customers    = $this->moduleModel->getCustomerBirthdays();
                $customerList = array();
                $now          = time();
                $afterWeek    = $now + 7 * 24 * 60 * 60;
                
                foreach ($customers as $key => $customer) {
                    if ((strtotime($customer['birthday']) > $now) && ((strtotime($customer['birthday']) < $afterWeek))) {
                        $tmp                  = $customer;
                        $tmp['birthday_date'] = $this->convertDateToTimeCurrentYear($customer['birthday']);
                        $customerList[]       = $tmp;
                    }
                }
                $this->aasort($customerList, "birthday_date");
                
                $customersListWeek = array();
                foreach ($customerList as $key => $customer) {
                    $tmp                  = $customer;
                    $tmp['birthday_date'] = $customer['birthday'];
                    $customersListWeek[]  = $tmp;
                }

                $this->moduleModel->sendAdminNotification($customersListWeek, $notificationSetting['admin_notification']);
            }
            
        }
    }
    
    private function aasort(&$array, $key)
    {
        $sorter = array();
        $ret    = array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }
    
    public function generateUniqueRandomVoucherCode()
    {
        $this->load->model($this->modulePath);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $couponCode = '';
        for ($i = 0; $i < 10; $i++) {
            $couponCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        if ($this->moduleModel->isUniqueCode($couponCode)) {
            return $couponCode;
        } else {
            return $this->generateUniqueRandomVoucherCode();
        }
    }
    
    private function isDateInThisYear($date)
    {
        $date_year = date("Y", strtotime($date));
        return $date_year == date("Y");
    }
    
    private function convertDateToTimeCurrentYear($date)
    {
        $date_month = date("F", strtotime($date));
        $date_day   = date("d", strtotime($date));
        $date       = "$date_month $date_day, " . date('Y');
        return strtotime($date);
    }
}