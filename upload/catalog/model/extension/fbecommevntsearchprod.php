<?php 
class ModelExtensionfbecommevntsearchprod extends Controller { 
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
		$this->load->model('extension/fbecommevnt');
		$rsdata = $this->model_extension_fbecommevnt->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $searchstr) {
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
			
  			$this->load->model('catalog/product');
  			
 			$view_search_results = array();
			$items_data = array();
 			
			$filter_data = array('filter_name' => $searchstr, 'start' => 0, 'limit' => 5);
			$results = $this->model_catalog_product->getProducts($filter_data);
			if(!empty($results)) {
				foreach ($results as $pinfo) { 
 					$price = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
					$pricetx = $this->tax->calculate($pinfo['price'] , $pinfo['tax_class_id'], $this->config->get('config_tax'));
					$items = array(
						"id" => $pinfo['model'] ? $pinfo['model'] : $pinfo['product_id'],
						"quantity" => $pinfo['minimum'],
						"item_price" => $this->model_extension_fbecommevnt->getcurval($pricetx),
					);				
					if($fbcatid) { 
						$items['product_catalog_id'] = $fbcatid;
					}
					$items_data[] = $items;
				}
				$view_search_results = array(
                    "value" => $this->cart->getTotal() ? $this->cart->getTotal() : 1,
                    "currency" => $this->session->data['currency'],
                    "content_type" => 'product', 
                    "content_category" => 'search', 
                    "search_string" => $searchstr, 
                    "contents" => $items_data,
                );
			}

$json_view_search_results = json_encode($view_search_results);
$code = <<<EOF
<script type="text/javascript">
fbq('track', 'Search', $json_view_search_results);
</script>
EOF;
return $code;
		
 		} 
	} 
}