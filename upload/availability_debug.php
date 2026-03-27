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

function availabilityDebugRespond(array $payload, int $status = 200): void
{
    if (! headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function availabilityDebugQty($value): float
{
    return (float) str_replace(',', '.', (string) $value);
}

function availabilityDebugIsVisibleLocation(array $location): bool
{
    return (int) ($location['vidljivost'] ?? 0) === 1
        && ! empty($location['skladiste'])
        && ! empty($location['skladiste_uid']);
}

function availabilityDebugNormalizeLocation(array $location, string $matchType = 'uid', array $extra = []): array
{
    return array_merge([
        'name'       => $location['name'] ?? '',
        'uid'        => $location['skladiste_uid'] ?? '',
        'store_code' => $location['skladiste'] ?? '',
        'geocode'    => $location['geocode'] ?? '',
        'address'    => $location['address'] ?? '',
        'telephone'  => $location['telephone'] ?? '',
        'email'      => $location['fax'] ?? '',
        'open'       => $location['open'] ?? '',
        'visible'    => (int) ($location['vidljivost'] ?? 0),
        'match_type' => $matchType,
    ], $extra);
}

function availabilityDebugWarehouseDisplayCode(array $warehouse): string
{
    $code = trim((string) ($warehouse['pj'] ?? ''));

    if ($code !== '') {
        return $code;
    }

    return trim((string) ($warehouse['skladiste'] ?? ''));
}

function availabilityDebugWarehouseLooksLikeStore(array $warehouse): bool
{
    $code = availabilityDebugWarehouseDisplayCode($warehouse);

    if ($code === '') {
        return false;
    }

    return (bool) preg_match('/^(D|K|P)[0-9A-Z]+$/', $code);
}

function availabilityDebugWarehouseAddress(array $warehouse): string
{
    $parts = [];

    foreach (['adresa', 'postanski_broj', 'mjesto'] as $field) {
        $value = trim((string) ($warehouse[$field] ?? ''));

        if ($value !== '') {
            $parts[] = $value;
        }
    }

    return implode(', ', array_unique($parts));
}

function availabilityDebugNormalizeWarehouse(array $warehouse, array $extra = []): array
{
    return array_merge([
        'name'       => trim((string) ($warehouse['pj_naziv'] ?? $warehouse['naziv'] ?? '')),
        'uid'        => trim((string) ($warehouse['skladiste_uid'] ?? '')),
        'store_code' => availabilityDebugWarehouseDisplayCode($warehouse),
        'geocode'    => '',
        'address'    => availabilityDebugWarehouseAddress($warehouse),
        'telephone'  => trim((string) ($warehouse['telefon'] ?? '')),
        'email'      => trim((string) ($warehouse['e_mail'] ?? '')),
        'open'       => '',
        'visible'    => 1,
        'match_type' => 'warehouse_fallback',
    ], $extra);
}

if (PHP_SAPI !== 'cli' && defined('WSPAY_CRON_KEY')) {
    $key = (string) ($_GET['key'] ?? '');

    if ($key === '' || ! hash_equals((string) WSPAY_CRON_KEY, $key)) {
        availabilityDebugRespond([
            'error' => 'Unauthorized',
        ], 403);
    }
}

$sifra = '';

if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $sifra = trim((string) $argv[1]);
}

if ($sifra === '') {
    $sifra = trim((string) ($_GET['sifra'] ?? ''));
}

if ($sifra === '') {
    availabilityDebugRespond([
        'error' => 'Missing `sifra` parameter.',
        'hint'  => 'Use ?sifra=134264-S26 or run `php availability_debug.php 134264-S26`.',
    ], 400);
}

if (! defined('DB_HOSTNAME') || ! class_exists(\Agmedia\Luceed\Facade\LuceedProduct::class)) {
    availabilityDebugRespond([
        'error' => 'OpenCart or vendor bootstrap failed.',
    ], 500);
}

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int) DB_PORT);

