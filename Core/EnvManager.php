<?php

namespace Framework\Core;

use Exception;

class EnvManager
{
	public static function initialize(string $envFilePath) : bool
	{
		if (!file_exists($envFilePath) || !is_file($envFilePath))
			throw new \Exception("ENV file not found!");

		$handle = fopen($envFilePath, 'r');

		if (!$handle)
			throw new \Exception("Unable to open ENV file!");

		while(($line = fgets($handle)) !== false) {
			$entry = explode('=', $line = trim($line), 2);
			if (ctype_digit($entry[1])) {
				$_ENV[$entry[0]] = intval($entry[1]);
			} else {
				$_ENV[$entry[0]] = trim($entry[1], "\"");
			}
		}

		if (!feof($handle))
			throw new Exception("Unexpected end of file!");

		fclose($handle);

		return !empty($_ENV);
	}
}
