<?php declare(strict_types=1);

namespace Userland\Packages;

class PharFormat implements FormatInterface {

	/**
	 * @param PackageLoader $loader
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generatePackage( PackageLoader $loader, Options $options ): string {
		$pkgFilepath = PackageType::PHAR->getFilepath( $loader->pkgPath, $options );
		$pkgName = basename($pkgFilepath);
		// Create a new PHAR archive
		$phar = new \Phar( $pkgFilepath);

		// Start buffering to modify the archive
		$phar->startBuffering();

		$filenames = "";
		foreach ( $loader->getPackageFilepaths() as $filepath ) {
			$code = file_get_contents($filepath,false);
			$code = $loader->transformPhpFile($filepath,$code,false);
			$filename = basename( $filepath );
			$phar->addFromString( $filename,$code );
			$stub = sprintf( 'include "phar://%s/%s";%s', basename($pkgFilepath), $filename, "\n" );
			$filenames .= $stub;
		}

		// Set the stub (entry point)
		$phar->setStub( "<?php\nPhar::mapPhar('{$pkgName}');\n{$filenames}__HALT_COMPILER();\n?>" );

		// Stop buffering and write changes to the archive
		$phar->stopBuffering();

		return $pkgFilepath;

	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 */
	public function loadPackage(  PackageLoader $loader, Options $options ): string {
		$filepath = PackageType::PHAR->getFilepath( $loader->pkgPath, $options );
		require($filepath);
		return $filepath;
	}


}