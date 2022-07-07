<?php

namespace Framework\Core;

use Framework\Core\interfaces\DatabaseInterface;
use PDO;

class Database implements DatabaseInterface
{
    private PDO $connection;
    public function __construct(string $dbName)
    {
        $this->connection = $this->makeConnection($dbName);
    }

    private function makeConnection($dbName) : PDO
    {
        $dsn = $_ENV['APP_DATABASE_TYPE'];
        $host = $_ENV['APP_DATABASE_ADDRESS'];
        $user = $_ENV['APP_DATABASE_USERNAME'];
        $pass = $_ENV['APP_DATABASE_PASSWORD'];
        return new PDO("$dsn:dbname=$dbName;host=$host", $user, $pass);
    }

    public function getRow(string $tbl_name, array $filters) : array
    {
        return [];
    }

    public function readAll(string $tbl_name, array $filters) : array
    {
        return [];
    }
    
    public function write(string $tbl_name, array ...$rows) : int
    {
        return 0;
    }

    public function addRow(string $tbl_name, array $data) : int
    {
        return 0;
    }

    public function count(string $tbl_name, array $filters) : int
    {
        return 0;
    }

    public function exists(string $tbl_name, array $filters) : bool
    {
        return false;
    }

    public function query(string $tbl_name, string $query, array $arguments) : array
    {
        return [];
    }

    public function execute(string $tbl_name, string $query, array $arguments) : bool
    {
        return false;
    }
}
