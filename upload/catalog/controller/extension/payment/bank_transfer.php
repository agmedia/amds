<?php
class ControllerExtensionPaymentBankTransfer extends Controller {
	public function index() {
		$this->load->language('extension/payment/bank_transfer');

		$data['bank'] = nl2br($this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')));

        $data['bank'] = str_replace('%order_id%', $this->session->data['order_id'], $data['bank']);

		return $this->load->view('extension/payment/bank_transfer', $data);
	}

	public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'bank_transfer') {
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

			$this->load->language('extension/payment/bank_transfer');

			$this->load->model('checkout/order');

			$comment  = $this->language->get('text_instruction') . "\n\n";
			$comment .= $this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')) . "\n\n";
			$comment .= $this->language->get('text_payment');

            $comment = str_replace('%order_id%', $this->session->data['order_id'], $comment);


				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_bank_transfer_order_status_id'), $comment, true);
			
				$json['redirect'] = $this->url->link('checkout/success', 'order_id=' . (int)$this->session->data['order_id'], true);
			}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
}
