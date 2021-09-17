<?php 
class ModelExtensionfbecommevntprodpage extends Controller { 
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
		$this->load->model('extension/fbecommevnt');
		$rsdata = $this->model_extension_fbecommevnt->getdata();
		if($rsdata && $rsdata['status'] == 1 && $rsdata['fbpixelid'] && $product_id) {
			$langdata = $this->model_extension_fbecommevnt->getlangdata($rsdata);
			
			$status = $rsdata['status'] ? $rsdata['status'] : false;
			$fbpixelid = $rsdata['fbpixelid'] ? $rsdata['fbpixelid'] : false;
			$fbcatid = $rsdata['fbcatid'] ? $rsdata['fbcatid'] : false;	
 			 
 			$this->load->model('catalog/product');
			
			$view_item = array();
  			$pinfo = $this->model_catalog_product->getProduct($product_id);
			if ($pinfo) { 
				$price = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
 				$pricetx = $this->tax->calculate($price , $pinfo['tax_class_id'], $this->config->get('config_tax'));
				$view_item = array(
					"content_ids" => array($pinfo['model'] ? $pinfo['model'] : $pinfo['product_id']),
					"content_type" => 'product',
					"content_name" => htmlspecialchars_decode(strip_tags($pinfo['name'])),
					"content_category" => $this->model_extension_fbecommevnt->getcatname($pinfo['product_id']),
					"price" => $this->model_extension_fbecommevnt->getcurval($pricetx),
					"currency" => $this->session->data['currency'], 					
				);
				if($fbcatid) { 
					$view_item['product_catalog_id'] = $fbcatid;
				}			
 			}
 			
			$view_item_list = array();
			$results = $this->model_catalog_product->getProductRelated($product_id);
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
				$view_item_list = array(
                    "value" => 1,
                    "currency" => $this->session->data['currency'],
                    "content_type" => 'product', 
                    "contents" => $items_data,
                );				
			} 			
 
$json_view_item_list = json_encode($view_item_list);
$json_view_item = json_encode($view_item);
$code = <<<EOF
<script type="text/javascript">
fbq('track', 'ViewContent', $json_view_item);
fbq('trackCustom', 'RelatedProduct', $json_view_item_list);
</script>
EOF;
return $code;
		
 		} 
	} 
}