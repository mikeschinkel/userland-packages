<?php declare(strict_types=1);

namespace UserlandPackages;

/**
 * @param string $pattern
 * @param string $filepath
 * @param int $lineNo
 * @param ...$args
 *
 * @return void
 */
function parseError(string $pattern, string $filepath, int $lineNo, ...$args):void {
	$pattern = "UserlandPackages Parse Error: {$pattern} in %s on line %d";
	$args[] = $filepath;
	$args[] = $lineNo;
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
