<?php
/** Offline coupon regression suite. Run: php tests/coupon/run.php */

if (PHP_SAPI !== 'cli') { exit("Run this regression from the command line.\n"); }

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

// These fixtures deliberately replace database models before the real export
// class is loaded. No framework bootstrap, credentials or external calls occur.
class CouponFixtureRecord
{
    private $data;
    public function __construct(array $data) { $this->data = $data; }
    public function __get($name) { return isset($this->data[$name]) ? $this->data[$name] : null; }
    public function __isset($name) { return isset($this->data[$name]); }
    public function toArray() { return $this->data; }
}

class CouponFixtureCollection implements IteratorAggregate, Countable
{
    private $items;
    public function __construct(array $items = array()) { $this->items = $items; }
    public function getIterator(): Traversable { return new ArrayIterator($this->items); }
    public function count(): int { return count($this->items); }
    public function isEmpty() { return !$this->items; }
    public function isNotEmpty() { return (bool) $this->items; }
    public function all() { return $this->items; }
    public function values() { return new self(array_values($this->items)); }
    public function get($key, $default = null) { return isset($this->items[$key]) ? $this->items[$key] : $default; }
    public function first() { return $this->items ? reset($this->items) : null; }
    public function filter($callback = null)
    {
        return new self($callback === null ? array_filter($this->items) : array_filter($this->items, $callback));
    }
    public function unique() { return new self(array_unique($this->items, SORT_REGULAR)); }
    public function pluck($field)
    {
        return new self(array_map(function ($item) use ($field) { return $item->$field; }, $this->items));
    }
    public function keyBy($field)
    {
        $items = array();
        foreach ($this->items as $item) { $items[$item->$field] = $item; }
        return new self($items);
    }
    public function toArray()
    {
        return array_map(function ($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
        }, $this->items);
    }
}

class CouponFixtures
{
    public static $tables = array();
    public static $queries = array();
    public static function reset() { self::$tables = array(); self::$queries = array(); }
}

class CouponFixtureQuery
{
    private $table;
    private $conditions = array();
    public function __construct($table) { $this->table = $table; CouponFixtures::$queries[] = $table; }
    public function where($field, $value)
    {
        $this->conditions[] = array($field, array($value));
        return $this;
    }
    public function whereIn($field, $values)
    {
        $this->conditions[] = array($field, $values instanceof CouponFixtureCollection ? $values->all() : $values);
        return $this;
    }
    public function get()
    {
        $records = array();
        $rows = isset(CouponFixtures::$tables[$this->table]) ? CouponFixtures::$tables[$this->table] : array();
        foreach ($rows as $row) {
            foreach ($this->conditions as $condition) {
                if (!isset($row[$condition[0]]) || !in_array($row[$condition[0]], $condition[1])) { continue 2; }
            }
            $records[] = new CouponFixtureRecord($row);
        }
        return new CouponFixtureCollection($records);
    }
    public function first() { return $this->get()->first(); }
}

class CouponFixtureModel
{
    protected static $table;
    public static function query() { return new CouponFixtureQuery(static::$table); }
    public static function where($field, $value) { return static::query()->where($field, $value); }
    public static function find($id) { return static::where('product_id', $id)->first(); }
}
class CouponFixtureOrderProduct extends CouponFixtureModel { protected static $table = 'order_product'; }
class CouponFixtureOrderOption extends CouponFixtureModel { protected static $table = 'order_option'; }
class CouponFixtureOrderTotal extends CouponFixtureModel { protected static $table = 'order_total'; }
class CouponFixtureProduct extends CouponFixtureModel { protected static $table = 'product'; }
class CouponFixtureProductOption extends CouponFixtureModel { protected static $table = 'product_option_value'; }
class CouponFixtureCoupon extends CouponFixtureModel { protected static $table = 'coupon'; }
class CouponFixtureLog { public static function store($message, $name = null) {} }

class_alias('CouponFixtureOrderProduct', 'Agmedia\\Models\\Order\\OrderProduct');
class_alias('CouponFixtureOrderOption', 'Agmedia\\Models\\Order\\OrderOption');
class_alias('CouponFixtureOrderTotal', 'Agmedia\\Models\\Order\\OrderTotal');
class_alias('CouponFixtureProduct', 'Agmedia\\Models\\Product\\Product');
class_alias('CouponFixtureProductOption', 'Agmedia\\Models\\Product\\ProductOption');
class_alias('CouponFixtureCoupon', 'Agmedia\\Models\\Coupon');
class_alias('CouponFixtureLog', 'Agmedia\\Helpers\\Log');

function collect($items = array()) { return new CouponFixtureCollection($items); }
function agconf($key)
{
    $config = array('default_shipping_price' => 2.50, 'luceed.shipping_article_uid' => 'shipping-fixture',
        'luceed.with_tax' => isset($GLOBALS['coupon_fixture_with_tax']) ? $GLOBALS['coupon_fixture_with_tax'] : 'D');
    return isset($config[$key]) ? $config[$key] : null;
}

class Model
{
    public $session, $load, $cart, $tax, $language, $config, $model_catalog_product, $db, $model_extension_total_voucher;
}
class CouponFixtureCart
{
    private $products;
    public function __construct($products) { $this->products = $products; }
    public function getProducts() { return $this->products; }
    public function getSubTotal() { return array_sum(array_column($this->products, 'total')); }
}
class CouponFixtureCatalog
{
    private $products;
    public function __construct($products) { $this->products = $products; }
    public function getProduct($id) { return isset($this->products[$id]) ? $this->products[$id] : array('special' => false); }
}
class CouponFixtureLoader
{
    public function language($route, $alias = null) {}
    public function model($route) {}
}
class CouponFixtureLanguage
{
    public function get($key) { return $key === 'coupon' ? $this : 'Coupon (%s)'; }
}
class CouponFixtureConfig { public function get($key) { return $key === 'total_coupon_sort_order' ? 4 : null; } }
class CouponFixtureTax
{
    public function getTax($amount, $class)
    {
        if (!$class) { return 0; }
        $rates = $this->getRates($amount, $class);
        return array_sum(array_column($rates, 'amount'));
    }
    public function getRates($amount, $class)
    {
        $rates = array(1 => 25, 2 => 13, 3 => 5);
        return array(
            array('type' => 'P', 'tax_rate_id' => $class, 'amount' => $amount * $rates[$class] / 100),
            array('type' => 'F', 'tax_rate_id' => 99, 'amount' => 2),
        );
    }
}

