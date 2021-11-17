<?php
class ControllerExtensionModuleWishlistDiscounts extends Controller {

    private $version;
    private $moduleName;
    private $modulePath;
    private $moduleModel;
    private $extensionLink;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->config->load('isenselabs/wishlistdiscounts');

        $this->moduleName = $this->config->get('wishlistdiscounts_name');
        $this->modulePath = $this->config->get('wishlistdiscounts_path');
        $this->version = $this->config->get('wishlistdiscounts_version');

        $this->load->language($this->modulePath);
        $this->load->model($this->modulePath);

        $this->moduleModel = $this->{$this->config->get('wishlistdiscounts_model')};
        $this->moduleModel->update();
        $this->extensionLink = $this->url->link($this->config->get('wishlistdiscounts_extensionLink'), 'user_token=' . $this->session->data['user_token'].$this->config->get('wishlistdiscounts_extensionLink_type'), 'SSL');

    }

    public function index() {

        $data['modulePath'] = $this->modulePath;

        $this->document->addStyle('view/javascript/summernote/summernote.css');
        $this->document->addScript('view/javascript/summernote/summernote.min.js');
        $this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');

        $this->document->addStyle($this->getCatalogURL() . 'admin/view/stylesheet/wishlistdiscounts.css');

        $this->load->model('localisation/language');

        $this->load->model('setting/setting');

        $this->load->model('setting/store');

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }

        $data['store_id'] = $store_id;

        $store = $this->getCurrentStore($store_id);

        $this->document->setTitle($this->language->get('heading_title').' '.$this->version);
        $data['error_warning'] = '';

        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = $languages;

        foreach ($data['languages'] as $key => $value) {
            if(version_compare(VERSION, '2.2.0.0', "<")) {
                $data['languages'][$key]['flag_url'] = 'view/image/flags/'.$data['languages'][$key]['image'];

            } else {
                $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
            }
        }

        $firstLanguage = array_shift($languages);
        $data['firstLanguageCode'] = $firstLanguage['code'];

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            if (!$this->user->hasPermission('modify', $this->modulePath)) {
                $this->response->redirect($this->extensionLink);
            }
            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post['WishlistDiscounts']['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post['WishlistDiscounts']['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }
            $this->model_setting_setting->editSetting('WishlistDiscounts', $this->request->post, $store_id);
			if ($this->request->post['WishlistDiscounts']['Enabled'] == 'yes'){               
                $this->model_setting_setting->editSetting('module_wishlistdiscounts_status', array('module_wishlistdiscounts_status' => 1));
            } else{
                $this->model_setting_setting->editSetting('module_wishlistdiscounts_status', array('module_wishlistdiscounts_status' => 0));
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'));
        }

        $languageVariables = array(
            'entry_code',
            'coupon_validity',
            'user_email',
            'default_subject',
            'default_message',
            'admin_notification',
            'admin_notification_help',
            'message_has_been_sent',
            'select_date_format',
            'message_to_customer_heading',
            'message_to_customer_help',
            'default_discount_message',
            'text_no_results',
            'text_default',
            'default_discount_message',
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $data['heading_title'] = $this->language->get('heading_title').' '.$this->version;

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }
        if (isset($this->session->data['success'])) {     
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->error['warning'])) { 
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        $data['breadcrumbs']   = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->extensionLink,
            'separator' => ' :: '
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );
        $data['currency'] = $this->config->get('config_currency');
        $data['action']        = $this->url->link($this->modulePath, 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cancel']        = $this->extensionLink;
        $data['data']          = $this->model_setting_setting->getSetting('WishlistDiscounts', $store_id);
        $data['user_token']         = $this->session->data['user_token'];

        $data['email']	=	$this->config->get('config_email');
        $data['currency'] = $this->config->get('config_currency');

        $data['stores'] = array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . ' (' . $data['text_default'] . ')', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());
        $data['store']                  = $store;

        if ($this->config->get('module_wishlistdiscounts_status')) { 
            $data['module_wishlistdiscounts_status'] = $this->config->get('module_wishlistdiscounts_status'); 
        } else {
            $data['module_wishlistdiscounts_status'] = '0';
        }

        $data['cronUrl'] = $this->getCatalogURL() . 'index.php?route=extension/module/' . $this->moduleName . '/notify';
        $data['cronPhpPath'] = '0 0 * * * ';
        if (function_exists('shell_exec') && trim(shell_exec('echo EXEC')) == 'EXEC') {
            $data['cronPhpPath'] .= shell_exec("which php"). ' ';
        } else {
            $data['cronPhpPath'] .= 'php ';
        }
        $data['cronPhpPath'] .= dirname(DIR_APPLICATION) . '/system/library/vendor/isenselabs/' . $this->moduleName . '/cron.php';

        $data['header']                 = $this->load->controller('common/header');
        $data['column_left']            = $this->load->controller('common/column_left');
        $data['footer']                 = $this->load->controller('common/footer');

        $storeURL = $this->getCatalogURL();

        $data['unlicensed_html']        = base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Utc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=');
        $data['now'] = time();
        $data['licenseEncoded'] = !empty($data['data']['WishlistDiscounts']['LicensedOn']) ? base64_encode(json_encode($data['data']['WishlistDiscounts']['License'])) : '';
        $data['licenseExpiresOn'] = !empty($data['data']['WishlistDiscounts']['LicensedOn']) ? date("F j, Y",strtotime($data['data']['WishlistDiscounts']['License']['licenseExpireDate'])) : '';
        $data['supportURL'] = base64_encode('Support Request') . '/' . base64_encode('116') . '/'. base64_encode($_SERVER['SERVER_NAME']);

        $tabs = array('tab_active_wishlists', 'tab_settings', 'tab_support', 'tab_template');
        foreach ($tabs as $tab) {
            $data[$tab] = $this->load->view($this->modulePath . '/' . $tab, $data);
        }

        $this->response->setOutput($this->load->view($this->modulePath, $data)); 

        $data = array();		


        $data['storeURL']       = $storeURL;
        $data['unsubscribeURL'] = $storeURL . 'index.php?route=account/newsletter';

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template'). '/'.$this->modulePath.'/discount_template')) {
            return $this->load->view($this->config->get('config_template'). '/'.$this->modulePath.'/discount_template', $data);
        } else {
            return $this->load->view('/'.$this->modulePath.'/discount_template', $data);
        }  

    }

    public function customerList($action) {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'firstname, lastname';
        }
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        } 

        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data   = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );		


        $data['modulePath'] = $this->modulePath;

        $languageVariables = array (
            'column_customer_name',
            'column_date_added',
            'column_customer_wishlist',
            'column_customer_email',
            'show_wishlist',
            'button_send_discounts_to_selected',
            'button_send_discount_to_all',
            'text_no_results',
            'wishlist_heading'
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $url = '';
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $pagination               = new Pagination();

        if($action == 'archivedWishlists') {
            $data['customers']  = $this->moduleModel->gethWishListArchive($data, $store_id);  
            $total_customers = $this->moduleModel->getTotalWishListArchive($store_id);
            $data['sort_name']       = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=name' . $url;
            $data['sort_date_added'] = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url;
            $data['sort_email']      = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url;
            $data['continue'] = $this->url->link('account/account', '', 'SSL');
            $pagination->total        = $total_customers;
            $pagination->page         = $page;
            $pagination->limit        = 2;
            $pagination->text         = $this->language->get('text_pagination');
            $pagination->url          = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . $url . '&page={page}';

        } else { 
            $data['customers']  = $this->moduleModel->getCustomersWithWishList($data, $store_id);  
            $total_customers  = $this->moduleModel->getTotalCustomersWithWishList($store_id);

            $data['sort_name']       = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=name' . $url;
            $data['sort_date_added'] = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url;
            $data['sort_email']      = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url;
            $data['continue'] = $this->url->link('account/account', '', 'SSL');
            $pagination->total        = $total_customers;
            $pagination->page         = $page;
            $pagination->limit        = 2;
            $pagination->text         = $this->language->get('text_pagination');
            $pagination->url          = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . $url . '&page={page}';
        }

        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) { 
            $url .= '&order=' . $this->request->get['order'];
        }


        $data['pagination'] = $pagination->render();
        $data['sort']       = $sort;
        $data['order']      = $order;

        foreach ($data['customers'] as &$customer) {
            $customer['date_added_time'] = strtotime($customer['date_added']);
        }

        $this->response->setOutput($this->load->view($this->modulePath.'/customerList', $data)); 
    }

    public function wishlist() {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');



        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        $languageVariables = array (
            'text_empty',
            'column_image',  
            'column_name',   
            'column_model',  
            'column_stock',  
            'column_price',  
            'column_action', 
            'button_continue',
            'button_cart',   
            'button_remove', 
            'discount_text' 
        );
        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $data['heading_title'] = $this->language->get('heading_title').' '.$this->version;

        $data['currency']                     = $this->config->get('config_currency');
        $data['products']                     = array(); 
        $data['customer_info']['name']        = $this->request->get['customer_name'];
        $data['customer_info']['customer_id'] = $this->request->get['customer_id'];
        $customer_id                          = $this->request->get['customer_id'];
        $data['currencyLeft']  = $this->currency->getSymbolLeft($this->config->get('config_currency'));
        $data['currencyRight'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
        $data['customers_url'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $wishList = $this->moduleModel->getCustomerWishlist($customer_id, $store_id);
        foreach ($wishList as $product_info) {
            if (isset($product_info)) {
                if ($product_info['image']) { //TODO
                    $image = $this->model_tool_image->resize($product_info['image'], 100, 100);
                } else {
                    $image = false;
                }
                if ($product_info['quantity'] <= 0) { 
                    $stock = $product_info['stock_status'];
                } elseif ($this->config->get('config_stock_display')) {
                    $stock = $product_info['quantity'];
                } else {
                    $stock = $this->language->get('text_instock');
                }
                if (!empty($product_info['regular_price'])) {
                    $price = $product_info['regular_price'];
                } else {
                    $price = false;
                }
                if ((float) $product_info['special_price']) {
                    $special = $product_info['special_price'];
                } else {
                    $special = false;
                }
                $data['products'][] = array(
                    'product_id' => $product_info['wishlist_product_id'],
                    'thumb' => $image,
                    'name' => $product_info['product_name'],
                    'model' => $product_info['model'],
                    'stock' => $stock,
                    'price' => $price,
                    'special' => $special,
                    'href' => $this->getCatalogURL() . 'index.php?route=product/product&product_id=' . $product_info['wishlist_product_id'],
                    'remove' => $this->url->link('account/wishlist', 'remove=' . $product_info['wishlist_product_id'])
                );
            } else {
                unset($this->session->data['wishlist'][$key]);
            }
        }

        $data['modulePath'] = $this->modulePath;

        $this->response->setOutput($this->load->view($this->modulePath.'/wishlist', $data)); 

    }

    public function mailForm() {
        $this->load->model('setting/setting');

        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        if (!empty($this->request->get['mode'])) {
            unset($this->session->data['wishlistdiscounts']);
            $this->session->data['wishlistdiscounts']['mode'] = $this->request->get['mode'];
            if ($this->request->get['mode'] == 'all') {
                $this->session->data['wishlistdiscounts']['customers'] = $this->moduleModel->getCustomersWithWishList($data=array(), $store_id);
            } else if ($this->request->get['mode'] == 'selected'){
                $this->session->data['wishlistdiscounts']['customers'] =  $this->moduleModel->getCustomers($this->request->get["customers"], $store_id);
            }
        } 

        $data = array();
        $data['total_mails_to_sent'] = $this->session->data['wishlistdiscounts']['total_mails_to_sent'] = count($this->session->data['wishlistdiscounts']['customers']);
        $storeURL = $data['server'] = $this->getCatalogURL();

        $languageVariables = array(
            'user_email',
            'default_discount_message',
            'subject_text',
            'default_subject',
            'total_amount',
            'discount_code_text',
            'text_subject',
            'text_type',
            'text_discount',
            'text_duration',
            'text_days',
            'text_send',
            'text_percentage',
            'text_fixed_amount',
            'text_tooltip_type',
            'text_tooltip_discount',
            'text_tooltip_duration'
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $data['currencyLeft']  = $this->currency->getSymbolLeft($this->config->get('config_currency'));
        $data['currencyRight'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
        $data['storeURL'] = $this->getCatalogURL();
        $data['unsubscribeURL'] = $this->getCatalogURL() . 'index.php?route=account/newsletter';

        $data['data'] = $this->model_setting_setting->getSetting('WishlistDiscounts', $store_id);  
        $data['currency'] = $this->config->get('config_currency');		
        $data['customers'] = $this->session->data['wishlistdiscounts']['customers']; 
        $data['user_token'] = $this->session->data['user_token'];
        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = $languages;
        foreach ($data['languages'] as $key => $value) {
            if(version_compare(VERSION, '2.2.0.0', "<")) {
                $data['languages'][$key]['flag_url'] = 'view/image/flags/'.$data['languages'][$key]['image'];

            } else {
                $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
            }
        }

        $data['modulePath'] = $this->modulePath;

        $firstLanguage = array_shift($languages);
        $data['firstLanguageCode'] = $firstLanguage['code'];

        $data['defaultMessage'] = $this->load->view($this->modulePath.'/discount_template', $data);

        $this->response->setOutput($this->load->view($this->modulePath.'/mailForm', $data)); 
    }

    public function sendMail() {

        $json = array();

        $this->load->model('customer/customer');

        $this->load->model('tool/image');
        $this->load->model('marketing/coupon');

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        $data['user_token'] = $this->session->data['user_token'];

        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = $languages;
        foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
        }
        $firstLanguage = array_shift($languages);
        $data['firstLanguageCode'] = $firstLanguage['code'];

        $data = array();

        $data['modulePath'] = $this->modulePath;

        $discount = $this->request->post['discount'];
        $discount_type = $this->request->post['discount_type'];
        $duration  = $this->request->post['duration'];

        if (!empty($this->session->data['wishlistdiscounts']['customers'])) {
            $current_customer = array_shift($this->session->data['wishlistdiscounts']['customers']);
            $customers_left = count($this->session->data['wishlistdiscounts']['customers']);

            $products = $this->moduleModel->getCustomerWishlist($current_customer['customer_id'], $store_id);

            $wishlist = array();
            foreach($products as $product) {
                $wishlist[] = $product['product_id'];
            }

            $coupon_product = array();
            foreach ($products as $product) {
                $coupon_product[] = $product['wishlist_product_id'];
            }
            $images   = array();
            foreach ($products as $product) {
                $images[$product['wishlist_product_id']] = $this->model_tool_image->resize($product['image'], 200, 200);
            }

            $discountCode =  $this->generateUniqueRandomVoucherCode();

            $dateEnd = date('Y-m-d', time() + ((int) $duration * 24 * 60 * 60));
            $couponInfo = array(
                'name' => 'WishlistDiscount [' . $current_customer['email'] . ']',
                'code' => $discountCode,
                'discount' => (int)$discount,
                'type' => $discount_type,
                'total' => '0',
                'logged' => '1',
                'shipping' => '0',
                'date_start' => date('Y-m-d', time()),
                'date_end' => $dateEnd,
                'uses_total' => '1',
                'uses_customer' => '1',
                'status' => '1',
                'coupon_product' => $coupon_product
            ); 

            $coupon_id = $this->model_marketing_coupon->addCoupon($couponInfo); 

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
                $current_customer['firstname'], 
                $current_customer['lastname'], 
                $discountCode, 
                $discount, 
                $dateEnd, 
                $this->load->view($this->modulePath.'/customer_wishlist_template', $data)
            ); 

            $data['storeURL'] = $this->getCatalogURL();
            if(!empty($this->request->post['message'])){
                $message = 	str_replace($wordTemplates, $words, trim($this->request->post['message'][$current_customer['language_id']]));
            } else {
                $message = str_replace($wordTemplates, $words, $this->load->view($this->modulePath.'/discount_template',$data));
            }

            $this->moduleModel->logDiscount(array('customer_id' => $current_customer['customer_id'], 'wishlist' => $wishlist, 'coupon_id' => $coupon_id), $store_id);

            $mailToUser = new Mail($this->config->get('config_mail_engine'));
            $mailToUser->parameter = $this->config->get('config_mail_parameter');
            $mailToUser->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mailToUser->smtp_username = $this->config->get('config_mail_smtp_username');
            $mailToUser->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mailToUser->smtp_port = $this->config->get('config_mail_smtp_port');
            $mailToUser->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mailToUser->setTo($current_customer['email']);
            $mailToUser->setFrom($this->config->get('config_email'));
            $mailToUser->setSender($this->config->get('config_email'));
            $mailToUser->setSubject(html_entity_decode($this->request->post['subject'][$current_customer['language_id']], ENT_QUOTES, 'UTF-8'));
            $mailToUser->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));

			$wishlistDiscountsSettings = $this->config->get('WishlistDiscounts');

			if(isset($wishlistDiscountsSettings['admin_notification']) && $wishlistDiscountsSettings['admin_notification'] == 'yes') {
				$mailToUser->setBcc($this->config->get('config_email'));
			}

            $mailToUser->send();

            $this->moduleModel->logCustomerNotification($current_customer['customer_id']);
            $mails_sent = $this->session->data['wishlistdiscounts']['total_mails_to_sent'] -  $customers_left;
            $percentage = ceil((($mails_sent / $this->session->data['wishlistdiscounts']['total_mails_to_sent']) * 100));
            $json = array(
                'status' => 'success',
                'data'  => array(
                    'customers_left' => $customers_left,
                    'mails_sent' => $mails_sent,
                    'persentage' => $percentage
                )
            );
        } 

        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    public function givenCoupons() {		
        $action='givenCoupons';
        $this->listCoupons($action);	
    }

    public function usedCoupons() {		
        $action='usedCoupons';
        $this->listCoupons($action);	
    }

    public function currentWishlists() {		

        $this->load->model('catalog/product');
        $this->load->model('tool/image');



        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'notified_count';
        }
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        } 

        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data   = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );		
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = $languages;
        foreach ($data['languages'] as $key => $value) {
            if(version_compare(VERSION, '2.2.0.0', "<")) {
                $data['languages'][$key]['flag_url'] = 'view/image/flags/'.$data['languages'][$key]['image'];

            } else {
                $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png"';
            }
        }



        $data['modulePath'] = $this->modulePath;

        $languageVariables = array (
            'column_customer_name',
            'column_date_added',
            'column_customer_wishlist',
            'column_customer_email',
            'show_wishlist',
            'button_send_discounts_to_selected',
            'button_send_discount_to_all',
            'text_no_results',
            'wishlist_heading',
            'column_notified'
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $url = '';
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $pagination               = new Pagination();


        $data['customers']  = $this->moduleModel->getCustomersWithWishList($data, $store_id); 

        $total_customers  = $this->moduleModel->getTotalCustomersWithWishList($store_id);

        $data['sort_name']       = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=name' . $url;
        $data['sort_date_added'] = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url;
        $data['sort_email']      = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url;

        $data['sort_notified_count']      = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . '&sort=notified_count ' . $url;
        if(version_compare(VERSION, '2.1.0.1', "<")) {
            $data['customers_url'] = $this->url->link('sale/customer/edit', 'user_token=' . $this->session->data['user_token'], 'SSL');
        } else {
            $data['customers_url'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'], 'SSL');
        }

        $data['continue'] = $this->url->link('account/account', '', 'SSL');
        $pagination->total        = $total_customers;
        $pagination->page         = $page;
        $pagination->limit        = $this->config->get('config_limit_admin');
        $pagination->text         = $this->language->get('text_pagination');
        $pagination->url          = 'index.php?route='.$this->modulePath.'/currentWishlists&user_token=' . $this->session->data['user_token'] . $url . '&page={page}';


        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) { 
            $url .= '&order=' . $this->request->get['order'];
        }


        $data['pagination'] = $pagination->render();
        $data['sort']       = $sort;
        $data['order']      = $order;

        foreach ($data['customers'] as &$customer) {
            $customer['date_added_time'] = strtotime($customer['date_added']);
        }

        $this->response->setOutput($this->load->view($this->modulePath.'/customerList', $data)); 	
    }

    public function archivedWishlists() {		

        $this->load->model('catalog/product');
        $this->load->model('tool/image');



        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'firstname, lastname';
        }
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        } 

        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data   = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );		


        $data['modulePath'] = $this->modulePath;

        $languageVariables = array (
            'column_customer_name',
            'column_date_added',
            'column_customer_wishlist',
            'column_customer_email',
            'show_wishlist',
            'button_send_discounts_to_selected',
            'button_send_discount_to_all',
            'text_no_results',
            'wishlist_heading'
        );

        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $url = '';
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $pagination               = new Pagination();

        $data['customers']  = $this->moduleModel->gethWishListArchive($data, $store_id);  
        $total_customers = $this->moduleModel->getTotalWishListArchive($store_id);
        $data['sort_name']       = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=name' . $url;
        $data['sort_date_added'] = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url;
        $data['sort_email']      = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url;
        $data['continue'] = $this->url->link('account/account', '', 'SSL');
        $pagination->total        = $total_customers;
        $pagination->page         = $page;
        $pagination->limit        = 2;
        $pagination->text         = $this->language->get('text_pagination');
        $pagination->url          = 'index.php?route='.$this->modulePath.'/archivedWishlists&user_token=' . $this->session->data['user_token'] . $url . '&page={page}';


        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) { 
            $url .= '&order=' . $this->request->get['order'];
        }


        $data['pagination'] = $pagination->render();
        $data['sort']       = $sort;
        $data['order']      = $order;

        $this->response->setOutput($this->load->view($this->modulePath.'/customerArchivedList', $data)); 
    }

    public function install() {
        $this->moduleModel->install();
    }	

    public function uninstall() {
        $this->load->model('setting/setting');	
        $this->load->model('setting/store');
        $this->model_setting_setting->deleteSetting('WishlistDiscounts', 0);
        $stores=$this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting('WishlistDiscounts', $store['store_id']);
        }
        $this->load->model($this->modulePath);
        $this->moduleModel->uninstall();
    }

    private function listCoupons($action) { 

        $this->load->language($this->modulePath);

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id']; 
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id']; 
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
        }
        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }
        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['givenCoupons']       = array();

        $data                        = array(
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );

        $data['modulePath'] = $this->modulePath;


        if($action == 'usedCoupons') {
            $coupon_total = $this->moduleModel->getTotalUsedCoupons($store_id); 
            $coupons  = $this->moduleModel->getUsedCoupons($data, $store_id);

        } else { 
            $coupon_total  = $this->moduleModel->getTotalGivenCoupons($store_id);
            $coupons  =      $this->moduleModel->getGivenCoupons($data, $store_id);
        }
        if(!empty($coupons)) {
            foreach ($coupons as $coupon) {
                $customer = $this->moduleModel->getCustomerName($coupon['customer_id']);
                $data['coupons'][] = array(
                    'coupon_id' => $coupon['coupon_id'],
                    'name' => $coupon['name'],
                    'customer_name' => $customer['firstname'].' '.$customer['lastname'],
                    'customer_email' => $customer['email'],
                    'code' => $coupon['code'],
                    'discount' => $coupon['discount'],
                    'date_start' => date($this->language->get('date_format_short'), strtotime($coupon['date_start'])),
                    'date_end' => date($this->language->get('date_format_short'), strtotime($coupon['date_end'])),
                    'status' => ($coupon['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                    'date_added' => $coupon['date_added']
                );
            }
        }

        $languageVariables = array(
            'text_no_results',
            'column_coupon_name',
            'column_code',   
            'column_discount',  
            'column_date_start',
            'column_date_end',  
            'column_status',  
            'button_insert',    
            'button_delete',    
            'column_email',    
            'column_date_added',
            'column_customer_email'
        );
        foreach ($languageVariables as $languageVariable) {
            $data[$languageVariable] = $this->language->get($languageVariable);
        }

        $data['heading_title'] = $this->language->get('heading_title').' '.$this->version;

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $url = '';
        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }
        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name']       = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=name' . $url;
        $data['sort_code']       = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=code' . $url;
        $data['sort_discount']   = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=discount' . $url;
        $data['sort_date_start'] = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=date_start' . $url;
        $data['sort_date_end']   = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=date_end' . $url;
        $data['sort_status']     = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=status' . $url;
        $data['sort_email']      = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=email' . $url;
        $data['sort_discount_type'] = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=discount_type' . $url;
        $data['sort_date_added']   = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url;
        $url = '';
        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }
        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }
        $pagination               = new Pagination();
        $pagination->total        = $coupon_total;
        $pagination->page         = $page;
        $pagination->limit        = $this->config->get('config_limit_admin');
        $pagination->text         = $this->language->get('text_pagination');
        $pagination->url          = 'index.php?route='.$this->modulePath.'/'.$action.'&user_token=' . $this->session->data['user_token'] . $url . '&page={page}';
        $data['pagination'] = $pagination->render();
        $data['sort']       = $sort;
        $data['order']      = $order;

        $this->response->setOutput($this->load->view($this->modulePath.'/coupon', $data)); 

    }

    private function generateUniqueRandomVoucherCode() {
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

    private function getCatalogURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;   
    }

    private function getServerURL() {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $serverURL = HTTPS_SERVER;
        } else {
            $serverURL = HTTP_SERVER;
        }
        return $serverURL;   
    }		

    private function getCurrentStore($store_id) {    
        if($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL(); 
        }
        return $store;
    }
}
