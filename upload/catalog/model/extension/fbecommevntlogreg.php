<?php 
class ModelExtensionfbecommevntlogreg extends Controller { 
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
 	}
	public function getcode($flag) {
		$this->load->model('extension/fbecommevnt');
		$rsdata = $this->model_extension_fbecommevnt->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid']) {
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;
			
			if($flag == 1) { 
				$eventdate = array(
					"content_category" => $langdata['logntxt'],
					"content_name" => $langdata['logntxt'],
					"value" => 1,
		            "currency" => $this->session->data['currency'], 
				);
			} else {
				$eventdate = array(
					"content_category" => $langdata['regtxt'],
					"content_name" => $langdata['regtxt'],
					"value" => 1,
		            "currency" => $this->session->data['currency'], 
				);
			}
			
			if($fbcatid) { 
				$eventdate['product_catalog_id'] = $fbcatid;
			}
 
$logreg_eventdate = json_encode($eventdate);
$code = <<<EOF
<script type="text/javascript">
if($flag == 1) { 
fbq('trackCustom', 'Sign_in', $logreg_eventdate);
} else {
fbq('trackCustom', 'Sign_up', $logreg_eventdate);
fbq('track', 'CompleteRegistration', $logreg_eventdate);
fbq('track', 'Lead', $logreg_eventdate);
}
</script>
EOF;
return $code;
		
 		} 
	} 
}