<?php

namespace Agmedia\Luceed\Connection;

use Agmedia\Helpers\Log;

/**
 * Class LuceedService
 * @package Agmedia\Luceed\Connection
 */
class LuceedService
{

    /**
     * @var mixed|string
     */
    private $base_url;

    /**
     * @var mixed|string
     */
    private $username;

    /**
     * @var mixed|string
     */
    private $password;

    /**
     * @var string
     */
    public $env;


    /**
     * LuceedService constructor.
     */
    public function __construct()
    {
        $this->base_url = agconf('service.base_url');
        $this->username = agconf('service.username');
        $this->password = agconf('service.password');
        $this->env      = agconf('env');
    }


    /**
     * @param string $url
     * @param string $option
     *
     * @return mixed
     */
    public function get(string $url, string $option = '')
    {
        $this->logRequest('GET', $url . $option);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return file_get_contents(DIR_UPLOAD . 'luceed_json/' . $url);
        }

        // Production or live enviroment.
        try {
            $ch = curl_init($this->base_url . $url . $option);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;

        } catch (\Exception $exception) {
            $this->log($url . $option, $exception);

            return false;
        }
    }


    /**
     * @param string $url
     * @param array  $body
     *
     * @return mixed
     */
    public function post(string $url, array $body)
    {
        $this->logRequest('POST', $url, $body);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return [];
        }

        // Production or live enviroment.
        try {
            $ch = curl_init($this->base_url . $url);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
            curl_setopt ($ch, CURLOPT_POST, true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $this->logResponse('POST', $url, [$body, json_decode($response)]);

            return $response;

        } catch (\Exception $exception) {
            $this->log($url, $exception);

            return false;
        }
    }


    /**
     * @param string $url
     * @param array  $body
     *
     * @return mixed
     */
    public function put(string $url, array $body)
    {
        $this->logRequest('PUT', $url, $body);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return [];
        }

        // Production or live enviroment.
        try {
            $ch = curl_init($this->base_url . $url);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($body));

            $response = curl_exec($ch);
            curl_close($ch);

            $this->logResponse('PUT', $url, [$body, json_decode($response)]);

            return $response;

        } catch (\Exception $exception) {
            $this->log('PUT', $url, $exception);

            return false;
        }
    }


    /**
     * @param string     $type
     * @param string     $url
     * @param \Exception $exception
     */
    private function log(string $type, string $url, \Exception $exception): void
    {
        $log_name = 'luceed_' . $type . '_error';

        Log::store($url, $log_name);
        Log::store($exception->getMessage(), $log_name);
    }


    /**
     * @param string $type
     * @param string $url
     * @param array  $body
     */
    private function logRequest(string $type, string $url, array $body = []): void
    {
        $log_name = 'luceed_' . $type . '_request';

        Log::store($url, $log_name);

        if (! empty($body)) {
            Log::store($body, $log_name);
            Log::store(json_encode($body), $log_name);
        }
    }


    /**
     * @param string $type
     * @param string $url
     * @param array  $body
     */
    private function logResponse(string $type, string $url, array $body = []): void
    {
        $log_name = 'luceed_' . $type . '_response';

        Log::store($url, $log_name);

        if (! empty($body)) {
            Log::store($body, $log_name);
        }
    }
}