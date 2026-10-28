<?php
namespace Cart;

/** Verify that a saved checkout still represents the currently available cart. */
class PaymentStock {
    public static function isReady($cart, $orderModel, $orderId, $hasVouchers = false) {
        $products = $cart->getProducts();
        if ((!$products && !$hasVouchers) || !$cart->hasStock()) {
            return false;
        }

        $ordered = $orderModel->getOrderProducts((int)$orderId);
        foreach ($ordered as &$product) {
            $product['option'] = $orderModel->getOrderOptions((int)$orderId, $product['order_product_id']);
        }
        unset($product);

        return self::quantities($products) === self::quantities($ordered);
    }

    private static function quantities(array $products) {
        $result = array();
        foreach ($products as $product) {
            $options = array();
            foreach ($product['option'] as $option) {
                $options[] = array(
                    (int)$option['product_option_id'],
                    (int)$option['product_option_value_id'],
                    (string)$option['value']
                );
            }
            sort($options);
            $key = json_encode(array((int)$product['product_id'], $options));
            if (!isset($result[$key])) {
                $result[$key] = 0;
            }
            $result[$key] += (int)$product['quantity'];
        }
        ksort($result);

        return $result;
    }
}
