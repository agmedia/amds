<?php  
$folder = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
chdir($folder);

require_once('config.php');
$domain = parse_url(HTTP_SERVER);
$host = $domain['host'];
putenv('SERVER_NAME='.$host);
$_SERVER['SERVER_NAME'] = $host;
$_GET['route'] = 'extension/module/birthdayreminder/sendWishes';

require_once('index.php');