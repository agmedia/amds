<?php
class ModelExtensionTotalCoupon extends Model {
	public function getCoupon($code) {
		$status = true;

		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) AND status = '1'");

		if ($coupon_query->num_rows) {
			if ($coupon_query->row['total'] > $this->cart->getSubTotal()) {
				$status = false;
			}

			$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);

			if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
				$status = false;
			}

			if ($coupon_query->row['logged'] && !$this->customer->getId()) {
				$status = false;
			}

			if ($this->customer->getId()) {
				$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $this->customer->getId());
				
				if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
					$status = false;
				}
			}

			// Products
			$coupon_product_data = array();

			$coupon_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_product_query->rows as $product) {
				$coupon_product_data[] = $product['product_id'];
			}

			// Categories
			$coupon_category_data = array();

			$coupon_category_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cc.category_id = cp.path_id) WHERE cc.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");

			foreach ($coupon_category_query->rows as $category) {
				$coupon_category_data[] = $category['category_id'];
			}

			$product_data = array();

			if ($coupon_product_data || $coupon_category_data) {
				foreach ($this->cart->getProducts() as $product) {
					if (in_array($product['product_id'], $coupon_product_data)) {
						$product_data[] = $product['product_id'];

						continue;
					}

					foreach ($coupon_category_data as $category_id) {
						$coupon_category_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int)$product['product_id'] . "' AND category_id = '" . (int)$category_id . "'");

						if ($coupon_category_query->row['total']) {
							$product_data[] = $product['product_id'];

							continue;
						}
					}
				}

				if (!$product_data) {
					$status = false;
				}
			}
		} else {
			$status = false;
		}

		if ($status) {
			return array(
				'coupon_id'     => $coupon_query->row['coupon_id'],
				'code'          => $coupon_query->row['code'],
				'name'          => $coupon_query->row['name'],
				'type'          => $coupon_query->row['type'],
				'discount'      => $coupon_query->row['discount'],
				'shipping'      => $coupon_query->row['shipping'],
				'total'         => $coupon_query->row['total'],
				'product'       => $product_data,
				'date_start'    => $coupon_query->row['date_start'],
				'date_end'      => $coupon_query->row['date_end'],
				'uses_total'    => $coupon_query->row['uses_total'],
				'uses_customer' => $coupon_query->row['uses_customer'],
				'status'        => $coupon_query->row['status'],
				'date_added'    => $coupon_query->row['date_added']
			);
		}
	}

	public function getTotal($total) {
		if (isset($this->session->data['coupon'])) {
			$this->load->language('extension/total/coupon', 'coupon');
			$this->load->model('catalog/product');

			$coupon_info = $this->getCoupon($this->session->data['coupon']);

			if ($coupon_info) {
				$products = $this->cart->getProducts();
				$details = array();
				$eligible = array();
				$sub_total = 0;

				foreach ($products as $index => $product) {
					if (!isset($details[$product['product_id']])) {
						$details[$product['product_id']] = $this->model_catalog_product->getProduct($product['product_id']);
					}

					// Preserve the shop's rule: coupons do not apply to active sale items.
					$eligible[$index] = (!$coupon_info['product'] || in_array($product['product_id'], $coupon_info['product']))
						&& empty($details[$product['product_id']]['special']);

					if ($eligible[$index]) {
						$sub_total += $product['total'];
					}
				}

				$fixed_discount = min(max(0, (float)$coupon_info['discount']), $sub_total);
				$percentage = min(100, max(0, (float)$coupon_info['discount']));
				$allocation = array(
					'version' => 1,
					'code' => $coupon_info['code'],
					'type' => $coupon_info['type'],
					'products' => array(),
					'shipping_discount' => 0,
					'shipping_price' => isset($this->session->data['shipping_method']) ? (float)$this->session->data['shipping_method']['cost'] : 0,
					'shipping_tax' => isset($this->session->data['shipping_method']) ? $this->tax->getTax($this->session->data['shipping_method']['cost'], !empty($this->session->data['shipping_method']['tax_class_id']) ? $this->session->data['shipping_method']['tax_class_id'] : 0) : 0
				);
				$discount_total = 0;

				foreach ($products as $index => $product) {
					$status = $eligible[$index];
					$allocation['products'][$index] = array(
						'key' => \Agmedia\LuceedOpencartWrapper\Helpers\CouponAllocation::productKey($product),
						'product_id' => (int)$product['product_id'],
						'quantity' => (int)$product['quantity'],
						'price' => (float)$product['price'],
						'base_price' => max((float)$product['price'], isset($product['base_price']) ? (float)$product['base_price'] : (float)$product['price']),
						'price_tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
						'base_price_tax' => $this->tax->getTax(isset($product['base_price']) ? $product['base_price'] : $product['price'], $product['tax_class_id']),
						'discount' => 0,
						'discount_tax' => 0
					);

					if ($status) {
						if ($coupon_info['type'] == 'F' && $sub_total > 0) {
							$allocation['products'][$index]['discount'] = $fixed_discount * ($product['total'] / $sub_total);
						} elseif ($coupon_info['type'] == 'P') {
							$allocation['products'][$index]['discount'] = $product['total'] / 100 * $percentage;
							$allocation['products'][$index]['coupon_percentage'] = $percentage;
						}
					}

					$discount_total += $allocation['products'][$index]['discount'];
				}

				if ($coupon_info['shipping'] && isset($this->session->data['shipping_method'])) {
					$allocation['shipping_discount'] = max(0, (float)$this->session->data['shipping_method']['cost']);
					$discount_total += $allocation['shipping_discount'];
				}

				// Apply the same cap to the allocation and its tax reductions.
				$scale = $discount_total > 0 ? min(1, max(0, $total['total']) / $discount_total) : 0;
				$discount_total = round($discount_total * $scale, 4);

				if ($discount_total > 0) {
					foreach ($products as $index => $product) {
						$allocation['products'][$index]['discount'] *= $scale;
						if ($product['tax_class_id']) {
							$tax_rates = $this->tax->getRates($allocation['products'][$index]['discount'], $product['tax_class_id']);
							foreach ($tax_rates as $tax_rate) {
								if ($tax_rate['type'] == 'P') {
									$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
									$allocation['products'][$index]['discount_tax'] += $tax_rate['amount'];
								}
							}
						}
					}

					$allocation['shipping_discount'] *= $scale;
					$allocation['shipping_discount_tax'] = 0;
					if ($allocation['shipping_discount'] > 0 && !empty($this->session->data['shipping_method']['tax_class_id'])) {
						$tax_rates = $this->tax->getRates($allocation['shipping_discount'], $this->session->data['shipping_method']['tax_class_id']);
						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 'P') {
								$total['taxes'][$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
								$allocation['shipping_discount_tax'] += $tax_rate['amount'];
							}
						}
					}

					$allocation['amount'] = $discount_total;
					$allocation = \Agmedia\LuceedOpencartWrapper\Helpers\CouponAllocation::finalize($allocation);
					$gross_allocation = \Agmedia\LuceedOpencartWrapper\Helpers\CouponAllocation::taxInclusive($allocation);
					foreach ($gross_allocation['products'] as $index => $gross_product) {
						$allocation['products'][$index]['gross_net_total'] = $gross_product['net_total'];
						$allocation['products'][$index]['gross_discount_amount'] = $gross_product['discount_amount'];
					}

					$total['totals'][] = array(
						'code' => 'coupon',
						'title' => sprintf($this->language->get('coupon')->get('text_coupon'), $this->session->data['coupon']),
						'value' => -$discount_total,
						'sort_order' => $this->config->get('total_coupon_sort_order'),
						'coupon_allocation' => $allocation
					);

					$total['total'] -= $discount_total;
				}
			}
		}
	}

	public function confirm($order_info, $order_total) {
		$code = '';

		$start = strpos($order_total['title'], '(') + 1;
		$end = strrpos($order_total['title'], ')');

		if ($start && $end) {
			$code = substr($order_total['title'], $start, $end - $start);
		}

		if ($code) {
			$status = true;
			
			$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND status = '1'");

			if ($coupon_query->num_rows) {
				$coupon_total = $this->getTotalCouponHistoriesByCoupon($code);
	
				if ($coupon_query->row['uses_total'] > 0 && ($coupon_total >= $coupon_query->row['uses_total'])) {
					$status = false;
				}
				
				if ($order_info['customer_id']) {
					$customer_total = $this->getTotalCouponHistoriesByCustomerId($code, $order_info['customer_id']);
					
					if ($coupon_query->row['uses_customer'] > 0 && ($customer_total >= $coupon_query->row['uses_customer'])) {
						$status = false;
					}
				}
			} else {
				$status = false;	
			}

			if ($status) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon_history` SET coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "', order_id = '" . (int)$order_info['order_id'] . "', customer_id = '" . (int)$order_info['customer_id'] . "', amount = '" . (float)$order_total['value'] . "', date_added = NOW()");
			} else {
				return $this->config->get('config_fraud_status_id');
			}
		}
	}

	public function unconfirm($order_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "coupon_history` WHERE order_id = '" . (int)$order_id . "'");
	}
	
	public function getTotalCouponHistoriesByCoupon($coupon) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "'");	
		
		return $query->row['total'];
	}
	
	public function getTotalCouponHistoriesByCustomerId($coupon, $customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch LEFT JOIN `" . DB_PREFIX . "coupon` c ON (ch.coupon_id = c.coupon_id) WHERE c.code = '" . $this->db->escape($coupon) . "' AND ch.customer_id = '" . (int)$customer_id . "'");
		
		return $query->row['total'];
	}
}
