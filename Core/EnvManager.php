<?php

namespace Framework\Core;

class EnvManager
{
    public static function initialize(string $envFilePath) : bool
    {
        if (!file_exists($envFilePath)) {
            throw new \Exception("ENV file not found!");
        }
        define('ENV', file_get_contents($envFilePath));
        define("ENTRIES", explode(PHP_EOL, ENV));
        foreach (ENTRIES as $entry) {
            $entry = explode('=', $entry);
            if (!str_contains($entry[1], "\"") && intval($entry[1]) == $entry[1]) {
                $_ENV[$entry[0]] = intval($entry[1]);
            } else {
                $_ENV[$entry[0]] = trim($entry[1], "\"");
            }
        }
        return !empty($_ENV);
    }
}