if ($db->connect_error) {
    availabilityDebugRespond([
        'error'   => 'Database connection failed.',
        'details' => $db->connect_error,
    ], 500);
}

$locationTable = DB_PREFIX . 'location';
$locations     = [];
$locationsByUid = [];
$locationsByStoreCode = [];
$badLocationRows = [];

$result = $db->query(
    "SELECT location_id, skladiste, skladiste_uid, vidljivost, name, address, telephone, fax, geocode, open
     FROM `" . $db->real_escape_string($locationTable) . "` 
     ORDER BY location_id"
);

if (! $result) {
    availabilityDebugRespond([
        'error'   => 'Failed to query locations.',
        'details' => $db->error,
    ], 500);
}

while ($row = $result->fetch_assoc()) {
    $locations[] = $row;

    if (! empty($row['skladiste_uid'])) {
        $locationsByUid[$row['skladiste_uid']] = $row;
    }

    if (! empty($row['skladiste'])) {
        if (! isset($locationsByStoreCode[$row['skladiste']])) {
            $locationsByStoreCode[$row['skladiste']] = [];
        }

        $locationsByStoreCode[$row['skladiste']][] = $row;
    }

    if ((int) ($row['vidljivost'] ?? 0) === 1) {
        if (empty($row['skladiste']) || empty($row['skladiste_uid'])) {
            $badLocationRows[] = array_merge($row, [
                'issue' => 'Visible location is missing `skladiste` or `skladiste_uid`.',
            ]);
        } elseif (! preg_match('/^[0-9]+-2987$/', (string) $row['skladiste_uid'])) {
            $badLocationRows[] = array_merge($row, [
                'issue' => 'Visible location has unexpected `skladiste_uid` format.',
            ]);
        }
    }
}

$warehouseRows = [];
$warehousesByUid = [];
$warehouseJson = function_exists('agconf') ? agconf('import.warehouse.json') : null;

if ($warehouseJson && is_file($warehouseJson)) {
    $warehouseRows = json_decode(file_get_contents($warehouseJson), true) ?: [];

    foreach ($warehouseRows as $warehouseRow) {
        if (! empty($warehouseRow['skladiste_uid'])) {
            $warehousesByUid[$warehouseRow['skladiste_uid']] = $warehouseRow;
        }
    }
}

$units = [];

foreach ($locations as $location) {
    if (! empty($location['skladiste'])) {
        $units[] = $location['skladiste'];
    }
}

$units = array_values(array_unique($units));
$unitsQuery = '[' . implode(',', $units) . ']';

$rawStock = \Agmedia\Luceed\Facade\LuceedProduct::stock($unitsQuery, urlencode($sifra));
$decodedStock = json_decode((string) $rawStock, true);

if (! is_array($decodedStock)) {
    availabilityDebugRespond([
        'error'        => 'Luceed response is not valid JSON.',
        'sifra'        => $sifra,
        'units_query'  => $unitsQuery,
        'response_raw' => substr((string) $rawStock, 0, 1500),
    ], 500);
}

$availables = $decodedStock['result'][0]['stanje'] ?? [];

$aggregatedAvailables = [];

foreach ($availables as $available) {
    $qty = availabilityDebugQty($available['raspolozivo_kol'] ?? 0);
    $uid = trim((string) ($available['skladiste_uid'] ?? ''));

    if ($qty <= 0 || $uid === '') {
        continue;
    }

    if (! isset($aggregatedAvailables[$uid])) {
        $aggregatedAvailables[$uid] = [
            'skladiste_uid'   => $uid,
            'raspolozivo_kol' => 0,
        ];
    }

    $aggregatedAvailables[$uid]['raspolozivo_kol'] += $qty;
}

$luceedPositive = [];
$currentWebLocations = [];
$expandedLocations = [];
$missingMappings = [];
$hiddenOrBrokenMappings = [];

