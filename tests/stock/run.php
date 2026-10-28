<?php
/** Offline inventory regressions: php tests/stock/run.php */
if (PHP_SAPI !== 'cli') { exit("Run from the command line.\n"); }
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});

define('DB_PREFIX', 'oc_');
define('VERSION', '3.0.3.8');
$root = dirname(__DIR__, 2);
require $root . '/upload/system/engine/controller.php';
require $root . '/upload/system/engine/model.php';
require $root . '/upload/system/library/cart/optionstock.php';
require $root . '/upload/system/library/cart/cart.php';
require $root . '/upload/catalog/model/catalog/product.php';
require $root . '/upload/catalog/controller/checkout/cart.php';
require $root . '/upload/catalog/controller/extension/basel/basel_features.php';
require $root . '/upload/catalog/controller/checkout/confirm.php';
require $root . '/upload/catalog/controller/extension/quickcheckout/confirm.php';

class StockRegistry {
    private $items = array();
    public function get($key) { return $this->items[$key] ?? null; }
    public function set($key, $value) { $this->items[$key] = $value; }
}
class StockConfig { public function get($key) { return $key === 'quickcheckout_payment_target' ? '' : 0; } }
class StockCustomer {
    private $id;
    public function __construct($id) { $this->id = $id; }
    public function getId() { return $this->id; }
    public function isLogged() { return (bool)$this->id; }
}
class StockSession {
    public $data = array('currency' => 'EUR', 'shipping_address' => array(), 'shipping_method' => array('code' => 'weight.weight_1'), 'payment_address' => array(), 'payment_method' => array('code' => 'cod'));
    public function getId() { return 'stock-fixture'; }
}
class StockResponse {
    public $output;
    public function addHeader($header) {}
    public function setOutput($output) { $this->output = $output; }
}
class StockLanguage {
    public function get($key) {
        if ($key === 'text_success') { return 'Added %s %s %s'; }
        if ($key === 'error_required') { return '%s required'; }
        return $key;
    }
}
class StockLoader {
    public function language($route) {}
    public function model($route) {}
    public function view($route, $data) { return json_encode($data); }
    public function controller($route) { throw new RuntimeException('Checkout reached payment despite stock failure'); }
}
class StockUrl { public function link($route, $args = '', $secure = false) { return $route . ($args ? '?' . $args : ''); } }
class StockTax {
    public function calculate($price, $class, $tax) { return $price; }
    public function getRates($price, $class) { return array(); }
}
class StockCurrency { public function format($price, $code) { return (string)$price; } }
class StockImage { public function resize($image, $width, $height) { return $image; } }
class StockExtensions { public function getExtensions($type) { return array(); } }

class StockDatabase {
    public $rows = array(), $options = array(), $values = array(), $products = array();
    public function escape($value) { return addslashes($value); }
    private function id($sql, $column) {
        preg_match('/' . preg_quote($column, '/') . " = '([0-9]+)'/", $sql, $matches);
        return isset($matches[1]) ? (int)$matches[1] : null;
    }
    public function query($sql) {
        $rows = array();
        if (strpos($sql, 'DELETE ') === 0 || strpos($sql, 'UPDATE ') === 0) {
            // Constructor housekeeping is intentionally isolated from any database.
        } elseif (strpos($sql, 'SELECT * FROM oc_cart ') === 0) {
            $customer = $this->id($sql, 'customer_id');
            $rows = array_values(array_filter($this->rows, function ($row) use ($customer) { return $row['customer_id'] === $customer; }));
        } elseif (strpos($sql, 'SELECT * FROM oc_product_to_store ') === 0) {
            $id = $this->id($sql, 'p2s.product_id');
            $rows = isset($this->products[$id]) ? array($this->products[$id]) : array();
        } elseif (strpos($sql, 'SELECT po.product_option_id FROM ') === 0) {
            foreach ($this->options as $option) {
                if ($option['product_id'] === $this->id($sql, 'po.product_id') && $option['required']) { $rows[] = $option; }
            }
        } elseif (strpos($sql, 'SELECT po.product_option_id, po.option_id') === 0) {
            foreach ($this->options as $option) {
                if ($option['product_option_id'] === $this->id($sql, 'po.product_option_id') && $option['product_id'] === $this->id($sql, 'po.product_id')) { $rows[] = $option; }
            }
        } elseif (strpos($sql, 'SELECT pov.option_value_id') === 0) {
            foreach ($this->values as $value) {
                if ($value['product_option_value_id'] === $this->id($sql, 'pov.product_option_value_id') && $value['product_option_id'] === $this->id($sql, 'pov.product_option_id') && $value['product_id'] === $this->id($sql, 'pov.product_id')) { $rows[] = $value; }
            }
        } elseif (strpos($sql, 'SELECT * FROM oc_product_option po ') === 0) {
            foreach ($this->options as $option) { if ($option['product_id'] === $this->id($sql, 'po.product_id')) { $rows[] = $option; } }
        } elseif (strpos($sql, 'SELECT * FROM oc_product_option_value pov ') === 0) {
            foreach ($this->values as $value) {
                if ($value['product_id'] === $this->id($sql, 'pov.product_id') && $value['product_option_id'] === $this->id($sql, 'pov.product_option_id')) { $rows[] = $value; }
            }
        } elseif (strpos($sql, 'SELECT price FROM oc_product_discount ') === 0 || strpos($sql, 'SELECT price FROM oc_product_special ') === 0 || strpos($sql, 'SELECT points FROM oc_product_reward ') === 0 || strpos($sql, 'SELECT * FROM oc_product_to_download ') === 0 || strpos($sql, 'SELECT * FROM oc_recurring ') === 0) {
        } else { throw new RuntimeException('Unexpected fixture query: ' . $sql); }
        return (object)array('rows' => $rows, 'row' => $rows ? $rows[0] : array(), 'num_rows' => count($rows));
    }
}
class StockCatalog extends ModelCatalogProduct {
    public function getProduct($id) { return $this->db->products[$id] ?? false; }
    public function getProfiles($id) { return array(); }
}
class StockCart extends Cart\Cart {
    public $added = array();
    public function add($id, $quantity = 1, $option = array(), $recurring = 0) { $this->added[] = array($id, $quantity, $option, $recurring); }
}

