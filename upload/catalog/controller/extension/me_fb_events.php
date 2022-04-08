<?php
class ControllerExtensionMefbevents extends Controller {
	public function index() {
		$json=array();
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		
		if(isset($this->request->get['product_id'])){
			$product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			$json['name'] = $product_info['name'];
			$json['price'] = $this->currency->format($product_info['price'], $this->session->data['currency'], '', false);
			$getcategories = $this->model_catalog_product->getCategories($this->request->get['product_id']);
			$categories = array();
			if($getcategories){
				foreach($getcategories as $cate){
					$getcategory = $this->model_catalog_category->getCategory($cate['category_id']);
					if($getcategory){
						$categories[] = $getcategory['name'];
					}
				}
			}
			$categories = implode(", ", $categories);
			$json['categories'] = $categories;
			$json['manufacturer'] = $product_info['manufacturer'];
			$json['currencycode'] = $this->session->data['currency'];
			$json['me_fb_events_track_cart'] = $this->config->get('module_me_fb_events_track_cart');
			$json['me_fb_events_track_wishlist'] = $this->config->get('module_me_fb_events_track_wishlist');
			$json['me_fb_events_pixel_id'] = $this->config->get('module_me_fb_events_pixel_id');
		}
		print_r(json_encode($json));
	}
	
	public function feed(){
		$me_fb_events_feeds = $this->config->get('module_me_fb_events_feed');
		if(isset($this->request->get['feed'])){
			foreach($me_fb_events_feeds as $feeds){
				$name = preg_replace('/[0-9\@\.\;\" "]+/', '', str_replace(' ','',$feeds['name'])).'.xml';
				if($name == $this->request->get['feed']){
					$this->facebookfeed($feeds);
				}
			}
		}
	}
	
	protected function getPath($parent_id, $current_path = '') {
		$category_info = $this->model_catalog_category->getCategory($parent_id);

		if ($category_info) {
			if (!$current_path) {
				$new_path = $category_info['category_id'];
			} else {
				$new_path = $category_info['category_id'] . '_' . $current_path;
			}

			$path = $this->getPath($category_info['parent_id'], $new_path);

			if ($path) {
				return $path;
			} else {
				return $new_path;
			}
		}
	}
	
