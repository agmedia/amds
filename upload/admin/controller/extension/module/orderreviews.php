<?php
class ControllerExtensionModuleOrderReviews extends Controller
{
    /**
     * @property   String $module_path String containing the path expression for OrderReviews files.
     * @property   String $call_model String containing the call to OrderReviews model.
     */
    private $data = array();
    private $error = array();
    private $version;
    private $module_path;
    private $extensions_link;
    private $language_variables;
    private $moduleModel;
    private $moduleName;
    private $call_model;
    private $vendorFolder;
    private $eventGroup = "orderreviews";
    /**
     * OrderReviews Controller Constructor
     * initialize necessary dependencies from the OpenCart framework.
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        
        $this->config->load('isenselabs/orderreviews');
        $this->data['default_language_id'] = $this->config->get('config_language_id');
		$this->moduleName         = $this->config->get('orderreviews_name');
		$this->call_model         = $this->config->get('orderreviews_model');
		$this->module_path        = $this->config->get('orderreviews_path');
		$this->version            = $this->config->get('orderreviews_version');
        $this->vendorFolder       = $this->config->get('orderreviews_vendor_folder');
		
		$this->extensions_link    = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL');
		
		$this->load->model($this->module_path);
		$this->moduleModel        = $this->{$this->call_model};
		
		$this->language_variables = $this->load->language($this->module_path);
        
        foreach ($this->language_variables as $code => $languageVariable) {
            $this->data[$code] = $languageVariable;
        }
        
        //Loading framework models
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');
        $this->load->model('customer/customer_group');
		
		$this->data['module_path']		= $this->module_path;
		$this->data['moduleName']		= $this->moduleName;
		$this->data['moduleNameMail']	= $this->moduleName . 'MailTemplate';
		$this->data['moduleNameSmall']	= $this->moduleName;
        
		
		$this->data['user_token']      = $this->session->data['user_token'];
    }
    
    public function index()
    {
        //echo '<pre>'; var_dump($this->data);exit;

		if(!$this->moduleModel->checkDbColumn('orderreviews_log', 'review_id')){
            $this->moduleModel->update();
			$this->load->model("setting/event");
			$this->model_setting_event->addEvent($this->eventGroup, "admin/model/catalog/review/deleteReview/after", $this->module_path . "/adminModelCatalogReviewDeleteAfter");
        }

		if(!$this->moduleModel->checkDbTable('orderreviews_mail_log')){
            $this->moduleModel->update392();
        }
        if(!$this->moduleModel->checkDbColumn('orderreviews_mail', 'mail_id')){
            $this->moduleModel->updateMailId();
        }

		if(!$this->moduleModel->checkDbTable('orderreviews_setting')){
			$dataStores		= array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . '&nbsp;' . $this->data['text_default'].'', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());

			$moduleSettings = array();

			foreach ($dataStores  as $st) {
				$moduleSettingsStores				= $this->model_setting_setting->getSetting($this->moduleName, $st['store_id']);
				$moduleSettings[$st['store_id']]	= (isset($moduleSettingsStores[$this->moduleName])) ? $moduleSettingsStores[$this->moduleName] : array();
			}

            $this->moduleModel->update3101($moduleSettings);
        }

		$this->setupEvent();

    	$this->document->setTitle($this->language->get('heading_title') . ' ' . $this->version);
        $this->data['text_email_default_message'] = str_replace('{catalog_link}', HTTP_CATALOG,  $this->data['text_email_default_message']);
       
        $this->document->addStyle('view/stylesheet/' . $this->moduleName . '/' . $this->moduleName . '.css');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/nprogress.js');
        $this->document->addScript('view/javascript/summernote/summernote.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');

        $this->data['call_model'] = $this->call_model;
        
        if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
        }
        
        $this->data['catalogURL'] = $this->getCatalogURL();
        $store                    = $this->getCurrentStore($this->request->get['store_id']);
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post[$this->moduleName]['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post[$this->moduleName]['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }
            
            if (isset($this->request->post[$this->moduleName]['Enabled']) && $this->request->post[$this->moduleName]['Enabled'] == 'yes') {
                $status = 1;
            } else {
                $status = 0;
            }
            
            $this->moduleModel->editSetting($this->moduleName, $this->request->post, $this->request->post['store_id']);
            
            $module_status = array(
                'group' => $this->config->get('orderreviews_status_group'),
                'value' => $this->config->get('orderreviews_status_value')
            );
            
            $this->model_setting_setting->editSetting($module_status['group'], array(
                $module_status['value'] => $status
            ));
            
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link($this->module_path, 'store_id=' . $this->request->post['store_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL'));
        }
        
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
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->extensions_link
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
        
        $this->data['heading_title'] = $this->language->get('heading_title') . ' ' . $this->version;
        
        $this->data['currency'] = $this->config->get('config_currency');
        
        $this->data['stores']    = array_merge(array(
            0 => array(
                'store_id' => '0',
                'name' => $this->config->get('config_name') . ' (' . $this->data['text_default'] . ')',
                'url' => HTTP_SERVER,
                'ssl' => HTTPS_SERVER
            )
        ), $this->model_setting_store->getStores());
        $this->data['languages'] = $this->model_localisation_language->getLanguages();
        
        foreach ($this->data['languages'] as $key => $value) {
            $this->data['languages'][$key]['flag_url'] = 'language/' . $this->data['languages'][$key]['code'] . '/' . $this->data['languages'][$key]['code'] . '.png"';
        }
        
		$this->data['store']                           = $store;
        $this->data['text_bcc_help'] = str_replace('{e_mail}', $this->config->get('config_email'), $this->data['text_bcc_help']);
		$this->data['action']                          = $this->url->link($this->module_path, 'user_token=' . $this->session->data['user_token'], 'SSL');
		$this->data['cancel']                          = $this->extensions_link;
		$this->data['moduleSettings']                  = $this->moduleModel->getSetting($this->moduleName, $store['store_id']);
		$this->data['moduleData']                      = (isset($this->data['moduleSettings'][$this->moduleName])) ? $this->data['moduleSettings'][$this->moduleName] : array();
		$this->data['moduleData']['orderStatuses']     = $this->getAllOrderStatuses();
		
		$this->data['moduleData']['customerGroups']    = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->data['moduleData']['ReviewMail'])) {
			foreach ($this->data['moduleData']['ReviewMail'] as $key => $value) {
				$this->data['moduleData']['ReviewMail'][$key]['products']= !empty($this->data['moduleData']['ReviewMail'][$key]['products'])?$this->moduleModel->getProductsInIDArray($this->data['moduleData']['ReviewMail'][$key]['products']):array();
				$this->data['moduleData']['ReviewMail'][$key]['categories']= !empty($this->data['moduleData']['ReviewMail'][$key]['categories'])?$this->moduleModel->getCategoriesByID($this->data['moduleData']['ReviewMail'][$key]['categories']):array();
			}
		}

		$this->data['header']                          = $this->load->controller('common/header');
		$this->data['column_left']                     = $this->load->controller('common/column_left');
		$this->data['footer']                          = $this->load->controller('common/footer');

		$this->data['cronPhpPath'] = '0 0 * * * ';
        if (function_exists('shell_exec') && trim(shell_exec('echo EXEC')) == 'EXEC') {
            $this->data['cronPhpPath'] .= shell_exec("which php"). ' ';
        } else {
            $this->data['cronPhpPath'] .= 'php ';
        }
        $this->data['cronPhpPath'] .= dirname(DIR_APPLICATION) . '/' . $this->vendorFolder . '/'.$this->moduleName.'/sendEmails.php';

		// License Data
		$hostname                                      = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		$this->data['hostname']                        = (strstr($hostname, 'http://') === false) ? 'http://' . $hostname : $hostname;
		$this->data['hostname_base64']                 = base64_encode($this->data['hostname']);
		$this->data['time_now']                        = time();
		
		$this->data['licenseExpireDate']               = !empty($this->data['moduleData']['LicensedOn']) ? date("F j, Y", strtotime($this->data['moduleData']['License']['licenseExpireDate'])) : "";
		
		$this->data['unlicensedHtml']                  = empty($this->data['moduleData']['LicensedOn']) ? base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBZb3UgYXJlIHJ1bm5pbmcgdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoZSBtb2R1bGUhPC9oND4NCiAgICAgICAgPHA+WW91IGFyZSBydW5uaW5nIGFuIHVubGljZW5zZWQgdmVyc2lvbiBvZiB0aGlzIG1vZHVsZSEgWW91IG5lZWQgdG8gZW50ZXIgeW91ciBsaWNlbnNlIGNvZGUgdG8gZW5zdXJlIHByb3BlciBmdW5jdGlvbmluZywgYWNjZXNzIHRvIHN1cHBvcnQgYW5kIHVwZGF0ZXMuPC9wPjxkaXYgc3R5bGU9ImhlaWdodDo1cHg7Ij48L2Rpdj4NCiAgICAgICAgPGEgY2xhc3M9ImJ0biBidG4tZGFuZ2VyIiBocmVmPSJqYXZhc2NyaXB0OnZvaWQoMCkiIG9uY2xpY2s9IiQoJ2FbaHJlZj0jaXNlbnNlLXN1cHBvcnRdJykudHJpZ2dlcignY2xpY2snKSI+RW50ZXIgeW91ciBsaWNlbnNlIGNvZGU8L2E+DQogICAgPC9kaXY+') : "";
		
		$this->data['licenseDataBase64']               = !empty($this->data['moduleData']['License']) ? base64_encode(json_encode($this->data['moduleData']['License'])) : '';
		$this->data['supportTicketLink']               = 'http://isenselabs.com/tickets/open/' . base64_encode('Support Request') . '/' . base64_encode('141') . '/' . base64_encode($_SERVER['SERVER_NAME']);
		$this->data['moduleData']['LicenseExpireDate'] = !empty($this->data['moduleData']['License']) ? date("F j, Y", strtotime($this->data['moduleData']['License']['licenseExpireDate'])) : "";
		
		$this->data['moduleTabs']                      = $this->getTabs();

		foreach ($this->data['moduleTabs'] as $key => $tab) {
			$tab['template'] = str_replace('\\', '/', $tab['template']);
			$this->data['moduleTabs'][$key]['content'] = $this->load->view($tab['template'], $this->data);
		}
		
        $this->response->setOutput($this->load->view($this->module_path, $this->data));
    }
    
    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', $this->module_path)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
    
    public function get_review_settings()
    {
		$this->data['currency']                     = $this->config->get('config_currency');
		$this->data['languages']                    = $this->model_localisation_language->getLanguages();
		
		foreach ($this->data['languages'] as $key   => $value) {
			$this->data['languages'][$key]['flag_url']  = 'language/' . $this->data['languages'][$key]['code'] . '/' . $this->data['languages'][$key]['code'] . '.png"';
		}
		
		$this->data['reviewmail']['id']             = $this->request->get['reviewmail_id'];
		
		$this->data['data']                         = $this->moduleModel->getSetting($this->moduleName, $this->request->get['store_id']);
		
		$this->data['moduleName']                   = $this->moduleName;
		$this->data['moduleNameMail']				= $this->moduleName . 'MailTemplate';
		$this->data['moduleData']                   = (isset($this->data['data'][$this->moduleName])) ? $this->data['data'][$this->moduleName] : array();
		$this->data['moduleData']['orderStatuses']  = $this->getAllOrderStatuses();
		$this->data['moduleData']['customerGroups'] = $this->model_customer_customer_group->getCustomerGroups();
		$this->data['newAddition']                  = true;
        
        $this->response->setOutput($this->load->view($this->module_path . '/tab_reviewtab', $this->data));
    }
    
    public function getAllSentCoupons()
    {
        if (!empty($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
        }
        
		$this->data['url_link']   = $this->url;
		$this->data['sale_order'] = $this->model_sale_order;
		
		$this->data['store_id']   = $this->request->get['store_id'];
		$this->data['limit']      = 10; // $this->config->get('config_limit_admin')
		$this->data['total']      = $this->moduleModel->getTotalCoupons($this->request->get['store_id']);
		
		$pagination               = new Pagination();
		$pagination->total        = $this->data['total'];
		$pagination->page         = $page;
		$pagination->limit        = $this->data['limit'];
		$pagination->url          = $this->url->link($this->module_path . '/getAllSentCoupons', 'user_token=' . $this->session->data['user_token'] . '&page={page}&store_id=' . $this->request->get['store_id'], 'SSL');
		
		$this->data['pagination'] = $pagination->render();
		$this->data['sources']    = $this->moduleModel->getAllGeneratedCoupons($page, $this->data['limit'], $this->request->get['store_id']);
        
        
        $this->data['results']  = sprintf($this->language->get('text_pagination'), ($this->data['total']) ? (($page - 1) * $this->data['limit']) + 1 : 0, ((($page - 1) * $this->data['limit']) > ($this->data['total'] - $this->data['limit'])) ? $this->data['total'] : ((($page - 1) * $this->data['limit']) + $this->data['limit']), $this->data['total'], ceil($this->data['total'] / $this->data['limit']));
        $this->data['store_id'] = $this->request->get['store_id'];
        
        $this->response->setOutput($this->load->view($this->module_path . '/view_all_coupons', $this->data));
    }
    
    public function getAllReviews()
    {
        $this->load->model('catalog/product');
        if (!empty($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = 1;
        }
        
        if (!isset($this->request->get['store_id'])) {
            $this->request->get['store_id'] = 0;
        }
        
		$this->data['url_link']   = $this->url;
		$this->data['sale_order'] = $this->model_sale_order;
		
		$this->data['store_id']   = $this->request->get['store_id'];
		$this->data['limit']      = 10; // $this->config->get('config_limit_admin')
		$this->data['total']      = $this->moduleModel->getTotalReviews($this->request->get['store_id']);

        $module_name    = $this->moduleName;
        $store_id       = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0;
        $module_setting = $this->moduleModel->getSetting($module_name, $store_id);
        $this->data['setting'] = (isset($module_setting[$module_name])) ? $module_setting[$module_name] : array();
        $this->data['text_agree'] = $this->language->get('text_agree');
        $this->data['text_privacy_policy'] = $this->language->get('text_privacy_policy');
        $this->data['text_empty_name'] = $this->language->get('text_empty_name');

		$pagination               = new Pagination();
		$pagination->total        = $this->data['total'];
		$pagination->page         = $page;
		$pagination->limit        = $this->data['limit'];
		$pagination->url          = $this->url->link($this->module_path . '/getAllReviews', 'user_token=' . $this->session->data['user_token'] . '&page={page}&store_id=' . $this->request->get['store_id'], 'SSL');
		
		$this->data['pagination'] = $pagination->render();
		$this->data['sources']    = $this->moduleModel->getAllReviews($page, $this->data['limit'], $this->request->get['store_id']);
        
        foreach ($this->data['sources'] as $key => $source) {
            $product_description = $this->model_catalog_product->getProductDescriptions($source['review_product_id']);
            if ($product_description) {
				$this->data['sources'][$key]['name'] = $product_description[$this->config->get('config_language_id')]['name'];
				$this->data['sources'][$key]['url']  = $this->url->link('catalog/product/edit', 'product_id=' . $source['review_product_id'] . '&user_token=' . $this->session->data['user_token'], 'SSL');
            } else {
                $this->data['sources'][$key]['name'] = 'Not Provided';
                $this->data['sources'][$key]['url']  = $this->url->link('common/home', '', 'SSL');
            }
        }
        
        $this->data['results']  = sprintf($this->language->get('text_pagination'), ($this->data['total']) ? (($page - 1) * $this->data['limit']) + 1 : 0, ((($page - 1) * $this->data['limit']) > ($this->data['total'] - $this->data['limit'])) ? $this->data['total'] : ((($page - 1) * $this->data['limit']) + $this->data['limit']), $this->data['total'], ceil($this->data['total'] / $this->data['limit']));
        $this->data['store_id'] = $this->request->get['store_id'];
        
        $this->response->setOutput($this->load->view($this->module_path . '/view_reviews_log', $this->data));
        
    }
    
    
    public function getAllOrderStatuses()
    {
        $query = 'SELECT * FROM ' . DB_PREFIX . 'order_status WHERE language_id=' . $this->config->get('config_language_id');
        return $this->db->query($query)->rows;
    }
    
    // Remove all expired coupons
    public function removeallexpiredcoupons()
    {
        $date_end = date('Y-m-d', time() - 60 * 60 * 24);
        if (isset($this->request->post['remove']) && ($this->request->post['remove'] == true)) {
            
            $run_query = $this->db->query("DELETE FROM `" . DB_PREFIX . "coupon` WHERE `name` LIKE '%OrderReviews Coupon [%' AND `date_end`<='" . $date_end . "'");
            if ($run_query)
                echo "Success!";
        }
    }
    
    public function install()
    {
        $this->moduleModel->install();
        $this->setupEvent();
    }
    
    public function uninstall()
    {
        $this->load->model('design/layout');
        $this->load->model("setting/event");
        $this->model_setting_setting->deleteSetting($this->moduleName, 0);
        $stores = $this->model_setting_store->getStores();
        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting($this->moduleName, $store['store_id']);
        }
        
		$this->moduleModel->uninstall();
		$this->removeEvent();
    }

	private function setupEvent() {
        $this->load->model('setting/event');

        $this->removeEvent();

        $this->model_setting_event->addEvent($this->eventGroup, "admin/model/catalog/review/deleteReview/after", $this->module_path . "/adminModelCatalogReviewDeleteAfter");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/getOrder/after", $this->module_path . "/handleGetOrderAfter");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/model/checkout/order/addOrderHistory/after", $this->module_path . "/handleAddOrderHistory");
        $this->model_setting_event->addEvent($this->eventGroup, "catalog/view/account/order_list/after", $this->module_path . "/viewOrderHistoryAfter");
    }

    private function removeEvent() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this->eventGroup);
    }
    
    private function getTabs()
    {
        $dir = 'extension' . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR;
        
        $result = array();
        
		$name_map = array(
			'tab_controlpanel' => array(
				'name' => $this->data['text_control_panel'],
				'id'   => 'controlpanel',
				'icon' => ''
			),
			'tab_reviews' => array(
				'name' => $this->data['text_received_reviews'],
				'id'   => 'reviews',
				'icon' => ''
			),
			'tab_coupons' => array(
				'name' => $this->data['text_sent_coupons'],
				'id'   => 'coupons',
				'icon' => ''
			),
			'tab_logs' => array(
				'name' => $this->data['text_mails_log'],
				'id'   => 'logs',
				'icon' => ''
			),
			'tab_support' => array(
				'name' => $this->data['text_tab_support'],
				'id'   => 'isense-support',
				'icon' => ''
			)
		);
        
        if (!function_exists('modification_vqmod')) {
            function modification_vqmod($file)
            {
                if (class_exists('VQMod')) {
                    return VQMod::modCheck(modification($file), $file);
                } else {
                    return modification($file);
                }
            }
        }
        
        foreach ($name_map as $file => $info) {
            
            $result[] = array(
                'file'     => modification_vqmod($dir . $file . '.twig'),
				'template' => modification_vqmod($dir . $file),
                'name' => $info['name'],
                'id' => $info['id'],
                'icon' => !empty($info['icon']) ? $info['icon'] : ''
            );
        }
        
        return $result;
    }
    
    private function getCatalogURL()
    {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;
    }
    
    private function getServerURL()
    {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_SERVER;
        } else {
            $storeURL = HTTP_SERVER;
        }
        return $storeURL;
    }
    
    private function getCurrentStore($store_id)
    {
        if ($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name']     = $this->config->get('config_name');
            $store['url']      = $this->getCatalogURL();
        }
        return $store;
    }

	public function adminModelCatalogReviewDeleteAfter(&$route, &$args, &$output)
	{
		$this->load->model('extension/module/orderreviews');
		$this->model_extension_module_orderreviews->deleteReview((int)$args[0]);
	}

	public function getlog() {
		$this->load->model('sale/order');
		if (!empty($this->request->get['page'])) {
			$page = (int) $this->request->get['page'];
		} else {
			$page = 1;
		}

		if(!isset($this->request->get['store_id'])) {
		   $this->request->get['store_id'] = 0;
		}

		$this->data['url_link']   = $this->url;
		$this->data['sale_order'] = $this->model_sale_order;

		$this->data['store_id']   = $this->request->get['store_id'];
		$this->data['token']      = $this->session->data['user_token'];
		$this->data['limit']      = 8; // $this->config->get('config_limit_admin')
		$this->data['total']      = $this->moduleModel->getTotalLog($this->request->get['store_id']);

		$pagination					= new Pagination();
		$pagination->total			= $this->data['total'];
		$pagination->page			= $page;
		$pagination->limit			= $this->data['limit'];
		$pagination->url			= $this->url->link($this->module_path.'/getlog','user_token=' . $this->session->data['user_token'].'&page={page}&store_id='.$this->request->get['store_id'], 'SSL');
		$this->data['pagination']			= $pagination->render();
		$this->data['sources']			= $this->moduleModel->viewLogs($page, $this->data['limit'], $this->request->get['store_id']);

		$this->data['results'] 			= sprintf($this->language->get('text_pagination'), ($this->data['total']) ? (($page - 1) * $this->data['limit']) + 1 : 0, ((($page - 1) * $this->data['limit']) > ($this->data['total'] - $this->data['limit'])) ? $this->data['total'] : ((($page - 1) * $this->data['limit']) + $this->data['limit']), $this->data['total'], ceil($this->data['total'] / $this->data['limit']));
		$this->data['token']      = $this->session->data['user_token'];
		$this->data['store_id']   = $this->request->get['store_id'];

		foreach ($this->data['sources'] as $key => $src) {
			$this->data['sources'][$key]['order_data'] = $this->model_sale_order->getOrder($src['order_id']);
		}

		$this->response->setOutput($this->load->view($this->module_path.'/view_log', $this->data));
	}

	public function deleteLogEntry(){
		$data = $this->request->post['selected_log_entries'];
		$store_id = $this->request->post['store_id'];
		$this->moduleModel->deleteLogEntry($data, $store_id);
	}
}
