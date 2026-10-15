<?php
/** Run with the site's PHP CLI: php bin/luceed-sync.php [--check] [--admin-dir=/path/to/admin] */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
date_default_timezone_set('Europe/Zagreb');
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        return true;
    }
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

$logFile = null;
$finished = false;
$started = microtime(true);
$writeLog = function ($message) use (&$logFile) {
    $line = '[' . date('c') . '] pid=' . getmypid() . ' ' . $message . PHP_EOL;
    if ($logFile !== null && file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException('Cannot write the Luceed CLI log.');
    }
    fwrite(STDERR, $line);
};

register_shutdown_function(function () use (&$finished, &$logFile) {
    if ($finished) {
        return;
    }
    $error = error_get_last();
    $message = 'FAILED: process ended before a completed sync result';
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $message .= ' (' . $error['message'] . ')';
    }
    $line = '[' . date('c') . '] pid=' . getmypid() . ' ' . $message . PHP_EOL;
    if ($logFile !== null) {
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
    fwrite(STDERR, $line);
    exit(1);
});

try {
    $check = false;
    $adminDirectory = null;
    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--help') {
            fwrite(STDOUT, "Usage: php bin/luceed-sync.php [--check] [--admin-dir=/path/to/admin]\n--check validates configuration and database access without changing stock.\n");
            $finished = true;
            exit(0);
        } elseif ($argument === '--check') {
            $check = true;
        } elseif (strpos($argument, '--admin-dir=') === 0) {
            $adminDirectory = substr($argument, strlen('--admin-dir='));
        } else {
            throw new InvalidArgumentException('Unknown argument: ' . $argument);
        }
    }

    if ($adminDirectory === null) {
        foreach ([__DIR__ . '/../upload/admin', __DIR__ . '/../admin'] as $candidate) {
            if (is_file($candidate . '/config.php')) {
                $adminDirectory = $candidate;
                break;
            }
        }
    }
    $adminDirectory = $adminDirectory === null ? false : realpath($adminDirectory);
    if ($adminDirectory === false || !is_file($adminDirectory . '/config.php')) {
        throw new RuntimeException('Admin configuration was not found; specify --admin-dir=/path/to/admin.');
    }
    foreach (['curl', 'mysqli', 'pdo_mysql'] as $extension) {
        if (!extension_loaded($extension)) {
            throw new RuntimeException('PHP CLI is missing the ' . $extension . ' extension.');
        }
    }

    define('VERSION', '3.0.3.7');
    require $adminDirectory . '/config.php';
    require dirname($adminDirectory) . '/env.php';
    if (!defined('DIR_APPLICATION') || realpath(DIR_APPLICATION) !== $adminDirectory) {
        throw new RuntimeException('Admin configuration points to a different application directory.');
    }
    if (!is_dir(DIR_LOGS) || !is_writable(DIR_LOGS)) {
        throw new RuntimeException('The configured log directory is not writable.');
    }
    $logFile = DIR_LOGS . 'luceed_sync_cli.log';

    $_SERVER['HTTP_HOST'] = parse_url(HTTPS_SERVER, PHP_URL_HOST) ?: 'localhost';
    $_SERVER['PHP_SELF'] = '/admin/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $adminDirectory . '/index.php';
    $_SERVER['DOCUMENT_ROOT'] = dirname($adminDirectory);
    $_SERVER['REQUEST_URI'] = '/admin/index.php?route=extension/module/luceed_sync/updatePricesAndQuantities';
    $_SERVER['QUERY_STRING'] = 'route=extension/module/luceed_sync/updatePricesAndQuantities';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    $_SERVER['HTTPS'] = 'on';

    // Bootstrap only the services this controller uses. No browser session,
    // login redirect, cart cleanup or front-end error document is involved.
    require DIR_SYSTEM . 'startup.php';
    $registry = new Registry();
    $config = new Config();
    $config->load('default');
    $config->load('admin');
    $registry->set('config', $config);
    $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
    $registry->set('db', $db);
    $settings = $db->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE store_id = 0 AND `key` IN ('config_admin_language', 'config_timezone')");
    foreach ($settings->rows as $setting) {
        $config->set($setting['key'], $setting['value']);
    }
    date_default_timezone_set($config->get('config_timezone') ?: 'Europe/Zagreb');
    $db->query("SET time_zone = '" . $db->escape(date('P')) . "'");
    $tables = [DB_PREFIX . 'product', DB_PREFIX . 'product_option_value', DB_PREFIX . 'product_temp'];
    $engines = $db->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('" . implode("','", array_map([$db, 'escape'], $tables)) . "')");
    $transactional = [];
    foreach ($engines->rows as $table) {
        if (strcasecmp($table['ENGINE'], 'InnoDB') === 0) {
            $transactional[] = $table['TABLE_NAME'];
        }
    }
    if (array_diff($tables, $transactional)) {
        throw new RuntimeException('Inventory tables must use InnoDB; review existing migration 32 before enabling this cron.');
    }
    $registry->set('event', new Event($registry));
    $registry->set('load', new Loader($registry));
    $languageCode = $config->get('config_admin_language') ?: 'hr-hr';
    $language = new Language($languageCode);
    $language->load($languageCode);
    $registry->set('language', $language);
    $_GET = ['route' => 'extension/module/luceed_sync/updatePricesAndQuantities'];
    $_POST = $_COOKIE = $_FILES = $_REQUEST = [];
    $_SERVER['HTTPS'] = true;
    $registry->set('request', new Request());
    $response = new Response();
    $registry->set('response', $response);

    if ($check) {
        $writeLog('CHECK OK: PHP CLI, configuration, database and logging are available; stock unchanged.');
        $finished = true;
        exit(0);
    }

    $writeLog('START updatePricesAndQuantities');
    require DIR_APPLICATION . 'controller/extension/module/luceed_sync.php';
    $controller = new ControllerExtensionModuleLuceedSync($registry);
    $controller->updatePricesAndQuantities();
    $result = json_decode((string)$response->getOutput(), true);
    if (!is_array($result) || !isset($result['status']) || (int)$result['status'] !== 200) {
        throw new RuntimeException('Sync did not complete successfully: ' . ($result['message'] ?? 'no valid result'));
    }

    fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    $writeLog('SUCCESS after ' . number_format(microtime(true) - $started, 2, '.', '') . ' seconds: ' . $result['message']);
    $finished = true;
    exit(0);
} catch (Throwable $exception) {
    try {
        $writeLog('FAILED after ' . number_format(microtime(true) - $started, 2, '.', '') . ' seconds: ' . $exception->getMessage());
    } catch (Throwable $logException) {
        fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . PHP_EOL);
    }
    $finished = true;
    exit(1);
}
