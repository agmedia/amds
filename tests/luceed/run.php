<?php
/** Offline inventory regressions: php tests/luceed/run.php */
if (PHP_SAPI !== 'cli') { exit(1); }
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});

class LuceedFixtureProduct
{
    public static $models = [];
    public static function query() { return new self(); }
    public function pluck($field) { return collect(self::$models); }
}
class LuceedFixtureDatabase
{
    public static $queries = [];
    public static $connections = 0;
    public static $failOn = null;
    public function __construct($database) { self::$connections++; }
    public function query($sql) {
        self::$queries[] = $sql;
        if (self::$failOn !== null && strpos($sql, self::$failOn) === 0) {
            throw new RuntimeException('Injected database failure');
        }
        return true;
    }
}
class LuceedFixtureLog { public static function store($message, $file = '') {} }
class LuceedHttpFixture
{
    public static $body = '{}';
    public static $status = 200;
    public static $error = 0;
    public static $options = [];
}
function agconf($key) {
    $configuration = [
        'env' => 'production', 'service.base_url' => 'http://fixture.invalid/',
        'service.username' => 'fixture', 'service.password' => 'fixture',
        'import.default_category' => 0, 'import.default_language' => 2,
        'import.image_path' => '', 'import.default_stock_full' => 7,
        'import.default_stock_empty' => 5,
    ];
    return $configuration[$key] ?? null;
}
class_alias('LuceedFixtureProduct', 'Agmedia\\Models\\Product\\Product');
class_alias('LuceedFixtureDatabase', 'Agmedia\\Helpers\\Database');
class_alias('LuceedFixtureLog', 'Agmedia\\Helpers\\Log');
define('DB_DATABASE', 'offline_fixture');
define('DB_PREFIX', 'oc_');
require __DIR__ . '/http_fixture.php';
require __DIR__ . '/../../storage/vendor/autoload.php';

use Agmedia\LuceedOpencartWrapper\Models\LOC_Product;
use Agmedia\Luceed\Connection\LuceedService;

$assertions = 0;
function check($condition, $message) {
    global $assertions;
    $assertions++;
    if (!$condition) { throw new RuntimeException($message); }
}
function rejects(callable $callback, $message) {
    $caught = false;
    try { $callback(); } catch (Throwable $exception) { $caught = true; }
    check($caught, $message);
}
function productRow($code, $quantity = 10, $parent = null, $price = 20) {
    return [
        'artikl' => $code, 'naziv' => 'Fixture', 'enabled' => 'D', 'webshop' => 'D',
        'osnovni__artikl' => $parent, 'raspolozivo_kol' => $quantity, 'mpc' => $price,
        'artikl_uid' => 'uid-' . $code, 'barcode' => $code, 'velicina_uid' => 'size',
        'velicina' => 'M', 'velicina_naziv' => 'M',
    ];
}
function payload($rows) { return json_encode(['result' => [['artikli' => $rows]]]); }
function resetDatabase() {
    LuceedFixtureDatabase::$queries = [];
    LuceedFixtureDatabase::$connections = 0;
    LuceedFixtureDatabase::$failOn = null;
}

// All upstream validation must happen before any database write/connection.
foreach (['<html>gateway failure</html>', '{}', payload([]), payload([['artikl' => 'bad']]),
    payload([productRow('bad', 'unknown')]), payload([productRow('bad', 10, null, 'unknown')])] as $invalid) {
    resetDatabase();
    rejects(function () use ($invalid) { new LOC_Product($invalid); }, 'Invalid source data was accepted');
    check(LuceedFixtureDatabase::$connections === 0, 'Invalid source data reached inventory database');
}

// Indexed lookups retain duplicate ordering and numeric-string comparisons.
LuceedFixtureProduct::$models = ['131303', '001', 'DUP', 'MISSING'];
$inventory = new LOC_Product(payload([
    productRow('131303', 31), productRow('131303-M', 0, '131303'), productRow('131303-L', 7, '131303'),
    productRow('1', 10, null, 11), productRow('001', 10, null, 22),
    productRow('DUP', 10, null, 33), productRow('DUP', 10, null, 44),
]));
$inventory->sortForUpdate();
$sorted = $inventory->getProductsToAdd()->all();
check(count($sorted) === 3, 'Existing product matching changed');
check($sorted['001']->mpc === 11, 'Numeric identifiers no longer preserve the first loose match');
check($sorted['DUP']->mpc === 33, 'Duplicate identifiers no longer preserve the first match');
check(count($sorted['131303']->opcije) === 1 && $sorted['131303']->opcije[0]['artikl'] === '131303-L', 'Unavailable size filtering changed');

