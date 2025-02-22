<?php declare(strict_types=1);

namespace Userland\Packages;

class PhpkgFormat implements FormatInterface {
	public const string PREFIX = "PHPKG ";
	public const string MANIFEST_PREFIX = PhpkgFormat::PREFIX . "manifest=";
	public const string FILE_PREFIX = PhpkgFormat::PREFIX . "file=";
	public const string MARKER = PhpkgFormat::PREFIX . "GENERATED FOR Userland Packages — DO NOT EDIT!!!\n";
	public const string HEADER = PhpkgFormat::MARKER . PhpkgFormat::MANIFEST_PREFIX;
	public const string CHECKSUM_PREFIX = PhpkgFormat::PREFIX . "checksum=";

	/**
	 * @param PackageLoader $loader
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generatePackage( PackageLoader $loader, Options $options ): string {
		$fileContents = $loader->loadPackageFiles( $loader->pkgPath );
		$manifest     = (object) [ 'files' => [] ];
		$sizes        = [];
		$checkSummer = new CheckSum();

		// Create a minimal manifest with positions==0 so we can determine how long the
		// manifest is as a string ignoring the lengths of the position values.
		// Also loop through to get code to write for each file.
		foreach ( $fileContents as $filename => $code ) {
			$filepath = Filepath::join($loader->pkgPath,$filename);
			$code = $loader->transformPhpFile($filepath,$code,false);
			$manifest->files[]         = $filename;
			$obj                       = (object) [
				'name' => $filename,
				'size' => strlen( $code ) + 1,
			];
			$sizes[ $filename ]        = $obj;
			$file = sprintf( "%s%s\n%s\n",
				self::FILE_PREFIX,
				json_encode( $obj ),
				$code
			);
			$fileContents[ $filename ] = $file;
			$checkSummer->append( $file );
		}
		$code = null;
		$manifest = json_encode( $manifest );
		$manifest = self::HEADER . $manifest;
		$checkSummer->prepend( $manifest );
		$package = sprintf( "%s\n%s",
			$manifest,
			implode( '', $fileContents ),
		);;
		$content = sprintf( "%s%s%s\n", $package, self::CHECKSUM_PREFIX, $checkSummer->getSum() );
		$outPath = $loader->packageFilepath($options);
		file_put_contents( PackageType::PHPKG->getFilepath( $outPath, $options ), $content );
		return PackageType::PHPKG->getFilepath( $loader->pkgPath, $options );
	}

	/**
	 * @param string $pkgName
	 * @param string $pkgPath
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function loadPackage( PackageLoader $loader, Options $options ): string {
		$phpkg = file_get_contents( PackageType::PHPKG->getFilepath( $loader->pkgPath, $options ), false );

		// TODO: Separate load out from parse.
		return $this->parseAndLoadPhpkg( $loader, $phpkg, $options );
	}

	/**
	 * @param string $phpkg
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function parseAndLoadPhpkg( PackageLoader $loader, string $phpkg, Options $options ): string {
		$pkgFilepath = PackageType::PHPKG->getFilepath( $loader->pkgPath, $options );
		$line        = 1;
		$pkgHandle   = memWrite( $phpkg );
		unset( $phpkg );
		$openTag = fgets( $pkgHandle );
		if ( ! str_starts_with( PhpkgFormat::HEADER, $openTag ) ) {
			$loader->parseError( $line, "unable to open tag; expected '%s', got '%s' instead for %s.",
				PhpkgFormat::HEADER,
				substr( $openTag, strlen( PhpkgFormat::HEADER ) ),
				$pkgFilepath
			);
		}
		unset( $openTag );
		$line ++;
		$manifest = fgets( $pkgHandle );
		if ( ! str_starts_with( $manifest, PhpkgFormat::MANIFEST_PREFIX ) ) {
			$loader->parseError( $line, "unable to parse manifest; expected '%s', got '%s' instead for %s.",
				PhpkgFormat::MANIFEST_PREFIX,
				substr( $manifest, strlen( PhpkgFormat::MANIFEST_PREFIX ) ),
				$pkgFilepath
			);
		}
		$verifyChecksum = $options->verifyChecksum();
		if ($verifyChecksum) {
			$checkSummer = new Checksum( rtrim( PhpkgFormat::MARKER . $manifest ) );
		}
		$manifest        = substr( $manifest, strlen( PhpkgFormat::MANIFEST_PREFIX ) );
		$decodedManifest = json_decode( $manifest );
		if ( ! is_object( $decodedManifest ) || ! is_array( $decodedManifest->files ) ) {
			$loader->parseError( $line, "unable to decode manifest; got '%s' for %s.", $manifest, $pkgFilepath );
		}
		unset( $manifest );
		foreach ( $decodedManifest->files as $index => $filename ) {
			$filepath = Filepath::join( $loader->pkgPath, $filename );
			$line ++;
			$fileHeader = fgets( $pkgHandle );
			if ( ! str_starts_with( $fileHeader, PhpkgFormat::FILE_PREFIX ) ) {
				$loader->parseError( $line, "unable to parse file header; expected '%s', got '%s' instead for %s of %s.",
					PhpkgFormat::FILE_PREFIX,
					substr( $fileHeader, strlen( PhpkgFormat::FILE_PREFIX ) ),
					$filename, $pkgFilepath
				);
			}
			$file = json_decode( substr( $fileHeader, strlen( PhpkgFormat::FILE_PREFIX ) ) );
			if ( is_null( $file ) ) {
				$loader->parseError( $line, " unable to decode file header for %s of %s.", $filename, $pkgFilepath );
			}
			if ( ! property_exists( $file, 'name' ) ) {
				$loader->parseError( $line, "corrupt file header for %s.", $filename, $pkgFilepath );
			}
			if ( $file->name != $filename ) {
				$loader->parseError( $line, "mismatch in manifest and file header of %s of %s.", $filename, $pkgFilepath );
			}
			$code = fread( $pkgHandle, $file->size );
			if ($verifyChecksum) {
				$checkSummer->append( $fileHeader . $code );
			}
			$handle = memWrite( $code );
			Handles::setHandle( $handle );
			$loader->loadPhpFile( $filepath, $handle );
			Handles::releaseHandle( $handle );
			$line += substr_count( $code, "\n" );
		}
		if ($verifyChecksum) {
			$checksum = fread( $pkgHandle, strlen( PhpkgFormat::CHECKSUM_PREFIX ) + 40 );
			if ( $checksum === false ) {
				throw new \Exception( "Unable to read from {$pkgFilepath}." );
			}
			$checksum = substr( $checksum, strlen( PhpkgFormat::CHECKSUM_PREFIX ) );
			if ( $checksum != $checkSummer->getSum() ) {
				$loader->parseError( $line, "mismatch in checksum of %s.", $pkgFilepath );
			}
		}
		return $pkgFilepath;
	}

}