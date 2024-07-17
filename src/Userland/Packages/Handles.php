<?php

namespace Userland\Packages;

class Handles {
	public static array $handles = [];

	/**
	 * @param int $handleNo
	 *
	 * @return void
	 */
	public static function releaseHandle( mixed $handleNo ): void {
		unset( self::$handles[ (int) $handleNo ] );
	}

	/**
	 * @param int $handleNo
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getHandle( int $handleNo ): mixed {
		if ( ! isset( self::$handles[ $handleNo ] ) ) {
			throw new \Exception( "Handle $handleNo does not exist" );
		}

		return self::$handles[ $handleNo ];
	}

	/**
	 * @param int $handleNo
	 * @param mixed $resource
	 *
	 * @return void
	 */
	public static function setHandle( mixed $handleNo ): void {
		self::$handles[ (int) $handleNo ] = $handleNo;
	}
}