<?php
class ControllerExtensionPaymentCod extends Controller {
	public function index() {
		return $this->load->view('extension/payment/cod');
	}

	public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'cod') {
			$this->load->model('checkout/order');
			if (empty($this->session->data['order_id']) || !\Cart\PaymentStock::isReady(
				$this->cart, $this->model_checkout_order, $this->session->data['order_id'], !empty($this->session->data['vouchers']))) {
				$this->load->language('checkout/cart');
				$this->session->data['error'] = $this->language->get('error_stock');
				$json['redirect'] = $this->url->link('checkout/cart', '', true);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			}

				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cod_order_status_id'));
			
				$json['redirect'] = $this->url->link('checkout/success', 'order_id=' . (int)$this->session->data['order_id'], true);
			}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
}
