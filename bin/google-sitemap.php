<?php
/** php bin/google-sitemap.php --check | --apply */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Never expose database configuration through PHP warnings or exception traces.
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException('Sitemap release command failed.', 0, $severity);
    }
    return true;
});

$mode = null;
$database = null;
$phase = 'invalid_arguments';
$exitCode = 1;
$result = array('mode' => null, 'status' => 'error', 'enabled' => null, 'settings_count' => null, 'registrations' => null);

try {
    if (count($argv) !== 2 || !in_array($argv[1], array('--check', '--apply'), true)) {
        throw new RuntimeException('Usage: php bin/google-sitemap.php --check | --apply');
    }
    $mode = substr($argv[1], 2);
    $result['mode'] = $mode;

    $phase = 'configuration_unavailable';
    $root = dirname(__DIR__);
    if (!extension_loaded('mysqli') || !is_file($root . '/upload/config.php')) {
        throw new RuntimeException('Required configuration is unavailable.');
    }
    require $root . '/upload/config.php';
    foreach (array('DB_DRIVER', 'DB_HOSTNAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_PORT', 'DB_PREFIX') as $constant) {
        if (!defined($constant)) {
            throw new RuntimeException('Required database configuration is unavailable.');
        }
    }
    if (DB_DRIVER !== 'mysqli' || !is_string(DB_PREFIX) || !preg_match('/^[A-Za-z0-9_]*$/D', DB_PREFIX)
        || !ctype_digit((string)DB_PORT) || (int)DB_PORT < 1 || (int)DB_PORT > 65535) {
        throw new RuntimeException('Unsupported database configuration.');
    }

    $phase = 'database_unavailable';
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $database = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
    $database->set_charset('utf8mb4');

    if ($mode === 'apply') {
        $phase = 'migration_failed';
        $migration = $root . '/storage/migrations/40_enable_google_sitemap.sql';
        $sql = file_get_contents($migration);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Sitemap migration is unavailable.');
        }
        $sql = str_replace('oc_', DB_PREFIX, $sql);
        if (!$database->multi_query($sql)) {
            throw new RuntimeException('Sitemap migration failed.');
        }
        // Consume every statement before querying the resulting feed state.
        while (true) {
            $statementResult = $database->store_result();
            if ($statementResult instanceof mysqli_result) {
                $statementResult->free();
            }
            if (!$database->more_results()) {
                break;
            }
            if (!$database->next_result()) {
                throw new RuntimeException('Sitemap migration failed.');
            }
        }
    }

    $phase = 'verification_failed';
    $settings = $database->query('SELECT code, value, serialized FROM `' . DB_PREFIX . "setting` WHERE store_id = 0 AND `key` = 'feed_google_sitemap_status'");
    $result['settings_count'] = (int)$settings->num_rows;
    $setting = $settings->fetch_assoc();
    $settings->free();
    $extensions = $database->query('SELECT COUNT(*) AS total FROM `' . DB_PREFIX . "extension` WHERE type = 'feed' AND code = 'google_sitemap'");
    $registration = $extensions->fetch_assoc();
    $result['registrations'] = (int)$registration['total'];
    $extensions->free();

    $result['enabled'] = $result['settings_count'] === 1 && $setting !== null
        && $setting['code'] === 'feed_google_sitemap' && $setting['value'] === '1'
        && (string)$setting['serialized'] === '0' && $result['registrations'] === 1;
    if ($mode === 'apply' && !$result['enabled']) {
        throw new RuntimeException('Sitemap state did not pass verification.');
    }
    $result['status'] = 'ok';
    $exitCode = 0;
} catch (Throwable $exception) {
    // Use fixed failure codes; exception details may contain database metadata.
    $result['error'] = $phase;
} finally {
    if ($database instanceof mysqli) {
        $database->close();
    }
}

fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL);
exit($exitCode);
