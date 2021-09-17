<?php
class ControllerExtensioncompgapro extends Controller {
  	private $modssl = 'SSL';
   	private $langid = 0;
	private $storeid = 0;
	private $storename = '';
	private $custgrpid = 0;
	public function __construct($registry) {
		parent::__construct($registry);
 		$this->langid = (int)$this->config->get('config_language_id');
		$this->storeid = (int)$this->config->get('config_store_id');
		$this->storename = $this->config->get('config_meta_title');
		$this->custgrpid = (int)$this->config->get('config_customer_group_id');
		ini_set("serialize_precision", -1);
		if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
  			$this->modssl = true;
 		}
  	}
	public function trackevent() {
		$this->load->model('extension/compgapro');
 		if(isset($this->request->post['product_id'])) { 
 			$json = $this->model_extension_compgapro->getevent($this->request->post['product_id']);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		} 
	}
	public function trackchkfunnel() {
		$this->load->model('extension/compgapro');
 		if(isset($this->request->post['stepnum'])) { 
 			$json = $this->model_extension_compgapro->getchkfunnel($this->request->post['stepnum']);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		} 
	}
	public function trackshipinfo() {
		$this->load->model('extension/compgapro');
 		$json = $this->model_extension_compgapro->getshipinfo();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function trackpayinfo() {
		$this->load->model('extension/compgapro');
 		$json = $this->model_extension_compgapro->getpayinfo();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}