<?php 
class ModelExtensioncompgaproprodpage extends Controller { 
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
	public function getcode($product_id = 0) {
		$this->load->model('extension/compgapro');
		$rsdata = $this->model_extension_compgapro->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $product_id) {
			$langdata = $this->model_extension_compgapro->getlangdata($rsdata);
 			 
 			$this->load->model('catalog/product');
			
			$view_item = array();
  			$pinfo = $this->model_catalog_product->getProduct($product_id);
			if ($pinfo) { 
 				$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
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
				);				
				if($pinfo['special']) { 
					$specialtx = $this->tax->calculate($pinfo['special'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
					$items['discount'] = $this->model_extension_compgapro->getcurval($pricetx - $specialtx);
					$view_item['value'] = $this->model_extension_compgapro->getcurval($specialtx);
				}
				$view_item['currency'] = $this->session->data['currency'];							
				$view_item['items'] = $items;
				$view_item['event_category'] = 'product_view';
				$view_item['event_label'] = 'product_details_page';
 			}
 			
			$view_item_list = array();
			$items_data = array();
			$counter = 0;
			$results = $this->model_catalog_product->getProductRelated($product_id);
			if(!empty($results)) {
				foreach ($results as $pinfo) { 
					$counter += 1;
					$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
					$items = array(
						"affiliation" => $this->storename,
						"list_name" => 'Related Products',
						"item_list_name" => 'Related Products',
						"item_list_id" => 'related_products',
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
					if($pinfo['special']) { 
						$specialtx = $this->tax->calculate($pinfo['special'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
						$items['discount'] = $this->model_extension_compgapro->getcurval($pricetx - $specialtx);
					}
					if (isset($this->session->data['coupon'])) {
						$items['coupon'] = $this->session->data['coupon'];
					}
					$items_data[] = $items;
				} 
				$view_item_list = array(
					"list_name" => 'Related Products',
					"item_list_name" => 'Related Products',
					"item_list_id" => 'related_products',
					"items" => $items_data,
				);
				$view_item_list['event_category'] = 'product_view';
				$view_item_list['event_label'] = 'related_products';
			} 			
 
$json_view_item_list = json_encode($view_item_list);
$json_view_item = json_encode($view_item);
$code = <<<EOF
<script type="text/javascript">
gtag('event', 'view_item', $json_view_item);
gtag('event', 'view_item_list', $json_view_item_list);
</script>
EOF;

return $code;
		
 		} 
	} 
}