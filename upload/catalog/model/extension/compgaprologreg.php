<?php 
class ModelExtensioncompgaprologreg extends Controller { 
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
		$this->load->model('extension/compgapro');
		$rsdata = $this->model_extension_compgapro->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid']) {
			$langdata = $this->model_extension_compgapro->getlangdata($rsdata);
			
			$gaid = $rsdata['gaid'] ? $rsdata['gaid'] : false;
			$gafid = $rsdata['gafid'] ? $rsdata['gafid'] : false;
			$adwid = $rsdata['adwid'] ? $rsdata['adwid'] : false;
			$adwlbl = $rsdata['adwlbl'] ? $rsdata['adwlbl'] : false;
			
			if($flag == 1) { 
				$eventdate = array(
					"event_category" => $langdata['logntxt'],
					"event_label" => $langdata['logntxt'],
				);
			} else {
				$eventdate = array(
					"event_category" => $langdata['regtxt'],
					"event_label" => $langdata['regtxt'],
				);
			}
 
$logreg_eventdate = json_encode($eventdate);
$code = <<<EOF
<script type="text/javascript">
if($flag == 1) { 
gtag('event', 'login', $logreg_eventdate);
} else {
gtag('event', 'sign_up', $logreg_eventdate);
}
</script>
EOF;

return $code;
		
 		} 
	} 
}