$root = dirname(__DIR__, 2);
require $root . '/storage/vendor/agmedia/luceed-opencart-wrapper/src/Helpers/CouponAllocation.php';
require $root . '/upload/catalog/model/extension/total/coupon.php';
require $root . '/upload/catalog/model/checkout/order.php';
require $root . '/storage/vendor/agmedia/luceed-opencart-wrapper/src/Models/LOC_Order.php';

use Agmedia\LuceedOpencartWrapper\Helpers\CouponAllocation;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;

class CouponFixtureCheckout extends ModelExtensionTotalCoupon
{
    public $coupon;
    public function getCoupon($code) { return $this->coupon; }
}

// Reapply the two coupon operations observed in this shop's local OCMOD cache.
// This exercises refreshed source without editing or relying on stale cache.
$ocmodSource = file_get_contents($root . '/upload/catalog/model/extension/total/coupon.php');
$ocmodSource = str_replace('class ModelExtensionTotalCoupon extends', 'class ModelExtensionTotalCouponOcmod extends', $ocmodSource);
$start = strpos($ocmodSource, 'public function getTotal(');
$end = strpos($ocmodSource, 'public function confirm(', $start);
$body = substr($ocmodSource, $start, $end - $start);
$body = str_replace("if (isset(\$this->session->data['coupon'])) {", "if (isset(\$this->session->data['coupon'])) {\n\$this->load->model('catalog/product');", $body);
$body = str_replace('if ($status) {', "\$product_details = \$this->model_catalog_product->getProduct(\$product['product_id']);\nif (\$product_details['special']) { continue; }\nif (\$status) {", $body);
$ocmodSource = substr($ocmodSource, 0, $start) . $body . substr($ocmodSource, $end);
eval('?>' . $ocmodSource);
class CouponFixtureCheckoutOcmod extends ModelExtensionTotalCouponOcmod
{
    public $coupon;
    public function getCoupon($code) { return $this->coupon; }
}

class CouponFixtureVoucher { public function disableVoucher($orderId) {} }
class CouponFixtureDatabase
{
    public $queries = array(), $snapshots = array(), $insertedProducts = array();
    private $lastId = 42;
    private $productSequence = 1000;
    public function escape($value)
    {
        $decoded = json_decode((string)$value, true);
        if (is_array($decoded) && isset($decoded['version'])) { $this->snapshots[] = $decoded; }
        return addslashes((string)$value);
    }
    public function query($sql)
    {
        $this->queries[] = $sql;
        if (strpos($sql, 'INSERT INTO oc_order_product ') === 0) {
            $this->lastId = ++$this->productSequence;
            $this->insertedProducts[] = $this->lastId;
        }
        return (object) array('row' => array(), 'rows' => array(), 'num_rows' => 0);
    }
    public function getLastId() { return $this->lastId; }
}
class CouponFixtureOrderWriter extends ModelCheckoutOrder
{
    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {}
}
if (!defined('DB_PREFIX')) { define('DB_PREFIX', 'oc_'); }

