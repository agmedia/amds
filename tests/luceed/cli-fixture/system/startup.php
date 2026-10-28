<?php
require __DIR__ . '/../../../../upload/system/engine/registry.php';
require __DIR__ . '/../../../../upload/system/library/response.php';
class Config {
    private $data = [];
    public function load($name) {}
    public function set($key, $value) { $this->data[$key] = $value; }
    public function get($key) { return $this->data[$key] ?? null; }
}
class DB {
    public function __construct(...$arguments) {}
    public function query($sql) {
        if (strpos($sql, 'SELECT TABLE_NAME, ENGINE') === 0) {
            $engine = getenv('LUCEED_CLI_FIXTURE_RESULT') === 'myisam' ? 'MyISAM' : 'InnoDB';
            return (object)['rows' => [
                ['TABLE_NAME' => 'oc_product', 'ENGINE' => $engine],
                ['TABLE_NAME' => 'oc_product_option_value', 'ENGINE' => $engine],
                ['TABLE_NAME' => 'oc_product_temp', 'ENGINE' => $engine],
            ]];
        }
        return (object)['rows' => []];
    }
    public function escape($value) { return $value; }
}
class Event { public function __construct($registry) {} }
class Loader { public function __construct($registry) {} }
class Language {
    public function __construct($code) {}
    public function load($code) {}
}
class Request {}
