<?php

namespace Framework\Core;

use Framework\Core\interfaces\HttpRequestInterface;
use CurlHandle;
use Exception;

/**
 * File: HttpRequest.php
 * Author: David@Refoua.me
 * Author: kouroshmoshrefi@hotmail.com
 * Version: 0.1.0
 */

class HttpRequest implements HttpRequestInterface
{

    /**
     * @var CurlHandle instance of the Curl request
     */
    private readonly CurlHandle $ch;

    public function __construct(string $url = null)
    {
        // Check if all the required extensions are present
        foreach ( ['curl'] as $extension ) if( !extension_loaded($extension) ) {
            throw new \Exception("The required '$extension' extension is not enabled.");
        }

        $this->ch = curl_init($url);

        if (!$this->ch)
            throw new \Exception("Failed to initialize the Curl resource!");
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    public function getURL() : string
    {
        return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
    }

    public function setURL(string $url) : bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new \Exception("The specified URL is not valid!");

        return $this->setOptions([CURLOPT_URL => &$url]);
    }

    public function setOptions(array $opts)
    {
        return curl_setopt_array($this->ch, $opts);
    }

    public function get(string $url, array $params, int $timeout): array
    {
        // TODO: Implement get() method.
    }

    public function post(string $url, array $params, int $timeout): array
    {
        // TODO: Implement post() method.
    }
}
