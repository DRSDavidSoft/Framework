<?php

namespace Framework\Core\Interfaces;

interface DatabaseInterface
{
    public function getRow(string $tbl_name, array $filters) : array;

    public function readAll(string $tbl_name, array $filters) : array;

    public function write(string $tbl_name, array $filters, array $data) : int;

    public function addRow(string $tbl_name, array $data) : int;

    public function count(string $tbl_name, array $filters) : int;

    public function exists(string $tbl_name, array $filters) : bool;

    public function query(string $tbl_name, string $query, array $arguments) : array;

    public function execute(string $tbl_name, string $query, array $arguments) : bool;
}
