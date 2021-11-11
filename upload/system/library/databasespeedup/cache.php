<?php

namespace databasespeedup;

class Cache {
    const CONFIG_FILE = 'isenselabs/databasespeedup_engine_config/config.ini';

    private static $instance = null;
    private static $config = array();
    private static $regex_checks = array(
        'cache_product_counts' => '~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(product)([\s]|$)~i',
        'cache_category_counts' => '~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(category)([\s]|$)~i',
        'cache_product_queries' => '~SELECT.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(product)([\s]|$)~i',
        'cache_category_queries' => '~SELECT.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(category)([\s]|$)~i',
        'cache_seo_urls' => '~SELECT.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(seo_url)([\s]|$)~i',
        'cache_manufacturer_queries' => '~SELECT.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(manufacturer)([\s]|$)~i',
        'cache_manufacturer_counts' => '~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(manufacturer)([\s]|$)~i',
        'cache_information_queries' => '~SELECT.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(information)([\s]|$)~i',
        'cache_information_counts' =>'~SELECT.*COUNT\(.*FROM.*[^0-9a-zA-Z_]{DB_PREFIX}(information)([\s]|$)~i',
    );
    private static function getInstance() {
        if (self::$instance === null) {
            self::$config = parse_ini_file(DIR_CONFIG . self::CONFIG_FILE, true);
            if ((bool)self::$config['main']['status']) {
                switch (self::$config['main']['engine']) {
                    case 'file' :
                        self::$instance = new Engine\File(self::$config['engine_file']);
                        break;
                    case 'redis' :
                        self::$instance = new Engine\Redis(self::$config['engine_redis']);
                        break;
                    case 'memcache' :
                        self::$instance = new Engine\Memcache(self::$config['engine_memcache']);
                        break;
                    case 'memcached' :
                        self::$instance = new Engine\Memcached(self::$config['engine_memcached']);
                        break;
                }
            }
        }

        return self::$instance;
    }

    private static function isCacheable($sql) {
        foreach (self::$regex_checks as $setting => $regex) {
            $regex = str_replace('{DB_PREFIX}', DB_PREFIX, $regex);
            if ((bool)self::$config['main'][$setting] && preg_match($regex, $sql)) {
                return true;
            }
        }

        return false;
    }

    public static function get($sql) {
        if (null !== self::getInstance() && self::isCacheable($sql)) {
            return self::getInstance()->get($sql);
        }

        return null;
    }

    public static function set($sql, $result) {
        if (null !== self::getInstance() && self::isCacheable($sql)) {
            return self::getInstance()->set($sql, $result);
        }

        return null;
    }

    public static function purge() {

        if (null !== self::getInstance()) {
            return self::getInstance()->purge();
        }

        return null;
    }
}
