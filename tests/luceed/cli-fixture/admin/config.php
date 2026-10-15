<?php
define('DIR_APPLICATION', __DIR__ . '/');
define('HTTPS_SERVER', 'https://fixture.invalid/admin/');
define('DIR_SYSTEM', dirname(__DIR__) . '/system/');
define('DIR_LOGS', getenv('LUCEED_CLI_FIXTURE_LOG') . '/');
define('DB_DRIVER', 'fixture');
define('DB_HOSTNAME', 'fixture');
define('DB_USERNAME', 'fixture');
define('DB_PASSWORD', 'fixture');
define('DB_DATABASE', 'fixture');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