	public function facebookfeed($feedsetting){
		$this->load->model('extension/me_fb_events');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		
		$output  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$output .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
		$output .= '  <channel>';
		
		$store_info = $this->model_extension_me_fb_events->getStore($feedsetting['store_id']);

		if ($store_info) {
			$store_name = $store_info['name'];
			$store_url = $store_info['url'];
		} else {
			$store_name = $this->config->get('config_name');
			$store_url = $this->config->get('config_url');
		}
		
		$output .= '  <title>' . $store_name . '</title>';
		$output .= '  <description>' . $this->config->get('config_meta_description') . '</description>';
		$output .= '  <link>' . $store_url . '</link>';

		$product_data = array();
		$categories = array();
		if(!empty($feedsetting['fbcategory_status'])){
			$categories = $this->model_extension_me_fb_events->getFbCategories();
		}
		
		if(!empty($feedsetting['gcategory_status'])){
			$categories = $this->model_extension_me_fb_events->getCategories();
		}
		
		foreach ($categories as $category) {
			if((!empty($feedsetting['fbcategory_status']) && in_array($category['category_id'],$feedsetting['category'])) || (!empty($feedsetting['gcategory_status']) && in_array($category['category_id'],$feedsetting['category']))){
				$filter_data = array(
					'filter_category_id' => $category['category_id'],
					'filter_language_id' => $feedsetting['language_id'],
					'filter_store_id' => $feedsetting['store_id'],
					'customer_group_id' => $feedsetting['customer_cgroup'],
					'filter_manufacturer' => isset($feedsetting['manufacturer']) ? $feedsetting['manufacturer'] : '',
					'filter_minprice' => $feedsetting['minprice'],
					'filter_maxprice' => $feedsetting['maxprice'],
					'filter_minqty' => $feedsetting['minqty'],
					'filter_maxqty' => $feedsetting['maxqty'],
					'filter_filter'      => false
				);
			}else{
				$filter_data = array(
					'filter_category_id' => 0,
					'customer_group_id' => $this->config->get('config_customer_group_id'),
					'filter_store_id' => $this->config->get('config_store_id'),
					'filter_language_id' => $this->config->get('config_language_id'),
					'filter_manufacturer' => '',
					'filter_minprice' => '',
					'filter_maxprice' => '',
					'filter_minqty' => '',
					'filter_maxqty' => '',
					'filter_filter'      => false
				);
				
			}
			
			if($filter_data){
				$products = $this->model_extension_me_fb_events->getProducts($filter_data);
				foreach ($products as $product) {
					if (!in_array($product['product_id'], $product_data) && $product['description']) {
						
						$product_data[] = $product['product_id'];
						
						$output .= '<item>';					
						if($feedsetting['gid_tag'] == 'product_id'){
							$output .= '<g:id>' . $product['product_id'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'model'){
							$output .= '<g:id>' . $product['model'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'sku'){
							$output .= '<g:id>' . $product['sku'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'upc'){
							$output .= '<g:id>' . $product['upc'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'ean'){
							$output .= '<g:id>' . $product['ean'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'jan'){
							$output .= '<g:id>' . $product['jan'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'isbn'){
							$output .= '<g:id>' . $product['isbn'] . '</g:id>';
						}elseif($feedsetting['gid_tag'] == 'mpn'){
							$output .= '<g:id>' . $product['mpn'] . '</g:id>';
						}
						
						$output .= '<g:title><![CDATA[' . $product['name'] . ']]></g:title>';
						$output .= '<g:description><![CDATA[' . strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')) . ']]></g:description>';
						$output .= '<g:link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</g:link>';
						if ($product['image']) {
							$output .= '  <g:image_link>' . $this->model_tool_image->resize($product['image'], 600, 600) . '</g:image_link>';
						} else {
							$output .= '  <g:image_link></g:image_link>';
						}
						$output .= '  <g:availability><![CDATA[' . ($product['quantity'] ? 'in stock' : 'out of stock') . ']]></g:availability>';
						
						$output .= '<g:condition>new</g:condition>';
						
						$currencies = array(
							'USD',
							'EUR',
							'GBP'
						);

						if (in_array($feedsetting['currency'], $currencies)) {
							$currency_code = $feedsetting['currency'];
							$currency_value = $this->currency->getValue($feedsetting['currency']);
						} else {
							$currency_code = 'USD';
							$currency_value = $this->currency->getValue('USD');
						}

						if ((float)$product['special']) {
							if($feedsetting['pricetax']){
								$output .= '  <g:price>' .  $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id']), $currency_code, $currency_value, false) . '</g:price>';
							}else{
								$output .= '  <g:price>' .  $this->currency->format($product['special'], $currency_code, $currency_value, false) . '</g:price>';
							}
						} else {
							if($feedsetting['pricetax']){
								$output .= '  <g:price>' . $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id']), $currency_code, $currency_value, false) . '</g:price>';
							}else{
								$output .= '  <g:price>' .  $this->currency->format($product['price'], $currency_code, $currency_value, false) . '</g:price>';
							}
						}
						
						if ($product['mpn']) {
							$output .= '  <g:mpn><![CDATA[' . $product['mpn'] . ']]></g:mpn>' ;
						} else {
							$output .= '  <g:identifier_exists>false</g:identifier_exists>';
						}

						if ($product['upc']) {
							$output .= '  <g:upc>' . $product['upc'] . '</g:upc>';
						}

						if ($product['ean']) {
							$output .= '  <g:ean>' . $product['ean'] . '</g:ean>';
						}
						
						$output .= '<g:brand><![CDATA[' . html_entity_decode($product['manufacturer'], ENT_QUOTES, 'UTF-8') . ']]></g:brand>';
						if(!empty($feedsetting['fbcategory_status'])){
							$output .= '  <g:fb_product_category>' . $category['facebook_category_id'] . '</g:fb_product_category>';
						}elseif(!empty($feedsetting['gcategory_status'])){
							$output .= '  <g:google_product_category>' . $category['google_category_id'] . '</g:google_product_category>';
						}
						
						$categories = $this->model_catalog_product->getCategories($product['product_id']);

						foreach ($categories as $cate) {
							$path = $this->getPath($cate['category_id']);

							if ($path) {
								$string = '';

								foreach (explode('_', $path) as $path_id) {
									$category_info = $this->model_catalog_category->getCategory($path_id);

									if ($category_info) {
										if (!$string) {
											$string = $category_info['name'];
										} else {
											$string .= ' &gt; ' . $category_info['name'];
										}
									}
								}

								$output .= '<g:product_type><![CDATA[' . $string . ']]></g:product_type>';
							}
						}
						
						$output .= '  <g:quantity>' . $product['quantity'] . '</g:quantity>';
						if(!empty($product['length']) && $product['length'] != '0.00'){
							$output .= '  <g:product_length>' . $this->length->format($product['length'], $product['length_class_id']) . '</g:product_length>';
						}
						if(!empty($product['width']) && $product['width'] != '0.00'){
							$output .= '  <g:product_width>' . $this->length->format($product['width'], $product['length_class_id']) . '</g:product_width>';
						}
						if(!empty($product['height']) && $product['height'] != '0.00'){
							$output .= '  <g:product_height>' . $this->length->format($product['height'], $product['length_class_id']) . '</g:product_height>';
						}
						if($product['weight']){
							$output .= '  <g:product_weight>' . $this->weight->format($product['weight'], $product['weight_class_id']) . '</g:product_weight>';
						}
						
						$output .= '</item>';
					}
				}
			}
		}

		$output .= '  </channel>';
		$output .= '</rss>';
		
		$this->response->addHeader('Content-Type: application/xml');
		$this->response->setOutput($output);
	}
}