<?php

namespace databasespeedup\Engine;

use databasespeedup\Cache_Interface;

class File implements Cache_Interface {
    private $config;
    private $cache_dir;
    private $registered_shutdown = false;

    public function __construct($config) {
        $this->config = $config;
        $this->cache_dir = (defined('DIR_STORAGE') ? DIR_STORAGE : DIR_CACHE) . 'databasespeedup_cache_file';

        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    public function get($sql) {
        $file = $this->fileFromSql($sql);

        if (file_exists($file)) {
            @touch($file);

            return unserialize(file_get_contents($file));
        } else {
            return null;
        }
    }

    public function set($sql, $result) {
        $file = $this->fileFromSql($sql);

        file_put_contents($file, serialize($result));

        if (!$this->registered_shutdown) {
            register_shutdown_function(function() {
                $this->cleanup();
            });

            $this->registered_shutdown = true;
        }
    }

    public function purge() {
        if (false !== $dh = @opendir($this->cache_dir)) {
            while (false !== $entry = @readdir($dh)) {
                if (is_file($this->cache_dir . '/' . $entry)) {
                    @unlink($this->cache_dir . '/' . $entry);
                }
            }

            @closedir($dh);
            return true;
        }
    }

    private function fileFromSql($sql) {
        return $this->cache_dir . '/' . md5($sql);
    }

    private function cleanup() {
        $files = array();

        if (false !== $dh = @opendir($this->cache_dir)) {
            while (false !== $entry = @readdir($dh)) {
                if (is_file($this->cache_dir . '/' . $entry)) {
                    $files[] = array(
                        'file' => $this->cache_dir . '/' . $entry,
                        'mtime' => filemtime($this->cache_dir . '/' . $entry)
                    );
                }
            }

            @closedir($dh);
        }

        uasort($files, function($f1, $f2) {
            return $f1['mtime'] >= $f2['mtime'];
        });

        for ($i = (int)$this->config['max_files']; $i < count($files); $i++) {
            @unlink($files[$i]['file']);
        }
    }
}
