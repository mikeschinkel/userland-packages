<?php

namespace UserlandPackages;

class Filepath{
	const DS = DIRECTORY_SEPARATOR;

	static function join(string ...$segments) {
		$path = implode(self::DS, $segments);
		if ( self::DS !== "/" && str_contains( $path, "/" ) ) {
			$path = str_replace( "/", self::DS, $path );
		}
		return $path;
	}
}