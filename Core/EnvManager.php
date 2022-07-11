<?php

namespace Framework\Core;

class EnvManager
{
	public static function initialize(string $envFilePath) : bool
	{
		if ( !file_exists($envFilePath) || !is_file($envFilePath) )
			throw new \Exception("ENV file not found!");

		$fileData = file_get_contents($envFilePath);
		$entries  = preg_split("/[\r\n]+/", $fileData, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($entries as $entry) {
			$entry = explode('=', $entry, 2);
			if (ctype_digit($entry[1])) {
				$_ENV[$entry[0]] = intval($entry[1]);
			} else {
				$_ENV[$entry[0]] = trim($entry[1], "\"");
			}
		}
		return !empty($_ENV);
	}
}
