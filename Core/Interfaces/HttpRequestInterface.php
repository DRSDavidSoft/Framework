<?php

namespace Framework\Core\Interfaces;

interface HttpRequestInterface
{

    public function get( string $url, array $params, int $timeout) : array;

    public function post( string $url, array $params, int $timeout) : array;

}
