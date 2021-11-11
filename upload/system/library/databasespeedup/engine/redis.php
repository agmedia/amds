<?php

namespace databasespeedup\Engine;

use databasespeedup\Cache_Interface;

class Redis implements Cache_Interface {

    private $expire;
    private $host;
    private $port;
    private $prefix;
    private $redis;

    public function __construct($config) {

        $this->password = $config['redis_password'];
        $this->host = $config['redis_hostname'];
        $this->port = $config['redis_port'];
        $this->prefix = $config['redis_prefix'];
        $this->expire = $config['redis_expire'];

        try {
        	$this->redis = new \Redis();

            if (!@$this->redis->connect($this->host, $this->port)) {
                throw new \RedisException("Can't connect to Redis server");
            }

            if ($this->password && !$this->redis->auth($this->password)) {
                throw new \RedisException("Can't authenticate to Redis server");
            }

        }
        catch (RedisException $e) {
            $this->redis = null;
        }
    }

    public function get($sql) {
        if ($this->redis) {
            try {
                if ($this->redis->get($this->prefix . md5($sql)) !== false) {
                    return unserialize($this->redis->get($this->prefix . md5($sql)));
                }else{
                    return null;
                }
            } catch (\RedisException $e) {
                return null;
            }
        }

        return null;
	}

	public function set($sql, $value) {

        if ($this->redis) {
            try {
                return $this->redis->set($this->prefix . md5($sql), serialize($value), (int)$this->expire);
            } catch (\RedisException $e) {
                return null;
            }
        }
        return null;
	}

	public function purge() {
        if ($this->redis) {
            try {
                return $this->redis->flushDb();
            } catch (\RedisException $e) {
                return null;
            }
        }

        return null;
	}
}
