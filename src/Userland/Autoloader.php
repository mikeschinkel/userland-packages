<?php /** @noinspection PhpIllegalPsrClassPathInspection,PhpStatementHasEmptyBodyInspection */

namespace Userland;
class Autoloader {
	private const string NAMESPACE_PREFIX = 'Userland';
	private const string DS=DIRECTORY_SEPARATOR;
	public static function register():void {
		spl_autoload_register( function ( string $class ) {
			if ( $class[0] !== self::NAMESPACE_PREFIX[0] ) {
				// Do a quick short-circuit test first. Check the first character of the class.
				// If it is not 'U', then not a class or namespace we manage.
				return;
			}
			if (!str_starts_with($class,self::NAMESPACE_PREFIX ) ) {
				// Not a class we handle
				return;
			}
			$class_file = match ( substr_count($class ,'\\') ) {
				0 => sprintf('%s\\%s\\%s',__DIR__, substr( $class, strlen(self::NAMESPACE_PREFIX) ),$class),
				1 => sprintf('%s\\%s',__DIR__,$class),
				default => sprintf('%s\\%s', dirname(__DIR__),$class),
			};
			if ( DIRECTORY_SEPARATOR === '/' ) {
				// If not on Windows, replace namespace separators with slashes.
				$class_file = str_replace( '\\', '/', $class_file );
			}
			// Now load that thang.
			include "{$class_file}.php";
		} );
	}
}
Autoloader::register();