resetDatabase();
check($inventory->update() === 3, 'Successful update count changed');
$queries = LuceedFixtureDatabase::$queries;
check($queries[0] === 'START TRANSACTION' && end($queries) === 'COMMIT', 'Inventory updates are not committed together');
check(LuceedFixtureDatabase::$connections === 1, 'Parent and option updates use different transactions');
check(strpos($queries[1], 'DELETE FROM `oc_product_temp`') === 0, 'Interrupted staging data is not cleared first');

resetDatabase();
LuceedFixtureDatabase::$failOn = 'UPDATE oc_product_option_value';
rejects(function () use ($inventory) { $inventory->update(); }, 'Database failure was swallowed');
check(end(LuceedFixtureDatabase::$queries) === 'ROLLBACK', 'Partial inventory failure is not rolled back');
check(!in_array('COMMIT', LuceedFixtureDatabase::$queries, true), 'Failed inventory update was committed');

// A complete, valid snapshot with no available stock must remove stale stock.
resetDatabase();
LuceedFixtureProduct::$models = ['ZERO'];
$zero = new LOC_Product(payload([productRow('ZERO', 0), productRow('ZERO-M', 0, 'ZERO')]));
$zero->sortForUpdate()->update();
check(in_array('UPDATE oc_product p SET p.quantity = 0, p.status = 0', LuceedFixtureDatabase::$queries, true), 'All-zero snapshot leaves parent stock available');
check(in_array('UPDATE oc_product_option_value p SET p.quantity = 0', LuceedFixtureDatabase::$queries, true), 'All-zero snapshot leaves size stock available');

resetDatabase();
LuceedFixtureProduct::$models = ['ABSENT'];
$unmatched = new LOC_Product(payload([productRow('OTHER')]));
$unmatched->sortForUpdate();
rejects(function () use ($unmatched) { $unmatched->update(); }, 'Unmatched response may erase stock');
check(LuceedFixtureDatabase::$connections === 0, 'Unmatched response reached inventory writes');

$service = new LuceedService();
LuceedHttpFixture::$body = payload([productRow('OK')]);
check($service->getValidated('artikli/lista/') === LuceedHttpFixture::$body, 'Successful upstream response changed');
check(LuceedHttpFixture::$options[CURLOPT_CONNECTTIMEOUT] === 10 && LuceedHttpFixture::$options[CURLOPT_TIMEOUT] === 180, 'Upstream request has no bounded timeout');
LuceedHttpFixture::$status = 503;
rejects(function () use ($service) { $service->getValidated('artikli/lista/'); }, 'HTTP failure accepted as stock');
LuceedHttpFixture::$status = 200;
LuceedHttpFixture::$body = '';
rejects(function () use ($service) { $service->getValidated('artikli/lista/'); }, 'Empty upstream body accepted');
LuceedHttpFixture::$body = false;
LuceedHttpFixture::$error = 28;
rejects(function () use ($service) { $service->getValidated('artikli/lista/'); }, 'Network timeout accepted as stock');

