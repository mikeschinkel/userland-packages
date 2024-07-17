<?php declare(strict_types=1);

namespace UserlandPackages;

use \ZipArchive;

class ZipFormat implements FormatInterface {
	const string CHECKSUM_FILE = 'CHECKSUM';
	/**
	 * @param PackageLoader $loader
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generatePackage( PackageLoader $loader, Options $options ): string {
		$fileContents = $loader->loadPackageFiles( $loader->pkgPath );
		$zip = new \ZipArchive();
		$checkSummer = new Checksum();
		$pkgFilepath = PackageType::ZIP->getFilepath( $loader->pkgPath, $options );
		$zip->open($pkgFilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE|ZipArchive::FL_OPEN_FILE_NOW);

		// Create a minimal manifest with positions==0 so we can determine how long the
		// manifest is as a string ignoring the lengths of the position values.
		// Also loop through to get code to write for each file.
		foreach ( $fileContents as $filename => $code ) {
			$filepath = Filepath::join($loader->pkgPath,$filename);
			$code = $loader->transformPhpFile($filepath,$code,false);
			$checkSummer->append("{$filename}\n{$code}");
			// Store, don't compress, for performance.
			// (I have not benchmarked this, but assume it will be faster.)
			$zip->addFromString($filename, $code,ZipArchive::CM_STORE|ZipArchive::FL_OVERWRITE);
		}
		$zip->addFromString( self::CHECKSUM_FILE, $checkSummer->getSum(), ZipArchive::CM_STORE | ZipArchive::FL_OVERWRITE );
		$zip->close();
		return $pkgFilepath;
	}

	/**
	 * @param PackageLoader $loader
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function loadPackage(  PackageLoader $loader, Options $options ): string {
		$pkgFilepath = PackageType::ZIP->getFilepath( $loader->pkgPath, $options );
		$zip = new ZipArchive;
		$result = $zip->open($pkgFilepath, ZipArchive::RDONLY|ZipArchive::CHECKCONS);
		if ($result !== true) {
			throw new \Exception("Failed to open ZIP package {$pkgFilepath}; result={$result}");
		}
		$verifyChecksum = $options->verifyChecksum();
		if ($verifyChecksum) {
			$checkSummer = new Checksum();
			$checksum    = "";
		}

 		for ($index = 0; $index < $zip->numFiles; $index++) {
			$filename = $zip->getNameIndex($index);
			if ($filename === self::CHECKSUM_FILE && $options->verifyChecksum()) {
				$checksum = $zip->getFromIndex($index, 0, ZipArchive::FL_UNCHANGED);
				continue;
			}
			if ($verifyChecksum) {
				$code = $zip->getFromIndex($index, 0, ZipArchive::FL_UNCHANGED);
				$checkSummer->append( "{$filename}\n{$code}" );
			}
			/** @noinspection PhpUndefinedMethodInspection */
			$handle = $zip->getStreamIndex($index, ZipArchive::FL_UNCHANGED);
			Handles::setHandle( $handle );
			$filepath = Filepath::join($loader->pkgPath,$filename);
			$loader->loadPhpFile( $filepath, $handle );
			Handles::releaseHandle( $handle );
		}
		if ($verifyChecksum && $checksum != $checkSummer->getSum() ) {
			$loader->parseError(0, "Mismatch in checksum of %s.", $pkgFilepath );
		}
		return $pkgFilepath;
	}

}