<?php 
class ModelExtensionfbecommevntchkbeg extends Controller { 
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
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $this->cart->hasProducts()) {
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
    			
 			$items_data = array();
			$begin_checkout = array();
			foreach ($this->cart->getProducts() as $pinfo) {
				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"quantity" => $pinfo['quantity'],
					"item_price" => $this->model_extension_fbecommevnt->getcurval($pricetx),
				);
				if($fbcatid) { 
					$items['product_catalog_id'] = $fbcatid;
				}
				$items_data[] = $items;
			}
 			$begin_checkout = array(
				"value" => $this->cart->getTotal(),
				"currency" => $this->session->data['currency'],
				"content_type" => 'product', 
				"contents" => $items_data
 			); 
			
$json_begin_checkout = json_encode($begin_checkout);
$code = <<<EOF
<script type="text/javascript">
fbq('track', 'InitiateCheckout', $json_begin_checkout);
</script>
EOF;
return $code;
		
 		} 
	} 
}