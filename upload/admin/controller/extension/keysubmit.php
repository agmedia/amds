<?php
class ControllerExtensionKeysubmit  extends Controller {
	private $error = array();
	public function loadfrom() {
		
		$user_token= $this->session->data['user_token'];
		$regkey= $this->config->get('moduledata_faq_key');
		 $url = 'https://www.opencartextensions.in/index.php?route=api/keysubmit&foldername=extension&regkey='.$regkey.'&user_token='.$user_token;
		
		$curl = curl_init($url);
	
		//curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		 echo $response = curl_exec($curl);
		 
	}
	public function index() {
		
		$this->load->language('extension/keysubmit');
        $this->load->model('setting/setting');
       
      	if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
          	$json = array();          
			
	        if(empty($this->request->post['moduledata_key'])) {
				$json['error']['moduledata_key']= $this->language->get('error_module_key');
			}
			 if(empty($json['error'])) {	
			
			
				 
		   $tmd_extensiondata= array(
			   'extension_id' => '25315',
			   'email' => $this->config->get('config_email'),
			   'store_url' => HTTP_CATALOG,
			   'module_key' =>$this->request->post['moduledata_key'],
			);
	
		$url = 'https://www.opencartextensions.in/index.php?route=api/chklicence';
		
		$curl = curl_init($url);
	
		//curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($tmd_extensiondata, '', '&'));
		
		 $response = curl_exec($curl);
		 $status=json_decode($response,true);
			if($status['status']) {
				$keydata=array();
				$keydata['moduledata_faq_key']=$this->request->post['moduledata_key'];
				$this->model_setting_setting->editSetting('moduledata',$keydata);
				$json['success'] = '<div class="success_heading"><i class="fa fa-check-circle" aria-hidden="true"></i>  '.$this->language->get('text_successhead').'</div>';
				
				} else {
				$json['warningmsg'] = '<div class="warning"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>  '.$this->language->get('text_notmatchinfo').'</div';	
				}			
			} 		
			
			
    	} 
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
?>