function stockFixture($customer = 0) {
    $registry = new StockRegistry();
    $db = new StockDatabase();
    $db->products[1] = array('product_id' => 1, 'name' => 'Example 131303', 'model' => '131303', 'quantity' => 31, 'subtract' => 1, 'price' => 40, 'minimum' => 1, 'shipping' => 1, 'image' => '', 'points' => 0, 'tax_class_id' => 0, 'weight' => 0, 'weight_class_id' => 0, 'length' => 0, 'width' => 0, 'height' => 0, 'length_class_id' => 0);
    $db->options[] = array('product_id' => 1, 'product_option_id' => 10, 'option_id' => 13, 'required' => 1, 'name' => 'Veličina', 'type' => 'radio', 'value' => '');
    $db->values[] = array('product_id' => 1, 'product_option_id' => 10, 'product_option_value_id' => 46015, 'option_value_id' => 38, 'name' => 'V38', 'sku' => 'article-uid-v38', 'sifra' => '131303-V38', 'quantity' => 0, 'subtract' => 0, 'image' => '', 'price' => 0, 'price_prefix' => '+', 'points' => 0, 'points_prefix' => '+', 'weight' => 0, 'weight_prefix' => '+');
    foreach (array('db' => $db, 'config' => new StockConfig(), 'customer' => new StockCustomer($customer), 'session' => new StockSession(), 'response' => new StockResponse(), 'language' => new StockLanguage(), 'load' => new StockLoader(), 'url' => new StockUrl(), 'tax' => new StockTax(), 'currency' => new StockCurrency(), 'model_tool_image' => new StockImage(), 'model_setting_extension' => new StockExtensions(), 'weight' => new stdClass()) as $key => $value) { $registry->set($key, $value); }
    $registry->set('model_catalog_product', new StockCatalog($registry));
    $registry->set('cart', new StockCart($registry));
    return $registry;
}
function stockRow($customer, $quantity = 1, $selection = array(10 => 46015), $recurring = 0) {
    return array('cart_id' => 1, 'product_id' => 1, 'customer_id' => $customer, 'quantity' => $quantity, 'recurring_id' => $recurring, 'option' => is_string($selection) ? $selection : json_encode($selection));
}
$assertions = 0;
function stockAssert($condition, $message) {
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($message); }
}

