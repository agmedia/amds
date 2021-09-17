<?php 
class ModelExtensioncompgaprochkbeg extends Controller { 
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
		$this->load->model('extension/compgapro');
		$rsdata = $this->model_extension_compgapro->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $this->cart->hasProducts()) {
			$langdata = $this->model_extension_compgapro->getlangdata($rsdata);
    			
 			$items_data = array();
			$counter = 0;
			foreach ($this->cart->getProducts() as $pinfo) {
				$counter += 1;
				$pricetx = $this->tax->calculate($pinfo['total'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->model_extension_compgapro->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['minimum'],
					"item_category" => $this->model_extension_compgapro->getcatname($pinfo['product_id']),
					"item_brand" => $this->model_extension_compgapro->getbrandname($pinfo['product_id']),
					"category" => $this->model_extension_compgapro->getcatname($pinfo['product_id']),
					"brand" => $this->model_extension_compgapro->getbrandname($pinfo['product_id']),
					"index" => $counter,
				);
				if (isset($this->session->data['coupon'])) {
					$items['coupon'] = $this->session->data['coupon'];
				}
				$items_data[] = $items;
			}
 			$begin_checkout = array(
				"currency" => $this->session->data['currency'],
				"value" => $this->cart->getTotal(),
				"items" => $items_data,
 			); 
			$begin_checkout['event_category'] = 'ecommerce';
			$begin_checkout['event_label'] = 'begin_checkout';
			
			if (isset($this->session->data['coupon'])) {
				$begin_checkout['coupon'] = $this->session->data['coupon'];
			}
			
$json_begin_checkout = json_encode($begin_checkout);
$code = <<<EOF
<script type="text/javascript">
gtag('event', 'begin_checkout', $json_begin_checkout);
</script>
EOF;

return $code;
		
 		} 
	} 
}