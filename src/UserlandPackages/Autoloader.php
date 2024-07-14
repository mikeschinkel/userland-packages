<?php /** @noinspection PhpIllegalPsrClassPathInspection,PhpStatementHasEmptyBodyInspection */

namespace UserlandPackages;
class Autoloader {
	private const string NAME='UserlandPackages';
	private const string DS=DIRECTORY_SEPARATOR;
	public static function register():void {
		spl_autoload_register( function ( string $class ) {
			if ( $class[0] !== self::NAME[0] ) {
				// Do a quick short-circuit test first. Check the first character of the class.
				// If it is not 'U', then not a class or namespace we manage.
				return;
			}
			if ( DIRECTORY_SEPARATOR === '/' ) {
				// If not on Windows, replace namespace separators with slashes.
				$class = str_replace( '\\', '/', $class );
			}
			if (str_starts_with($class,self::NAME.self::DS ) ) {
				// If class starts with 'UserlandPackages/' strip NS  as it is already in __DIR__
				$class = substr($class,strlen(self::NAME)+1 );
			} else if ( $class === self::NAME) {
				// If class equals 'UserlandPackages' leave namespace to load non-namespaced
				// class with same name as namespace but located in namespace's directory.
			} else {
				// Not a class we manage, so return
				return;
			}
			// Now load that thang.
			include sprintf( "%s%s%s.php",__DIR__,self::DS,$class );
		} );
	}
}
Autoloader::register();