<?php
class ControllerExtensionModuleWishlistDiscounts extends Controller {

    private $version;
    private $moduleName;
    private $modulePath;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->config->load('isenselabs/wishlistdiscounts');

        $this->version = $this->config->get('wishlistdiscounts_version');
        $this->moduleName = $this->config->get('wishlistdiscounts_name');
        $this->modulePath = $this->config->get('wishlistdiscounts_path');

        $this->load->model($this->modulePath);

        $this->moduleModel = $this->{$this->config->get('wishlistdiscounts_model')};
    }

    public function index()
    {
        // Do nothing
    }

    /*
     * CRON: Send mail to all customer that have 0 notification
     * Note: Replicate admin wishlishdiscount::sendMail
     */
    public function notify()
    {
        $this->load->model('tool/image');

        $store_id      = (int)$this->config->get('config_store_id');
        $setting       = $this->model_setting_setting->getSetting('WishlistDiscounts', $store_id);
        $settingModule = $setting['WishlistDiscounts'];
        $mailSubject   = $settingModule['discountSubject'][$this->config->get('config_language_id')];
        $mailContent   = $settingModule['message'][$this->config->get('config_language_id')];

        if (empty($setting) || !$setting['WishlistDiscounts_status'] || empty($settingModule['cron']) || $settingModule['cron']['status'] != 'yes') {
            return;
        }

        $customers         = $this->moduleModel->getCustomers($store_id, $settingModule['cron']['interval']);
        $discount_type     = $settingModule['cron']['discount_type'];
        $discount_value    = (int)$settingModule['cron']['discount_value'];
        $duration_duration = (int)$settingModule['cron']['discount_duration'];

        foreach ($customers as $customer) {
            $discount_code  = $this->generateUniqueRandomVoucherCode();
            $date_end       = date('Y-m-d', time() + ($duration_duration * 24 * 60 * 60));

            // Product list
            $images         = array();
            $wishlist       = array();
            $coupon_product = array();

            $products       = $this->moduleModel->getCustomerWishlist($customer['customer_id'], $store_id);
            foreach ($products as $product) {
                $wishlist[] = $product['product_id'];
                $coupon_product[] = $product['wishlist_product_id'];
                $images[$product['wishlist_product_id']] = $this->model_tool_image->resize($product['image'], 153, 230);
            }

            // Add coupon per customer
            $couponInfo = array(
                'name'           => 'WishlistDiscount [' . $customer['email'] . ']',
                'code'           => $discount_code,
                'discount'       => $discount_value,
                'type'           => $discount_type,
                'total'          => '0',
                'logged'         => '1',
                'shipping'       => '0',
                'date_start'     => date('Y-m-d', time()),
                'date_end'       => $date_end,
                'uses_total'     => '1',
                'uses_customer'  => '1',
                'status'         => '1',
                'coupon_product' => $coupon_product
            ); 
            $coupon_id = $this->moduleModel->addCoupon($couponInfo);

            // Mail content
            $wordTemplates = array(
                "{firstname}", 
                "{lastname}", 
                "{discount_code}", 
                "{discount_value}", 
                "{date_end}", 
                "{customer_wishlist}"
            );

            $data['products'] = $products;
            $data['images'] = $images;
            $data['currencyLeft']  = $this->currency->getSymbolLeft($this->config->get('config_currency'));
            $data['currencyRight'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
            $data['storeURL'] = $this->getCatalogURL();
            $words = array(
                $customer['firstname'], 
                $customer['lastname'], 
                $discount_code,
                $discount_value,
                $date_end,
                $this->load->view($this->modulePath . '/customer_wishlist_template', $data)
            ); 
            $message = str_replace($wordTemplates, $words, $mailContent);

            // Send mail
            $mailToUser = new Mail($this->config->get('config_mail_engine'));
            $mailToUser->parameter = $this->config->get('config_mail_parameter');
            $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
            $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
            $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mailToUser->setTo($customer['email']);
            $mailToUser->setFrom($this->config->get('config_email'));
            $mailToUser->setSender($this->config->get('config_email'));
            $mailToUser->setSubject(html_entity_decode($mailSubject, ENT_QUOTES, 'UTF-8'));
            $mailToUser->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));

			if(isset($settingModule['admin_notification']) && $settingModule['admin_notification'] == 'yes') {
				$mailToUser->setBcc($this->config->get('config_email'));
			}

            $mailToUser->send();

            // Log
            $this->moduleModel->logCustomerNotification($customer['customer_id']);
            $this->moduleModel->logDiscount(array('customer_id' => $customer['customer_id'], 'wishlist' => $wishlist, 'coupon_id' => $coupon_id), $store_id);

        }
    }

    private function getCatalogURL()
    {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_SERVER;
        } else {
            $storeURL = HTTP_SERVER;
        }
        return $storeURL;   
    }

    private function generateUniqueRandomVoucherCode()
    {
        $couponCode = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($i = 0; $i < 10; $i++) {
            $couponCode .= $characters[rand(0, strlen($characters) - 1)];
        }

        if ($this->moduleModel->isUniqueCode($couponCode)) {
            return $couponCode;
        } else {
            return $this->generateUniqueRandomVoucherCode();
        }
    }
}