foreach ($aggregatedAvailables as $available) {
    $qty = availabilityDebugQty($available['raspolozivo_kol']);
    $uid = trim((string) $available['skladiste_uid']);

    $warehouse = $warehousesByUid[$uid] ?? [];
    $storeCode = availabilityDebugWarehouseDisplayCode($warehouse);
    $locationByUid = $locationsByUid[$uid] ?? null;

    $luceedPositive[] = [
        'uid'        => $uid,
        'qty'        => $qty,
        'store_code' => $storeCode,
        'warehouse'  => $warehouse['naziv'] ?? '',
        'store_name' => $warehouse['pj_naziv'] ?? '',
    ];

    if ($locationByUid && availabilityDebugIsVisibleLocation($locationByUid)) {
        $currentWebLocations[$uid] = availabilityDebugNormalizeLocation($locationByUid, 'uid', [
            'qty' => $qty,
        ]);
    }

    $resolved = null;
    $resolvedKey = $uid;

    if ($locationByUid && availabilityDebugIsVisibleLocation($locationByUid)) {
        $resolved = availabilityDebugNormalizeLocation($locationByUid, 'uid', [
            'qty' => $qty,
        ]);
        $resolvedKey = 'location:' . $locationByUid['location_id'];
    } elseif ($storeCode !== '' && ! empty($locationsByStoreCode[$storeCode])) {
        foreach ($locationsByStoreCode[$storeCode] as $locationCandidate) {
            if (availabilityDebugIsVisibleLocation($locationCandidate)) {
                $resolved = availabilityDebugNormalizeLocation($locationCandidate, 'store_code', [
                    'qty'              => $qty,
                    'luceed_uid'       => $uid,
                    'luceed_store_code'=> $storeCode,
                    'luceed_warehouse' => $warehouse['naziv'] ?? '',
                ]);
                $resolvedKey = 'location:' . $locationCandidate['location_id'];
                break;
            }
        }
    } elseif ($warehouse && availabilityDebugWarehouseLooksLikeStore($warehouse)) {
        $resolved = availabilityDebugNormalizeWarehouse($warehouse, [
            'qty' => $qty,
        ]);
        $resolvedKey = 'warehouse:' . $uid;
    }

    if ($resolved) {
        $expandedLocations[$resolvedKey] = $resolved;
        continue;
    }

    if ($locationByUid) {
        $hiddenOrBrokenMappings[] = array_merge(
            availabilityDebugNormalizeLocation($locationByUid, 'hidden_or_broken', [
                'qty' => $qty,
            ]),
            [
                'luceed_uid'        => $uid,
                'luceed_store_code' => $storeCode,
                'warehouse'         => $warehouse['naziv'] ?? '',
            ]
        );
    } else {
        $missingMappings[] = $warehouse
            ? availabilityDebugNormalizeWarehouse($warehouse, [
                'qty' => $qty,
            ])
            : [
                'uid'        => $uid,
                'qty'        => $qty,
                'store_code' => $storeCode,
                'warehouse'  => $warehouse['naziv'] ?? '',
            ];
    }
}

availabilityDebugRespond([
    'sifra' => $sifra,
    'summary' => [
        'queried_units'                 => count($units),
        'luceed_positive_locations'     => count($luceedPositive),
        'current_web_locations'         => count($currentWebLocations),
        'expanded_locations'            => count($expandedLocations),
        'missing_location_mappings'     => count($missingMappings),
        'hidden_or_broken_location_rows'=> count($hiddenOrBrokenMappings),
        'bad_visible_location_rows'     => count($badLocationRows),
    ],
    'current_web_locations'      => array_values($currentWebLocations),
    'expanded_locations'         => array_values($expandedLocations),
    'missing_location_mappings'  => array_values($missingMappings),
    'hidden_or_broken_locations' => array_values($hiddenOrBrokenMappings),
    'bad_visible_location_rows'  => array_values($badLocationRows),
    'luceed_positive_locations'  => $luceedPositive,
]);
