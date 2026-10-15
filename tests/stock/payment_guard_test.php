<?php
// Offline controller tests. No database, order, email or gateway is contacted.
require __DIR__ . '/../../upload/system/engine/registry.php';
require __DIR__ . '/../../upload/system/engine/controller.php';
require __DIR__ . '/../../upload/system/library/cart/paymentstock.php';
require __DIR__ . '/../../upload/catalog/controller/extension/payment/wspay.php';
require __DIR__ . '/../../upload/catalog/controller/extension/payment/cod.php';
require __DIR__ . '/../../upload/catalog/controller/extension/payment/bank_transfer.php';

class PaymentTestCart {
    public $stock = true;
    public $products;
    public function getProducts() { return $this->products; }
    public function hasStock() { return $this->stock; }
}
class PaymentTestOrder {
    public $products;
    public $options;
    public $history = 0;
    public $info;
    public function getOrder($id) { return $id == 99 ? $this->info : false; }
    public function getOrderProducts($id) { return $this->products; }
    public function getOrderOptions($id, $productId) { return $this->options; }
    public function addOrderHistory() { $this->history++; }
}
class PaymentTestLanguage {
    public function get($key) { return $key === 'error_stock' ? 'Unavailable stock' : $key; }
}
class PaymentTestConfig {
    public function get($key) {
        $values = array('payment_wspay_merchant' => 'fixture-shop', 'payment_wspay_password' => 'fixture-secret');
        return isset($values[$key]) ? $values[$key] : '';
    }
}
class PaymentTestUrl {
    public function link($route, $args = '', $secure = false) { return 'https://fixture.invalid/index.php?route=' . $route; }
}
class PaymentTestResponse {
    public $output = '';
    public $redirect = '';
    public function addHeader($header) {}
    public function setOutput($output) { $this->output = $output; }
    public function redirect($url) { $this->redirect = $url; }
}
class PaymentTestLoader {
    public $views = array();
    public function language($route) {}
    public function model($route) {}
    public function view($route, $data) {
        $this->views[] = array($route, $data);
        return '<form name="' . ($route === 'extension/payment/wspay' ? 'pay' : 'local') . '">'
            . htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8') . '</form>';
    }
}

$assertions = 0;
function paymentAssert($condition, $label) {
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($label); }
}
function paymentFixture($code = 'wspay') {
    $option = array('product_option_id' => 10202, 'product_option_value_id' => 46014, 'value' => 'L/V40');
    $cart = new PaymentTestCart();
    $cart->products = array(array('product_id' => 10201, 'quantity' => 1, 'option' => array($option)));
    $order = new PaymentTestOrder();
    $order->products = array(array('product_id' => 10201, 'order_product_id' => 1, 'quantity' => 1));
    $order->options = array($option);
    $order->info = array('order_id' => 99, 'order_status_id' => 0, 'payment_code' => $code,
        'total' => 13.20, 'currency_code' => 'EUR', 'payment_firstname' => 'Test', 'payment_lastname' => 'Fixture',
        'payment_address_1' => '', 'payment_city' => '', 'payment_postcode' => '', 'payment_iso_code_2' => 'HR',
        'telephone' => '', 'email' => '');
    $registry = new Registry();
    $registry->set('cart', $cart);
    $registry->set('model_checkout_order', $order);
    $registry->set('session', (object)array('data' => array('order_id' => 99, 'payment_method' => array('code' => $code))));
    $registry->set('request', (object)array('post' => array(), 'server' => array('REQUEST_METHOD' => 'POST')));
    $registry->set('config', new PaymentTestConfig());
    $registry->set('language', new PaymentTestLanguage());
    $registry->set('url', new PaymentTestUrl());
    $registry->set('response', new PaymentTestResponse());
    $registry->set('load', new PaymentTestLoader());
    return $registry;
}
function paymentValidPost($r) {
    $r->get('request')->post = array('order_id' => 99, 'payment_token' => $r->get('session')->data['wspay_payment_token']);
}

$r = paymentFixture();
$wspay = new ControllerExtensionPaymentWSPay($r);
$wspay->index();
$view = $r->get('load')->views[0];
paymentAssert($view[0] === 'extension/payment/wspay_start', 'Initial form must submit locally');
paymentAssert(strpos($view[1]['action'], '/start') !== false, 'Local submit route');
paymentAssert(!isset($view[1]['Signature']) && !isset($view[1]['TotalAmount']), 'Gateway fields must not be issued before submit');
paymentValidPost($r);
$wspay->start();
$gateway = $r->get('load')->views[1];
paymentAssert($gateway[0] === 'extension/payment/wspay', 'Available unchanged order reaches gateway form');
paymentAssert($gateway[1]['TotalAmount'] === '13,20' && strlen($gateway[1]['Signature']) === 128, 'Gateway amount and signature retained');
paymentAssert(strpos($r->get('response')->output, 'document.forms.pay.submit') !== false, 'Validated gateway form auto-submits');
paymentAssert($r->get('model_checkout_order')->history === 0, 'Payment initiation never finalizes order');

foreach (array('zero-stock', 'changed-quantity', 'changed-size', 'invalid-token', 'wrong-order', 'wrong-method', 'changed-payment', 'paid-order') as $scenario) {
    $r = paymentFixture();
    $wspay = new ControllerExtensionPaymentWSPay($r);
    $wspay->index();
    paymentValidPost($r);
    if ($scenario === 'zero-stock') $r->get('cart')->stock = false;
    if ($scenario === 'changed-quantity') $r->get('cart')->products[0]['quantity'] = 2;
    if ($scenario === 'changed-size') $r->get('cart')->products[0]['option'][0]['product_option_value_id'] = 46015;
    if ($scenario === 'invalid-token') $r->get('request')->post['payment_token'] = 'wrong';
    if ($scenario === 'wrong-order') $r->get('request')->post['order_id'] = 98;
    if ($scenario === 'wrong-method') $r->get('request')->server['REQUEST_METHOD'] = 'GET';
    if ($scenario === 'changed-payment') $r->get('session')->data['payment_method']['code'] = 'cod';
    if ($scenario === 'paid-order') $r->get('model_checkout_order')->info['order_status_id'] = 1;
    $wspay->start();
    paymentAssert(count($r->get('load')->views) === 1, $scenario . ' must not create gateway form');
    paymentAssert($r->get('model_checkout_order')->history === 0, $scenario . ' must not finalize order');
}

foreach (array('cod' => 'ControllerExtensionPaymentCod', 'bank_transfer' => 'ControllerExtensionPaymentBankTransfer') as $code => $class) {
    foreach (array('available', 'zero-stock', 'changed-quantity', 'changed-size', 'empty-cart') as $scenario) {
        $r = paymentFixture($code);
        if ($scenario === 'zero-stock') $r->get('cart')->stock = false;
        if ($scenario === 'changed-quantity') $r->get('cart')->products[0]['quantity'] = 2;
        if ($scenario === 'changed-size') $r->get('cart')->products[0]['option'][0]['value'] = 'M/V38';
        if ($scenario === 'empty-cart') $r->get('cart')->products = array();
        $controller = new $class($r);
        $controller->confirm();
        paymentAssert($r->get('model_checkout_order')->history === ($scenario === 'available' ? 1 : 0), $code . ' ' . $scenario);
        $json = json_decode($r->get('response')->output, true);
        paymentAssert(strpos($json['redirect'], $scenario === 'available' ? 'checkout/success' : 'checkout/cart') !== false, $code . ' ' . $scenario . ' redirect');
    }
}

echo 'Payment guards passed: ' . $assertions . " assertions.\n";
