<?php

namespace UserlandPackages;

class PackageFormatObjects {
	private static array $objects = [];

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public static function release( string $key ): void {
		unset( self::$objects[ $key ] );
	}

	/**
	 * @param string $key
	 *
	 * @return ?FormatInterface
	 * @throws \Exception
	 */
	public static function get( string $key ): ?FormatInterface {
		if ( !isset( self::$objects[ $key ] ) ) {
			return null;
		}

		return self::$objects[ $key ];
	}

	/**
	 * @param string $key
	 * @param ?FormatInterface $formatObject
	 *
	 * @return void
	 */
	public static function set( string $key, ?FormatInterface $formatObject ): void {
		self::$objects[ $key ] = $formatObject;
	}

}