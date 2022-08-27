<?php

require_once __DIR__ . '/application.php';

$w = $app->db->write('tbl_2fa_login', ['2fa_id' => 1], ['2fa_code' => rand(1000, 9999)]);

var_dump($w);

$r = $app->db->getRow('tbl_2fa_login', ['2fa_id' => 1]);

var_dump($r);

$c = $app->db->count('tbl_2fa_login', ['2fa_id' => 1]);

var_dump($c);
