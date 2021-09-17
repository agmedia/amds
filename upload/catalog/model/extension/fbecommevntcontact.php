<?php 
class ModelExtensionfbecommevntcontact extends Controller { 
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
	public function getcode() {
		$this->load->model('extension/fbecommevnt');
		$rsdata = $this->model_extension_fbecommevnt->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid']) {
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;						
 
$code = <<<EOF
<script type="text/javascript">
fbq('track', 'Contact');
</script>
EOF;
return $code;
		
 		} 
	} 
}