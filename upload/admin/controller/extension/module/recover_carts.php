<?php
error_reporting(E_ALL); ini_set('display_errors', 1); 

class ControllerExtensionModuleRecovercarts extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/recover_carts');

		$this->document->setTitle($this->language->get('heading_title2'));

		$this->load->model('setting/setting');
		$this->load->model('extension/ebemail_template');
		
		$this->model_extension_ebemail_template->CreateTables();
		
		if(isset($this->request->get['store_id'])) {
			$data['store_id'] = $this->request->get['store_id'];
		}else{
			$data['store_id']	= 0;
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			
			$this->model_setting_setting->editSetting('module_recover_carts', $this->request->post,$data['store_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			if($this->request->post['stay']==1){
				$this->response->redirect($this->url->link('extension/module/recover_carts', '&store_id='.$data['store_id'].'&user_token=' . $this->session->data['user_token'] , true));
			}else{
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
		}

		$data['heading_title'] = $this->language->get('heading_title2');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_registered'] = $this->language->get('text_registered');
		$data['text_guest'] = $this->language->get('text_guest');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_sendgrid'] = $this->language->get('text_sendgrid');
		$data['text_default_mail'] = $this->language->get('text_default_mail');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_dateformat'] = $this->language->get('entry_dateformat');
		$data['entry_customer_email'] = $this->language->get('entry_customer_email');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_model'] = $this->language->get('entry_model');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_from_date'] = $this->language->get('entry_from_date');
		$data['entry_to_date'] = $this->language->get('entry_to_date');
		$data['entry_vistor'] = $this->language->get('entry_vistor');
		$data['entry_notify'] = $this->language->get('entry_notify');
		$data['entry_Bulk_email'] = $this->language->get('entry_Bulk_email');
		$data['entry_mail_protocol'] = $this->language->get('entry_mail_protocol');
		$data['entry_username'] = $this->language->get('entry_username');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_menu'] = $this->language->get('entry_menu');
		
		$data['tab_control_panel'] = $this->language->get('tab_control_panel');
		$data['tab_abanoned_carts'] = $this->language->get('tab_abanoned_carts');
		$data['tab_coupon_history'] = $this->language->get('tab_coupon_history');
		$data['tab_unused_coupons'] = $this->language->get('tab_unused_coupons');
		$data['tab_used_coupon'] = $this->language->get('tab_used_coupon');
		$data['tab_support'] = $this->language->get('tab_support');
		$data['tab_support'] = $this->language->get('tab_support');
		$data['tab_abandoned_orders'] = $this->language->get('tab_abandoned_orders');
		$data['tab_languge'] = $this->language->get('tab_languge');
		$data['tab_cron'] = $this->language->get('tab_cron');
		
		
		//popup start
		$data['tab_abandoned_popup'] = $this->language->get('tab_abandoned_popup');
		$data['text_yes_required'] = $this->language->get('text_yes_required');
		$data['entry_heading'] = $this->language->get('entry_heading');
		$data['tab_general'] = $this->language->get('tab_general');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_lastname'] = $this->language->get('entry_lastname');
		
		$data['column_name'] = $this->language->get('column_name');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_cron_add'] = $this->language->get('button_cron_add');
		
		if($data['store_id']){
            $store_infox = $this->model_setting_setting->getSetting('config', $data['store_id']);
            if ($this->request->server['HTTPS']) {
                $data['server'] = ($store_infox['config_ssl'] ? $store_infox['config_ssl'] : HTTPS_CATALOG);
            } else {
                $data['server'] = ($store_infox['config_ssl'] ? $store_infox['config_ssl'] : HTTP_CATALOG);
            }
        }else{
            if ($this->request->server['HTTPS']) {
                $data['server'] = HTTPS_CATALOG;
            } else {
                $data['server'] = HTTP_CATALOG;
            }
        }
		
		
		
		$this->load->model('setting/store');
		$data['stores'] = $this->model_setting_store->getStores();

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
		
		
		if (isset($this->error['module_recover_carts_password'])) {
			$data['error_module_recover_carts_password'] = $this->error['module_recover_carts_password'];
		} else {
			$data['error_module_recover_carts_password'] = '';
		}
		
		if (isset($this->error['module_recover_carts_username'])) {
			$data['error_module_recover_carts_username'] = $this->error['module_recover_carts_username'];
		} else {
			$data['error_module_recover_carts_username'] = '';
		}
		
		$data['user_token'] = $this->session->data['user_token'];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title2'),
			'href' => $this->url->link('extension/module/recover_carts', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['action'] = $this->url->link('extension/module/recover_carts', 'user_token=' . $this->session->data['user_token']. '&store_id='. $data['store_id'], true);
		
		$data['store_action'] =  $this->url->link('extension/module/recover_carts','user_token=' . $this->session->data['user_token'], true . '&type=module');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		$data['email_template'] = $this->url->link('extension/ebemail_template', 'user_token=' . $this->session->data['user_token'], true);
		
		$store_info = $this->model_setting_setting->getSetting('module_recover_carts', $data['store_id']);

		if (isset($this->request->post['module_recover_carts_status'])) {
			$data['module_recover_carts_status'] = $this->request->post['module_recover_carts_status'];
		}elseif(isset($store_info['module_recover_carts_status'])){
			$data['module_recover_carts_status'] = $store_info['module_recover_carts_status'];
		} else {
			$data['module_recover_carts_status'] = '';
		}
		
		if (isset($this->request->post['module_recover_carts_protocol'])) {
			$data['module_recover_carts_protocol'] = $this->request->post['module_recover_carts_protocol'];
		}elseif(isset($store_info['module_recover_carts_protocol'])){
			$data['module_recover_carts_protocol'] = $store_info['module_recover_carts_protocol'];
		} else {
			$data['module_recover_carts_protocol'] = '';
		}
		
		
		if (isset($this->request->post['module_recover_carts_password'])) {
			$data['module_recover_carts_password'] = $this->request->post['module_recover_carts_password'];
		}elseif(isset($store_info['module_recover_carts_password'])){
			$data['module_recover_carts_password'] = $store_info['module_recover_carts_password'];
		} else {
			$data['module_recover_carts_password'] = '';
		}
		
		
		if (isset($this->request->post['module_recover_carts_username'])) {
			$data['module_recover_carts_username'] = $this->request->post['module_recover_carts_username'];
		}elseif(isset($store_info['module_recover_carts_username'])){
			$data['module_recover_carts_username'] = $store_info['module_recover_carts_username'];
		} else {
			$data['module_recover_carts_username'] = '';
		}
		
		if (isset($this->request->post['module_recover_carts_clear_cart'])) {
			$data['module_recover_carts_clear_cart'] = $this->request->post['module_recover_carts_clear_cart'];
		}elseif(isset($store_info['module_recover_carts_clear_cart'])){
			$data['module_recover_carts_clear_cart'] = $store_info['module_recover_carts_clear_cart'];
		} else {
			$data['module_recover_carts_clear_cart'] = '';
		}
		
		if (isset($this->request->post['module_recover_carts_menu'])) {
			$data['module_recover_carts_menu'] = $this->request->post['module_recover_carts_menu'];
		}elseif(isset($store_info['module_recover_carts_menu'])){
			$data['module_recover_carts_menu'] = $store_info['module_recover_carts_menu'];
		} else {
			$data['module_recover_carts_menu'] = '';
		}
		
	
		
		/*23-10-2017*/
		if (isset($this->request->post['module_recover_carts_unknown_user'])) {
			$data['module_recover_carts_unknown_user'] = $this->request->post['module_recover_carts_unknown_user'];
		}elseif(isset($store_info['module_recover_carts_unknown_user'])){
			$data['module_recover_carts_unknown_user'] = $store_info['module_recover_carts_unknown_user'];
		} else {
			$data['module_recover_carts_unknown_user'] = 1;
		}
		
		$this->load->model('extension/ebcart');
		$data['totalunknownrecord'] = $this->model_extension_ebcart->getunkownrecordclean();
		$data['totalexpirecoupons'] = $this->model_extension_ebcart->getexpirecoupons();
		
		if($this->config->get('module_recover_carts_unknown_user')){
			$filter_unknown = $this->config->get('module_recover_carts_unknown_user');
		}else{
			$filter_unknown = '';
		}
		
		$filter_data = array(
		  'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $data['store_id'],
		);
		
		$data['totalebcarts'] = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		
		//Only Guest
		$filter_data = array(
		  'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $data['store_id'],
		  'filter_vistor'	=> 2,
		);
		
		$data['guestebcarts'] = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		//Only Registered
		$filter_data = array(
		  'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $data['store_id'],
		  'filter_vistor'	=> 1,
		);
		
		$data['registeredebcarts'] = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		//Only Notify
		$filter_data = array(
		  'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $data['store_id'],
		  'filter_notify'	=> 1,
		);
		
		$data['totalnotify'] = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		//Only Unnotify
		$filter_data = array(
		  'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $data['store_id'],
		  'filter_notify'	=> 0,
		);
		
		$data['totalunnotify'] = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		///unused coupons
		$filter_data = array(
		  'filter_unused_coupons'	=> true,
		);
		$data['total_unused_coupons'] = $this->model_extension_ebemail_template->getTotalCustomerCoupons($filter_data);
		
		//used coupons
		$filter_data = array(
		  'filter_unused_coupons'	=> false,
		);
		$data['total_used_coupons'] = $this->model_extension_ebemail_template->getTotalCustomerCoupons($filter_data);
		$data['visitor_hisorys']=array();
		$toptenlastvistor = $this->model_extension_ebcart->getvistorhistory();
		foreach($toptenlastvistor as $result){
			$data['visitor_hisorys'][] = array(
			  'visit_page' 		=>  $result['link'],
			  'total_count' =>  '<span class="btn-sm btn-success">'.$result['total_count'].'</span>',
			  'visit_date'  =>  date($this->language->get('datetime_format'),strtotime($result['date_added']))
			);
		}
		/*23-10-2017*/
		
		if (isset($this->request->post['module_recover_carts_count'])) {
			$data['module_recover_carts_count'] = $this->request->post['module_recover_carts_count'];
		}elseif(isset($store_info['module_recover_carts_count'])){
			$data['module_recover_carts_count'] = $store_info['module_recover_carts_count'];
		} else {
			$data['module_recover_carts_count'] = '';
		}
		
		if (isset($this->request->post['module_recover_carts_cronlinks'])) {
			$data['module_recover_carts_cronlinks'] = $this->request->post['module_recover_carts_cronlinks'];
		}elseif(isset($store_info['module_recover_carts_cronlinks'])){
			$data['module_recover_carts_cronlinks'] = $store_info['module_recover_carts_cronlinks'];
		} else {
			$data['module_recover_carts_cronlinks'] = '';
		}
		
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {
			if (isset($this->request->post['module_recover_carts_label' . $language['language_id']])) {
				$data['module_recover_carts_label'][$language['language_id']] = $this->request->post['out_of_stock_button_label' . $language['language_id']];
			} elseif(isset($store_info['module_recover_carts_label'. $language['language_id']])){
				$data['module_recover_carts_label'][$language['language_id']] = $store_info['module_recover_carts_label'. $language['language_id']];
			} else {
				$data['module_recover_carts_label'][ $language['language_id']] = '';
			}
		}

		$data['languages'] = $languages;
		
		
		$this->load->model('extension/ebcart');
		$filter_data=array(
		  'filter_store_id' => $data['store_id'],
		  'filter_notify' => 0,
		  'filter_unknown'	=> $filter_unknown,
		);
		$ebcart_info = $this->model_extension_ebcart->getebcarts($filter_data);
		$total_count = count($ebcart_info);
		if($this->config->get('module_recover_carts_count')){
			$data['count']  = '('. $total_count .')';
		}else{
			$data['count'] = '';
		}
		
		$this->load->model('extension/ebemail_template');
		$data['templates']  = $this->model_extension_ebemail_template->getEmailTemplates(array());
		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ebcart/ebcarts', $data));
	}
	
	public function getOrders(){
		$this->language->load('extension/module/recover_carts');
		$this->load->model('extension/ebcart');
		$this->load->model('localisation/language');
		
		
		$data['column_action'] = $this->language->get('column_action');
		$data['column_id'] = $this->language->get('column_id');
		$data['column_customerifno'] = $this->language->get('column_customerifno');
		$data['column_vistor_type'] = $this->language->get('column_vistor_type');
		$data['column_cart_products'] = $this->language->get('column_cart_products');
		$data['column_ip'] = $this->language->get('column_ip');
		$data['column_notify'] = $this->language->get('column_notify');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_currency'] = $this->language->get('text_currency');
		
		if(isset($this->request->get['filter_from'])){
			$filter_from = $this->request->get['filter_from'];
		}else{
			$filter_from = null;
		}
		
		if(isset($this->request->get['filter_to'])){
			$filter_to = $this->request->get['filter_to'];
		}else{
			$filter_to = null;
		}
		
		if(isset($this->request->get['store_id'])){
			$store_id = $this->request->get['store_id'];
		}else{
			$store_id = 0;
		}
		
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url = '';
		
		if (isset($this->request->get['filter_from'])) {
			$url .= '&filter_from=' . $this->request->get['filter_from'];
		}

		if (isset($this->request->get['filter_to'])) {
			$url .= '&filter_to=' . $this->request->get['filter_to'];
		}
		
		$filter_data = array(
		  'filter_from'		=> $filter_from,
		  'filter_to'		=> $filter_to,
		  'filter_store_id'		=> $store_id,
		  'start' 			=> ($page - 1) * $this->config->get('config_limit_admin'),
		  'limit' 			=> $this->config->get('config_limit_admin')
		);
		
		$total_ebcarts = $this->model_extension_ebcart->getTotalOrders($filter_data);
		$data['orders'] = $this->model_extension_ebcart->getCompleteOrders($filter_data);
		
		
		$pagination = new Pagination();
		$pagination->total = $total_ebcarts;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/recover_carts/getOrders', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total_ebcarts) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total_ebcarts - $this->config->get('config_limit_admin'))) ? $total_ebcarts : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total_ebcarts, ceil($total_ebcarts / $this->config->get('config_limit_admin')));
		
		
		$this->response->setOutput($this->load->view('extension/ebcart/eborders', $data));
	}
	
	public function getebcarts(){
		//GET EBCARTS
		$this->language->load('extension/module/recover_carts');
		$this->load->model('extension/ebcart');
		$this->load->model('localisation/language');
		
		if(isset($this->request->get['store_id'])) {
			$filter_store_id = $this->request->get['store_id'];
		}else{
			$filter_store_id	= 0;
		}
		
		if(isset($this->request->get['filter_from'])){
			$filter_from = $this->request->get['filter_from'];
		}else{
			$filter_from = null;
		}
		
		if(isset($this->request->get['filter_to'])){
			$filter_to = $this->request->get['filter_to'];
		}else{
			$filter_to = null;
		}
		
		if(isset($this->request->get['filter_vistor'])){
			$filter_vistor = $this->request->get['filter_vistor'];
		}else{
			$filter_vistor = null;
		}
		
		if(isset($this->request->get['filter_notify'])){
			$filter_notify = $this->request->get['filter_notify'];
		}else{
			$filter_notify = 0;
		}
		
		if($this->config->get('module_recover_carts_unknown_user')){
			$filter_unknown = $this->config->get('module_recover_carts_unknown_user');
		}else{
			$filter_unknown = '';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url = '';

		if (isset($this->request->get['filter_store_id'])) {
			$url .= '&filter_store_id=' . $this->request->get['filter_store_id'];
		}

		if (isset($this->request->get['filter_from'])) {
			$url .= '&filter_from=' . $this->request->get['filter_from'];
		}

		if (isset($this->request->get['filter_to'])) {
			$url .= '&filter_to=' . $this->request->get['filter_to'];
		}
		
		if (isset($this->request->get['filter_vistor'])) {
			$url .= '&filter_vistor=' . $this->request->get['filter_vistor'];
		}
		
		if (isset($this->request->get['filter_notify'])) {
			$url .= '&filter_notify=' . $this->request->get['filter_notify'];
		}
		
		
		$data['column_action'] = $this->language->get('column_action');
		$data['column_id'] = $this->language->get('column_id');
		$data['column_customerifno'] = $this->language->get('column_customerifno');
		$data['column_vistor_type'] = $this->language->get('column_vistor_type');
		$data['column_cart_products'] = $this->language->get('column_cart_products');
		$data['column_ip'] = $this->language->get('column_ip');
		$data['column_notify'] = $this->language->get('column_notify');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');
		$data['entry_last_visited'] = $this->language->get('entry_last_visited');
		$data['text_no_results'] = $this->language->get('text_no_results');
		
		
		$data['text_currency'] = $this->language->get('text_currency');
		
		$data['button_remove'] = $this->language->get('button_remove');
		$data['selected'] = array();
		
		$filter_data = array(
		 'filter_unknown'	=> $filter_unknown,
		  'filter_store_id'	=> $filter_store_id,
		  'filter_from'		=> $filter_from,
		  'filter_to'		=> $filter_to,
		  'filter_vistor'	=> $filter_vistor,
		  'filter_notify'	=> $filter_notify,
		  'start' 			=> ($page - 1) * $this->config->get('config_limit_admin'),
		  'limit' 			=> $this->config->get('config_limit_admin')
		);
		
		$total_ebcarts = $this->model_extension_ebcart->getTotalbcarts($filter_data);
		
		$results = $this->model_extension_ebcart->getebcarts($filter_data);
		
		$data['ebcarts']=array();
		foreach($results as $result){
			//VISTOR LAST VISIT HISTORY
			$visitor_hisory = $this->model_extension_ebcart->getlastvisit($result['ebabandonedcart_id']);
			$visit_page = '';
			$visit_date = '';
			$visit_time = '';
			if(isset($visitor_hisory['link'])):
			$visit_page = $visitor_hisory['link'];
			endif;
			if(isset($visitor_hisory['date_added'])):
			$visit_date = date($this->language->get('datetime_format'),strtotime($visitor_hisory['date_added']));
			endif;
			
			if(isset($visitor_hisory['time_added'])):
			$visit_time = $visitor_hisory['time_added'];
			endif;
			
			$language_info = $this->model_localisation_language->getLanguage($result['language_id']);
			$products = $this->model_extension_ebcart->getebcartproducts($result['ebabandonedcart_id']);
			$data['ebcarts'][]=array(
			   'ebabandonedcart_id' => $result['ebabandonedcart_id'],
			   'visitor'        	=> ($result['customer_id'] ? '<span class="btn-sm btn-success">'. $this->language->get('text_registered').'</span>' : '<span class="btn-sm btn-success">'. $this->language->get("text_guest").'</span>'),
			   'name'         	    => trim($result['firstname'].' '. $result['lastname']),
			   'email'         		=> $result['email'],
			   'telephone'         	=> $result['telephone'],
			   'notify_status'      => ($result['notify_status'] ? '<span class="btn-sm btn-success">'.$this->language->get('text_yes').'</span>' : '<span class="btn-sm btn-success">'.$this->language->get('text_no').'</span>'),
			   'date_added'         => date($this->language->get('datetime_format'),strtotime($result['date_added'])),
			   'store'              => ($result['store_id'] ? $result['name'] : $this->language->get('text_default')),
			   'language'			=> (isset($language_info['name']) ? $language_info['name'] : ''),
			   'language_code'		=> (isset($language_info['code']) ? $language_info['code'] : ''),
			   'ebcart_products'	=> $products,
			   'currency'			=> $result['currency'],
			   'ip'					=> $result['ip'],
			   'visit_link'			=> $visit_page,
			   'visit_page' 		=>  substr($visit_page,-30),
			   'visit_date'			=> $visit_date,
			   'visit_time'			=> $visit_time,
			   'cart_total'         => $this->model_extension_ebcart->getebcarttotalprice($result['ebabandonedcart_id']),
			   'ip_href'			=> 'http://whatismyipaddress.com/ip/'. $result['ip'],
			);
		}
		
		
		
		$pagination = new Pagination();
		$pagination->total = $total_ebcarts;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/recover_carts/getebcarts', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total_ebcarts) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total_ebcarts - $this->config->get('config_limit_admin'))) ? $total_ebcarts : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total_ebcarts, ceil($total_ebcarts / $this->config->get('config_limit_admin')));

		
		$this->response->setOutput($this->load->view('extension/ebcart/ebcart_products', $data));
	}
	
	public function vistorhistory(){
		$this->load->model('extension/ebcart');
		if(isset($this->request->post['ebabandonedcart_id'])){
			$ebabandonedcart_id = $this->request->post['ebabandonedcart_id'];
		}else{
			$ebabandonedcart_id = 0;
		}
		$data['visitor_hisorys'] = array();
		$results = $this->model_extension_ebcart->getlastvisithistory($ebabandonedcart_id);
		foreach($results as $result){
			$data['visitor_hisorys'][] = array(
			  'link' 			=>  $result['link'],
			  'visit_page' 		=>  substr($result['link'],-30),
			  'total_count' 	=>  '<span class="btn-sm btn-success">'.$result['total_count'].'</span>',
			  'visit_date'  	=>  date($this->language->get('datetime_format'),strtotime($result['date_added']))
			);
		}
		
		$this->response->setOutput($this->load->view('extension/ebcart/vistorhistory', $data));
	}
	
	/*23-10-2017*/
	public function cleanrecord(){
		$json=array();
		$this->load->model('extension/ebcart');
		$this->model_extension_ebcart->deleteunkownrecordclean();
		$this->session->data['success'] = "Success: unkown user's record has deleted";
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function cleancoupons(){
		$json=array();
		$this->load->model('extension/ebcart');
		$results = $this->model_extension_ebcart->deleteexpirecoupons();
		$this->session->data['success'] = "Success: Expired Coupons has deleted";
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	/*23-10-2017*/
	
	public function deletecart(){
		//DELETE EBCARTS
		$json=array();
		$this->load->model('extension/ebcart');
		if(isset($this->request->get['ebabandonedcart_id'])){
			$this->model_extension_ebcart->deleteebcart($this->request->get['ebabandonedcart_id']);
			$json['success']= true;
		}else{
			//bulk deletecart
			if(isset($this->request->post['ebcartids'])){
			 $ebcartids	= explode(',',$this->request->post['ebcartids']);
			 foreach($ebcartids as $id):
			  if((int)$id):
				$this->model_extension_ebcart->deleteebcart($id);
			  endif;
			 endforeach;
			}
			$json['success']= true;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function deleteOrder(){
		//DELETE ORDERS
		$json=array();
		$this->load->model('extension/ebcart');
		if(isset($this->request->post['orderids'])){
			 $orderids	= explode(',',$this->request->post['orderids']);
			 foreach($orderids as $id):
			  if((int)$id):
				$this->model_extension_ebcart->deleteOrders($id);
			  endif;
			 endforeach;
		}
		$json['success']= true;
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function sendemail(){
		//SEND EMAILS to the Customer
		$json=array();
		$this->load->language('extension/module/recover_carts');
		$this->load->model('setting/store');
		$this->load->model('setting/setting');
		$this->load->model('extension/ebemail_template');
		$this->load->model('extension/ebcart');
		$store_info = $this->model_setting_setting->getSetting('config', $this->request->get['store_id']);
		if($store_info){
			$store_email = isset($store_info['config_email']) ? $store_info['config_email'] : $this->config->get('config_email');
			if(empty($this->request->post['template_id'])){
				$json['warning'] = $this->language->get('error_template');
			}
			if(!$json){
				 $ebcarts = explode(',',$this->request->post['ebcartids']);
				 $customers=array();
				 foreach($ebcarts as $id):
					if((int)$id):
					$ebinfo = $this->model_extension_ebcart->getebcart($id);	
					if(!empty($ebinfo['email'])){
					  $customers[]=array(
					    'ebabandonedcart_id' => $id,
						'firstname'		=> $ebinfo['firstname'],
						'lastname'		=> $ebinfo['lastname'],
						'email'			=> $ebinfo['email'],
						'telephone'		=> $ebinfo['telephone'],
						'language_id'	=> $ebinfo['language_id'],
						'currency'		=> $ebinfo['currency'],
						'products'		=> $this->model_extension_ebcart->getebcartproducts($id),
					  );
					}
					endif;
				 endforeach;
				 
				 
				
				$coupon_settings = $this->model_extension_ebemail_template->getEmailCoupon($this->request->post['template_id']);
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
					
					$templateinfo = $this->model_extension_ebemail_template->getEmailTemplateDatabylanguage($this->request->post['template_id'],$email['language_id']);
					
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
						'logo'					=> '<img src="' . HTTP_CATALOG . 'image/'. $store_info['config_logo'] .'" title="'. $store_info['config_name'] .'" alt="'. $store_info['config_name'] .'" />',
						'store'					=> $store_info['config_name'],
						'store_adddress'		=> $store_info['config_address'],
						'store_email'			=> $store_info['config_email'],
						'store_telephone'		=> $store_info['config_telephone'],
						'store_url'				=> HTTP_CATALOG,
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
					
					if($this->request->post['remove_record']):
						$this->model_extension_ebcart->deleteebcart($email['ebabandonedcart_id']);
					endif;
					$this->model_extension_ebcart->updatenotifystatus($email['ebabandonedcart_id'],$email['email']);
				endforeach;
			}
		}
		
		$json['success'] = 'Email Sent !';
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function getcartproducts($products,$ebabandonedcart_id){
		//CART PRODUCTS EMAIL TEMPLETES
		$this->language->load('extension/module/recover_carts');
		$this->load->model('extension/ebcart');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_total'] = $this->language->get('column_total');
		
		$data['products'] = $products;
		
		$data['carttotal'] = $this->model_extension_ebcart->getebcarttotalprice($ebabandonedcart_id);
		
		return $this->load->view('extension/ebcart/email_products', $data);
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
	
	public function getCouponByCode($code) {
		//GET COUPON CODE BY CODE DETAILS
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($code) . "'");

		return $query->row;
	}
	
	public function getCoupon($coupon_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coupon WHERE coupon_id = '" . (int)$coupon_id . "' AND status = 1");

		return $query->row;
	}
	
	public function getunuseCoupons() {
		//GET UNUSE COUPONS
		$this->language->load('extension/module/recover_carts');
		$this->language->load('marketing/coupon');

		$this->load->model('extension/ebemail_template');
		
		
		$data['text_no_results'] = $this->language->get('text_no_results');

		
		$data['column_name'] = $this->language->get('column_name');
		$data['column_code'] = $this->language->get('column_code');
		$data['column_customer_name'] = $this->language->get('column_customer_name');
		$data['column_email'] = $this->language->get('column_email');
		$data['column_date_added'] = $this->language->get('column_date_added');		
		$data['column_date_start'] = $this->language->get('column_date_start');
		$data['column_date_end'] = $this->language->get('column_date_end');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_delete'] = $this->language->get('button_delete');
		
		if (isset($this->request->get['filter_customer_name'])) {
			$filter_customer_name = $this->request->get['filter_customer_name'];
		} else {
			$filter_customer_name = null;
		}
		
		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}
		
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'customer_name';
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

		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}
		
		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['customers'] = array();

		$filter_data = array(
			'filter_customer_name' => $filter_customer_name,
			'filter_email'      	 => $filter_email,
			'sort'                 => $sort,
			'filter_unused_coupons'	=> true,
			'order'                => $order,
			'start'                => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                => $this->config->get('config_limit_admin')
		);

		$customer_total = $this->model_extension_ebemail_template->getTotalCustomerCoupons($filter_data);

		$results = $this->model_extension_ebemail_template->getCustomerCoupons($filter_data);

		foreach ($results as $result) {
			$data['customers'][] = array(
				'coupon_id'				=> $result['coupon_id'],
				'coupon_name'   		=> (isset($result['name']) ? $result['name'] : ''),
				'code'   				=> $result['code'],
				'email'      			=> $result['email'],
				'date_start'			=> date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'				=> date($this->language->get('date_format_short'), strtotime($result['date_end'])),
				'status'				=> ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'date_added'      		=> date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'					=> $this->url->link('marketing/coupon/edit','&user_token='.$this->session->data['user_token'].'&coupon_id='.$result['coupon_id']),
			);
		}
		
		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}
		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		$data['sort_name'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.name' . $url, $this->ssl);
		$data['sort_code'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.code' . $url, $this->ssl);
		$data['sort_customer_name'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=customer_name' . $url, $this->ssl);
		$data['sort_email'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url, $this->ssl);
		$data['sort_date_start'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.date_start' . $url, $this->ssl);
		$data['sort_date_end'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.date_end' . $url, $this->ssl);
		$data['sort_date_added'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url, $this->ssl);

		$url = '';

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}
		
		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $customer_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/recover_carts/getunuseCoupons', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_total - $this->config->get('config_limit_admin'))) ? $customer_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_total, ceil($customer_total / $this->config->get('config_limit_admin')));

		$data['filter_email'] = $filter_email;
		$data['filter_customer_name'] = $filter_customer_name;
		
		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->response->setOutput($this->load->view('extension/ebcart/coupon_customers', $data));
	}
	
	
	public function getUsedCoupons() {
		//GET USED COUPONS
		$this->language->load('extension/module/recover_carts');

		$this->language->load('marketing/coupon');

		$this->load->model('extension/ebemail_template');
		
		
		$data['text_no_results'] = $this->language->get('text_no_results');

		
		$data['column_name'] = $this->language->get('column_name');
		$data['column_code'] = $this->language->get('column_code');
		$data['column_customer_name'] = $this->language->get('column_customer_name');
		$data['column_email'] = $this->language->get('column_email');
		$data['column_date_added'] = $this->language->get('column_date_added');		
		$data['column_date_start'] = $this->language->get('column_date_start');
		$data['column_date_end'] = $this->language->get('column_date_end');
		$data['column_action'] = $this->language->get('column_action');
		
		if (isset($this->request->get['filter_customer_name'])) {
			$filter_customer_name = $this->request->get['filter_customer_name'];
		} else {
			$filter_customer_name = null;
		}
		
		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}
		
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'customer_name';
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

		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}
		
		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['customers'] = array();

		$filter_data = array(
			'filter_customer_name' => $filter_customer_name,
			'filter_email'      	 => $filter_email,
			'sort'                 => $sort,
			'order'                => $order,
			'start'                => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                => $this->config->get('config_limit_admin')
		);

		$customer_total = $this->model_extension_ebemail_template->getTotalCustomerCoupons($filter_data);

		$results = $this->model_extension_ebemail_template->getCustomerCoupons($filter_data);

		foreach ($results as $result) {
			$data['customers'][] = array(
				'coupon_id'				=> $result['coupon_id'],
				'coupon_name'   		=> (isset($result['name']) ? $result['name'] : ''),
				'code'   				=> $result['code'],
				'email'      			=> $result['email'],
				'date_start'			=> date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'				=> date($this->language->get('date_format_short'), strtotime($result['date_end'])),
				'status'				=> ($result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'date_added'      		=> date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'					=> $this->url->link('marketing/coupon/edit','&user_token='.$this->session->data['user_token'].'&coupon_id='.$result['coupon_id']),
			);
		}
		
		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}
		
		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		$data['sort_name'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.name' . $url, $this->ssl);
		$data['sort_code'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.code' . $url, $this->ssl);
		$data['sort_customer_name'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=customer_name' . $url, $this->ssl);
		$data['sort_email'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=c.email' . $url, $this->ssl);
		$data['sort_date_start'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.date_start' . $url, $this->ssl);
		$data['sort_date_end'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=cp.date_end' . $url, $this->ssl);
		$data['sort_date_added'] = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url, $this->ssl);

		$url = '';

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . $this->request->get['filter_email'];
		}
		
		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . $this->request->get['filter_customer_name'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $customer_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/recover_carts/getUsedCoupons', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_total - $this->config->get('config_limit_admin'))) ? $customer_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_total, ceil($customer_total / $this->config->get('config_limit_admin')));

		$data['filter_email'] = $filter_email;
		$data['filter_customer_name'] = $filter_customer_name;
		
		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->response->setOutput($this->load->view('extension/ebcart/coupon_customers', $data));
	}
	
	public function deletecoupons(){
		$this->load->language('marketing/coupon');
		$this->load->model('marketing/coupon');
		if(isset($this->request->post['coupon_ids']) && $this->validateDelete()){
			$coupons = explode(',',$this->request->post['coupon_ids']);
			foreach($coupons as $coupon_id){
				if((int)$coupon_id){
					$this->db->query("DELETE FROM ".DB_PREFIX."ebcart_coupon WHERE coupon_id = ".(int)$coupon_id."");
					$this->model_marketing_coupon->deleteCoupon($coupon_id);	
				}
			}
			$json['success'] = 'coupon deleted';
		}else{
			$json['warning'] = $this->language->get('error_permission');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/recover_carts')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/recover_carts')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if($this->request->post['module_recover_carts_protocol']=='sendgrid'){
			if (!$this->request->post['module_recover_carts_username']) {
				$this->error['module_recover_carts_username'] = $this->language->get('error_module_recover_carts_username');
			}

			if (!$this->request->post['module_recover_carts_password']) {
				$this->error['module_recover_carts_password'] = $this->language->get('error_module_recover_carts_password');
			}
		}

		return !$this->error;
	}
}