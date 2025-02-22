<?php declare(strict_types=1);

namespace Userland\Packages;

use JetBrains\PhpStorm\NoReturn;

/**
 * @param string $pattern
 * @param string $filepath
 * @param int $lineNo
 * @param ...$args
 *
 * @return void
 */
#[NoReturn]
function parseError(string $pattern, string $filepath, int $lineNo, ...$args):void {
	$pattern = "Userland\Packages Parse Error: {$pattern} in %s";
	$args[] = $filepath;
	if ($lineNo !== 0) {
		$args[] = $lineNo;
		$pattern .= " on line %d";
	}
	fprintf( STDOUT, $pattern,...$args);
	exit(1);
}

/**
 * Write parameter to php://memory and return handle
 *
 * @param string $code
 *
 * @return resource
 * @throws \Exception
 */
function memWrite( string $code ): mixed {
	$handle = fopen( 'php://memory', 'r+' );
	if ( $handle === false ) {

		throw new \Exception( "error opening php://memory for storing code" );
	}
	$n = fwrite( $handle, $code );
	if ( $n === false ) {

		throw new \Exception( "error writing to php://memory" );
	}
	rewind( $handle );

	return $handle;
}
