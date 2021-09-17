<?php 
class ModelExtensioncompgaprochksuccess extends Controller { 
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
	public function getcode($order_id = 0) {
		$this->load->model('extension/compgapro');
		$rsdata = $this->model_extension_compgapro->getdata();
		if(!$order_id && isset($this->session->data['compgapro_order_id'])) {
			$order_id = $this->session->data['compgapro_order_id'];
		}
		if($rsdata && $order_id) {
			$storequery = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . $this->storeid . "'");
 			$this->storename = isset($storequery->row['name']) ? $storequery->row['name'] : $this->config->get('config_name');
			$langdata = $this->model_extension_compgapro->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$gaid = $rsdata['gaid'] ? $rsdata['gaid'] : false;
			$gafid = $rsdata['gafid'] ? $rsdata['gafid'] : false;
			$adwstatus = $rsdata['adwstatus'] ? $rsdata['adwstatus'] : false;
			$adwid = $rsdata['adwid'] ? $rsdata['adwid'] : false;
			$adwlbl = $rsdata['adwlbl'] ? $rsdata['adwlbl'] : false;
			 
 			$this->load->model('checkout/order');
			
			$orderdata = $this->model_checkout_order->getOrder($order_id);
 			$order_products = $this->model_extension_compgapro->getorderproduct($order_id);
 			$order_tax = $this->model_extension_compgapro->getordertax($order_id);
			$order_shipping = $this->model_extension_compgapro->getordershipping($order_id);
			
			$items_data = array();
			$purchase = array();
 			$counter = 0; 			
			foreach ($order_products as $pinfo) {
				$counter += 1;
				$pricetx = $pinfo['price'] + $pinfo['tax'];
				$items = array(
					"affiliation" => $this->storename,
					"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"name" => $pinfo['name'],
					"item_id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
					"item_name" => $pinfo['name'],
					"price" => $this->model_extension_compgapro->getcurval($pricetx),
					"currency" => $this->session->data['currency'],
					"quantity" => $pinfo['quantity'],
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
		
			$purchase = array(
				"affiliation" => $this->storename,
				"currency" => $this->session->data['currency'],
				"transaction_id" => $orderdata['order_id'],
				"value" => $this->model_extension_compgapro->getcurval($orderdata['total']),
				"tax" => $this->model_extension_compgapro->getcurval($order_tax),
				"shipping" => $this->model_extension_compgapro->getcurval($order_shipping),
				"items" => $items_data,
			);
			if (isset($this->session->data['coupon'])) {
				$purchase['coupon'] = $this->session->data['coupon'];
			} 			
			
$adw_currency = $this->session->data['currency'];
$adw_order_id = $orderdata['order_id'];
$adw_total = round($orderdata['total'],2);
$json_purchase = json_encode($purchase);

$code1 = '';
if($status) {
$code1 = <<<EOF
<script type="text/javascript">
gtag('event', 'purchase', $json_purchase);
</script>
EOF;
}

$code2 = '';
if($adwstatus) {
$code2 = <<<EOF
<script type="text/javascript">
gtag('event', 'conversion', {'send_to': '$adwid/$adwlbl', 'transaction_id': '$adw_order_id', 'value': $adw_total, 'currency': '$adw_currency' });
</script>
EOF;
}

return $code1 . $code2;
		
 		} 
	} 
}