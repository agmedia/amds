<?php

namespace databasespeedup\Engine;

use databasespeedup\Cache_Interface;

class Memcached implements Cache_Interface {

    private $expire;
    private $host;
    private $port;
    private $prefix;
    private $memcached;

    public function __construct($config) {
        $this->expire = $config['memcached_expire'];
        $this->host = $config['memcached_hostname'];
        $this->port = $config['memcached_port'];
        $this->prefix = $config['memcached_prefix'];

        try {
        	$this->memcached = new \Memcached();
            $this->memcached->addServer($this->host, $this->port);
        }
        catch (Exception $e) {
        	die($e->getMessage());
        }
    }

    public function get($sql) {
        if ($this->memcached->get($this->prefix . md5($sql)) !== false) {
            return $this->memcached->get($this->prefix . md5($sql));
        }else{
            return null;
        }

	}

	public function set($sql, $value) {
		return $this->memcached->set($this->prefix . md5($sql), $value, (int)$this->expire);
	}

	public function purge() {
		return $this->memcached->flush();
	}
}
