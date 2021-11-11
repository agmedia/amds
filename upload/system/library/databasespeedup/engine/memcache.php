<?php

namespace databasespeedup\Engine;

use databasespeedup\Cache_Interface;

class Memcache implements Cache_Interface {

    private $expire;
    private $host;
    private $port;
    private $prefix;
    private $memcache;

    public function __construct($config) {
        $this->expire = $config['memcache_expire'];
        $this->host = $config['memcache_hostname'];
        $this->port = $config['memcache_port'];
        $this->prefix = $config['memcache_prefix'];

        try {
        	$this->memcache = new \Memcache();
            $this->memcache->addServer($this->host, $this->port);
        }
        catch (Exception $e) {
        	die($e->getMessage());
        }

    }

    public function get($sql) {
        if ($this->memcache->get($this->prefix . md5($sql)) !== false) {
            return $this->memcache->get($this->prefix . md5($sql));
        }else{
            return null;
        }

	}

	public function set($sql, $value) {
		return $this->memcache->set($this->prefix . md5($sql), $value, 0, (int)$this->expire);
	}

	public function purge() {
		return $this->memcache->flush();
	}
}
