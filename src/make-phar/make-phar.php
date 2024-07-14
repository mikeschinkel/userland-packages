<?php
namespace UserlandPackages;

const DS=DIRECTORY_SEPARATOR;

/**
 * @param string $pkgName
 * @param string $path
 *
 * @return int
 * @throws \Exception
 */
function makePhar(string $pkgName, string $path):int {
	$pharFile = $pkgName . '.tar';
	$pharDir = sprintf("%s%s%s",sys_get_temp_dir(), DS, uniqid('pkg-', true) );
	if ( $pharDir === false ) {
		throw new \Exception( "Failed to get temporary name." );
	}
	if (!mkdir( $pharDir )) {
		throw new \Exception( "Failed to create temporary directory." );
	}
	$pharFilepath = $pharDir . DS . $pharFile;
	// Create a new PHAR archive
	$phar = new \PharData( $pharFilepath,
		\Phar::CURRENT_AS_FILEINFO | \Phar::KEY_AS_FILENAME| \Phar::SKIP_DOTS | \Phar::UNIX_PATHS,
		$pkgName,
		\Phar::TAR
	);

	// Start buffering to modify the archive
	$phar->startBuffering();

	$filenames = "";
	foreach ( glob( "{$path}/*.php" ) as $file ) {
		$basename = basename( $file );
		$phar->addFile($file, $basename );
		$filenames .= sprintf( 'include "phar://%s/%s";%s', $pharFile, $basename, "\n" );
	}

//	// Set the stub (entry point)
/*	$phar->setStub( "<?php\nPhar::mapPhar('{$pkgName}');\n{$filenames}__HALT_COMPILER();\n?>" );*/

	// Stop buffering and write changes to the archive
	$phar->stopBuffering();

	$handle = fopen("/path/to/file",'r+');
	$n = fwrite( $handle, file_get_contents( $pharFilepath ) );
	unlink( $pharFilepath );
	rmdir( $pharDir );

 	return $handle;
}