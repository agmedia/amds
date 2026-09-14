<?php

if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	exit('Command line only.');
}

$current_directory = dirname(__FILE__);
$application_root = dirname(dirname(dirname($current_directory)));

chdir($application_root);

$host = isset($argv[1]) && $argv[1] !== '' ? $argv[1] : 'localhost';
$port_argument = isset($argv[2]) && $argv[2] !== '' ? (string)$argv[2] : '80';
$port = ctype_digit($port_argument) ? (int)$port_argument : 0;

if (!preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/i', $host) || $port < 1 || $port > 65535) {
	fwrite(STDERR, "Invalid host or port.\n");
	exit(64);
}

$_SERVER['HTTP_HOST'] = $host;
$_SERVER['SERVER_NAME'] = $host;
$_SERVER['SERVER_PORT'] = $port;
$_SERVER['HTTPS'] = $port === 443 ? 'on' : '';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $application_root . DIRECTORY_SEPARATOR . 'index.php';
$_SERVER['REQUEST_URI'] = '/index.php?route=extension/cron/klaviyo_consent';
$_SERVER['QUERY_STRING'] = 'route=extension/cron/klaviyo_consent';
$_GET['route'] = 'extension/cron/klaviyo_consent';

putenv('SERVER_NAME=' . $host);

$index = $application_root . DIRECTORY_SEPARATOR . 'index.php';

if (!is_file($index)) {
	fwrite(STDERR, "OpenCart index.php not found.\n");
	exit(66);
}

$GLOBALS['klaviyo_consent_cron_exit_code'] = 70;
require_once $index;

exit((int)$GLOBALS['klaviyo_consent_cron_exit_code']);
