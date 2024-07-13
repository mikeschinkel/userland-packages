<?php

namespace UserlandPackages;

class Packages {
	private static array $packages = [];
	private static array $nums = [];
	public static function packageLoaded(string $pkgName): bool {
		return isset(self::$packages[$pkgName]);
	}
	public static function addPackage(Package $package): void {
		self::$packages[$package->name] = &$package;
	}
	public static function getPackage(string $pkgName): Package {
		if (!isset(self::$packages[$pkgName])) {
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new \Exception("Package '{$pkgName}' not valid.");
		}
		return self::$packages[$pkgName];
	}
	public static function getNum(string $pkgName):string{
		if (!isset(self::$nums[$pkgName])) {
			do {
				// Generate a random number between 1,000,000 and 9,999,999
				$num = mt_rand( 10000, 99999 );
			} while ( in_array( $num, self::$nums, true ) ); // Ensure the handle is unique
			self::$nums[$pkgName] = $num;
		}
		return (string)self::$nums[$pkgName];
	}
}
