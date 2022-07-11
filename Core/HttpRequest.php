<?php

namespace Framework\Core;

use Framework\Core\interfaces\HttpRequestInterface;
use CurlHandle;

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

}
