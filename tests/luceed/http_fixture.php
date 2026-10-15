<?php
namespace Agmedia\Luceed\Connection;

// These functions intercept only this service's requests; no network is used.
function curl_init($url) { return new \stdClass(); }
function curl_setopt_array($handle, $options) { \LuceedHttpFixture::$options = $options; return true; }
function curl_exec($handle) { return \LuceedHttpFixture::$body; }
function curl_errno($handle) { return \LuceedHttpFixture::$error; }
function curl_getinfo($handle, $option) { return \LuceedHttpFixture::$status; }
function curl_close($handle) {}
