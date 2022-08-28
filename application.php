<?php

require_once __DIR__.'\config.php';

require_once __DIR__.'\Core\Interfaces\HttpRequestInterface.php';
require_once __DIR__.'\Core\HttpRequest.php';

require_once __DIR__.'\Core\Interfaces\DatabaseInterface.php';
require_once __DIR__.'\Core\Database.php';

use Framework\Core\Database;
use Framework\Core\HttpRequest;

class Application
{
    public readonly Database $db;
//    public readonly Database $db_logs;
//    public readonly Database $db_servers;

    public readonly HttpRequest $curl;

    public function __construct()
    {
        $this->db = new Database("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASS);
//        $this->db_logs = new Database("mysql:host=localhost;dbname=mizbanc_logs", "root");
//        $this->db_servers = new Database("mysql:host=localhost;dbname=mizbanc_servers", "root");

        $this->curl = new HttpRequest();
    }
}

GLOBAL $app;

$app = new Application();

var_dump($app->db->readAll('users', ['id' => [2, 3]]));