<?php
/**
 * Optional local MySQL regression: php tests/coupon/database.php
 *
 * Clones the five order tables under a random prefix. Application SQL is routed
 * through an adapter that permits only those fixture tables. No live order,
 * coupon, stock, email or Luceed code is called, and all fixtures are dropped.
 */

use Agmedia\LuceedOpencartWrapper\Helpers\CouponAllocation;

if (PHP_SAPI !== 'cli') {
    exit("This regression must run from the command line.\n");
}

$root = dirname(__DIR__, 2);
if (!is_file($root . '/upload/config.php') || !extension_loaded('mysqli')) {
    exit("SKIP: Local configuration and mysqli are required.\n");
}

require $root . '/upload/config.php';
if (!in_array(DB_HOSTNAME, array('127.0.0.1', 'localhost', '::1'), true)) {
    fwrite(STDERR, "Refusing to run: database hostname must be loopback.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int) DB_PORT);
    $connection->set_charset('utf8mb4');
    $connection->query("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION'");
} catch (Throwable $error) {
    exit("SKIP: Local database connection is unavailable.\n");
}

require $root . '/upload/system/engine/registry.php';
require $root . '/upload/system/engine/model.php';
require $root . '/storage/vendor/agmedia/luceed-opencart-wrapper/src/Helpers/CouponAllocation.php';
require $root . '/upload/catalog/model/checkout/order.php';

class CouponFixtureDatabase
{
    private $connection;
    private $tables;

    public function __construct(mysqli $connection, array $tables)
    {
        $this->connection = $connection;
        $this->tables = $tables;
    }

    public function query($sql)
    {
        $replacements = 0;
        $sql = preg_replace_callback('/\b(FROM|INTO|UPDATE|JOIN)\s+`?([a-zA-Z0-9_]+)`?/i', function ($match) use (&$replacements) {
            if (!isset($this->tables[$match[2]])) {
                throw new LogicException('SQL attempted to access a non-fixture table.');
            }
            $replacements++;
            return $match[1] . ' `' . $this->tables[$match[2]] . '`';
        }, $sql);
        if (!$replacements || preg_match('/;\s*\S/', $sql)) {
            throw new LogicException('Only individual fixture-table statements are allowed.');
        }

        try {
            $query = $this->connection->query($sql);
        } catch (Throwable $error) {
            throw new RuntimeException('Fixture SQL failed with database error ' . $this->connection->errno . '.');
        }
        if (!$query instanceof mysqli_result) {
            return true;
        }

        $result = new stdClass();
        $result->rows = $query->fetch_all(MYSQLI_ASSOC);
        $result->num_rows = count($result->rows);
        $result->row = $result->num_rows ? $result->rows[0] : array();
        $query->free();
        return $result;
    }

    public function escape($value)
    {
        return $this->connection->real_escape_string($value);
    }

    public function getLastId()
    {
        return $this->connection->insert_id;
    }
}

class CouponFixtureLoader
{
    public function model($route)
    {
        if ($route !== 'extension/total/voucher') {
            throw new LogicException('Unexpected model dependency in persistence regression.');
        }
    }
}

class CouponFixtureVoucher
{
    public function disableVoucher($orderId) {}
}

class CouponFixtureOrder extends ModelCheckoutOrder
{
    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {}
}

function coupon_database_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function coupon_database_product($optionValueId, $quantity, $price)
{
    return array(
        'product_id' => 987654,
        'name' => 'Ženska haljina',
        'model' => 'FIXTURE-ONLY',
        'quantity' => $quantity,
        'price' => $price,
        'total' => $quantity * $price,
        'tax' => 0,
        'reward' => 0,
        'option' => array(array(
            'product_option_id' => 1234,
            'product_option_value_id' => $optionValueId,
            'name' => 'Veličina',
            'value' => $optionValueId === 1 ? 'M' : 'L',
            'type' => 'select',
        )),
    );
}

function coupon_database_data(array $products, $withCoupon = true)
{
    $strings = array(
        'invoice_prefix', 'store_name', 'store_url', 'firstname', 'lastname', 'email', 'telephone',
        'payment_firstname', 'payment_lastname', 'payment_company', 'payment_address_1', 'payment_address_2',
        'payment_city', 'payment_postcode', 'payment_country', 'payment_zone', 'payment_address_format',
        'payment_method', 'payment_code', 'shipping_firstname', 'shipping_lastname', 'shipping_company',
        'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country',
        'shipping_zone', 'shipping_address_format', 'shipping_method', 'shipping_code', 'comment', 'tracking',
        'forwarded_ip', 'user_agent', 'accept_language',
    );
    $numbers = array(
        'store_id', 'customer_id', 'customer_group_id', 'payment_country_id', 'payment_zone_id',
        'shipping_country_id', 'shipping_zone_id', 'affiliate_id', 'commission', 'marketing_id',
        'language_id', 'currency_id',
    );
    $data = array_fill_keys($strings, '');
    foreach ($numbers as $key) {
        $data[$key] = 0;
    }
    $data['custom_field'] = $data['payment_custom_field'] = $data['shipping_custom_field'] = array();
    $data['currency_code'] = 'EUR';
    $data['currency_value'] = 1;
    $data['ip'] = '127.0.0.1';
    $data['products'] = $products;
    $data['vouchers'] = array();
    $subtotal = array_sum(array_column($products, 'total'));
    $amount = 0;
    $snapshot = array('version' => 1, 'code' => "DB'TEST", 'type' => 'P', 'shipping_discount' => 0, 'products' => array());
    foreach ($products as $product) {
        $discount = $product['total'] * 0.1;
        $amount += $discount;
        $snapshot['products'][] = array(
            'key' => CouponAllocation::productKey($product),
            'product_id' => $product['product_id'],
            'quantity' => $product['quantity'],
            'price' => $product['price'],
            'base_price' => $product['price'] + 5,
            'discount' => $discount,
        );
    }
    $amount = $withCoupon ? round($amount, 4) : 0;
    $snapshot['amount'] = $amount;
    $data['total'] = $subtotal + 2.5 - $amount;
    $data['totals'] = array(
        array('code' => 'sub_total', 'title' => 'Subtotal', 'value' => $subtotal, 'sort_order' => 1),
        array('code' => 'shipping', 'title' => 'Shipping', 'value' => 2.5, 'sort_order' => 3),
    );
    if ($withCoupon) {
        $data['totals'][] = array(
            'code' => 'coupon', 'title' => "Kupon (DB'TEST)", 'value' => -$amount, 'sort_order' => 4,
            'coupon_allocation' => CouponAllocation::finalize($snapshot),
        );
    }
    $data['totals'][] = array('code' => 'total', 'title' => 'Total', 'value' => $data['total'], 'sort_order' => 9);
    return $data;
}

function coupon_database_snapshot(CouponFixtureDatabase $db, $orderId)
{
    $rows = $db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int) $orderId . "' AND code = 'coupon'")->rows;
    coupon_database_assert(count($rows) === 1, 'Expected exactly one persisted coupon total.');
    $snapshot = json_decode($rows[0]['coupon_allocation'], true);
    coupon_database_assert(is_array($snapshot), 'Coupon allocation did not survive MySQL JSON text storage.');
    coupon_database_assert(abs($snapshot['amount'] + (float) $rows[0]['value']) < 0.00001, 'Snapshot amount differs from persisted DECIMAL value.');
    return $snapshot;
}

