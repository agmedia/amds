<?php
header('Cache-Control: no-cache, no-store');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 900);
ini_set('error_reporting', E_ALL);
include DIR_SYSTEM.'library/me_order_manager/PHPExcel.php';
class ControllerExtensionMeordermanager extends Controller {
	private $error = array();
			
	public function index(){
		$this->load->model('sale/order');
		$this->load->model('extension/me_order_manager');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('tool/upload');
		$this->load->language('extension/me_order_manager');
		
		$this->model_extension_me_order_manager->create_table();
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}
		
		if (isset($this->request->get['filter_customeremail'])) {
			$filter_customeremail = $this->request->get['filter_customeremail'];
		} else {
			$filter_customeremail = '';
		}
		
		if (isset($this->request->get['filter_customer_telephone'])) {
			$filter_customer_telephone = $this->request->get['filter_customer_telephone'];
		} else {
			$filter_customer_telephone = '';
		}

		if (isset($this->request->get['filter_order_status'])) {
			$filter_order_status = $this->request->get['filter_order_status'];
		} else {
			$filter_order_status = '';
		}
		
		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = '';
		}
		
		if (isset($this->request->get['filter_payment_country'])) {
			$filter_payment_country = $this->request->get['filter_payment_country'];
		} else {
			$filter_payment_country = '';
		}
		
		if (isset($this->request->get['filter_shipping_country'])) {
			$filter_shipping_country = $this->request->get['filter_shipping_country'];
		} else {
			$filter_shipping_country = '';
		}
		
		if (isset($this->request->get['filter_currency'])) {
			$filter_currency = $this->request->get['filter_currency'];
		} else {
			$filter_currency = '';
		}
		
		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = '';
		}
		
		if (isset($this->request->get['filter_payment_method'])) {
			$filter_payment_method = $this->request->get['filter_payment_method'];
		} else {
			$filter_payment_method = '';
		}
		
		if (isset($this->request->get['filter_shipping_method'])) {
			$filter_shipping_method = $this->request->get['filter_shipping_method'];
		} else {
			$filter_shipping_method = '';
		}
		
		if (isset($this->request->get['filter_customer_group'])) {
			$filter_customer_group = $this->request->get['filter_customer_group'];
		} else {
			$filter_customer_group = '';
		}
		
		if (isset($this->request->get['filter_store'])) {
			$filter_store = $this->request->get['filter_store'];
		} else {
			$filter_store = null;
		}
		
		if (isset($this->request->get['filter_product'])) {
			$filter_product = $this->request->get['filter_product'];
		} else {
			$filter_product = '';
		}
		
		if (isset($this->request->get['filter_shipping_zone'])) {
			$filter_shipping_zone = $this->request->get['filter_shipping_zone'];
		} else {
			$filter_shipping_zone = '';
		}
		
		if (isset($this->request->get['filter_carrier_name'])) {
			$filter_carrier_name = $this->request->get['filter_carrier_name'];
		} else {
			$filter_carrier_name = '';
		}
		
		if (isset($this->request->get['filter_tracking_code'])) {
			$filter_tracking_code = $this->request->get['filter_tracking_code'];
		} else {
			$filter_tracking_code = '';
		}
		
		if (isset($this->request->get['filter_ip'])) {
			$filter_ip = $this->request->get['filter_ip'];
		} else {
			$filter_ip = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit = $this->config->get('config_limit_admin');
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_carrier_name'])) {
			$url .= '&filter_carrier_name=' . urlencode(html_entity_decode($this->request->get['filter_carrier_name'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_customeremail'])) {
			$url .= '&filter_customeremail=' . urlencode(html_entity_decode($this->request->get['filter_customeremail'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_customer_telephone'])) {
			$url .= '&filter_customer_telephone=' . $this->request->get['filter_customer_telephone'];
		}
		
		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}
	
		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}
		
		if (isset($this->request->get['filter_payment_country'])) {
			$url .= '&filter_payment_country=' . $this->request->get['filter_payment_country'];
		}
		
		if (isset($this->request->get['filter_shipping_country'])) {
			$url .= '&filter_shipping_country=' . $this->request->get['filter_shipping_country'];
		}
		
		if (isset($this->request->get['filter_currency'])) {
			$url .= '&filter_currency=' . $this->request->get['filter_currency'];
		}
			
		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}
		
		if (isset($this->request->get['filter_payment_method'])) {
			$url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
		}
		
		if (isset($this->request->get['filter_shipping_method'])) {
			$url .= '&filter_shipping_method=' . $this->request->get['filter_shipping_method'];
		}
		
		if (isset($this->request->get['filter_customer_group'])) {
			$url .= '&filter_customer_group=' . $this->request->get['filter_customer_group'];
		}
		
		if (isset($this->request->get['filter_store'])) {
			$url .= '&filter_store=' . $this->request->get['filter_store'];
		}
		
		if (isset($this->request->get['filter_shipping_zone'])) {
			$url .= '&filter_shipping_zone=' . $this->request->get['filter_shipping_zone'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}
		
		if (isset($this->request->get['filter_tracking_code'])) {
			$url .= '&filter_tracking_code=' . $this->request->get['filter_tracking_code'];
		}
		
		if (isset($this->request->get['filter_ip'])) {
			$url .= '&filter_ip=' . $this->request->get['filter_ip'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['action'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'], true);
		$data['order_history_action'] = $this->url->link('extension/me_order_manager/addOrderHistory', 'user_token=' . $this->session->data['user_token'], true);
		$data['settings'] = $this->url->link('extension/me_order_manager_setting', 'user_token=' . $this->session->data['user_token'], true);
		$data['order_export'] = $this->url->link('extension/me_order_manager/exportOrder', 'user_token=' . $this->session->data['user_token'], true);
		$data['invoice'] = $this->url->link('sale/order/invoice', 'user_token=' . $this->session->data['user_token'], true);
		$data['shipping'] = $this->url->link('sale/order/shipping', 'user_token=' . $this->session->data['user_token'], true);
		$data['add'] = $this->url->link('sale/order/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = str_replace('&amp;', '&', $this->url->link('extension/me_order_manager/delete', 'user_token=' . $this->session->data['user_token'] . $url, true));

		$data['orders'] = array();

		$filter_data = array(
			'filter_order_id'        => $filter_order_id,
			'filter_customer'	     => $filter_customer,
			'filter_customeremail'	     => $filter_customeremail,
			'filter_customer_telephone'	     => $filter_customer_telephone,
			'filter_order_status' => $filter_order_status_id,
			'filter_total'           => $filter_total,
			'filter_date_added'      => $filter_date_added,
			'filter_date_modified'   => $filter_date_modified,
			'filter_payment_method'   => $filter_payment_method,
			'filter_shipping_method'   => $filter_shipping_method,
			'filter_customer_group'   => $filter_customer_group,
			'filter_product'   => $filter_product,
			'filter_store'   => $filter_store,
			'filter_shipping_zone'   => $filter_shipping_zone,
			'filter_carrier_name'   => $filter_carrier_name,
			'filter_payment_country'   => $filter_payment_country,
			'filter_shipping_country'   => $filter_shipping_country,
			'filter_currency'   => $filter_currency,
			'filter_tracking_code'   => $filter_tracking_code,
			'filter_ip'   => $filter_ip,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $limit,
			'limit'                  => $limit
		);

		$order_total = $this->model_extension_me_order_manager->getTotalOrders($filter_data);
		$sum_order_total = $this->model_extension_me_order_manager->getTotalSumOrders($filter_data);

		$results = $this->model_extension_me_order_manager->getOrders($filter_data);
		
		foreach ($results as $result) {
			$order_info = $this->model_extension_me_order_manager->getOrder($result['order_id']);
			
			//Payment Address
			$payment_address = $order_info['payment_firstname'].' '.$order_info['payment_lastname'] . "," . (!empty($order_info['payment_company']) ? $order_info['payment_company'] . "," : ''). $order_info['payment_address_1'] . "," . (!empty($order_info['payment_address_2']) ? $order_info['payment_address_2'] . "," : '') . $order_info['payment_city'].' '.$order_info['payment_postcode'] . "," . $order_info['payment_zone'] . "," . $order_info['payment_country'];
			
			//Shipping Address
			$shipping_address = $order_info['shipping_firstname'].' '.$order_info['shipping_lastname'] . "," . (!empty($order_info['shipping_company']) ? $order_info['shipping_company'] . "," : ''). $order_info['shipping_address_1'] . "," . (!empty($order_info['shipping_address_2']) ? $order_info['shipping_address_2'] . "," : '') . $order_info['shipping_city'].' '.$order_info['shipping_postcode'] . "," . $order_info['shipping_zone'] . "," . $order_info['shipping_country'];
			
			//Affiliate
			if ($order_info['affiliate_id']) {
				$affiliate = $order_info['affiliate_firstname'].' '.$order_info['affiliate_lastname'];
			} else {
				$affiliate = '';
			}
			
			//Products
			$totalproducts = $this->model_extension_me_order_manager->getTotalOrderProducts($result['order_id']); 
			$products = array();
			$order_weight = '';
			$weight = 0;
			$order_products = $this->model_sale_order->getOrderProducts($result['order_id']);

			foreach ($order_products as $order_product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($result['order_id'], $order_product['order_product_id']);
				$option_weight = 0;
				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
					
					$product_option_value_info = $this->model_catalog_product->getProductOptionValue($order_product['product_id'], $option['product_option_value_id']);

					if ($product_option_value_info) {
						if ($product_option_value_info['weight_prefix'] == '+') {
							$option_weight += $product_option_value_info['weight'];
						} elseif ($product_option_value_info['weight_prefix'] == '-') {
							$option_weight -= $product_option_value_info['weight'];
						}
					}
				}
				
				$product_info = $this->model_catalog_product->getProduct($order_product['product_id']);
				if ($product_info) {
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], 50, 50);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', 50, 50);
					}
					
					$weight += $this->weight->convert(($product_info['weight'] + (float)$option_weight) * $order_product['quantity'], $product_info['weight_class_id'], $this->config->get('config_weight_class_id'));
				}else {
					$image = $this->model_tool_image->resize('placeholder.png', 50, 50);
				}
				
				$products[] = array(
					'order_product_id' => $order_product['order_product_id'],
					'product_id'       => $order_product['product_id'],
					'name'    	 	   => $order_product['name'],
					'image'    	 	   => $image,
					'model'    		   => $order_product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $order_product['quantity'],
					'price'    		   => $this->currency->format($order_product['price'] + ($this->config->get('config_tax') ? $order_product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $order_product['product_id'], true)
				);
			}
			
			$order_weight = $this->weight->format($weight, $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
			
			//Coupon,Voucher,reward
			$ordertotals = $this->model_sale_order->getOrderTotals($result['order_id']);
			
			$couponvoucher = array();
			foreach ($ordertotals as $ordertotal) {
				// If coupon, voucher or reward points
				$start = strpos($ordertotal['title'], '(') + 1;
				$end = strrpos($ordertotal['title'], ')');
				
				if ($start && $end) {
					$couponvoucher[$ordertotal['code']] = substr($ordertotal['title'], $start, $end - $start);
				}
			}
			$bgcolor = '';
			$color = '';
			if(isset($this->config->get('module_me_order_manager_setting_ostatus')[$order_info['order_status_id']])){
				$bgcolor = $this->config->get('module_me_order_manager_setting_ostatus')[$order_info['order_status_id']]['bgcolor'];
				$color = $this->config->get('module_me_order_manager_setting_ostatus')[$order_info['order_status_id']]['color'];
			} 
			
			if($order_info['customer_id']){
				$customer_type = 1;
			}else{
				$customer_type = 0;
			}
			
			//Tracking
			$ordertracking = $this->model_extension_me_order_manager->getTracking($result['order_id']);
			if($ordertracking){
				$carrier_name = $ordertracking['carrier_name'];
				$tracking_code = $ordertracking['tracking_code'];
				$tracking_url = $ordertracking['tracking_url'];
			}else{
				$carrier_name = '';
				$tracking_code = '';
				$tracking_url = '';
			}
			
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'invoice_no'      => $order_info['invoice_no'],
				'invoice_prefix'      => $order_info['invoice_prefix'],
				'store_id'      => $order_info['store_id'],
				'store_name'      => $order_info['store_name'],
				'store_url'      => $order_info['store_url'],
				'payment_method'      => $order_info['payment_method'],
				'shipping_method'      => $order_info['shipping_method'],
				'comment'      => $order_info['comment'],
				'language_code'      => $order_info['language_code'],
				'currency_code'      => $order_info['currency_code'],
				'ip'      => $order_info['ip'],
				'customer'      => $result['customer'],
				'email'      => $order_info['email'],
				'telephone'      => $order_info['telephone'],
				'customer_type'      => $customer_type,
				'customer_group'      => $result['customer_group'],
				'affiliate'      => $affiliate,
				'order_weight'      => $order_weight,
				'payment_address'      => $payment_address,
				'shipping_address'      => $shipping_address,
				'couponvoucher'      => $couponvoucher,
				'products'      => $products,
				'totalproducts'      => $totalproducts,
				'bgcolor'      => $bgcolor,
				'color'      => $color,
				'tracking_code'      => $tracking_code,
				'carrier_name'      => $carrier_name,
				'tracking_url'      => $tracking_url,
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'invoice' => $this->url->link('sale/order/invoice', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$result['order_id'], true),
				'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, true),
				'edit'          => $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}
		
		$data['store_id'] = $this->config->get('config_store_id') ? $this->config->get('config_store_id') : 0; 
		$data['language_id'] = $this->config->get('config_language_id');
		
		### Get All stores
		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		### Get All languages
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		### Get All Currencies
		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
		
		### Get All Countries
		$this->load->model('localisation/country');
		$data['countries'] = $this->model_localisation_country->getCountries();
		
		### Get Customer groups
		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		
		### Get Zones
		$this->load->model('localisation/zone');
		$country_id = isset($this->config->get('module_me_order_manager_setting_filter')['country_id']) ? $this->config->get('module_me_order_manager_setting_filter')['country_id'] : '';
		$data['zones'] = $this->model_localisation_zone->getZonesByCountryId($country_id);
		
		### Get All Payment Methods
		$this->load->model('setting/extension');
		$payment_methods = $this->model_setting_extension->getInstalled('payment');
		$data['payment_methods'] = array();
		
		foreach($payment_methods as $payment_method){
			if ($this->config->get('payment_' . $payment_method . '_status')) {
				$this->load->language('extension/payment/'.$payment_method);
				$data['payment_methods'][] = array(
					'code' => $payment_method,
					'name' => $this->language->get('heading_title')
				);
			}
		}
		
		### Get All Shipping Methods
		$this->load->model('setting/extension');
		$shipping_methods = $this->model_setting_extension->getInstalled('shipping');
		$data['shipping_methods'] = array();
		
		foreach($shipping_methods as $shipping_method){
			if ($this->config->get('shipping_' . $shipping_method . '_status')) {
				$this->load->language('extension/shipping/'.$shipping_method);
				if($shipping_method == 'xshippingpro'){
					$getdatas = $this->model_extension_me_order_manager->getshippingpro();
					foreach($getdatas as $getdata){
						$methods = json_decode($getdata['method_data'],true);
						
						$data['shipping_methods'][] = array(
							'code' => 'xshippingpro.xshippingpro'.$getdata['id'],
							'name' => $methods['display']
						);
					}
				}else{
					$data['shipping_methods'][] = array(
						'code' => $shipping_method,
						'name' => $this->language->get('heading_title')
					);
				}
			}
		}
		
		$this->load->language('extension/me_order_manager');

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_carrier_name'])) {
			$url .= '&filter_carrier_name=' . urlencode(html_entity_decode($this->request->get['filter_carrier_name'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_customeremail'])) {
			$url .= '&filter_customeremail=' . urlencode(html_entity_decode($this->request->get['filter_customeremail'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_customer_telephone'])) {
			$url .= '&filter_customer_telephone=' . $this->request->get['filter_customer_telephone'];
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}
		
		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}
		
		if (isset($this->request->get['filter_payment_country'])) {
			$url .= '&filter_payment_country=' . $this->request->get['filter_payment_country'];
		}
		
		if (isset($this->request->get['filter_shipping_country'])) {
			$url .= '&filter_shipping_country=' . $this->request->get['filter_shipping_country'];
		}
		
		if (isset($this->request->get['filter_currency'])) {
			$url .= '&filter_currency=' . $this->request->get['filter_currency'];
		}
			
		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}
		
		if (isset($this->request->get['filter_payment_method'])) {
			$url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
		}
		
		if (isset($this->request->get['filter_shipping_method'])) {
			$url .= '&filter_shipping_method=' . $this->request->get['filter_shipping_method'];
		}
		
		if (isset($this->request->get['filter_customer_group'])) {
			$url .= '&filter_customer_group=' . $this->request->get['filter_customer_group'];
		}
		
		if (isset($this->request->get['filter_store'])) {
			$url .= '&filter_store=' . $this->request->get['filter_store'];
		}
		
		if (isset($this->request->get['filter_shipping_zone'])) {
			$url .= '&filter_shipping_zone=' . $this->request->get['filter_shipping_zone'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}
		
		if (isset($this->request->get['filter_tracking_code'])) {
			$url .= '&filter_tracking_code=' . $this->request->get['filter_tracking_code'];
		}
		
		if (isset($this->request->get['filter_ip'])) {
			$url .= '&filter_ip=' . $this->request->get['filter_ip'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}
		
		$data['order_manager_setting'] = $this->config->get('module_me_order_manager_setting');
		$data['order_manager_filter'] = $this->config->get('module_me_order_manager_setting_filter');
		$data['order_manager_setting_active_orderstatus'] = $this->config->get('module_me_order_manager_setting_active_orderstatus');
		$data['order_manager_setting_bulkupdate'] = $this->config->get('module_me_order_manager_setting_bulkupdate');
		$data['order_manager_setting_tracking'] = $this->config->get('module_me_order_manager_setting_tracking');
		
		$data['order_status_message'] = !empty($this->config->get('module_me_order_manager_setting_bulkupdate')[$data['order_manager_setting_active_orderstatus']]) ? $this->config->get('module_me_order_manager_setting_bulkupdate')[$data['order_manager_setting_active_orderstatus']] : array();
		
		$columns = $this->config->get('module_me_order_manager_setting_column');
		$sortcolumns = array();
		
		if($columns){
			foreach($columns as $key => $column){
				$sortcolumns[] = array(
					'key' => $key,
					'sort_order' => $column['sort_order'],
					'status' => isset($column['status']) ? $column['status'] : ''
				);
			}
			
			function sortcolumn( $a, $b ){
				return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
			}
			
			usort($sortcolumns, "sortcolumn");
		}

		$data['sort_order_id'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . $url, true);
		$data['sort_customer'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . $url, true);
		$data['sort_store'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.store_id' . $url, true);
		$data['sort_customer_group'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.customer_group_id' . $url, true);
		$data['sort_currency'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.currency_code' . $url, true);
		$data['sort_language'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.language_id' . $url, true);
		$data['sort_order_status'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=order_status' . $url, true);
		$data['sort_total'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.total' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);
		$data['sort_date_modified'] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_modified' . $url, true);
		
		$allordercolumns = $this->model_extension_me_order_manager->getOrderColumns();
		
		foreach($allordercolumns as $allordercolumn){
			$data['sort_'.$allordercolumn] = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&sort=o.'.$allordercolumn . $url, true);
		}
		
		$data['order_manager_column'] = array();
		foreach($sortcolumns as $column){
			$data['order_manager_column'][$column['key']] = array(
				'sort_order' => $column['sort_order'],
				'status' => $column['status'],
				'name' => $this->language->get('column_'.$column['key']),
				'sort' => isset($data['sort_'.$column['key']]) ? $data['sort_'.$column['key']] : $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'].$url, true)
			);
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_carrier_name'])) {
			$url .= '&filter_carrier_name=' . urlencode(html_entity_decode($this->request->get['filter_carrier_name'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}
		
		if (isset($this->request->get['filter_customeremail'])) {
			$url .= '&filter_customeremail=' . urlencode(html_entity_decode($this->request->get['filter_customeremail'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_customer_telephone'])) {
			$url .= '&filter_customer_telephone=' . $this->request->get['filter_customer_telephone'];
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}
		
		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}
		
		if (isset($this->request->get['filter_payment_country'])) {
			$url .= '&filter_payment_country=' . $this->request->get['filter_payment_country'];
		}
		
		if (isset($this->request->get['filter_shipping_country'])) {
			$url .= '&filter_shipping_country=' . $this->request->get['filter_shipping_country'];
		}
		
		if (isset($this->request->get['filter_currency'])) {
			$url .= '&filter_currency=' . $this->request->get['filter_currency'];
		}
			
		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}
		
		if (isset($this->request->get['filter_payment_method'])) {
			$url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
		}
		
		if (isset($this->request->get['filter_shipping_method'])) {
			$url .= '&filter_shipping_method=' . $this->request->get['filter_shipping_method'];
		}
		
		if (isset($this->request->get['filter_customer_group'])) {
			$url .= '&filter_customer_group=' . $this->request->get['filter_customer_group'];
		}
		
		if (isset($this->request->get['filter_store'])) {
			$url .= '&filter_store=' . $this->request->get['filter_store'];
		}
		
		if (isset($this->request->get['filter_shipping_zone'])) {
			$url .= '&filter_shipping_zone=' . $this->request->get['filter_shipping_zone'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}
		
		if (isset($this->request->get['filter_tracking_code'])) {
			$url .= '&filter_tracking_code=' . $this->request->get['filter_tracking_code'];
		}
		
		if (isset($this->request->get['filter_ip'])) {
			$url .= '&filter_ip=' . $this->request->get['filter_ip'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($order_total - $limit)) ? $order_total : ((($page - 1) * $limit) + $limit), $order_total, ceil($order_total / $limit));
		

		$data['sum_order_total'] = $this->currency->format($sum_order_total, $this->config->get('config_currency'));
		$data['order_total'] = $order_total;
		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_customeremail'] = $filter_customeremail;
		$data['filter_customer_telephone'] = $filter_customer_telephone;
		$data['filter_order_status'] = $filter_order_status;
		if(isset($filter_order_status_id) && $filter_order_status_id != ''){
			$data['filter_order_status_id'] = explode(',',$filter_order_status_id);
		}else{
			$data['filter_order_status_id'] = '';
		}
		$data['filter_total'] = $filter_total;
		$data['filter_date_added'] = $filter_date_added;
		$data['filter_date_modified'] = $filter_date_modified;
		$data['filter_payment_method'] = $filter_payment_method;
		$data['filter_shipping_method'] = $filter_shipping_method;
		$data['filter_customer_group'] = $filter_customer_group;
		$data['filter_store'] = $filter_store;
		$data['filter_product'] = $filter_product;
		$data['filter_shipping_zone'] = $filter_shipping_zone;
		$data['filter_carrier_name'] = $filter_carrier_name;
		$data['filter_tracking_code'] = $filter_tracking_code;
		$data['filter_payment_country'] = $filter_payment_country;
		$data['filter_shipping_country'] = $filter_shipping_country;
		$data['filter_currency'] = $filter_currency;
		$data['filter_ip'] = $filter_ip;
		$data['limit'] = $limit;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		$data['total_orders'] = array();
		
		foreach($data['order_statuses'] as $order_statuses){
			if(isset($data['order_manager_setting']['total_order_status']) && in_array($order_statuses['order_status_id'],$data['order_manager_setting']['total_order_status'])){
				$data['total_orders'][$order_statuses['order_status_id']] = array(
					'name' => $order_statuses['name'],
					'total' => $this->model_extension_me_order_manager->getTotalOrders(array('filter_order_status_id' => $order_statuses['order_status_id'])),
					'bgcolor' => isset($this->config->get('module_me_order_manager_setting_ostatus')[$order_statuses['order_status_id']]['bgcolor']) ? $this->config->get('module_me_order_manager_setting_ostatus')[$order_statuses['order_status_id']]['bgcolor'] : '#fff',
					'color' => isset($this->config->get('module_me_order_manager_setting_ostatus')[$order_statuses['order_status_id']]['color']) ? $this->config->get('module_me_order_manager_setting_ostatus')[$order_statuses['order_status_id']]['color'] : '#2f2f2f',
					'href' => $this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . '&filter_order_status_id=' . $order_statuses['order_status_id'], true)
				);
			}
		}
	
		// API login
		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
		
		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info && $this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$session = new Session($this->config->get('session_engine'), $this->registry);
			
			$session->start();
					
			$this->model_extension_me_order_manager->deleteApiSessionBySessonId($session->getId());
			
			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
			
			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();
		} else {
			$data['api_token'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/me_order_manager/me_order_manager', $data));
	}
	
	public function delete() {
		$this->load->language('sale/order');
		$this->load->model('extension/me_order_manager');

		$this->document->setTitle($this->language->get('heading_title'));
		$url = '';
		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $order_id) {
				$this->model_extension_me_order_manager->deleteOrder($order_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_carrier_name'])) {
				$url .= '&filter_carrier_name=' . urlencode(html_entity_decode($this->request->get['filter_carrier_name'], ENT_QUOTES, 'UTF-8'));
			}
			
			if (isset($this->request->get['filter_customeremail'])) {
				$url .= '&filter_customeremail=' . urlencode(html_entity_decode($this->request->get['filter_customeremail'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_customer_telephone'])) {
				$url .= '&filter_customer_telephone=' . $this->request->get['filter_customer_telephone'];
			}

			if (isset($this->request->get['filter_order_status'])) {
				$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
			}
		
			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}
			
			if (isset($this->request->get['filter_payment_country'])) {
				$url .= '&filter_payment_country=' . $this->request->get['filter_payment_country'];
			}
			
			if (isset($this->request->get['filter_shipping_country'])) {
				$url .= '&filter_shipping_country=' . $this->request->get['filter_shipping_country'];
			}
			
			if (isset($this->request->get['filter_currency'])) {
				$url .= '&filter_currency=' . $this->request->get['filter_currency'];
			}
				
			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}
			
			if (isset($this->request->get['filter_payment_method'])) {
				$url .= '&filter_payment_method=' . $this->request->get['filter_payment_method'];
			}
			
			if (isset($this->request->get['filter_shipping_method'])) {
				$url .= '&filter_shipping_method=' . $this->request->get['filter_shipping_method'];
			}
			
			if (isset($this->request->get['filter_customer_group'])) {
				$url .= '&filter_customer_group=' . $this->request->get['filter_customer_group'];
			}
			
			if (isset($this->request->get['filter_store'])) {
				$url .= '&filter_store=' . $this->request->get['filter_store'];
			}
			
			if (isset($this->request->get['filter_shipping_zone'])) {
				$url .= '&filter_shipping_zone=' . $this->request->get['filter_shipping_zone'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}
			
			if (isset($this->request->get['filter_ip'])) {
				$url .= '&filter_ip=' . $this->request->get['filter_ip'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
		}
		
		$this->response->redirect($this->url->link('extension/me_order_manager', 'user_token=' . $this->session->data['user_token'] . $url, true));
	
	}
	
	public function getproduct(){
		$this->load->model('extension/me_order_manager');
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'GET') {
			$order_info = $this->model_extension_me_order_manager->getOrder($this->request->get['id']);
			
			$this->load->model('tool/upload');
			$this->load->model('sale/order');
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			
			$products = $this->model_extension_me_order_manager->getOrderProducts($this->request->get['id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
				}
				
				$product_info = $this->model_catalog_product->getProduct($product['product_id']);
				if ($product_info) {
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], 50, 50);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', 50, 50);
					}
				}else {
					$image = $this->model_tool_image->resize('placeholder.png', 50, 50);
				}

				$json['getvalue'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'image'            => $image,
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], true)
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function addOrderHistory() {
		$json = array();
		$this->load->language('extension/me_order_manager');
		$this->load->model('extension/me_order_manager');
		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			if(!isset($this->request->post['selected'])){
				$json['error']['warning'] = $this->language->get('error_order_id');
			}
		}
		
		if(!$json){
			if (isset($this->request->post['notify'])){
				$notify = true;
			}else{
				$notify = false;
			}
			
			if (isset($this->request->post['override'])){
				$override = true;
			}else{
				$override = false;
			}
			
			if (!empty($this->request->post['comment'])){
				$comment = $this->request->post['comment'];
			}else{
				$comment = '';
			}
			
			$order_status_id = $this->request->post['order_status_id'];
			
			if (isset($this->request->post['selected'])) {
				foreach ($this->request->post['selected'] as $order_id) {
					$this->model_extension_me_order_manager->addOrderHistory($order_id,$order_status_id,$comment, $notify, $override);
					
					if(isset($this->config->get('module_me_order_manager_setting_ostatus')[$order_status_id])){
						$bgcolor = $this->config->get('module_me_order_manager_setting_ostatus')[$order_status_id]['bgcolor'];
						$color = $this->config->get('module_me_order_manager_setting_ostatus')[$order_status_id]['color'];
					} 
					$order_info = $this->model_extension_me_order_manager->getOrder($order_id);
					$json['order'][$order_id] = array(
						'order_status'  => $order_info['order_status'] ? $order_info['order_status'] : $this->language->get('text_missing'),
						'date_modified' => date($this->language->get('date_format_short'), strtotime($order_info['date_modified'])),
						'bgcolor' => isset($bgcolor) ? $bgcolor : '',
						'color' => isset($color) ? $color : ''
					);
				}
			}
			
			$json['success'] = $this->language->get('text_success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function info() {
		$this->load->model('sale/order');
		$this->load->model('extension/me_order_manager');
		
		$data['order_manager_setting'] = $this->config->get('module_me_order_manager_setting');
		$data['order_manager_setting_tracking'] = $this->config->get('module_me_order_manager_setting_tracking');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order_info = $this->model_sale_order->getOrder($order_id);

		if ($order_info) {
			$this->load->language('extension/me_order_manager');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);
			$data['text_order'] = sprintf($this->language->get('text_order'), $this->request->get['order_id']);

			$data['shipping'] = $this->url->link('sale/order/shipping', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['invoice'] = $this->url->link('sale/order/invoice', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['edit'] = $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['cancel'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true);

			$data['user_token'] = $this->session->data['user_token'];

			$data['order_id'] = $this->request->get['order_id'];

			$data['store_id'] = $order_info['store_id'];
			$data['store_name'] = $order_info['store_name'];
			
			if ($order_info['store_id'] == 0) {
				$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
			} else {
				$data['store_url'] = $order_info['store_url'];
			}

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

			$data['firstname'] = $order_info['firstname'];
			$data['lastname'] = $order_info['lastname'];

			if ($order_info['customer_id']) {
				$data['customer'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['customer_id'], true);
			} else {
				$data['customer'] = '';
			}

			$this->load->model('customer/customer_group');

			$customer_group_info = $this->model_customer_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];

			$data['shipping_method'] = $order_info['shipping_method'];
			$data['payment_method'] = $order_info['payment_method'];

			// Payment Address
			if ($order_info['payment_address_format']) {
				$format = $order_info['payment_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			}

			$find = array(
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['payment_firstname'],
				'lastname'  => $order_info['payment_lastname'],
				'company'   => $order_info['payment_company'],
				'address_1' => $order_info['payment_address_1'],
				'address_2' => $order_info['payment_address_2'],
				'city'      => $order_info['payment_city'],
				'postcode'  => $order_info['payment_postcode'],
				'zone'      => $order_info['payment_zone'],
				'zone_code' => $order_info['payment_zone_code'],
				'country'   => $order_info['payment_country']
			);

			$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Shipping Address
			if ($order_info['shipping_address_format']) {
				$format = $order_info['shipping_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			}

			$find = array(
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			);

			$replace = array(
				'firstname' => $order_info['shipping_firstname'],
				'lastname'  => $order_info['shipping_lastname'],
				'company'   => $order_info['shipping_company'],
				'address_1' => $order_info['shipping_address_1'],
				'address_2' => $order_info['shipping_address_2'],
				'city'      => $order_info['shipping_city'],
				'postcode'  => $order_info['shipping_postcode'],
				'zone'      => $order_info['shipping_zone'],
				'zone_code' => $order_info['shipping_zone_code'],
				'country'   => $order_info['shipping_country']
			);

			$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Uploaded files
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
				}

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], true)
				);
			}

			$data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
					'href'        => $this->url->link('sale/voucher/edit', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $voucher['voucher_id'], true)
				);
			}

			$data['totals'] = array();

			$totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}

			$data['comment'] = nl2br($order_info['comment']);

			$this->load->model('customer/customer');

			$data['reward'] = $order_info['reward'];

			$data['reward_total'] = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($this->request->get['order_id']);

			$data['affiliate_firstname'] = $order_info['affiliate_firstname'];
			$data['affiliate_lastname'] = $order_info['affiliate_lastname'];

			if ($order_info['affiliate_id']) {
				$data['affiliate'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['affiliate_id'], true);
			} else {
				$data['affiliate'] = '';
			}

			$data['commission'] = $this->currency->format($order_info['commission'], $order_info['currency_code'], $order_info['currency_value']);

			$this->load->model('customer/customer');

			$data['commission_total'] = $this->model_customer_customer->getTotalTransactionsByOrderId($this->request->get['order_id']);

			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			$data['order_status_id'] = $order_info['order_status_id'];

			$data['account_custom_field'] = $order_info['custom_field'];

			// Custom Fields
			$this->load->model('customer/custom_field');

			$data['account_custom_fields'] = array();

			$filter_data = array(
				'sort'  => 'cf.sort_order',
				'order' => 'ASC'
			);

			$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account' && isset($order_info['custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['account_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['account_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['custom_field'][$custom_field['custom_field_id']]
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name']
							);
						}
					}
				}
			}

			// Custom fields
			$data['payment_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['payment_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['payment_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['payment_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['payment_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			// Shipping
			$data['shipping_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['shipping_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['shipping_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			$data['ip'] = $order_info['ip'];
			$data['forwarded_ip'] = $order_info['forwarded_ip'];
			$data['user_agent'] = $order_info['user_agent'];
			$data['accept_language'] = $order_info['accept_language'];

			// Additional Tabs
			$data['tabs'] = array();

			if ($this->user->hasPermission('access', 'extension/payment/' . $order_info['payment_code'])) {
				if (is_file(DIR_CATALOG . 'controller/extension/payment/' . $order_info['payment_code'] . '.php')) {
					$content = $this->load->controller('extension/payment/' . $order_info['payment_code'] . '/order');
				} else {
					$content = '';
				}

				if ($content) {
					$this->load->language('extension/payment/' . $order_info['payment_code']);

					$data['tabs'][] = array(
						'code'    => $order_info['payment_code'],
						'title'   => $this->language->get('heading_title'),
						'content' => $content
					);
				}
			}

			$this->load->model('setting/extension');

			$extensions = $this->model_setting_extension->getInstalled('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get('fraud_' . $extension . '_status')) {
					$this->load->language('extension/fraud/' . $extension, 'extension');

					$content = $this->load->controller('extension/fraud/' . $extension . '/order');

					if ($content) {
						$data['tabs'][] = array(
							'code'    => $extension,
							'title'   => $this->language->get('extension')->get('heading_title'),
							'content' => $content
						);
					}
				}
			}
			
			if(VERSION < '3.0.3.6'){
				$data['openbaystaus'] = 1;
			}else{
				$data['openbaystaus'] = 0;
			}
			
			// The URL we send API requests to
			$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
			
			// API login
			$this->load->model('user/api');

			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

			if ($api_info && $this->user->hasPermission('modify', 'extension/me_order_manager')) {
				$session = new Session($this->config->get('session_engine'), $this->registry);
				
				$session->start();
				
				$this->model_extension_me_order_manager->deleteApiSessionBySessonId($session->getId());
				
				$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
				
				$session->data['api_id'] = $api_info['api_id'];

				$data['api_token'] = $session->getId();
			} else {
				$data['api_token'] = '';
			}

			$this->response->setOutput($this->load->view('extension/me_order_manager/me_order_manager_info', $data));
		} 
	}
	
	public function edit() {
		$this->load->model('sale/order');
		$this->load->language('extension/me_order_manager');
		$this->load->model('extension/me_order_manager');
		$data['text_form'] = !isset($this->request->get['order_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['order_id'])) {
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
		}

		if (!empty($order_info)) {
			$data['order_id'] = $this->request->get['order_id'];
			$data['store_id'] = $order_info['store_id'];
			$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			$data['customer'] = $order_info['customer'];
			$data['customer_id'] = $order_info['customer_id'];
			$data['customer_group_id'] = $order_info['customer_group_id'];
			$data['firstname'] = $order_info['firstname'];
			$data['lastname'] = $order_info['lastname'];
			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];
			$data['account_custom_field'] = $order_info['custom_field'];

			$this->load->model('customer/customer');

			$data['addresses'] = $this->model_customer_customer->getAddresses($order_info['customer_id']);

			$data['payment_firstname'] = $order_info['payment_firstname'];
			$data['payment_lastname'] = $order_info['payment_lastname'];
			$data['payment_company'] = $order_info['payment_company'];
			$data['payment_address_1'] = $order_info['payment_address_1'];
			$data['payment_address_2'] = $order_info['payment_address_2'];
			$data['payment_city'] = $order_info['payment_city'];
			$data['payment_postcode'] = $order_info['payment_postcode'];
			$data['payment_country_id'] = $order_info['payment_country_id'];
			$data['payment_zone_id'] = $order_info['payment_zone_id'];
			$data['payment_custom_field'] = $order_info['payment_custom_field'];
			$data['payment_method'] = $order_info['payment_method'];
			$data['payment_code'] = $order_info['payment_code'];

			$data['shipping_firstname'] = $order_info['shipping_firstname'];
			$data['shipping_lastname'] = $order_info['shipping_lastname'];
			$data['shipping_company'] = $order_info['shipping_company'];
			$data['shipping_address_1'] = $order_info['shipping_address_1'];
			$data['shipping_address_2'] = $order_info['shipping_address_2'];
			$data['shipping_city'] = $order_info['shipping_city'];
			$data['shipping_postcode'] = $order_info['shipping_postcode'];
			$data['shipping_country_id'] = $order_info['shipping_country_id'];
			$data['shipping_zone_id'] = $order_info['shipping_zone_id'];
			$data['shipping_custom_field'] = $order_info['shipping_custom_field'];
			$data['shipping_method'] = $order_info['shipping_method'];
			$data['shipping_code'] = $order_info['shipping_code'];

			// Products
			$data['order_products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$data['order_products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']),
					'quantity'   => $product['quantity'],
					'price'      => $product['price'],
					'total'      => $product['total'],
					'reward'     => $product['reward']
				);
			}

			// Vouchers
			$data['order_vouchers'] = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';

			$data['order_totals'] = array();

			$order_totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($order_totals as $order_total) {
				// If coupon, voucher or reward points
				$start = strpos($order_total['title'], '(') + 1;
				$end = strrpos($order_total['title'], ')');

				if ($start && $end) {
					$data[$order_total['code']] = substr($order_total['title'], $start, $end - $start);
				}
			}

			$data['order_status_id'] = $order_info['order_status_id'];
			$data['comment'] = $order_info['comment'];
			$data['affiliate_id'] = $order_info['affiliate_id'];
			$data['affiliate'] = $order_info['affiliate_firstname'] . ' ' . $order_info['affiliate_lastname'];
			$data['currency_code'] = $order_info['currency_code'];
		} else {
			$data['order_id'] = 0;
			$data['store_id'] = 0;
			$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
			
			$data['customer'] = '';
			$data['customer_id'] = '';
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['customer_custom_field'] = array();

			$data['addresses'] = array();

			$data['payment_firstname'] = '';
			$data['payment_lastname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_address_2'] = '';
			$data['payment_city'] = '';
			$data['payment_postcode'] = '';
			$data['payment_country_id'] = '';
			$data['payment_zone_id'] = '';
			$data['payment_custom_field'] = array();
			$data['payment_method'] = '';
			$data['payment_code'] = '';

			$data['shipping_firstname'] = '';
			$data['shipping_lastname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_address_2'] = '';
			$data['shipping_city'] = '';
			$data['shipping_postcode'] = '';
			$data['shipping_country_id'] = '';
			$data['shipping_zone_id'] = '';
			$data['shipping_custom_field'] = array();
			$data['shipping_method'] = '';
			$data['shipping_code'] = '';

			$data['order_products'] = array();
			$data['order_vouchers'] = array();
			$data['order_totals'] = array();

			$data['order_status_id'] = $this->config->get('config_order_status_id');
			$data['comment'] = '';
			$data['affiliate_id'] = '';
			$data['affiliate'] = '';
			$data['currency_code'] = $this->config->get('config_currency');

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';
		}

		// Stores
		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name']
			);
		}

		// Customer Groups
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Custom Fields
		$this->load->model('customer/custom_field');

		$data['custom_fields'] = array();

		$filter_data = array(
			'sort'  => 'cf.sort_order',
			'order' => 'ASC'
		);

		$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][] = array(
				'custom_field_id'    => $custom_field['custom_field_id'],
				'custom_field_value' => $this->model_customer_custom_field->getCustomFieldValues($custom_field['custom_field_id']),
				'name'               => $custom_field['name'],
				'value'              => $custom_field['value'],
				'type'               => $custom_field['type'],
				'location'           => $custom_field['location'],
				'sort_order'         => $custom_field['sort_order']
			);
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$data['voucher_min'] = $this->config->get('config_voucher_min');

		$this->load->model('sale/voucher_theme');

		$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

		// API login
		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
		
		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info && $this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$session = new Session($this->config->get('session_engine'), $this->registry);
			
			$session->start();
					
			$this->model_extension_me_order_manager->deleteApiSessionBySessonId($session->getId());
			
			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
			
			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();
		} else {
			$data['api_token'] = '';
		}

		$this->response->setOutput($this->load->view('extension/me_order_manager/me_order_manager_form', $data));
	}
	
	public function history() {
		$this->load->language('extension/me_order_manager');

		$json = array();
		
		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		}
		
		if (isset($this->request->post['tracking_number'])){
			$tracking_number = $this->request->post['tracking_number'];
		}else{
			$tracking_number = '';
		}
		
		if (isset($this->request->post['carrier_id'])){
			$carrier_id = $this->request->post['carrier_id'];
		}else{
			$carrier_id = '';
		}

		if(!$json){
			// Add keys for missing post vars
			$keys = array(
				'order_status_id',
				'notify',
				'override',
				'comment'
			);

			foreach ($keys as $key) {
				if (!isset($this->request->post[$key])) {
					$this->request->post[$key] = '';
				}
			}

			$this->load->model('extension/me_order_manager');

			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}
			
			$order_manager_setting_tracking = $this->config->get('module_me_order_manager_setting_tracking');

			$order_info = $this->model_extension_me_order_manager->getOrder($order_id);

			if ($order_info) {
				$this->model_extension_me_order_manager->addOrderHistory($order_id, $this->request->post['order_status_id'], $this->request->post['comment'], $this->request->post['notify'], $this->request->post['override']);
				$order_info = $this->model_extension_me_order_manager->getOrder($order_id);
				$json['order_status']  = $order_info['order_status'] ? $order_info['order_status'] : $this->language->get('text_missing');
				$json['date_modified'] = date($this->language->get('date_format_short'), strtotime($order_info['date_modified']));
				if(isset($this->config->get('module_me_order_manager_setting_ostatus')[$this->request->post['order_status_id']])){
					$json['bgcolor'] = $this->config->get('module_me_order_manager_setting_ostatus')[$this->request->post['order_status_id']]['bgcolor'];
					$json['color'] = $this->config->get('module_me_order_manager_setting_ostatus')[$this->request->post['order_status_id']]['color'];
				}
				
				if($order_manager_setting_tracking['orderstatus'] == $this->request->post['order_status_id']){
					$order_manager_setting_carrier = !empty($order_manager_setting_tracking['carrier']) ? $order_manager_setting_tracking['carrier'] : array();
					if(!empty($tracking_number) && ($carrier_id != '')){
						$carrier_name = $order_manager_setting_carrier[$carrier_id]['name'];
						$tracking_url = str_replace('{tracking_number}',$tracking_number,$order_manager_setting_carrier[$carrier_id]['url']);
						$this->model_extension_me_order_manager->addTracking($order_id, $this->request->post['order_status_id'],$tracking_number,$carrier_name,$tracking_url);
						
						if($tracking_url){
							$json['tracking_detail'] = '<a href='.$tracking_url.'>'.$carrier_name.' - '.$tracking_number .'</a>';
						}else{
							$json['tracking_detail'] = $carrier_name.' - '.$tracking_number;
						}
					}
				}
				
				$json['success'] = $this->language->get('text_success');
			} else {
				$json['error'] = $this->language->get('error_not_found');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
	public function createInvoiceNo() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$invoice_no = $this->model_sale_order->createInvoiceNo($order_id);

			if ($invoice_no) {
				$json['invoice_no'] = $invoice_no;
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info && $order_info['customer_id'] && ($order_info['reward'] > 0)) {
				$this->load->model('customer/customer');

				$reward_total = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($order_id);

				if (!$reward_total) {
					$this->model_customer_customer->addReward($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['reward'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_reward_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$this->model_customer_customer->deleteReward($order_id);
			}

			$json['success'] = $this->language->get('text_reward_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$affiliate_total = $this->model_customer_customer->getTotalTransactionsByOrderId($order_id);

				if (!$affiliate_total) {
					$this->model_customer_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_commission_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/me_order_manager')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$this->model_customer_customer->deleteTransactionByOrderId($order_id);
			}

			$json['success'] = $this->language->get('text_commission_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function exportOrder(){
		$this->load->model('sale/order');
		$this->load->language('extension/me_order_manager');
		$this->load->model('extension/me_order_manager');
		
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}
		
		if (isset($this->request->get['selected'])) {
			$selected = implode(',', $this->request->get['selected']);
		} else {
			$selected = array();
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}
		
		if (isset($this->request->get['filter_customeremail'])) {
			$filter_customeremail = $this->request->get['filter_customeremail'];
		} else {
			$filter_customeremail = '';
		}
		
		if (isset($this->request->get['filter_customer_telephone'])) {
			$filter_customer_telephone = $this->request->get['filter_customer_telephone'];
		} else {
			$filter_customer_telephone = '';
		}

		if (isset($this->request->get['filter_order_status'])) {
			$filter_order_status = $this->request->get['filter_order_status'];
		} else {
			$filter_order_status = '';
		}
		
		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = '';
		}
		
		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = '';
		}
		
		if (isset($this->request->get['filter_payment_method'])) {
			$filter_payment_method = $this->request->get['filter_payment_method'];
		} else {
			$filter_payment_method = '';
		}
		
		if (isset($this->request->get['filter_shipping_method'])) {
			$filter_shipping_method = $this->request->get['filter_shipping_method'];
		} else {
			$filter_shipping_method = '';
		}
		
		if (isset($this->request->get['filter_customer_group'])) {
			$filter_customer_group = $this->request->get['filter_customer_group'];
		} else {
			$filter_customer_group = '';
		}
		
		if (isset($this->request->get['filter_store'])) {
			$filter_store = $this->request->get['filter_store'];
		} else {
			$filter_store = null;
		}
		
		if (isset($this->request->get['filter_product'])) {
			$filter_product = $this->request->get['filter_product'];
		} else {
			$filter_product = '';
		}
		
		if (isset($this->request->get['filter_payment_country'])) {
			$filter_payment_country = $this->request->get['filter_payment_country'];
		} else {
			$filter_payment_country = '';
		}
		
		if (isset($this->request->get['filter_shipping_country'])) {
			$filter_shipping_country = $this->request->get['filter_shipping_country'];
		} else {
			$filter_shipping_country = '';
		}
		
		if (isset($this->request->get['filter_currency'])) {
			$filter_currency = $this->request->get['filter_currency'];
		} else {
			$filter_currency = '';
		}
		
		if (isset($this->request->get['filter_shipping_zone'])) {
			$filter_shipping_zone = $this->request->get['filter_shipping_zone'];
		} else {
			$filter_shipping_zone = '';
		}				
		
		if (isset($this->request->get['filter_carrier_name'])) {
			$filter_carrier_name = $this->request->get['filter_carrier_name'];		
		} else {
			$filter_carrier_name = '';
		}		
				
		if (isset($this->request->get['filter_tracking_code'])) {
			$filter_tracking_code = $this->request->get['filter_tracking_code'];
		} else {
			$filter_tracking_code = '';
		}
		
		if (isset($this->request->get['filter_ip'])) {
			$filter_ip = $this->request->get['filter_ip'];
		} else {
			$filter_ip = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit = $this->config->get('config_limit_admin');
		}

		$data['orders'] = array();

		$filter_data = array(
			'selected'        => $selected,
			'filter_order_id'        => $filter_order_id,
			'filter_customer'	     => $filter_customer,
			'filter_customeremail'	     => $filter_customeremail,
			'filter_customer_telephone'	     => $filter_customer_telephone,
			'filter_order_status' => $filter_order_status_id,
			'filter_total'           => $filter_total,
			'filter_date_added'      => $filter_date_added,
			'filter_date_modified'   => $filter_date_modified,
			'filter_payment_method'   => $filter_payment_method,
			'filter_shipping_method'   => $filter_shipping_method,
			'filter_customer_group'   => $filter_customer_group,
			'filter_product'   => $filter_product,
			'filter_store'   => $filter_store,
			'filter_shipping_zone'   => $filter_shipping_zone,
			'filter_carrier_name'   => $filter_carrier_name,	
			'filter_tracking_code'   => $filter_tracking_code,
			'filter_payment_country'   => $filter_payment_country,
			'filter_shipping_country'   => $filter_shipping_country,
			'filter_currency'   => $filter_currency,
			'filter_ip'   => $filter_ip,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $limit,
			'limit'                  => $limit
		);

		$results = $this->model_extension_me_order_manager->getOrders($filter_data);
		
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		
		$i=1;
		
		$objPHPExcel->getActiveSheet()->setTitle("Orders");
		
		//Change Cell Format 
		$objPHPExcel->getActiveSheet()->getStyle('O')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getStyle('P')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getStyle('AB')->getAlignment()->setWrapText(true);
		
		$columns = $this->config->get('module_me_order_manager_setting_export_column');
		$sortcolumns = array();
		
		if($columns){
			foreach($columns as $key => $column){
				if(isset($column['status']) && $column['status']){
					$sortcolumns[] = array(
						'key' => $key,
						'sort_order' => $column['sort_order'],
						'status' => isset($column['status']) ? $column['status'] : ''
					);
				}
			}
			
			function exportsortcolumn( $a, $b ){
				return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
			}
			
			usort($sortcolumns, "exportsortcolumn");
		}
		
		$column = 'A';
		foreach($sortcolumns as $sortcolumn){
			$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $this->language->get('entry_'.$sortcolumn['key']))->getColumnDimension($column)->setAutoSize(true);
			$column++;
		}
		
		//Order History
		$h=1;
		$objhistoryWorkSheet = $objPHPExcel->createSheet(2);
		$objhistoryWorkSheet->setTitle("Order History");
		$objhistoryWorkSheet->setCellValue('A'.$h, 'Order History ID')->getColumnDimension('A')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('B'.$h, 'Order ID')->getColumnDimension('B')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('C'.$h, 'Order Status ID')->getColumnDimension('C')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('D'.$h, 'Order Status')->getColumnDimension('D')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('E'.$h, 'Notify')->getColumnDimension('E')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('F'.$h, 'Comment')->getColumnDimension('F')->setAutoSize(true);
		$objhistoryWorkSheet->setCellValue('G'.$h, 'Date Added')->getColumnDimension('G')->setAutoSize(true);
		
		//Order Voucher
		$v=1;
		$objVoucherWorkSheet = $objPHPExcel->createSheet(3);
		$objVoucherWorkSheet->setTitle("Order Voucher");
		$objVoucherWorkSheet->setCellValue('A'.$v, 'Order Voucher ID')->getColumnDimension('A')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('B'.$v, 'Order ID')->getColumnDimension('B')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('C'.$v, 'Voucher ID')->getColumnDimension('C')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('D'.$v, 'Description')->getColumnDimension('D')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('E'.$v, 'Code')->getColumnDimension('E')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('F'.$v, 'From Name')->getColumnDimension('F')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('G'.$v, 'From Email')->getColumnDimension('G')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('H'.$v, 'To Name')->getColumnDimension('H')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('I'.$v, 'Voucher Theme ID')->getColumnDimension('I')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('J'.$v, 'Message')->getColumnDimension('J')->setAutoSize(true);
		$objVoucherWorkSheet->setCellValue('K'.$v, 'Amount')->getColumnDimension('K')->setAutoSize(true);
			
		foreach($results as $value){
			$result = $this->model_extension_me_order_manager->getOrder($value['order_id']);
			
			$i++;
			$column = 'A';
			foreach($sortcolumns as $sortcolumn){
				if($sortcolumn['key'] == 'store'){
					$sortcolumn['key'] = 'store_id';
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $result[$sortcolumn['key']]);
					$column++;
				}elseif($sortcolumn['key'] == 'export_product' || $sortcolumn['key'] == 'product_option' || $sortcolumn['key'] == 'order_weight'){
					$order_products  = $this->model_sale_order->getOrderProducts($result['order_id']);
					$product_detail = '';
					$product_option = '';
					$p = 0;
					$weight = 0;
					$order_weight = '';
					foreach($order_products as $orderproduct){
						if($p > 0){
							$product_detail .= "\n";
						}
						$product_price = $this->currency->format($orderproduct['price'] + ($this->config->get('config_tax') ? $orderproduct['tax'] : 0), $result['currency_code'], $result['currency_value']);
						$product_total = $this->currency->format($orderproduct['total'] + ($this->config->get('config_tax') ? ($orderproduct['tax'] * $orderproduct['quantity']) : 0), $result['currency_code'], $result['currency_value']);
						$product_tax = $this->currency->format($orderproduct['tax'], $result['currency_code'], $result['currency_value']);
						$product_detail .= $orderproduct['name'] .' > '.$orderproduct['model'] .' :: '.$orderproduct['quantity'] .' :: '.$product_price .' :: '. $product_total .' :: '. $product_tax .' :: '. $orderproduct['reward'];
						$p++;
						$order_product_options = $this->model_sale_order->getOrderOptions($result['order_id'],$orderproduct['order_product_id']);
						$o = 0;
						if($order_product_options){
							if($p > 0){
								$product_option .= "\n";
							}
							$product_option .= $orderproduct['name'] .' > ';
							foreach($order_product_options as $option){
								if($o > 0){
									$product_option .= "\n";
								}
								$product_option .= $option['name'] .' :: '. $option['value'] .' :: '.$option['type'];
								$o++;
							}
						}
						
						$order_weight = $this->weight->format($weight, $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
					}
					if($sortcolumn['key'] == 'export_product'){
						$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $product_detail);
					}
					if($sortcolumn['key'] == 'product_option'){
						$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $product_option);
					}
					if($sortcolumn['key'] == 'order_weight'){
						$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $order_weight);
					}
					$column++;
				}elseif($sortcolumn['key'] == 'custom_field'){
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, json_encode($result['custom_field']));
					$column++;
				}elseif($sortcolumn['key'] == 'payment_address'){
					$payment_address = $result['payment_firstname'] .' '.$result['payment_lastname'];
					if($result['payment_company']){
						$payment_address .= ' ::'.$result['payment_company'];
					}
					if($result['payment_address_1']){
						$payment_address .= ' :: '.$result['payment_address_1'];
					}
					if($result['payment_address_2']){
						$payment_address .= ' :: '.$result['payment_address_2'];
					}
					if($result['payment_postcode']){
						$payment_address .= ' :: '.$result['payment_postcode'];
					}
					
					if($result['payment_city']){
						$payment_address .= ' :: '.$result['payment_city'];
					}
					
					if($result['payment_zone']){
						$payment_address .= ' :: '.$result['payment_zone'];
					}
					
					if($result['payment_country']){
						$payment_address .= ' :: '.$result['payment_country'];
					}
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $payment_address);
					$column++;
				}elseif($sortcolumn['key'] == 'payment_custom_field'){
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, json_encode($result['payment_custom_field']));
					$column++;
				}elseif($sortcolumn['key'] == 'shipping_address'){
					$shipping_address = $result['shipping_firstname'] .' '.$result['shipping_lastname'];
					if($result['shipping_company']){
						$shipping_address .= ' :: '.$result['shipping_company'];
					}
					if($result['shipping_address_1']){
						$shipping_address .= ' :: '.$result['shipping_address_1'];
					}
					if($result['payment_address_2']){
						$shipping_address .= ' :: '.$result['shipping_address_2'];
					}
					if($result['payment_postcode']){
						$shipping_address .= ' :: '.$result['shipping_postcode'];
					}
					
					if($result['shipping_city']){
						$shipping_address .= ' :: '.$result['shipping_city'];
					}
					
					if($result['shipping_zone']){
						$shipping_address .= ' :: '.$result['shipping_zone'];
					}
					
					if($result['shipping_country']){
						$shipping_address .= ' :: '.$result['shipping_country'];
					}
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $shipping_address);
					$column++;
				}elseif($sortcolumn['key'] == 'shipping_custom_field'){
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, json_encode($result['shipping_custom_field']));
					$column++;
				}elseif($sortcolumn['key'] == 'total'){
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']));
					$column++;
				}elseif($sortcolumn['key'] == 'total_details'){
					$order_totals  = $this->model_sale_order->getOrderTotals($result['order_id']);
					$ordertotal = '';
					$t = 0;
					foreach($order_totals as $total){
						if($t > 0){
							$ordertotal .= "\n";
						}
						$ordertotal .= $total['title'] .' - '. $this->currency->format($total['value'], $result['currency_code'], $result['currency_value']);
						$t++;
					}
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $ordertotal);
					$column++;
				}elseif($sortcolumn['key'] == 'affiliate'){
					$affiliate = $result['affiliate_firstname'].' '.$result['affiliate_lastname'];
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $affiliate);
					$column++;
				}elseif($sortcolumn['key'] == 'tracking_code' || $sortcolumn['key'] == 'courier_name'){
					$ordertracking = $this->model_extension_me_order_manager->getTracking($result['order_id']);
					if($ordertracking){
						$carrier_name = $ordertracking['carrier_name'];
						$tracking_code = $ordertracking['tracking_code'];
					}else{
						$carrier_name = '';
						$tracking_code = '';
					}
					if($sortcolumn['key'] == 'tracking_code'){
						$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $tracking_code);
					}
					if($sortcolumn['key'] == 'courier_name'){
						$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $carrier_name);
					}
					$column++;
				}elseif($sortcolumn['key'] == 'customer_group'){
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $value['customer_group']);
					$column++;
				}else{
					$objPHPExcel->getActiveSheet()->setCellValue($column.$i, $result[$sortcolumn['key']]);
					$column++;
				}
			}
		
			//history
			$order_historys  = $this->model_extension_me_order_manager->getOrderexportHistories($result['order_id']);
			foreach($order_historys as $history){
				$h++;
				$objhistoryWorkSheet->setCellValue('A'.$h, $history['order_history_id']);
				$objhistoryWorkSheet->setCellValue('B'.$h, $history['order_id']);
				$objhistoryWorkSheet->setCellValue('C'.$h, $history['order_status_id']);
				$objhistoryWorkSheet->setCellValue('D'.$h, $history['name']);
				$objhistoryWorkSheet->setCellValue('E'.$h, $history['notify']);
				$objhistoryWorkSheet->setCellValue('F'.$h, $history['comment']);
				$objhistoryWorkSheet->setCellValue('G'.$h, $history['date_added']);
			}
		
			//Voucher
			$order_vouchers  = $this->model_sale_order->getOrderVouchers($result['order_id']);
			foreach($order_vouchers as $voucher){
				$v++;
				$objVoucherWorkSheet->setCellValue('A'.$v, $voucher['order_voucher_id']);
				$objVoucherWorkSheet->setCellValue('B'.$v, $voucher['order_id']);
				$objVoucherWorkSheet->setCellValue('C'.$v, $voucher['voucher_id']);
				$objVoucherWorkSheet->setCellValue('D'.$v, $voucher['description']);
				$objVoucherWorkSheet->setCellValue('E'.$v, $voucher['code']);
				$objVoucherWorkSheet->setCellValue('F'.$v, $voucher['from_name']);
				$objVoucherWorkSheet->setCellValue('G'.$v, $voucher['from_email']);
				$objVoucherWorkSheet->setCellValue('H'.$v, $voucher['to_name']);
				$objVoucherWorkSheet->setCellValue('I'.$v, $voucher['to_email']);
				$objVoucherWorkSheet->setCellValue('J'.$v, $voucher['voucher_theme_id']);
				$objVoucherWorkSheet->setCellValue('K'.$v, $voucher['message']);
				$objVoucherWorkSheet->setCellValue('L'.$v, $voucher['amount']);
			}
		}
		
		$format = 'xls';
		if(!empty($this->config->get('module_me_order_manager_setting')['exportformat'])){
			$format = $this->config->get('module_me_order_manager_setting')['exportformat'];
		}
		
		if($format == 'csv'){
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		}else{
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		}
		
		$filename = 'orderexport-'.time().'.'.$format;
		
		header('Content-Type: application/vnd.ms-excel'); 
		header('Content-Disposition: attachment;filename='.$filename); 
		header('Cache-Control: max-age=0'); 
		$objWriter->save('php://output'); 
		
		exit(); 
	}
}