foreach (array(0, 7) as $customer) {
    $registry = stockFixture($customer);
    $db = $registry->get('db');
    $cart = $registry->get('cart');
    $catalog = $registry->get('model_catalog_product');
    $db->rows = array(stockRow($customer));
    stockAssert(!$cart->hasStock(), 'Luceed subtract=0 sold-out size must fail');
    stockAssert($catalog->getProductOptions(1)[0]['product_option_value'] === array(), 'Sold-out inventory size hidden centrally');
    stockAssert(count($catalog->getProductOptions(1, false)[0]['product_option_value']) === 1, 'Validation can inspect sold-out values');
    stockAssert($db->values[0]['subtract'] === 0, 'Luceed decrement semantics preserved');
    $db->values[0]['quantity'] = 1;
    stockAssert($cart->hasStock(), 'Available imported size allowed');
    $db->rows[] = stockRow($customer, 1, array(10 => 46015), 9);
    stockAssert(!$cart->hasStock(), 'Same size aggregates across recurring cart lines');
    $db->values[0]['quantity'] = 2;
    stockAssert($cart->hasStock(), 'Exact aggregate size quantity allowed');
    $db->products[1]['quantity'] = 1;
    stockAssert(!$cart->hasStock(), 'Parent product aggregate quantity enforced');
    $db->products[1]['quantity'] = 31;
    foreach (array(array(), array(10 => 99999), array(999 => 46015), array(10 => array(46015)), '{broken') as $selection) {
        $db->rows = array(stockRow($customer, 1, $selection));
        stockAssert(!$cart->hasStock(), 'Stale/missing/malformed selection fails: ' . json_encode($selection));
    }
    $db->rows = array(stockRow($customer));
    $saved = $db->values;
    $db->values = array();
    stockAssert(!$cart->hasStock(), 'Deleted selected value fails');
    $db->rows = array(stockRow($customer, 1, array()));
    stockAssert(!$cart->hasStock(), 'Missing required size fails even after all values are deleted');
    $db->values = $saved;
    $db->options[0]['type'] = 'checkbox';
    $db->rows = array(stockRow($customer, 1, array(10 => array(46015, 46015))));
    stockAssert(!$cart->hasStock(), 'Duplicate checkbox IDs fail');
    $db->rows = array(stockRow($customer, 1, array(10 => array(46015))));
    stockAssert($cart->hasStock(), 'Valid checkbox stock allowed');
    $db->options[0]['type'] = 'radio';
    $db->values[0]['quantity'] = 0;
    $db->values[0]['sku'] = '';
    $db->rows = array(stockRow($customer));
    stockAssert($cart->hasStock(), 'Generic non-stock size remains unrestricted');
    stockAssert(count($catalog->getProductOptions(1)[0]['product_option_value']) === 1, 'Generic non-stock size remains visible');
    $db->values[0]['subtract'] = 1;
    stockAssert(!$cart->hasStock(), 'Generic subtract=1 option still tracks quantity');

    // Execute the real checkout confirmation controllers against the real Cart.
    foreach (array('ControllerCheckoutConfirm', 'ControllerExtensionQuickCheckoutConfirm') as $class) {
        $registry = stockFixture($customer);
        $registry->get('db')->rows = array(stockRow($customer));
        (new $class($registry))->index();
        $output = json_decode($registry->get('response')->output, true);
        stockAssert(($output['redirect'] ?? '') === 'checkout/cart', $class . ' must reject guest/logged stale inventory');
    }

    foreach (array(array('ControllerCheckoutCart', 'add'), array('ControllerExtensionBaselBaselFeatures', 'add_to_cart')) as $route) {
        foreach (array('sold_out', 'available', 'aggregate', 'unknown_value', 'wrong_option', 'missing', 'malformed', 'generic', 'parent_aggregate', 'checkbox', 'checkbox_duplicate') as $scenario) {
            $registry = stockFixture($customer);
            $db = $registry->get('db');
            $selection = array(10 => 46015);
            $allowed = in_array($scenario, array('available', 'generic', 'checkbox'), true);
            if ($scenario !== 'sold_out') { $db->values[0]['quantity'] = 1; }
            if ($scenario === 'aggregate') { $db->rows = array(stockRow($customer)); }
            if ($scenario === 'unknown_value') { $selection = array(10 => 99999); }
            if ($scenario === 'wrong_option') { $selection = array(999 => 46015); }
            if ($scenario === 'missing') { $selection = array(); }
            if ($scenario === 'malformed') { $selection = array(10 => array(46015)); }
            if ($scenario === 'generic') { $db->values[0]['sku'] = ''; $db->values[0]['quantity'] = 0; }
            if ($scenario === 'parent_aggregate') { $db->products[1]['quantity'] = 1; $db->values[0]['quantity'] = 2; $db->rows = array(stockRow($customer)); }
            if ($scenario === 'checkbox' || $scenario === 'checkbox_duplicate') { $db->options[0]['type'] = 'checkbox'; $selection = array(10 => $scenario === 'checkbox' ? array(46015) : array(46015, 46015)); }
            $registry->set('request', (object)array('post' => array('product_id' => 1, 'quantity' => 1, 'option' => $selection)));
            (new $route[0]($registry))->{$route[1]}();
            $output = json_decode($registry->get('response')->output, true);
            stockAssert((bool)$registry->get('cart')->added === $allowed, $route[0] . ': ' . $scenario . ' cart mutation');
            stockAssert(isset($output[$allowed ? 'success' : 'error']), $route[0] . ': ' . $scenario . ' response');
        }
    }
}
echo "Passed $assertions inventory assertions (real Cart, model, add routes, and both checkout confirmations; guest and logged in).\n";
