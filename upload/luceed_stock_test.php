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

function luceedStockTestCall(
    string $method,
    string $requestPath,
    callable $callback,
    bool $includeRaw = false,
    array $warehousesByUid = [],
    array $focusUids = []
): array
{
    $raw = $callback();
    $decoded = luceedStockTestDecode($raw);
    $positiveRows = luceedStockTestPositiveRows($decoded);
    $focusRows = luceedStockTestFocusRows($decoded, $warehousesByUid, $focusUids);

    return [
        'method' => $method,
        'request_path' => $requestPath,
        'result_blocks' => $decoded ? count($decoded['result'] ?? []) : 0,
        'stanje_rows' => luceedStockTestCountRows($decoded),
        'positive_locations' => count($positiveRows),
        'positive_rows' => $positiveRows,
        'focus_rows' => $focusRows,
        'decoded_response' => $includeRaw ? $decoded : null,
        'response_raw' => $decoded ? null : substr((string) $raw, 0, 4000),
    ];
}

function luceedStockTestWarehouseStoreCode(array $warehouse): string
{
    $pj = trim((string) ($warehouse['pj'] ?? ''));

    if ($pj !== '') {
        return $pj;
    }

    return trim((string) ($warehouse['skladiste'] ?? ''));
}

function luceedStockTestFocusRows(
    ?array $decoded,
    array $warehousesByUid,
    array $focusUids
): array {
    if (! $decoded || empty($focusUids)) {
        return [];
    }

    $rows = [];

    foreach (($decoded['result'] ?? []) as $resultRow) {
        foreach (($resultRow['stanje'] ?? []) as $stockRow) {
            $uid = trim((string) ($stockRow['skladiste_uid'] ?? ''));

            if ($uid === '' || ! isset($focusUids[$uid])) {
                continue;
            }

            $warehouse = $warehousesByUid[$uid] ?? [];

            $rows[] = [
                'uid' => $uid,
                'qty' => luceedStockTestQty($stockRow['raspolozivo_kol'] ?? 0),
                'artikl_uid' => $stockRow['artikl_uid'] ?? '',
                'warehouse_code' => $warehouse['skladiste'] ?? '',
                'store_code' => luceedStockTestWarehouseStoreCode($warehouse),
                'warehouse_name' => $warehouse['naziv'] ?? '',
                'store_name' => $warehouse['pj_naziv'] ?? '',
            ];
        }
    }

    return $rows;
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
$focusStore = '';

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

if (PHP_SAPI === 'cli' && isset($argv[4])) {
    $focusStore = strtoupper(trim((string) $argv[4]));
}

if ($focusStore === '') {
    $focusStore = strtoupper(trim((string) ($_GET['focus_store'] ?? '')));
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
$locationsByStore = [];
$result = $db->query(
    "SELECT skladiste, skladiste_uid, name, vidljivost FROM `" . $db->real_escape_string($locationTable) . "` WHERE skladiste != '' ORDER BY location_id"
);

if (! $result) {
    luceedStockTestRespond([
        'error' => 'Failed to query location units.',
        'details' => $db->error,
    ], 500);
}

while ($row = $result->fetch_assoc()) {
    $units[] = $row['skladiste'];
    $locationsByStore[$row['skladiste']][] = $row;
}

$units = array_values(array_unique($units));
$unitsQuery = '[' . implode(',', $units) . ']';
$calls = [];
$warehousesByUid = [];
$focusCandidates = [];

$warehouseJson = function_exists('agconf') ? agconf('import.warehouse.json') : null;

if ($warehouseJson && is_file($warehouseJson)) {
    $warehouseRows = json_decode(file_get_contents($warehouseJson), true) ?: [];

    foreach ($warehouseRows as $warehouseRow) {
        if (! empty($warehouseRow['skladiste_uid'])) {
            $warehousesByUid[$warehouseRow['skladiste_uid']] = $warehouseRow;
        }

        if (
            $focusStore !== ''
            && (
                strtoupper(trim((string) ($warehouseRow['pj'] ?? ''))) === $focusStore
                || strtoupper(trim((string) ($warehouseRow['skladiste'] ?? ''))) === $focusStore
            )
        ) {
            $focusCandidates[] = [
                'uid' => $warehouseRow['skladiste_uid'] ?? '',
                'warehouse_code' => $warehouseRow['skladiste'] ?? '',
                'store_code' => luceedStockTestWarehouseStoreCode($warehouseRow),
                'warehouse_name' => $warehouseRow['naziv'] ?? '',
                'store_name' => $warehouseRow['pj_naziv'] ?? '',
                'source' => 'warehouse_json',
            ];
        }
    }
}

if ($focusStore !== '' && isset($locationsByStore[$focusStore])) {
    foreach ($locationsByStore[$focusStore] as $locationRow) {
        $focusCandidates[] = [
            'uid' => $locationRow['skladiste_uid'] ?? '',
            'warehouse_code' => $locationRow['skladiste'] ?? '',
            'store_code' => $locationRow['skladiste'] ?? '',
            'warehouse_name' => $locationRow['name'] ?? '',
            'store_name' => $locationRow['name'] ?? '',
            'source' => 'oc_location',
            'visible' => (int) ($locationRow['vidljivost'] ?? 0),
        ];
    }
}

$focusUids = [];

foreach ($focusCandidates as $candidate) {
    if (! empty($candidate['uid'])) {
        $focusUids[$candidate['uid']] = true;
    }
}

$calls[] = luceedStockTestCall(
    'stock_by_sifra',
    'StanjeZalihe/Skladiste/' . $unitsQuery . '/' . urlencode($sifra),
    function () use ($unitsQuery, $sifra) {
        return \Agmedia\Luceed\Facade\LuceedProduct::stock($unitsQuery, urlencode($sifra));
    },
    $includeRaw,
    $warehousesByUid,
    $focusUids
);

if ($sku !== '') {
    $calls[] = luceedStockTestCall(
        'stock_by_sku',
        'StanjeZalihe/Skladiste/' . $unitsQuery . '/' . urlencode($sku),
        function () use ($unitsQuery, $sku) {
            return \Agmedia\Luceed\Facade\LuceedProduct::stock($unitsQuery, urlencode($sku));
        },
        $includeRaw,
        $warehousesByUid,
        $focusUids
    );

    $calls[] = luceedStockTestCall(
        'individual_stock_by_sku',
        'StanjeZalihe/ArtiklUID/' . rawurlencode($sku) . '/' . $unitsQuery,
        function () use ($unitsQuery, $sku) {
            return \Agmedia\Luceed\Facade\LuceedProduct::individualStock($sku, $unitsQuery);
        },
        $includeRaw,
        $warehousesByUid,
        $focusUids
    );
}

luceedStockTestRespond([
    'sifra' => $sifra,
    'sku' => $sku,
    'include_raw' => $includeRaw,
    'focus_store' => $focusStore,
    'focus_candidates' => array_values($focusCandidates),
    'units_query' => $unitsQuery,
    'calls' => $calls,
]);
