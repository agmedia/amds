<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

if (is_file(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

if (defined('DIR_STORAGE') && is_file(DIR_STORAGE . 'vendor/autoload.php')) {
    require_once DIR_STORAGE . 'vendor/autoload.php';
}

function luceedStockTestRespond(array $payload, int $status = 200): void
{
    if (! headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function luceedStockTestQty($value): float
{
    return (float) str_replace(',', '.', (string) $value);
}

function luceedStockTestDecode($raw): ?array
{
    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : null;
}

function luceedStockTestPositiveRows(?array $decoded): array
{
    if (! $decoded) {
        return [];
    }

    $rows = [];

    foreach (($decoded['result'] ?? []) as $resultRow) {
        foreach (($resultRow['stanje'] ?? []) as $stockRow) {
            $qty = luceedStockTestQty($stockRow['raspolozivo_kol'] ?? 0);
            $uid = trim((string) ($stockRow['skladiste_uid'] ?? ''));

            if ($qty <= 0 || $uid === '') {
                continue;
            }

            $rows[$uid] = [
                'uid' => $uid,
                'qty' => $qty,
                'artikl' => $stockRow['artikl'] ?? '',
                'artikl_uid' => $stockRow['artikl_uid'] ?? '',
                'skladiste' => $stockRow['skladiste'] ?? '',
                'skladiste_uid' => $uid,
            ];
        }
    }

    return array_values($rows);
}

function luceedStockTestCountRows(?array $decoded): int
{
    if (! $decoded) {
        return 0;
    }

    $count = 0;

    foreach (($decoded['result'] ?? []) as $resultRow) {
        $count += count($resultRow['stanje'] ?? []);
    }

    return $count;
}

function luceedStockTestCall(string $method, string $requestPath, callable $callback, bool $includeRaw = false): array
{
    $raw = $callback();
    $decoded = luceedStockTestDecode($raw);
    $positiveRows = luceedStockTestPositiveRows($decoded);

    return [
        'method' => $method,
        'request_path' => $requestPath,
        'result_blocks' => $decoded ? count($decoded['result'] ?? []) : 0,
        'stanje_rows' => luceedStockTestCountRows($decoded),
        'positive_locations' => count($positiveRows),
        'positive_rows' => $positiveRows,
        'decoded_response' => $includeRaw ? $decoded : null,
        'response_raw' => $decoded ? null : substr((string) $raw, 0, 4000),
    ];
}

if (PHP_SAPI !== 'cli' && defined('WSPAY_CRON_KEY')) {
    $key = (string) ($_GET['key'] ?? '');

    if ($key === '' || ! hash_equals((string) WSPAY_CRON_KEY, $key)) {
        luceedStockTestRespond([
            'error' => 'Unauthorized',
        ], 403);
    }
}

$sifra = '';
$sku = '';
$includeRaw = false;

if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $sifra = trim((string) $argv[1]);
}

if (PHP_SAPI === 'cli' && isset($argv[2])) {
    $sku = trim((string) $argv[2]);
}

if ($sifra === '') {
    $sifra = trim((string) ($_GET['sifra'] ?? ''));
}

if ($sku === '') {
    $sku = trim((string) ($_GET['sku'] ?? ''));
}

if (PHP_SAPI === 'cli' && isset($argv[3])) {
    $includeRaw = in_array(strtolower((string) $argv[3]), ['1', 'true', 'yes', 'raw'], true);
}

if (isset($_GET['raw'])) {
    $includeRaw = in_array(strtolower((string) $_GET['raw']), ['1', 'true', 'yes', 'raw'], true);
}

if ($sifra === '') {
    luceedStockTestRespond([
        'error' => 'Missing `sifra` parameter.',
        'hint' => 'Use ?sifra=134264-S26 or run `php luceed_stock_test.php 134264-S26`.',
    ], 400);
}

if (! defined('DB_HOSTNAME') || ! class_exists(\Agmedia\Luceed\Facade\LuceedProduct::class)) {
    luceedStockTestRespond([
        'error' => 'OpenCart or vendor bootstrap failed.',
    ], 500);
}

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int) DB_PORT);

if ($db->connect_error) {
    luceedStockTestRespond([
        'error' => 'Database connection failed.',
        'details' => $db->connect_error,
    ], 500);
}

$locationTable = DB_PREFIX . 'location';
$units = [];
$result = $db->query(
    "SELECT skladiste FROM `" . $db->real_escape_string($locationTable) . "` WHERE skladiste != '' ORDER BY location_id"
);

if (! $result) {
    luceedStockTestRespond([
        'error' => 'Failed to query location units.',
        'details' => $db->error,
    ], 500);
}

while ($row = $result->fetch_assoc()) {
    $units[] = $row['skladiste'];
}

$units = array_values(array_unique($units));
$unitsQuery = '[' . implode(',', $units) . ']';
$calls = [];

$calls[] = luceedStockTestCall(
    'stock_by_sifra',
    'StanjeZalihe/Skladiste/' . $unitsQuery . '/' . urlencode($sifra),
    function () use ($unitsQuery, $sifra) {
        return \Agmedia\Luceed\Facade\LuceedProduct::stock($unitsQuery, urlencode($sifra));
    },
    $includeRaw
);

if ($sku !== '') {
    $calls[] = luceedStockTestCall(
        'stock_by_sku',
        'StanjeZalihe/Skladiste/' . $unitsQuery . '/' . urlencode($sku),
        function () use ($unitsQuery, $sku) {
            return \Agmedia\Luceed\Facade\LuceedProduct::stock($unitsQuery, urlencode($sku));
        },
        $includeRaw
    );

    $calls[] = luceedStockTestCall(
        'individual_stock_by_sku',
        'StanjeZalihe/ArtiklUID/' . rawurlencode($sku) . '/' . $unitsQuery,
        function () use ($unitsQuery, $sku) {
            return \Agmedia\Luceed\Facade\LuceedProduct::individualStock($sku, $unitsQuery);
        },
        $includeRaw
    );
}

luceedStockTestRespond([
    'sifra' => $sifra,
    'sku' => $sku,
    'include_raw' => $includeRaw,
    'units_query' => $unitsQuery,
    'calls' => $calls,
]);
