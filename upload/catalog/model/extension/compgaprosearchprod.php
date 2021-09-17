<?php 
class ModelExtensioncompgaprosearchprod extends Controller { 
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
	public function getcode($searchstr = '') {
		$this->load->model('extension/compgapro');
		$rsdata = $this->model_extension_compgapro->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['gaid'] && $searchstr) {
			$langdata = $this->model_extension_compgapro->getlangdata($rsdata);
			
  			$this->load->model('catalog/product');
  			
 			$items_data = array();
			$counter = 0;
			
			$filter_data = array('filter_name' => $searchstr, 'start' => 0, 'limit' => 5);
			$results = $this->model_catalog_product->getProducts($filter_data);
			if(!empty($results)) {
				foreach ($results as $pinfo) { 
					$counter += 1;
					$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
					$items = array(
						"affiliation" => $this->storename,
						"list_name" => 'Search Products',
						"item_list_name" => 'Search Products',
						"item_list_id" => 'search_page',
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
				$view_search_results = array(
					"search_term" => $searchstr,
					"items" => $items_data,
				);
				$view_search_results['event_category'] = 'product_search';
				$view_search_results['event_label'] = $searchstr;
			}

$json_view_search_results = json_encode($view_search_results);
$code = <<<EOF
<script type="text/javascript">
gtag('event', 'view_search_results', $json_view_search_results);
</script>
EOF;

return $code;
		
 		} 
	} 
}