$tests = 0;
$assertions = 0;
$failures = array();
function check($condition, $message)
{
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($message); }
}
function same($expected, $actual, $message)
{
    check($expected === $actual, $message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
function money($expected, $actual, $message)
{
    check(abs($expected - $actual) < 0.0000001, $message . ': expected ' . $expected . ', got ' . $actual);
}
function moneyArray(array $expected, array $actual, $message)
{
    same(count($expected), count($actual), $message . ' element count');
    foreach ($expected as $index => $amount) { money($amount, $actual[$index], $message . ' item ' . $index); }
}
function rejects($callback, $message)
{
    try { $callback(); } catch (InvalidArgumentException $e) { check(true, $message); return; }
    catch (LogicException $e) { check(true, $message); return; }
    check(false, $message . ': expected invalid input to be rejected');
}
function scenario($name, $callback)
{
    global $tests, $failures;
    $tests++;
    try { $callback(); echo 'PASS ' . $name . PHP_EOL; }
    catch (Throwable $e) { $failures[] = $name . ': ' . $e->getMessage(); echo 'FAIL ' . end($failures) . PHP_EOL; }
}
function product($id, $price, $quantity = 1, array $extra = array())
{
    return array_merge(array(
        'product_id' => $id, 'quantity' => $quantity, 'price' => $price,
        'total' => $price * $quantity, 'tax_class_id' => 0, 'option' => array(), 'model' => 'MODEL-' . $id,
        'name' => 'Fixture product ' . $id, 'tax' => 0, 'reward' => 0,
    ), $extra);
}
function snapshot(array $products, array $discounts, $amount = null, $shipping = 0, $type = 'P')
{
    $items = array();
    foreach ($products as $index => $product) {
        $items[] = array(
            'key' => CouponAllocation::productKey($product), 'product_id' => $product['product_id'],
            'quantity' => $product['quantity'], 'price' => $product['price'],
            'base_price' => isset($product['base_price']) ? $product['base_price'] : $product['price'],
            'discount' => $discounts[$index], 'discount_tax' => 0,
        );
    }
    return array('version' => 1, 'code' => 'TEST', 'type' => $type,
        'amount' => $amount === null ? array_sum($discounts) + $shipping : $amount,
        'shipping_discount' => $shipping, 'shipping_price' => $shipping, 'products' => $items);
}
function lineCents(array $lines)
{
    $sum = 0;
    foreach ($lines as $line) {
        $sum += (int) round($line['cijena'] * (1 - $line['rabat'] / 100) * 100) * $line['kolicina'];
    }
    return $sum;
}
function lineTotalCents(array $lines)
{
    $sum = 0;
    foreach ($lines as $line) {
        $sum += (int) round($line['cijena'] * (1 - $line['rabat'] / 100) * $line['kolicina'] * 100);
    }
    return $sum;
}
function assertAllocation(array $allocation)
{
    $discount = $allocation['shipping_discount_amount'];
    $paid = $allocation['shipping_net_total'];
    $rawCharged = $allocation['shipping_price'];
    foreach ($allocation['products'] as $item) {
        $discount += $item['discount_amount'];
        $paid += $item['net_total'];
        $rawCharged += $item['price'] * $item['quantity'];
        check($item['discount_amount'] >= 0 && $item['net_total'] >= 0, 'Nonnegative item amounts');
        same((int) round($item['charged_amount'] * 100),
            (int) round(($item['discount_amount'] + $item['net_total']) * 100), 'Item discount and net conserve charged price');
        if ($item['discount'] == 0) { money(0, $item['discount_amount'], 'Ineligible item receives no coupon discount'); }
        $lines = CouponAllocation::buildLines($item);
        same((int) round($item['net_total'] * 100), lineCents($lines), 'Luceed unit rounding reproduces paid line total');
        same((int) round($item['net_total'] * 100), lineTotalCents($lines), 'Luceed extended-line rounding reproduces paid line total');
        same($item['quantity'], array_sum(array_column($lines, 'kolicina')), 'Export quantity conserved');
        check(count($lines) >= 1, 'Export contains a rebate group');
        foreach ($lines as $line) {
            check($line['rabat'] >= 0 && $line['rabat'] <= 100, 'Rebate stays within zero and 100 percent');
        }
    }
    same((int) round($allocation['allocated_amount'] * 100), (int) round($discount * 100), 'Coupon components conserve allocated discount');
    same((int) round($allocation['paid_total'] * 100), (int) round($paid * 100), 'Paid components conserve final order total');
    same((int) round(round(round($rawCharged - $allocation['amount'], 4), 2) * 100), (int) round($paid * 100), 'Final paid amount follows stored order and checkout rounding');
}
function checkout(array $products, array $coupon = array(), array $details = array(), $shipping = null, $limit = null, $ocmod = false)
{
    $model = $ocmod ? new CouponFixtureCheckoutOcmod() : new CouponFixtureCheckout();
    $model->coupon = array_merge(array('code' => 'TEST', 'type' => 'P', 'discount' => 10, 'product' => array(), 'shipping' => false), $coupon);
    $model->session = (object) array('data' => array('coupon' => 'TEST'));
    if ($shipping !== null) { $model->session->data['shipping_method'] = $shipping; }
    $model->cart = new CouponFixtureCart($products);
    $model->tax = new CouponFixtureTax();
    $model->load = new CouponFixtureLoader();
    $model->language = new CouponFixtureLanguage();
    $model->config = new CouponFixtureConfig();
    $model->model_catalog_product = new CouponFixtureCatalog($details);
    $total = $model->cart->getSubTotal() + ($shipping === null ? 0 : $shipping['cost']);
    $totals = array();
    $taxes = array(1 => 100, 2 => 100, 3 => 100, 99 => 100);
    if ($limit !== null) { $total = $limit; }
    $model->getTotal(array('totals' => &$totals, 'taxes' => &$taxes, 'total' => &$total));
    return array('totals' => $totals, 'taxes' => $taxes, 'total' => $total);
}
function invokePrivate($object, $method)
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection->invoke($object);
}
function exporter(array $products, $allocation, $couponValue, $shipping = 0)
{
    CouponFixtures::reset();
    foreach ($products as $index => &$item) {
        $item['order_id'] = 42;
        if (!isset($item['order_product_id'])) { $item['order_product_id'] = 100 + $index; }
    }
    unset($item);
    CouponFixtures::$tables['order_product'] = $products;
    CouponFixtures::$tables['product'] = array_map(function ($item) {
        return array('product_id' => $item['product_id'], 'price' => 999.99);
    }, $products);
    if (is_array($allocation)) {
        $allocation = CouponAllocation::bindProducts($allocation, $products);
        $allocation = json_encode($allocation);
    }
    CouponFixtures::$tables['order_total'] = array(
        array('order_id' => 42, 'code' => 'coupon', 'title' => 'Coupon (TEST)', 'value' => $couponValue, 'coupon_allocation' => $allocation),
        array('order_id' => 42, 'code' => 'shipping', 'value' => $shipping),
    );
    $reflection = new ReflectionClass(LOC_Order::class);
    $object = $reflection->newInstanceWithoutConstructor();
    $property = $reflection->getProperty('oc_order');
    $property->setAccessible(true);
    $property->setValue($object, array('order_id' => 42));
    invokePrivate($object, 'resolveCouponDiscount');
    return $object;
}

scenario('Receipt: seven products, 10 percent, discount 29.15 and paid 262.32', function () {
    $prices = array(38.50, 24.02, 33.99, 59.99, 75.99, 23.99, 34.99);
    $products = array();
    foreach ($prices as $id => $price) { $products[] = product($id + 1, $price); }
    $result = checkout($products);
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(29.15, round(-$result['totals'][0]['value'], 2), 'Receipt coupon total');
    money(262.32, round($result['total'], 2), 'Receipt paid total');
    moneyArray(array(3.85, 2.40, 3.40, 6.0, 7.60, 2.40, 3.50), array_column($allocation['products'], 'discount_amount'), 'Expected cent distribution');
    money(262.32, array_sum(array_column($allocation['products'], 'net_total')), 'Partial refunds sum to entire paid receipt');
});

scenario('Receipt: 21.00 at 20 percent yields 16.80', function () {
    $allocation = checkout(array(product(1, 21)), array('discount' => 20))['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(4.20, $allocation['products'][0]['discount_amount'], 'Dress discount');
    money(16.80, $allocation['products'][0]['net_total'], 'Dress refund');
    money(20, CouponAllocation::buildLines($allocation['products'][0])[0]['rabat'], 'Visible dress rebate');
});

scenario('Fixed coupon applies only to eligible products', function () {
    $allocation = checkout(array(product(1, 10), product(2, 30), product(3, 20)),
        array('type' => 'F', 'discount' => 8, 'product' => array(1, 2)))['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    moneyArray(array(2.0, 6.0, 0.0), array_column($allocation['products'], 'discount_amount'), 'Fixed coupon proportional eligibility');
});

scenario('Sale items remain excluded and fixed coupon uses eligible subtotal', function () {
    $allocation = checkout(array(product(1, 10), product(2, 30)), array('type' => 'F', 'discount' => 8),
        array(2 => array('special' => 30)))['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    moneyArray(array(8.0, 0.0), array_column($allocation['products'], 'discount_amount'), 'Full fixed coupon on eligible item only');
    same(2, count($allocation['products']), 'Excluded items remain bound in immutable snapshot');
});

scenario('Zero subtotal and no eligible products produce no coupon line', function () {
    same(array(), checkout(array(product(1, 0)), array('type' => 'F', 'discount' => 10))['totals'], 'Zero eligible subtotal');
    same(array(), checkout(array(product(1, 30)), array(), array(1 => array('special' => 30)))['totals'], 'All items on sale');
    same(array(), checkout(array(product(1, 30)), array('product' => array(999)))['totals'], 'No eligible product');
});

scenario('Fixed coupon and percentages cannot discount below zero', function () {
    foreach (array(array('type' => 'F', 'discount' => 99), array('discount' => 150)) as $coupon) {
        $result = checkout(array(product(1, 10)), $coupon);
        money(0, $result['total'], 'Coupon capped at charged price');
        assertAllocation($result['totals'][0]['coupon_allocation']);
    }
    same(array(), checkout(array(product(1, 10)), array('discount' => -10))['totals'], 'Negative percent ignored');
    same(array(), checkout(array(product(1, 10)), array('type' => 'F', 'discount' => -10))['totals'], 'Negative fixed coupon ignored');
});

scenario('Shipping-only coupon discounts delivery and no merchandise', function () {
    $result = checkout(array(product(1, 20)), array('discount' => 0, 'shipping' => true), array(), array('cost' => 2.50));
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(2.50, $allocation['shipping_discount_amount'], 'Free shipping amount');
    money(0, $allocation['products'][0]['discount_amount'], 'No merchandise discount');
    money(20, $result['total'], 'Shipping-only paid total');
});

scenario('Merchandise and shipping discounts remain separate', function () {
    $result = checkout(array(product(1, 20)), array('discount' => 20, 'shipping' => true), array(), array('cost' => 2.50));
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(4, $allocation['products'][0]['discount_amount'], 'Merchandise discount');
    money(2.50, $allocation['shipping_discount_amount'], 'Shipping discount');
    money(16, $result['total'], 'Combined paid amount');
});

scenario('Mixed tax classes retain percentage tax reductions and fixed tax', function () {
    $result = checkout(array(product(1, 100, 1, array('tax_class_id' => 1)), product(2, 50, 1, array('tax_class_id' => 2))),
        array('discount' => 20, 'shipping' => true), array(), array('cost' => 10, 'tax_class_id' => 3));
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(95, $result['taxes'][1], '25 percent tax reduced by 5');
    money(98.70, $result['taxes'][2], '13 percent tax reduced by 1.30');
    money(99.50, $result['taxes'][3], '5 percent shipping tax reduced by 0.50');
    money(100, $result['taxes'][99], 'Fixed taxes unchanged');
    money(5, $allocation['products'][0]['discount_tax'], 'Recorded first item tax reduction');
    money(1.30, $allocation['products'][1]['discount_tax'], 'Recorded second item tax reduction');
    money(0.50, $allocation['shipping_discount_tax'], 'Recorded shipping tax reduction');
});

scenario('Other order discounts cap coupon and scale its tax reduction', function () {
    $result = checkout(array(product(1, 10, 1, array('tax_class_id' => 1))), array('discount' => 50), array(), null, 2);
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(0, $result['total'], 'Remaining order amount consumed');
    money(2, $allocation['products'][0]['discount_amount'], 'Recorded coupon scaled to remaining amount');
    money(99.50, $result['taxes'][1], 'Tax reduction scales with capped coupon');
});

scenario('Fractional coupon percentages and four-decimal prices reconcile', function () {
    foreach (array(product(1, 19.99), product(1, 0.3333, 3)) as $product) {
        $result = checkout(array($product), array('discount' => 12.3456));
        assertAllocation($result['totals'][0]['coupon_allocation']);
        same((int) round($product['total'] * (1 - 0.123456) * 100),
            (int) round($result['total'] * 100), 'Fractional percentage paid amount');
    }
});

scenario('Half-cent coupon discounts reproduce the actual paid order', function () {
    $result = checkout(array(product(1, 19.95)), array('discount' => 10));
    $allocation = $result['totals'][0]['coupon_allocation'];
    assertAllocation($allocation);
    money(17.96, round($result['total'], 2), 'Checkout charges 17.96');
    money(17.96, $allocation['products'][0]['net_total'], 'Recorded refund is 17.96');
    money(1.99, $allocation['products'][0]['discount_amount'], 'Actual cent discount complements the paid amount');
    $object = exporter(array(product(1, 19.95)), $allocation, $result['totals'][0]['value']);
    same(1796, lineCents(invokePrivate($object, 'getRegularProducts')), 'Luceed agrees with charged payment');
});

scenario('Quantity splits preserve per-unit cents for partial refunds', function () {
    $allocation = CouponAllocation::finalize(snapshot(array(product(1, 10, 3)), array(1), null, 0, 'F'));
    assertAllocation($allocation);
    $lines = CouponAllocation::buildLines($allocation['products'][0]);
    same(2, count($lines), 'Indivisible fixed coupon split into two rebate groups');
    same(2900, lineCents($lines), 'Three units paid exactly 29.00');
    $unitNets = array();
    foreach ($lines as $line) {
        for ($i = 0; $i < $line['kolicina']; $i++) { $unitNets[] = (int) round($line['cijena'] * (1 - $line['rabat'] / 100) * 100); }
    }
    sort($unitNets);
    same(array(966, 967, 967), $unitNets, 'Refundable unit amounts differ by at most one cent');
});

scenario('High quantities preserve both unit and extended-line rounding', function () {
    foreach (array(100, 1000, 10000) as $quantity) {
        $allocation = CouponAllocation::finalize(snapshot(array(product(1, 99.99, $quantity)), array(123.45), null, 0, 'F'));
        assertAllocation($allocation);
        $lines = CouponAllocation::buildLines($allocation['products'][0]);
        $expected = (int) round((99.99 * $quantity - 123.45) * 100);
        same($expected, lineCents($lines), 'High quantity unit rounding');
        same($expected, lineTotalCents($lines), 'High quantity extended-line rounding');
    }
});

scenario('Sub-cent discounts allocate deterministically without touching excluded rows', function () {
    $raw = snapshot(array(product(1, 0.05), product(2, 0.05), product(3, 0.05), product(4, 0.05)), array(0.005, 0.005, 0.005, 0));
    $allocation = CouponAllocation::finalize($raw);
    assertAllocation($allocation);
    moneyArray(array(0.01, 0.0, 0.0, 0.0), array_column($allocation['products'], 'discount_amount'), 'Stable equal-remainder cent allocation');
    same($allocation, CouponAllocation::finalize($raw), 'Allocation deterministic');
});

scenario('Global charged rounding keeps excluded items and fully discounted items correct', function () {
    $allocation = CouponAllocation::finalize(snapshot(array(product(1, 10.006), product(2, 10.006), product(3, 10.004)), array(0, 0, 10.004)));
    assertAllocation($allocation);
    money(20.01, $allocation['paid_total'], 'Actual rounded payment across excluded items');
    money(0, $allocation['products'][0]['discount_amount'], 'First excluded item retains zero coupon');
    money(0, $allocation['products'][1]['discount_amount'], 'Second excluded item retains zero coupon');
    money(0, $allocation['products'][2]['net_total'], 'Fully discounted eligible item remains free');
});

scenario('Tiny fractional discounts reconcile even when effective coupon cents are zero', function () {
    foreach (array(
        array(array(product(1, 10.006), product(2, 10.004)), array(0, 0.001)),
        array(array(product(1, 10.006), product(2, 0.004)), array(0, 0.004)),
        array(array(product(1, 0.0051), product(2, 0.0051)), array(0, 0.0049)),
    ) as $case) {
        $allocation = CouponAllocation::finalize(snapshot($case[0], $case[1]));
        assertAllocation($allocation);
        money(0, $allocation['products'][0]['discount_amount'], 'Excluded fractional item remains without coupon');
    }
});

scenario('Coordinated charged rounding stays within one cent for mixed eligibility', function () {
    $products = array(
        product(1, 0.0073, 16), product(2, 26.229, 3), product(3, 1.454, 20),
        product(4, 13.8227, 6), product(5, 3.6368, 4),
    );
    $raw = snapshot($products, array(0, 64.9010376, 29.08, 0, 0), 94.6283, 0.6473);
    $allocation = CouponAllocation::finalize($raw);
    assertAllocation($allocation);
    foreach ($allocation['products'] as $index => $item) {
        $rawCents = (int) round(round($products[$index]['total'], 2) * 100);
        $chargedCents = (int) round($item['charged_amount'] * 100);
        check(abs($chargedCents - $rawCents) <= 1, 'Coordinated line charge differs by at most one cent');
    }
    money(0, $allocation['products'][2]['net_total'], 'Fully discounted product remains free');
    money(0, $allocation['shipping_net_total'], 'Fully discounted delivery remains free');
});

scenario('Original sale price and coupon combine into one rebate', function () {
    $allocation = CouponAllocation::finalize(snapshot(array(product(1, 16.80, 1, array('base_price' => 21))), array(1.68)));
    assertAllocation($allocation);
    $line = CouponAllocation::buildLines($allocation['products'][0])[0];
    money(21, $line['cijena'], 'Original price retained');
    money(28, $line['rabat'], '20 percent sale and 10 percent coupon combine to 28 percent');
    same(1512, lineCents(array($line)), 'Paid amount is 15.12');
});

scenario('Product identity handles option ordering, variants and reordered duplicates', function () {
    $options = array(
        array('product_option_id' => 5, 'product_option_value_id' => 12, 'value' => 'M'),
        array('product_option_id' => 8, 'product_option_value_id' => 20, 'value' => 'Blue'),
    );
    $first = product(1, 10, 1, array('option' => $options));
    $same = product(1, 10, 1, array('option' => array_reverse($options), 'name' => 'Changed display label'));
    $variant = product(1, 10, 1, array('option' => array(array('product_option_id' => 5, 'product_option_value_id' => 13, 'value' => 'L'))));
    same(CouponAllocation::productKey($first), CouponAllocation::productKey($same), 'Option ordering and product name are immaterial');
    check(CouponAllocation::productKey($first) !== CouponAllocation::productKey($variant), 'Variants have distinct identity');
    $raw = snapshot(array($first, $variant, $first), array(1, 0, 1));
    $bound = CouponAllocation::bindProducts($raw, array(
        array_merge($variant, array('order_product_id' => 9)),
        array_merge($same, array('order_product_id' => 8)),
        array_merge($first, array('order_product_id' => 7)),
    ));
    same(array(8, 9, 7), array_column($bound['products'], 'order_product_id'), 'Binding respects variant identity and consumes duplicate IDs once');
    assertAllocation(CouponAllocation::finalize($bound));
});

scenario('Malformed snapshots and inconsistent totals fail explicitly', function () {
    $valid = snapshot(array(product(1, 10)), array(1));
    $invalid = array(
        array(), array_merge($valid, array('version' => 2)), array_merge($valid, array('code' => '')),
        array_merge($valid, array('type' => 'Q')), array_merge($valid, array('products' => array())),
        array_merge($valid, array('amount' => -1)), array_merge($valid, array('amount' => INF)),
        array_merge($valid, array('amount' => 2)), array_merge($valid, array('shipping_discount' => -1)),
    );
    foreach ($invalid as $item) { rejects(function () use ($item) { CouponAllocation::finalize($item); }, 'Malformed snapshot'); }
    foreach (array('quantity' => 0, 'price' => -1, 'base_price' => NAN, 'discount' => -1, 'product_id' => 0, 'key' => '') as $field => $value) {
        $item = $valid; $item['products'][0][$field] = $value;
        rejects(function () use ($item) { CouponAllocation::finalize($item); }, 'Malformed item ' . $field);
    }
    $item = CouponAllocation::finalize($valid)['products'][0];
    $item['net_total'] = 8;
    rejects(function () use ($item) { CouponAllocation::buildLines($item); }, 'Inconsistent finalized total');
    $item['net_total'] = 9.001;
    rejects(function () use ($item) { CouponAllocation::buildLines($item); }, 'Fractional finalized cents');
});

scenario('Missing, extra, mismatched and duplicate order IDs fail binding', function () {
    $product = product(1, 10);
    $raw = snapshot(array($product), array(1));
    foreach (array(
        array(), array(array_merge($product, array('order_product_id' => 0))),
        array(array_merge(product(2, 10), array('order_product_id' => 1))),
        array(array_merge($product, array('order_product_id' => 1)), array_merge($product, array('order_product_id' => 2))),
        array(array_merge($product, array('order_product_id' => 1)), array_merge($product, array('order_product_id' => 1))),
    ) as $products) {
        rejects(function () use ($raw, $products) { CouponAllocation::bindProducts($raw, $products); }, 'Invalid order binding');
    }
});

scenario('Generated monetary cases conserve cents, quantity and eligibility', function () {
    // A fixed seed makes failures reproducible across PHP versions. Test the
    // externally observable money/quantity invariants rather than the algorithm.
    mt_srand(20261012);
    for ($case = 0; $case < 400; $case++) {
        $products = array(); $discounts = array();
        $count = mt_rand(1, 8);
        for ($id = 1; $id <= $count; $id++) {
            $price = $case % 4 === 0 ? mt_rand(0, 9999999) / 10000 : mt_rand(0, 99999) / 100;
            $quantity = mt_rand(1, 12);
            $base = $price + mt_rand(0, 1000) / 100;
            $products[] = product($id, $price, $quantity, array('base_price' => $base));
            $discounts[] = mt_rand(0, 3) === 0 ? 0 : $price * $quantity * mt_rand(0, 10000) / 10000;
        }
        $shipping = mt_rand(0, 500) / 100;
        $raw = array_sum($discounts) + $shipping;
        $amount = $raw * mt_rand(0, 10000) / 10000;
        $allocation = CouponAllocation::finalize(snapshot($products, $discounts, $amount, $shipping));
        assertAllocation($allocation);
    }
});

scenario('Generated sub-cent baskets preserve exclusions and fully discounted lines', function () {
    mt_srand(20261014);
    for ($case = 0; $case < 150; $case++) {
        $products = array(); $discounts = array();
        $count = mt_rand(2, 6);
        for ($id = 1; $id <= $count; $id++) {
            $price = mt_rand(1, 100) / 10000;
            $quantity = mt_rand(1, 5);
            $products[] = product($id, $price, $quantity);
            $mode = mt_rand(0, 3);
            $discounts[] = $mode === 0 ? 0 : ($mode === 1 ? $price * $quantity : $price * $quantity * mt_rand(1, 9) / 10);
        }
        $allocation = CouponAllocation::finalize(snapshot($products, $discounts));
        assertAllocation($allocation);
        foreach ($allocation['products'] as $index => $item) {
            if ($discounts[$index] === $products[$index]['total']) {
                money(0, $item['net_total'], 'Fully discounted sub-cent item remains free');
            }
        }
    }
});

scenario('Luceed exports immutable per-item rebates without U300 or current coupon lookup', function () {
    $products = array(product(1, 21), product(2, 10));
    $allocation = CouponAllocation::finalize(snapshot($products, array(4.20, 0)));
    $object = exporter($products, $allocation, -4.20);
    $lines = invokePrivate($object, 'getRegularProducts');
    same(2, count($lines), 'Exactly two merchandise lines, no negative coupon product');
    same(2680, lineCents($lines), 'Export monetary total includes item coupon');
    money(21, $lines[0]['cijena'], 'Historical original price retained despite current price 999.99');
    money(20, $lines[0]['rabat'], 'First product coupon rebate');
    money(0, $lines[1]['rabat'], 'Ineligible product unchanged');
    check(!in_array('coupon', CouponFixtures::$queries), 'No lookup of a deleted/changed coupon');
});

scenario('Luceed same-product variants retain the correct eligibility', function () {
    $small = product(1, 10, 1, array('option' => array(array('product_option_id' => 4, 'product_option_value_id' => 11, 'value' => 'S'))));
    $large = product(1, 10, 1, array('option' => array(array('product_option_id' => 4, 'product_option_value_id' => 12, 'value' => 'L'))));
    $products = array($small, $large);
    $allocation = CouponAllocation::finalize(snapshot($products, array(2, 0)));
    $object = exporter($products, $allocation, -2);
    CouponFixtures::$tables['order_option'] = array(
        array('order_id' => 42, 'order_product_id' => 100, 'product_option_value_id' => 11),
        array('order_id' => 42, 'order_product_id' => 101, 'product_option_value_id' => 12),
    );
    CouponFixtures::$tables['product_option_value'] = array(
        array('product_option_value_id' => 11, 'sku' => 'SMALL-UID'),
        array('product_option_value_id' => 12, 'sku' => 'LARGE-UID'),
    );
    $lines = invokePrivate($object, 'getRegularProducts');
    same('SMALL-UID', $lines[0]['artikl_uid'], 'First variant UID');
    same('LARGE-UID', $lines[1]['artikl_uid'], 'Second variant UID');
    money(20, $lines[0]['rabat'], 'Eligible variant rebate');
    money(0, $lines[1]['rabat'], 'Excluded variant rebate');
    same(1800, lineCents($lines), 'Variant monetary total');
});

scenario('Luceed free shipping applies to delivery without a negative coupon line', function () {
    $products = array(product(1, 20));
    $allocation = CouponAllocation::finalize(snapshot($products, array(4), null, 2.50));
    $object = exporter($products, $allocation, -6.50, 2.50);
    $lines = invokePrivate($object, 'getRegularProducts');
    $shipping = invokePrivate($object, 'getShippingProduct');
    same(1, count($lines), 'One merchandise line');
    same(1600, lineCents($lines), 'Merchandise stays at 16.00');
    same(0, lineCents(array($shipping)), 'Free delivery exports zero paid amount');
    same('shipping-fixture', $shipping['artikl_uid'], 'Delivery product preserved');
});

scenario('Snapshot orders without a shipping total export zero delivery', function () {
    $products = array(product(1, 20));
    $allocation = CouponAllocation::finalize(snapshot($products, array(4)));
    $object = exporter($products, $allocation, -4);
    CouponFixtures::$tables['order_total'] = array(CouponFixtures::$tables['order_total'][0]);
    same(0, lineCents(array(invokePrivate($object, 'getShippingProduct'))), 'No physical delivery means zero delivery charge');
    $object = exporter($products, null, -4);
    CouponFixtures::$tables['order_total'] = array(CouponFixtures::$tables['order_total'][0]);
    same(250, lineCents(array(invokePrivate($object, 'getShippingProduct'))), 'Historical shipping fallback remains unchanged');
});

scenario('Legacy orders retain saved U300 amount after coupon deletion', function () {
    foreach (array(null, '') as $missing) {
        $object = exporter(array(product(1, 21)), $missing, -4.20);
        $lines = invokePrivate($object, 'getRegularProducts');
        $last = end($lines);
        same('668150-2987', $last['artikl_uid'], 'Legacy promotional product UID');
        money(-4.20, $last['cijena'], 'Legacy amount comes from saved order total');
        check(!in_array('coupon', CouponFixtures::$queries), 'Legacy amount independent of deleted coupon');
    }
});

scenario('Malformed persisted metadata blocks incorrect exports', function () {
    foreach (array('{broken', '{}', json_encode(array('version' => 99))) as $invalid) {
        rejects(function () use ($invalid) { exporter(array(product(1, 21)), $invalid, -4.20); }, 'Malformed persisted snapshot');
    }
});

scenario('Refreshed OCMOD coupon injections preserve complete allocation', function () {
    $products = array(product(1, 10), product(2, 30), product(3, 20));
    $details = array(1 => array('special' => false), 2 => array('special' => 30), 3 => array('special' => false));
    foreach (array(array('type' => 'F', 'discount' => 8), array('type' => 'P', 'discount' => 20)) as $coupon) {
        $plain = checkout($products, $coupon, $details);
        $modified = checkout($products, $coupon, $details, null, null, true);
        same($plain, $modified, 'Observed OCMOD hooks retain source behavior and all product rows');
        assertAllocation($modified['totals'][0]['coupon_allocation']);
        same(3, count($modified['totals'][0]['coupon_allocation']['products']), 'Sale item remains recorded');
    }
});

scenario('Add and edit order persist allocation bound to newly inserted product IDs', function () {
    $products = array(product(1, 21), product(2, 10));
    $result = checkout($products, array('discount' => 20));
    $data = array_fill_keys(explode(' ', 'invoice_prefix store_id store_name store_url customer_id customer_group_id firstname lastname email telephone payment_firstname payment_lastname payment_company payment_address_1 payment_address_2 payment_city payment_postcode payment_country payment_country_id payment_zone payment_zone_id payment_address_format payment_method payment_code shipping_firstname shipping_lastname shipping_company shipping_address_1 shipping_address_2 shipping_city shipping_postcode shipping_country shipping_country_id shipping_zone shipping_zone_id shipping_address_format shipping_method shipping_code comment affiliate_id commission marketing_id tracking language_id currency_id currency_code currency_value ip forwarded_ip user_agent accept_language'), '');
    $data['total'] = $result['total'];
    $data['custom_field'] = $data['payment_custom_field'] = $data['shipping_custom_field'] = array();
    $data['products'] = $products;
    $data['totals'] = $result['totals'];
    $writer = new CouponFixtureOrderWriter();
    $writer->db = new CouponFixtureDatabase();
    $writer->load = new CouponFixtureLoader();
    $writer->model_extension_total_voucher = new CouponFixtureVoucher();
    $orderId = $writer->addOrder($data);
    same(42, $orderId, 'New order ID');
    same(array(1001, 1002), array_column($writer->db->snapshots[0]['products'], 'order_product_id'), 'Create binds inserted IDs');
    assertAllocation($writer->db->snapshots[0]);
    $writer->editOrder($orderId, $data);
    same(array(1003, 1004), array_column($writer->db->snapshots[1]['products'], 'order_product_id'), 'Edit binds replacement product IDs');
    assertAllocation($writer->db->snapshots[1]);
    $inserts = array_filter($writer->db->queries, function ($sql) { return strpos($sql, 'INSERT INTO `oc_order_total`') === 0; });
    same(2, count($inserts), 'Create and edit each save the coupon total');
    foreach ($inserts as $sql) { check(strpos($sql, 'coupon_allocation = ') !== false, 'Snapshot JSON included in total insert'); }
    $wrong = $result['totals'][0]; $wrong['value'] = -999;
    $saved = array();
    foreach ($products as $index => $item) { $saved[] = array_merge($item, array('order_product_id' => 1003 + $index)); }
    $method = new ReflectionMethod($writer, 'saveOrderTotal');
    $method->setAccessible(true);
    rejects(function () use ($method, $writer, $wrong, $saved) { $method->invoke($writer, 42, $wrong, $saved); }, 'Mismatched order total rejected before persistence');
});

scenario('Saved order edits and duplicate coupon totals block stale metadata', function () {
    $products = array(product(1, 21), product(2, 10));
    $allocation = CouponAllocation::finalize(snapshot($products, array(4.20, 0)));
    foreach (array('quantity' => 2, 'price' => 22, 'product_id' => 999, 'order_product_id' => 999) as $field => $value) {
        $object = exporter($products, $allocation, -4.20);
        CouponFixtures::$tables['order_product'][0][$field] = $value;
        rejects(function () use ($object) { invokePrivate($object, 'getRegularProducts'); }, 'Stale saved product ' . $field);
    }
    $object = exporter($products, $allocation, -4.20);
    array_pop(CouponFixtures::$tables['order_product']);
    rejects(function () use ($object) { invokePrivate($object, 'getRegularProducts'); }, 'Missing saved product');
    $object = exporter($products, $allocation, -4.20);
    CouponFixtures::$tables['order_total'][] = CouponFixtures::$tables['order_total'][0];
    rejects(function () use ($object) { invokePrivate($object, 'resolveCouponDiscount'); }, 'Duplicate coupon totals');
    rejects(function () use ($products, $allocation) { exporter($products, $allocation, -5); }, 'Saved coupon amount disagrees with metadata');
});

scenario('Tax-inclusive Luceed rows reproduce paid gross amounts for mixed tax classes', function () {
    $products = array(product(1, 100, 1, array('tax_class_id' => 1)), product(2, 50, 1, array('tax_class_id' => 2)));
    $result = checkout($products, array('discount' => 20, 'shipping' => true), array(), array('cost' => 10, 'tax_class_id' => 3));
    $allocation = $result['totals'][0]['coupon_allocation'];
    $object = exporter($products, $allocation, $result['totals'][0]['value'], 10);
    $lines = invokePrivate($object, 'getRegularProducts');
    $shipping = invokePrivate($object, 'getShippingProduct');
    // Net: 80 and 40. Gross after proportional tax reductions: 102 and
    // 47.20. The deliberately fixed 2 EUR tax is not coupon-discounted.
    same(10200, lineCents(array($lines[0])), '25 percent tax and fixed tax on first item');
    same(4720, lineCents(array($lines[1])), '13 percent tax and fixed tax on second item');
    same(200, lineCents(array($shipping)), 'Fixed shipping tax retained after free delivery');
    same(15120, lineCents($lines) + lineCents(array($shipping)), 'Whole gross export paid total');
    check(!in_array('coupon', CouponFixtures::$queries), 'Gross export never reads current coupon');
    check(!in_array('product', CouponFixtures::$queries), 'Gross export never reads mutable catalog prices');
    $GLOBALS['coupon_fixture_with_tax'] = 'N';
    try {
        $object = exporter($products, $allocation, $result['totals'][0]['value'], 10);
        same(12000, lineCents(invokePrivate($object, 'getRegularProducts')), 'Tax-exclusive config preserves net merchandise');
        same(0, lineCents(array(invokePrivate($object, 'getShippingProduct'))), 'Tax-exclusive config preserves net free delivery');
    } finally { unset($GLOBALS['coupon_fixture_with_tax']); }
});

scenario('Fractional gross prices reconcile to the rounded paid order total', function () {
    foreach (array(array(10.01, 10.01), array(10.01, 10.05), array(10.05, 10.05), array(10.02, 10.03)) as $prices) {
        $products = array(product(1, $prices[0], 1, array('tax_class_id' => 1)), product(2, $prices[1], 1, array('tax_class_id' => 1)));
        $result = checkout($products, array('discount' => 20));
        $object = exporter($products, $result['totals'][0]['coupon_allocation'], $result['totals'][0]['value']);
        $lines = invokePrivate($object, 'getRegularProducts');
        // 20 percent off a price including 25 percent proportional tax returns
        // exactly the original net price. Fixed taxes add 2 EUR per product.
        $expected = (int) round(($prices[0] + $prices[1] + 4) * 100);
        same($expected, lineCents($lines), 'Gross merchandise agrees with final payment for ' . implode('/', $prices));
        same($expected, lineTotalCents($lines), 'Gross extended lines agree with final payment');
    }
});

scenario('Gross fractional payment follows database storage before currency rounding', function () {
    $products = array(product(1, 0.3333, 1, array('tax_class_id' => 1)));
    $result = checkout($products, array('discount' => 10));
    $allocation = $result['totals'][0]['coupon_allocation'];
    // Gross charged 2.416625 minus stored net coupon 0.0333 and proportional
    // tax discount 0.0083325 leaves 2.3749925. DECIMAL(15,4) stores 2.3750,
    // which the payment provider's two-decimal amount rounds to 2.38.
    $object = exporter($products, $allocation, $result['totals'][0]['value']);
    $lines = invokePrivate($object, 'getRegularProducts');
    same(238, lineCents($lines), 'Stored gross payment rounding');
    same(238, lineTotalCents($lines), 'Stored gross extended-line rounding');
    money(2.38, $allocation['products'][0]['gross_net_total'], 'Admin refund amount follows paid gross amount');
});

scenario('Free shipping with fractional gross prices preserves payment and zero delivery', function () {
    $products = array(product(1, 10.01, 1, array('tax_class_id' => 1)), product(2, 10.01, 1, array('tax_class_id' => 1)));
    $result = checkout($products, array('discount' => 0, 'shipping' => true), array(), array('cost' => 2.50));
    $object = exporter($products, $result['totals'][0]['coupon_allocation'], $result['totals'][0]['value'], 2.50);
    $lines = invokePrivate($object, 'getRegularProducts');
    $shipping = invokePrivate($object, 'getShippingProduct');
    same(2903, lineCents($lines), 'Rounded gross merchandise remains payable');
    same(0, lineCents(array($shipping)), 'Free delivery remains zero after rounding reconciliation');
});

scenario('Generated mixed-tax exports equal actual gross payment', function () {
    mt_srand(20261013);
    $tax = new CouponFixtureTax();
    for ($case = 0; $case < 80; $case++) {
        $products = array(); $grossCharged = 0;
        $count = mt_rand(1, 6);
        for ($id = 1; $id <= $count; $id++) {
            $price = $case % 4 === 0 ? mt_rand(1, 999999) / 10000 : mt_rand(1, 9999) / 100;
            $quantity = mt_rand(1, 7);
            $class = mt_rand(0, 3);
            $products[] = product($id, $price, $quantity, array('tax_class_id' => $class, 'base_price' => $price + 1));
            $grossCharged += ($price + $tax->getTax($price, $class)) * $quantity;
        }
        $shipping = array('cost' => mt_rand(1, 500) / 100, 'tax_class_id' => mt_rand(0, 3));
        $grossCharged += $shipping['cost'] + $tax->getTax($shipping['cost'], $shipping['tax_class_id']);
        $result = checkout($products, array('discount' => mt_rand(1, 90), 'shipping' => $case % 2 === 0), array(), $shipping);
        $coupon = $result['totals'][0];
        $allocation = $coupon['coupon_allocation'];
        assertAllocation($allocation);
        $taxReduction = array_sum(array_column($allocation['products'], 'discount_tax')) + $allocation['shipping_discount_tax'];
        $paidCents = (int) round(round(round($grossCharged + $coupon['value'] - $taxReduction, 4), 2) * 100);
        $object = exporter($products, $allocation, $coupon['value'], $shipping['cost']);
        $lines = invokePrivate($object, 'getRegularProducts');
        $shippingLine = invokePrivate($object, 'getShippingProduct');
        same($paidCents, lineCents($lines) + lineCents(array($shippingLine)), 'Generated gross paid amount, basket ' . $case);
        same($paidCents, lineTotalCents($lines) + lineTotalCents(array($shippingLine)), 'Generated gross extended-line amount, basket ' . $case);
    }
});

echo PHP_EOL . $tests . ' scenarios, ' . $assertions . ' assertions, ' . count($failures) . ' failures' . PHP_EOL;
if ($failures) {
    foreach ($failures as $failure) { fwrite(STDERR, $failure . PHP_EOL); }
    exit(1);
}
exit(0);
