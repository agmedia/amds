<?php
class ControllerExtensionModuleMefbevents extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/me_fb_events');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		if(isset($this->request->get['store_id'])){
			$store_id = $this->request->get['store_id'];
		}else{
			$store_id = $this->config->get('config_store_id') ? $this->config->get('config_store_id') : 0;
		}
		
		$data['store_id'] = $store_id;
		
		$this->CreateTable();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_me_fb_events', $this->request->post,$store_id);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['error_feed'])) {
			$data['error_feed'] = $this->error['error_feed'];
		} else {
			$data['error_feed'] = array();
		}
		
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
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/me_fb_events', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/me_fb_events', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$store_info = $this->model_setting_setting->getSetting('module_me_fb_events',$store_id);

		if (isset($this->request->post['module_me_fb_events_status'])) {
			$data['module_me_fb_events_status'] = $this->request->post['module_me_fb_events_status'];
		} elseif(!empty($store_info['module_me_fb_events_status'])) {
			$data['module_me_fb_events_status'] = $store_info['module_me_fb_events_status'];
		}else{
			$data['module_me_fb_events_status'] = $this->config->get('module_me_fb_events_status');
		}
		
		if (isset($this->request->post['module_me_fb_events_pixel_id'])) {
			$data['module_me_fb_events_pixel_id'] = $this->request->post['module_me_fb_events_pixel_id'];
		} elseif(!empty($store_info['module_me_fb_events_pixel_id'])) {
			$data['module_me_fb_events_pixel_id'] = $store_info['module_me_fb_events_pixel_id'];
		}else{
			$data['module_me_fb_events_pixel_id'] = $this->config->get('module_me_fb_events_pixel_id');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_view'])) {
			$data['module_me_fb_events_track_view'] = $this->request->post['module_me_fb_events_track_view'];
		} elseif(!empty($store_info['module_me_fb_events_track_view'])) {
			$data['module_me_fb_events_track_view'] = $store_info['module_me_fb_events_track_view'];
		}else{
			$data['module_me_fb_events_track_view'] = $this->config->get('module_me_fb_events_track_view');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_content'])) {
			$data['module_me_fb_events_track_content'] = $this->request->post['module_me_fb_events_track_content'];
		} elseif(!empty($store_info['module_me_fb_events_track_content'])) {
			$data['module_me_fb_events_track_content'] = $store_info['module_me_fb_events_track_content'];
		}else{
			$data['module_me_fb_events_track_content'] = $this->config->get('module_me_fb_events_track_content');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_cart'])) {
			$data['module_me_fb_events_track_cart'] = $this->request->post['module_me_fb_events_track_cart'];
		} elseif(!empty($store_info['module_me_fb_events_track_cart'])) {
			$data['module_me_fb_events_track_cart'] = $store_info['module_me_fb_events_track_cart'];
		}else{
			$data['module_me_fb_events_track_cart'] = $this->config->get('module_me_fb_events_track_cart');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_wishlist'])) {
			$data['module_me_fb_events_track_wishlist'] = $this->request->post['module_me_fb_events_track_wishlist'];
		} elseif(!empty($store_info['module_me_fb_events_track_wishlist'])) {
			$data['module_me_fb_events_track_wishlist'] = $store_info['module_me_fb_events_track_wishlist'];
		}else{
			$data['module_me_fb_events_track_wishlist'] = $this->config->get('module_me_fb_events_track_wishlist');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_checkout'])) {
			$data['module_me_fb_events_track_checkout'] = $this->request->post['module_me_fb_events_track_checkout'];
		} elseif(!empty($store_info['module_me_fb_events_track_checkout'])) {
			$data['module_me_fb_events_track_checkout'] = $store_info['module_me_fb_events_track_checkout'];
		}else{
			$data['module_me_fb_events_track_checkout'] = $this->config->get('module_me_fb_events_track_checkout');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_purchase'])) {
			$data['module_me_fb_events_track_purchase'] = $this->request->post['module_me_fb_events_track_purchase'];
		} elseif(!empty($store_info['module_me_fb_events_track_purchase'])) {
			$data['module_me_fb_events_track_purchase'] = $store_info['module_me_fb_events_track_purchase'];
		}else{
			$data['module_me_fb_events_track_purchase'] = $this->config->get('module_me_fb_events_track_purchase');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_search'])) {
			$data['module_me_fb_events_track_search'] = $this->request->post['module_me_fb_events_track_search'];
		} elseif(!empty($store_info['module_me_fb_events_track_search'])) {
			$data['module_me_fb_events_track_search'] = $store_info['module_me_fb_events_track_search'];
		}else{
			$data['module_me_fb_events_track_search'] = $this->config->get('module_me_fb_events_track_search');
		}
		
		if (isset($this->request->post['module_me_fb_events_track_contact'])) {
			$data['module_me_fb_events_track_contact'] = $this->request->post['module_me_fb_events_track_contact'];
		} elseif(!empty($store_info['module_me_fb_events_track_contact'])) {
			$data['module_me_fb_events_track_contact'] = $store_info['module_me_fb_events_track_contact'];
		}else{
			$data['module_me_fb_events_track_contact'] = $this->config->get('module_me_fb_events_track_contact');
		}
		
		if (isset($this->request->post['module_me_fb_events_addtocart'])) {
			$data['module_me_fb_events_addtocart'] = $this->request->post['module_me_fb_events_addtocart'];
		} elseif(!empty($store_info['module_me_fb_events_addtocart'])) {
			$data['module_me_fb_events_addtocart'] = $store_info['module_me_fb_events_addtocart'];
		} else {
			$data['module_me_fb_events_addtocart'] = $this->config->get('module_me_fb_events_addtocart');
		}
		
		if (isset($this->request->post['module_me_fb_events_initiatecheckout'])) {
			$data['module_me_fb_events_initiatecheckout'] = $this->request->post['module_me_fb_events_initiatecheckout'];
		} elseif(!empty($store_info['module_me_fb_events_initiatecheckout'])) {
			$data['module_me_fb_events_initiatecheckout'] = $store_info['module_me_fb_events_initiatecheckout'];
		} else {
			$data['module_me_fb_events_initiatecheckout'] = $this->config->get('module_me_fb_events_initiatecheckout');
		}
		
		if (isset($this->request->post['module_me_fb_events_glanguage'])) {
			$data['module_me_fb_events_glanguage'] = $this->request->post['module_me_fb_events_glanguage'];
		} elseif(!empty($store_info['module_me_fb_events_glanguage'])) {
			$data['module_me_fb_events_glanguage'] = $store_info['module_me_fb_events_glanguage'];
		} else {
			$data['module_me_fb_events_glanguage'] = $this->config->get('module_me_fb_events_glanguage');
		}
		
		if (isset($this->request->post['module_me_fb_events_flanguage'])) {
			$data['module_me_fb_events_flanguage'] = $this->request->post['module_me_fb_events_flanguage'];
		} elseif(!empty($store_info['module_me_fb_events_flanguage'])) {
			$data['module_me_fb_events_flanguage'] = $store_info['module_me_fb_events_flanguage'];
		} else {
			$data['module_me_fb_events_flanguage'] = $this->config->get('module_me_fb_events_flanguage');
		}
		
		if (isset($this->request->post['module_me_fb_events_feed'])) {
			$module_me_fb_events_feeds = $this->request->post['module_me_fb_events_feed'];
		} elseif(!empty($store_info['module_me_fb_events_feed'])) {
			$module_me_fb_events_feeds = $store_info['module_me_fb_events_feed'];
		} elseif($this->config->get('module_me_fb_events_feed')) {
			$module_me_fb_events_feeds = $this->config->get('module_me_fb_events_feed');
		}else{
			$module_me_fb_events_feeds = array();
		}
		
		$data['module_me_fb_events_feeds'] = array();
		foreach($module_me_fb_events_feeds as $key => $module_me_fb_events_feed){
			$name = preg_replace('/[0-9\@\.\;\" "]+/', '', str_replace(' ','',$module_me_fb_events_feed['name']));
			
			$data['module_me_fb_events_feeds'][$key] = array(
				'name' => $module_me_fb_events_feed['name'],
				'store_id' => $module_me_fb_events_feed['store_id'],
				'customer_cgroup' => $module_me_fb_events_feed['customer_cgroup'],
				'language_id' => $module_me_fb_events_feed['language_id'],
				'currency' => $module_me_fb_events_feed['currency'],
				'category' => isset($module_me_fb_events_feed['category']) ? $module_me_fb_events_feed['category'] : array(),
				'manufacturer' => isset($module_me_fb_events_feed['manufacturer']) ? $module_me_fb_events_feed['manufacturer'] : array(),
				'gid_tag' => $module_me_fb_events_feed['gid_tag'],
				'pricetax' => $module_me_fb_events_feed['pricetax'],
				'gcategory_status' => isset($module_me_fb_events_feed['gcategory_status']) ? $module_me_fb_events_feed['gcategory_status'] : '',
				'fbcategory_status' => isset($module_me_fb_events_feed['fbcategory_status']) ? $module_me_fb_events_feed['fbcategory_status'] : '',
				'minprice' => $module_me_fb_events_feed['minprice'],
				'maxprice' => $module_me_fb_events_feed['maxprice'],
				'minqty' => $module_me_fb_events_feed['minqty'],
				'maxqty' => $module_me_fb_events_feed['maxqty'],
				'link' => HTTP_CATALOG.'index.php?route=extension/me_fb_events/feed&feed='.$name.'.xml'
			);
		}
		$data['facebook_feed'] = HTTP_CATALOG.'index.php?route=extension/me_fb_events/feed';		
		
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		
		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		$this->load->model('catalog/category');
		$page = 1;
		$filter_data = array(
			'start' => 0,
			'limit' => $this->config->get('config_limit_admin')
		);
		
		$category_total = $this->model_catalog_category->getTotalCategories();
		$categories = $this->model_catalog_category->getCategories($filter_data);
		$data['categories'] = array();

		foreach($categories as $category){
			$data['categories'][] = array(
				'category_id' => $category['category_id'],
				'name' => str_replace("'","",str_replace('�','',html_entity_decode($category['name']))),
			);
		}
		
		$data['google_categories'] = $this->getBaseCategories();
		$data['facebook_categories'] = $this->getFacebookCategories();
		
		$pagination = new Pagination();
		$pagination->total = $category_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/me_fb_events/category', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));
		
		
		$fpagination = new Pagination();
		$fpagination->total = $category_total;
		$fpagination->page = $page;
		$fpagination->limit = $this->config->get('config_limit_admin');
		$fpagination->url = $this->url->link('extension/module/me_fb_events/facebook', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['fpagination'] = $fpagination->render();

		$data['fresults'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));
		
		$this->load->model('catalog/manufacturer');
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers();
		$data['manufacturers'] = array();

		foreach($manufacturers as $manufacturer){
			$data['manufacturers'][] = array(
				'manufacturer_id' => $manufacturer['manufacturer_id'],
				'name' => str_replace("'","",str_replace('�','',html_entity_decode($manufacturer['name']))),
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/me_fb_events', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/me_fb_events')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if(isset($this->request->post['module_me_fb_events_feed'])){
			foreach($this->request->post['module_me_fb_events_feed'] as $key => $value){
				if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
					$this->error['error_feed'][$key] = $this->language->get('error_feed');
				}
			}
		}
		
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
	
	public function getCategories(){
		$json = array();
	
		$domain = 'http://martextensions.in/demos/';

		if (isset($this->request->get['filter_name'])) {
			$language_code = $this->request->get['language_code'];
			if($this->request->get['language_code'] == 'en-gb'){
				$language_code = 'en-GB';
			}
			$filepath = $domain.'google_category/taxonomy-with-ids.'.$language_code.'.txt';

			$categories = explode("\n", file_get_contents($filepath));
			if($this->request->get['filter_name']){
				foreach ($categories as $key => $Word){
				if ($key != 0) {
					if (isset($Word)) {
					if (strpos(strtolower($Word), strtolower($this->request->get['filter_name'])) !== false) {
						$json[] = array(
								'category_id' => (int)$key,
								'name'       => strip_tags(html_entity_decode($Word, ENT_QUOTES, 'UTF-8')),
							);
						}
					}
				   }
				 }
				 $json = array_slice($json, 0, 10);
			}else{
				$categories = preg_grep('/'. $this->request->get['filter_name'] .'/i', $categories);
			
				$categories = array_slice($categories, 0, 10);

				foreach($categories as $key => $line){
					if ($key != 0) {
						if (isset($line)) {
							$json[] = array(
								'category_id' => (int)$key,
								'name'       => strip_tags(html_entity_decode($line, ENT_QUOTES, 'UTF-8')),
							);
						}
					}
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function getFbCategories(){
		$json = array();
	
		$domain = 'http://martextensions.in/demos/';

		if (isset($this->request->get['filter_name'])) {
			$language_code = $this->request->get['language_code'];
			if($this->request->get['language_code'] == 'en-gb'){
				$language_code = 'en_US';
			}
			$filepath = $domain.'facebook_category/fb_product_categories_'.$language_code.'.txt';

			$categories = explode("\n", file_get_contents($filepath));
			if($this->request->get['filter_name']){
				foreach ($categories as $key => $Word){
				if ($key != 0) {
					if (isset($Word)) {
					if (strpos(strtolower($Word), strtolower($this->request->get['filter_name'])) !== false) {
						$json[] = array(
								'category_id' => (int)$key,
								'name'       => strip_tags(html_entity_decode($Word, ENT_QUOTES, 'UTF-8')),
							);
						}
					}
				   }
				 }
				 $json = array_slice($json, 0, 10);
			}else{
				$categories = preg_grep('/'. $this->request->get['filter_name'] .'/i', $categories);
			
				$categories = array_slice($categories, 0, 10);

				foreach($categories as $key => $line){
					if ($key != 0) {
						if (isset($line)) {
							$json[] = array(
								'category_id' => (int)$key,
								'name'       => strip_tags(html_entity_decode($line, ENT_QUOTES, 'UTF-8')),
							);
						}
					}
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function savecategory(){
		$json = array();
		
		if(isset($this->request->get['category_id'])){
			$category_id = $this->request->get['category_id'];
		}else{
			$category_id = 0;
		}
		
		if (!empty($this->request->post['google_category']) && !empty($this->request->post['google_category_id'])) {
			$this->savegoogleCategory($this->request->post,$category_id);
			$json['success'] = $this->language->get('text_success');
		}else{
			if(!empty($this->request->post['gcategory'])){
				$categories = explode(' - ',$this->request->post['gcategory']);
				if(isset($categories[0]) && isset($categories[1])){
					$this->request->post['google_category'] = $this->request->post['gcategory'];
					$this->request->post['google_category_id'] = $categories[0];
					$this->savegoogleCategory($this->request->post,$category_id);
					$json['success'] = $this->language->get('text_success');
				}
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function removecategory(){
		$json = array();
		
		if(isset($this->request->get['category_id'])){
			$category_id = $this->request->get['category_id'];
		}else{
			$category_id = 0;
		}
		
		if (!empty($category_id)) {
			$this->removegoogleCategory($category_id);
			$json['success'] = $this->language->get('text_success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function savefbcategory(){
		$json = array();
		
		if(isset($this->request->get['category_id'])){
			$category_id = $this->request->get['category_id'];
		}else{
			$category_id = 0;
		}
		
		if (!empty($this->request->post['facebook_category']) && !empty($this->request->post['facebook_category_id'])) {
			$this->savefacebookCategory($this->request->post,$category_id);
			$json['success'] = $this->language->get('text_success');
		}else{
			if(!empty($this->request->post['fcategory'])){
				$categories = explode(',',$this->request->post['fcategory']);
				if(isset($categories[0]) && isset($categories[1])){
					$this->request->post['facebook_category'] = $this->request->post['fcategory'];
					$this->request->post['facebook_category_id'] = $categories[0];
					$this->savefacebookCategory($this->request->post,$category_id);
					$json['success'] = $this->language->get('text_success');
				}
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function removefbcategory(){
		$json = array();
		
		if(isset($this->request->get['category_id'])){
			$category_id = $this->request->get['category_id'];
		}else{
			$category_id = 0;
		}
		
		if (!empty($category_id)) {
			$this->removefacebookCategory($category_id);
			$json['success'] = $this->language->get('text_success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function CreateTable(){
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."me_google_category` (`google_category_id` int(11) NOT NULL,`category_id` int(11) NOT NULL,`google_category` varchar(250) NOT NULL, PRIMARY KEY (`google_category_id`,`category_id`))");
		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."me_facebook_category` (`facebook_category_id` int(11) NOT NULL,`category_id` int(11) NOT NULL,`facebook_category` varchar(250) NOT NULL, PRIMARY KEY (`facebook_category_id`,`category_id`))");
	}
	
	public function savegoogleCategory($data,$category_id){
		$this->db->query("DELETE FROM " . DB_PREFIX . "me_google_category WHERE category_id = '" . (int)$category_id . "'");
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "me_google_category SET google_category_id = '" . (int)$data['google_category_id'] . "',google_category = '" . $data['google_category'] . "', category_id = '" . (int)$category_id . "'");
	}
	
	public function savefacebookCategory($data,$category_id){
		$this->db->query("DELETE FROM " . DB_PREFIX . "me_facebook_category WHERE category_id = '" . (int)$category_id . "'");
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "me_facebook_category SET facebook_category_id = '" . (int)$data['facebook_category_id'] . "',facebook_category = '" . $data['facebook_category'] . "', category_id = '" . (int)$category_id . "'");
	}
	
	public function getBaseCategories($data = array()) {
		$categories = array();
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "me_google_category` ORDER BY google_category ASC");
		if($query->num_rows){
			foreach($query->rows as $row){
				$categories[$row['category_id']]['google_category_id'] = $row['google_category_id'];
				$categories[$row['category_id']]['google_category'] = str_replace("'","",str_replace('�','',html_entity_decode($row['google_category'])));
			}
		}
		
		return $categories;
    }
	
	public function getFacebookCategories($data = array()) {
		$categories = array();
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "me_facebook_category` ORDER BY facebook_category ASC");
		if($query->num_rows){
			foreach($query->rows as $row){
				$categories[$row['category_id']]['facebook_category_id'] = $row['facebook_category_id'];
				$categories[$row['category_id']]['facebook_category'] = str_replace("'","",str_replace('�','',html_entity_decode($row['facebook_category'])));
			}
		}
		
		return $categories;
    }
	
	public function removegoogleCategory($category_id){
		$this->db->query("DELETE FROM " . DB_PREFIX . "me_google_category WHERE category_id = '" . (int)$category_id . "'");
	}
	
	public function removefacebookCategory($category_id){
		$this->db->query("DELETE FROM " . DB_PREFIX . "me_facebook_category WHERE category_id = '" . (int)$category_id . "'");
	}
	
	public function category(){
		$this->load->model('catalog/category');
		$this->load->language('extension/module/me_fb_events');
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url = '';
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		$filter_data = array(
			'start' => ($page -1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);
		
		$category_total = $this->model_catalog_category->getTotalCategories();
		$categories = $this->model_catalog_category->getCategories($filter_data);
		$data['categories'] = array();

		foreach($categories as $category){
			$data['categories'][] = array(
				'category_id' => $category['category_id'],
				'name' => str_replace("'","",str_replace('�','',html_entity_decode($category['name']))),
			);
		}
		
		$data['google_categories'] = $this->getBaseCategories();
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$pagination = new Pagination();
		$pagination->total = $category_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/me_fb_events/category', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));
		
		$this->response->setOutput($this->load->view('extension/module/me_google_category', $data));
	}
	
	public function facebook(){
		$this->load->model('catalog/category');
		$this->load->language('extension/module/me_fb_events');
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url = '';
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		$filter_data = array(
			'start' => ($page -1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);
		
		$category_total = $this->model_catalog_category->getTotalCategories();
		$categories = $this->model_catalog_category->getCategories($filter_data);
		$data['categories'] = array();

		foreach($categories as $category){
			$data['categories'][] = array(
				'category_id' => $category['category_id'],
				'name' => str_replace("'","",str_replace('�','',html_entity_decode($category['name']))),
			);
		}
		
		$data['facebook_categories'] = $this->getFacebookCategories();
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$fpagination = new Pagination();
		$fpagination->total = $category_total;
		$fpagination->page = $page;
		$fpagination->limit = $this->config->get('config_limit_admin');
		$fpagination->url = $this->url->link('extension/module/me_fb_events/facebook', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['fpagination'] = $fpagination->render();

		$data['fresults'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));
		
		$this->response->setOutput($this->load->view('extension/module/me_facebook_category', $data));
	}
}