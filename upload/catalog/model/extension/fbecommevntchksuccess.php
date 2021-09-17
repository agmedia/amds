<?php 
class ModelExtensionfbecommevntchksuccess extends Controller { 
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
		$this->load->model('extension/fbecommevnt');
		$rsdata = $this->model_extension_fbecommevnt->getdata();
		if(!$order_id && isset($this->session->data['fbecommevnt_order_id'])) {
			$order_id = $this->session->data['fbecommevnt_order_id'];
		}
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $order_id) {
			$storequery = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '" . $this->storeid . "'");
 			$this->storename = isset($storequery->row['name']) ? $storequery->row['name'] : $this->config->get('config_name');
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
			 
 			$this->load->model('checkout/order');
			
			$orderdata = $this->model_checkout_order->getOrder($order_id);
 			$order_products = $this->model_extension_fbecommevnt->getorderproduct($order_id); 
			$order_tax = $this->model_extension_fbecommevnt->getordertax($order_id);
			$order_shipping = $this->model_extension_fbecommevnt->getordershipping($order_id);
			
			$items_data = array();
			$purchase = array();
			foreach ($order_products as $pinfo) {
				$pricetx = $pinfo['price'] + $pinfo['tax'];
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
		
			/*$purchase = array(
				"tax" => $this->model_extension_fbecommevnt->getcurval($order_tax),
				"shipping" => $this->model_extension_fbecommevnt->getcurval($order_shipping),
			);*/
			$purchase = array(
				"order_id" => $orderdata['order_id'],
				"value" => $this->model_extension_fbecommevnt->getcurval($orderdata['total']),
				"currency" => $this->session->data['currency'],
				"content_type" => 'product', 
				"content_category" => 'Purchase',
				"contents" => $items_data
			);
			$leaddata = array(
 				"content_category" => 'CompleteOrder',
				"content_name" => 'leadtracking',
				"value" => 1,
				"currency" => $this->session->data['currency'], 
			);
			if($fbcatid) { 
				$leaddata['product_catalog_id'] = $fbcatid;
			}
			/*if (isset($this->session->data['coupon'])) {
				$purchase['coupon'] = $this->session->data['coupon'];
			}*/ 			
			
$json_purchase = json_encode($purchase);
$json_leaddata = json_encode($leaddata);
$code = <<<EOF
<script type="text/javascript">
fbq('track', 'Purchase', $json_purchase);
fbq('track', 'Lead', $json_leaddata);
</script>
EOF;
return $code;
		
 		} 
	} 
}