// A second inventory route must not modify the shared staging table mid-sync.
require __DIR__ . '/../../upload/system/engine/registry.php';
require __DIR__ . '/../../upload/system/engine/controller.php';
require __DIR__ . '/../../upload/system/library/response.php';
require __DIR__ . '/../../upload/admin/controller/extension/module/luceed_sync.php';
$lockDirectory = sys_get_temp_dir() . '/amds_luceed_lock_' . bin2hex(random_bytes(6));
mkdir($lockDirectory);
define('DIR_STORAGE', $lockDirectory . '/');
$registry = new Registry();
$response = new Response();
$registry->set('response', $response);
$first = new ControllerExtensionModuleLuceedSync($registry);
$second = new ControllerExtensionModuleLuceedSync($registry);
$acquire = new ReflectionMethod($first, 'acquireSyncLock'); $acquire->setAccessible(true);
$release = new ReflectionMethod($first, 'releaseSyncLock'); $release->setAccessible(true);
$guard = new ReflectionMethod($first, 'runGuardedSync'); $guard->setAccessible(true);
try {
    check($acquire->invoke($first, 'product_inventory'), 'Could not acquire fixture inventory lock');
    $called = false;
    $guard->invoke($second, 'updatePrices', function () use (&$called) { $called = true; });
    check(!$called && json_decode($response->getOutput(), true)['status'] === 300, 'Overlapping price route bypasses inventory lock');
    $release->invoke($first, 'product_inventory');
    check($guard->invoke($second, 'updatePrices', function () { return 42; }) === 42, 'Skipped route leaves its lock held');
    rejects(function () use ($guard, $second) {
        $guard->invoke($second, 'updatePricesAndQuantities', function () { throw new RuntimeException('Injected callback failure'); });
    }, 'Guard swallowed an inventory failure');
    check($guard->invoke($first, 'updateQuantities', function () { return 43; }) === 43, 'Failed inventory callback left the common lock held');
} finally {
    foreach ([$first, $second] as $controller) {
        foreach (['product_inventory', 'updatePrices', 'updateQuantities', 'updatePricesAndQuantities'] as $name) {
            $release->invoke($controller, $name);
        }
    }
    foreach (glob($lockDirectory . '/locks/*') as $file) { unlink($file); }
    if (is_dir($lockDirectory . '/locks')) { rmdir($lockDirectory . '/locks'); }
    rmdir($lockDirectory);
}

// Exercise the real CLI process and its exit/logging contract with fake services.
$fixture = __DIR__ . '/cli-fixture/admin';
$logDirectory = sys_get_temp_dir() . '/amds_luceed_cli_' . bin2hex(random_bytes(6));
mkdir($logDirectory);
function cliCase($arguments, $result, $logDirectory) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../../bin/luceed-sync.php');
    foreach ($arguments as $argument) { $command .= ' ' . escapeshellarg($argument); }
    $environment = getenv();
    $environment['LUCEED_CLI_FIXTURE_RESULT'] = $result;
    $environment['LUCEED_CLI_FIXTURE_LOG'] = $logDirectory;
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    return [proc_close($process), $stdout, $stderr];
}
try {
    $arguments = ['--admin-dir=' . $fixture];
    $success = cliCase($arguments, '{"status":200,"message":"Updated fixture"}', $logDirectory);
    check($success[0] === 0 && json_decode($success[1], true)['status'] === 200, 'CLI success is not machine-readable');
    check(strpos(file_get_contents($logDirectory . '/luceed_sync_cli.log'), 'SUCCESS') !== false, 'Successful completion not logged');
    $duplicate = cliCase($arguments, '{"status":300,"message":"Sync already running."}', $logDirectory);
    check($duplicate[0] === 1 && strpos($duplicate[2], 'FAILED') !== false, 'Skipped job reported success');
    $exception = cliCase($arguments, 'throw', $logDirectory);
    check($exception[0] === 1 && strpos($exception[2], 'Fixture exception') !== false, 'CLI exception reported success');
    $noResult = cliCase($arguments, 'exit', $logDirectory);
    check($noResult[0] === 1 && strpos($noResult[2], 'before a completed sync result') !== false, 'Premature PHP exit reported success');
    $dryRun = cliCase(array_merge($arguments, ['--check']), 'throw', $logDirectory);
    check($dryRun[0] === 0 && strpos($dryRun[2], 'CHECK OK') !== false, 'CLI check unexpectedly executed inventory sync');
    $nonTransactional = cliCase($arguments, 'myisam', $logDirectory);
    check($nonTransactional[0] === 1 && strpos($nonTransactional[2], 'must use InnoDB') !== false
        && strpos($nonTransactional[2], 'START') === false, 'Nontransactional tables allowed inventory writes');
} finally {
    foreach (glob($logDirectory . '/*') as $file) { unlink($file); }
    rmdir($logDirectory);
}

echo $assertions . " inventory assertions passed; no network or real database used.\n";
