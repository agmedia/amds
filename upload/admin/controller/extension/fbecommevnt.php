<?php
class ControllerExtensionfbecommevnt extends Controller {
	private $error = array();  
	private $modpath = 'extension/fbecommevnt';
	private $modtpl = 'extension/fbecommevnt.tpl';
	private $modssl = 'SSL';
	private $token_str = ''; 
	
	public function __construct($registry) {
		parent::__construct($registry);
 		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3') { 
 			$this->modpath = 'extension/fbecommevnt';
 			$this->modtpl = 'extension/fbecommevnt';
  		} else if(substr(VERSION,0,3)=='2.2') {
 			$this->modtpl = 'extension/fbecommevnt';
		} 
		 
		if(substr(VERSION,0,3)>='3.0') { 
 			$this->token_str = 'user_token=' . $this->session->data['user_token'];
		} else {
			$this->token_str = 'token=' . $this->session->data['token'];
		}
		
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$this->modssl = true;
		} 
 	} 

	public function index() {
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);
		
		$this->model_extension_fbecommevnt->checkdb();
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token_str, $this->modssl)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token_str, $this->modssl)
		);
		
		if(substr(VERSION,0,3)>='3.0') { 
 			$data['user_token'] = $this->session->data['user_token']; 
		} else {
			$data['token'] = $this->session->data['token'];
		}
		
		$data['action'] = $this->url->link($this->modpath, $this->token_str, $this->modssl);
		$data['cancel'] = $this->url->link('common/dashboard', $this->token_str, $this->modssl);
		
		$this->load->model('setting/store');
		$store_default = array(0=> array("name"=>'Default',"store_id"=>0));
		$data['stores'] = $this->model_setting_store->getStores();	
		$data['stores'] = array_merge($store_default,$data['stores']);
		
		$this->load->model('localisation/language');
  		$languages = $this->model_localisation_language->getLanguages();
		foreach($languages as $language) {
			if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
				$imgsrc = "language/".$language['code']."/".$language['code'].".png";
			} else {
				$imgsrc = "view/image/flags/".$language['image'];
			}
			$data['languages'][] = array("language_id" => $language['language_id'], "name" => $language['name'], "imgsrc" => $imgsrc);
		}
 		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->session->data['success'] = $this->language->get('text_success');
			$this->model_extension_fbecommevnt->add($this->request->post);
		}
 		
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
		
		$rs = $this->model_extension_fbecommevnt->getdata();
  		
		foreach ($data['stores'] as $store) {
			$data['status'][$store['store_id']] = isset($rs[$store['store_id']]) ? $rs[$store['store_id']]['status'] : 0;
			$data['fbpixelid'][$store['store_id']] = isset($rs[$store['store_id']]) ? $rs[$store['store_id']]['fbpixelid'] : 'XXXXXXXXXXXXX';
			$data['fbcatid'][$store['store_id']] = isset($rs[$store['store_id']]) ? $rs[$store['store_id']]['fbcatid'] : 'XXXXXXXXXXXXX';
			
			foreach($languages as $language) {
				$data['atctxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['atctxt'][$language['language_id']]) ? $rs[$store['store_id']]['atctxt'][$language['language_id']] : 'Add To Cart';
				$data['atwtxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['atwtxt'][$language['language_id']]) ? $rs[$store['store_id']]['atwtxt'][$language['language_id']] : 'Add To Compare';
				$data['atcmtxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['atcmtxt'][$language['language_id']]) ? $rs[$store['store_id']]['atcmtxt'][$language['language_id']] : 'Add To Wishlist';
				
				$data['rmctxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['rmctxt'][$language['language_id']]) ? $rs[$store['store_id']]['rmctxt'][$language['language_id']] : 'Remove From Cart';
				
				$data['logntxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['logntxt'][$language['language_id']]) ? $rs[$store['store_id']]['logntxt'][$language['language_id']] : 'Login';
				$data['regtxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['regtxt'][$language['language_id']]) ? $rs[$store['store_id']]['regtxt'][$language['language_id']] : 'Register';
				
				$data['chkonetxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chkonetxt'][$language['language_id']]) ? $rs[$store['store_id']]['chkonetxt'][$language['language_id']] : 'Checkout Login';
				$data['chktwotxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chktwotxt'][$language['language_id']]) ? $rs[$store['store_id']]['chktwotxt'][$language['language_id']] : 'Billing Details';
				$data['chkthreetxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chkthreetxt'][$language['language_id']]) ? $rs[$store['store_id']]['chkthreetxt'][$language['language_id']] : 'Delivery Details';
				$data['chkfourtxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chkfourtxt'][$language['language_id']]) ? $rs[$store['store_id']]['chkfourtxt'][$language['language_id']] : 'Shipping Method';
				$data['chkfivetxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chkfivetxt'][$language['language_id']]) ? $rs[$store['store_id']]['chkfivetxt'][$language['language_id']] : 'Payment Method';
				$data['chksixtxt'][$store['store_id']][$language['language_id']] = isset($rs[$store['store_id']]['chksixtxt'][$language['language_id']]) ? $rs[$store['store_id']]['chksixtxt'][$language['language_id']] : 'Confirming Order';
			}			
 		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->modtpl, $data));
	}
	protected function validateForm() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}		
		return !$this->error;
	}
}
