<?php

namespace Framework\Core\Interfaces;

interface HttpRequestInterface
{
    
    public function get( string $url, array $params, int $timeout, int $maxTryCount ) : array;

    public function post( string $url, array $params, int $timeout, int $maxTryCount  ) : array;

}