$tables = array();
$created = array();
$prefix = 'coupon_regression_' . bin2hex(random_bytes(6)) . '_';
$exitCode = 0;
try {
    foreach (array('order', 'order_product', 'order_option', 'order_total', 'order_voucher') as $table) {
        $source = DB_PREFIX . $table;
        coupon_database_assert((bool) preg_match('/^[a-zA-Z0-9_]+$/', $source), 'Unsafe configured table prefix.');
        $fixture = $prefix . $table;
        $connection->query('CREATE TABLE `' . $fixture . '` LIKE `' . $source . '`');
        $created[] = $fixture;
        $tables[$source] = $fixture;
    }

    $totalTable = $tables[DB_PREFIX . 'order_total'];
    $columns = $connection->query('SHOW COLUMNS FROM `' . $totalTable . "` LIKE 'coupon_allocation'");
    if ($columns->num_rows) {
        $connection->query('ALTER TABLE `' . $totalTable . '` DROP COLUMN `coupon_allocation`');
    }
    $connection->query('INSERT INTO `' . $totalTable . "` SET order_id = 0, code = 'coupon', title = 'Legacy fixture', value = -1, sort_order = 4");
    $migration = file_get_contents($root . '/storage/migrations/37_add_coupon_allocation_to_order_total.sql');
    coupon_database_assert($migration !== false, 'Coupon allocation migration is unavailable.');
    $migration = str_replace('oc_order_total', $totalTable, $migration);
    for ($run = 0; $run < 2; $run++) {
        $connection->multi_query($migration);
        do {
            $result = $connection->store_result();
            if ($result instanceof mysqli_result) {
                $result->free();
            }
        } while ($connection->more_results() && $connection->next_result());
    }
    $legacy = $connection->query('SELECT coupon_allocation FROM `' . $totalTable . '` WHERE order_id = 0')->fetch_assoc();
    coupon_database_assert($legacy['coupon_allocation'] === null, 'Migration must preserve NULL allocation on historical totals.');
    echo "PASS: Migration creates the column, reruns safely and preserves historical NULL values.\n";

    $db = new CouponFixtureDatabase($connection, $tables);
    $registry = new Registry();
    $registry->set('db', $db);
    $registry->set('load', new CouponFixtureLoader());
    $registry->set('model_extension_total_voucher', new CouponFixtureVoucher());
    $order = new CouponFixtureOrder($registry);
    $products = array(coupon_database_product(1, 1, 12.3456), coupon_database_product(1, 1, 12.3456), coupon_database_product(2, 2, 18.99));
    $data = coupon_database_data($products);
    $orderId = $order->addOrder($data);
    $saved = coupon_database_snapshot($db, $orderId);
    $savedIds = array_map('intval', array_column($saved['products'], 'order_product_id'));
    $actual = $db->query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int) $orderId . "' ORDER BY order_product_id")->rows;
    coupon_database_assert($savedIds === array_map('intval', array_column($actual, 'order_product_id')), 'Allocation IDs do not match inserted order-product IDs.');
    coupon_database_assert(count(array_unique($savedIds)) === 3, 'Duplicate cart lines must receive distinct order-product IDs.');
    coupon_database_assert($saved['code'] === "DB'TEST", 'Quoted coupon code was changed by JSON/SQL escaping.');
    coupon_database_assert((float) $actual[0]['price'] === 12.3456, 'Four-decimal checkout price was changed by persistence.');
    echo "PASS: addOrder persists exact prices and binds duplicate lines plus distinct variants to real IDs.\n";

    $products[2] = coupon_database_product(2, 3, 17.1234);
    $order->editOrder($orderId, coupon_database_data($products));
    $edited = coupon_database_snapshot($db, $orderId);
    $editedIds = array_map('intval', array_column($edited['products'], 'order_product_id'));
    coupon_database_assert(!array_intersect($savedIds, $editedIds), 'Edit must replace obsolete allocation product IDs.');
    coupon_database_assert($edited['products'][2]['quantity'] === 3, 'Edited quantity was not captured.');
    $actual = $db->query("SELECT order_product_id FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int) $orderId . "' ORDER BY order_product_id")->rows;
    coupon_database_assert($editedIds === array_map('intval', array_column($actual, 'order_product_id')), 'Edited allocation IDs do not match current products.');
    echo "PASS: editOrder replaces allocation and rebinds newly inserted product IDs.\n";

    $order->editOrder($orderId, coupon_database_data($products, false));
    $totals = $db->query("SELECT code, coupon_allocation FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int) $orderId . "'")->rows;
    foreach ($totals as $total) {
        coupon_database_assert($total['code'] !== 'coupon' && $total['coupon_allocation'] === null, 'Removing the coupon retained a stale allocation.');
    }
    echo "PASS: Removing a coupon on edit clears its stored allocation.\n";

    $legacyData = coupon_database_data($products);
    foreach ($legacyData['totals'] as &$total) {
        unset($total['coupon_allocation']);
    }
    unset($total);
    $legacyId = $order->addOrder($legacyData);
    $legacy = $db->query("SELECT coupon_allocation FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int) $legacyId . "' AND code = 'coupon'")->row;
    coupon_database_assert(array_key_exists('coupon_allocation', $legacy) && $legacy['coupon_allocation'] === null, 'Legacy callers must remain compatible without allocation metadata.');
    echo "PASS: Callers without allocation metadata retain the historical export path.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    foreach (array_reverse($created) as $fixture) {
        try {
            $connection->query('DROP TABLE `' . $fixture . '`');
        } catch (Throwable $error) {
            fwrite(STDERR, "FAIL: A transient fixture table could not be removed.\n");
            $exitCode = 1;
        }
    }
    $connection->close();
}
exit($exitCode);
