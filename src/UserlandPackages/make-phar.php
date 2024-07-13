<?php
namespace UserlandPackages;
use \UserlandPackages\PackageFS;
const DS=DIRECTORY_SEPARATOR;
function makePhar(string $pkgName, string $path):int {
	$pharFile = $pkgName . '.tar';
	$pharDir = sprintf("%s%s%s",sys_get_temp_dir(), DS, uniqid('pkg-', true) );
	if ( $pharDir === false ) {
		/** @noinspection PhpUnhandledExceptionInspection */
		throw new \Exception( "Failed to get temporary name." );
	}
	if (!mkdir( $pharDir )) {
		/** @noinspection PhpUnhandledExceptionInspection */
		throw new \Exception( "Failed to create temporary directory." );
	}
	$pharFilepath = $pharDir . DS . $pharFile;
	// Create a new PHAR archive
	/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
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

	$memHandle = PackageFS::createHandle();
	PackageFS::write( $memHandle, file_get_contents( $pharFilepath ) );
	unlink( $pharFilepath );
	rmdir( $pharDir );

 	Packages::addPackage(new Package($pkgName,$memHandle));
	return $memHandle;
}