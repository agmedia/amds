<?php

namespace databasespeedup;

interface Cache_Interface {
    public function get($sql);

    public function set($sql, $data);

    public function purge();
}
