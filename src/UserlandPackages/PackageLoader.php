<?php
declare( strict_types=1 );

namespace UserlandPackages;

class PackageLoader {

	private PackageType $packageType;
	private Package $package;
	private static array $filepaths = [];
	public string $pkgPath;

	/**
	 * @throws \Exception
	 */
	public function loadPackage( string $pkgName, string $root, Options $options = null ): string {
		$this->pkgPath = Filepath::join( $root, $pkgName );
		$this->package = new Package( $pkgName, $this->pkgPath );
		if ( is_null( $options ) ) {
			$options = new Options();
		}
		if ( ! $options->usePackageFile() ) {
			$pkgType = PackageType::PHP;
		} else {
			$pkgType = ! $options->hasPackageType()
				? $this->selectPackageType( $options )
				: $options->getPackageType();
			if ( $options->allowPackageGen() && $this->packageNeedsRegeneration( $pkgType, $options ) ) {
				// We assume if a package was selected by priority rules and existence
				// then we want to regenerate it rather than keep looking for other types
				// of packages that may or may not exist
				$this->removeAndBackupPackage( $pkgType, $options );
				$this->generatePackage( $pkgName, $options, $pkgType );
			}
		}
		if ($pkgType===PackageType::PHP && $options->allowPackageGen() ) {
			$pkgType = $this->generatePackage( $pkgName, $options );
		}
		$formatObject = $pkgType->dispenseFormatObject();
		$filepath = $formatObject->loadPackage( $this, $options );

		Packages::addPackage( $this->package );
		$options->setPackageType( $pkgType );

		return $filepath;
	}

	/**
	 * @param \UserlandPackages\PackageType $pkgType
	 * @param Options $options
	 *
	 * @return bool
	 */
	public function packageExists( PackageType $pkgType, Options $options ): bool {
		return file_exists( $pkgType->getFilepath( $this->pkgPath, $options ) );
	}

	/**
	 * Compares the modified time of the package file with the modified time for
	 * all .PHP files in the package directory. If any .PHP file has a later
	 * timestamp then it returns `true` meaning package regeneration is needed.
	 *
	 * @param Options $options
	 * @param PackageType $pkgType
	 *
	 * @return bool
	 */
	public function packageNeedsRegeneration( PackageType $pkgType, Options $options ): bool {
		$needsRegen = false;
		switch ( $pkgType ) {
			case PackageType::PHPKG:
			case PackageType::PHAR:
			case PackageType::ZIP:
			case PackageType::TAR:
				$filepath = $pkgType->getFilepath( $this->pkgPath, $options );
				if ( !file_exists( $filepath ) ) {
					$needsRegen = true;
					break;
				}
				$modified = filemtime( $filepath );
				if ($modified === false ) {
					break;
				}
				foreach ( $this->getPackageFilepaths() as $filepath ) {
					if ( $modified < filemtime( $filepath ) ) {
						$needsRegen = true;
						break;
					}
				}
				break;
			case PackageType::PHP:
				// Nothing to do. Just here to full represent the enum.
			case PackageType::APCU:
				// TODO: Maybe later
		}
		return $needsRegen;
	}

	/**
	 * Renames current package to a backup file w/ "${pkgPath}.{$timestamp}.bak" format.
	 * Deletes packages older than Options::RETAIN_BACKUP_DAYS days old.
	 *
	 * @throws \Exception
	 */
	public function removeAndBackupPackage( PackageType $pkgType, Options $options ): void {
		if ( ! $options->allowBackup() ) {
			goto end;
		}
		$oldest   = time() - ( Options::RETAIN_BACKUP_DAYS * 86400 ); // 30 days
		$filepath = $pkgType->getFilepath( $this->pkgPath, $options );
		if ( ! file_exists( $filepath ) ) {
			goto end;
		}
		foreach ( glob( "{$filepath}.*.bak" ) as $backupFile ) {
			$timestamp = (int) substr( basename( $backupFile, '.bak' ), strlen( basename( $filepath ) ) + 1 );
			if ( $timestamp >= $oldest ) {
				continue;
			}
			if ( ! unlink( $backupFile ) ) {
				throw new \Exception( "Unable to remove package file '{$backupFile}'." );
			}
		}
		$backupFile = sprintf( "%s.%d.bak", $filepath, time() );
		if ( ! rename( $filepath, $backupFile ) ) {
			throw new \Exception( "Unable to rename package file '{$filepath}' to '{$backupFile}'." );
		}
		end:
	}

	/**
	 * @param Options $options
	 *
	 * @return PackageType
	 * @throws \Exception
	 */
	public function selectPackageType( Options $options ): PackageType {
		$pkgType = null;
		if ( ! $options->usePackageFile() ) {
			goto end;
		}
		foreach ( $options->getTypePriority() as $type ) {
			switch ( $type ) {
				case PackageType::PHP:
					continue 2;
				case PackageType::APCU:
					// Have an APCU_OK property
					// Which allows using APCU if APCU is available
				case PackageType::PHAR:
				case PackageType::PHPKG:
				case PackageType::ZIP:
				case PackageType::TAR:
					$filepath = $type->getFilepath( $this->pkgPath, $options );
					if ( ! file_exists( $filepath ) ) {
						continue 2;
					}
					$pkgType = $type;
					break 2;
			}
		}
		end:
		if ( is_null( $pkgType ) ) {
			$pkgType = PackageType::PHP;
		}

		return $pkgType;
	}

	public function getPackageFilepaths(?string $pkgPath = null): array {
		if (is_null($pkgPath)){
			$pkgPath = $this->pkgPath;
		}
		if ( ! isset( self::$filepaths[ $pkgPath ] ) ) {
			self::$filepaths[ $this->pkgPath ] = glob( "{$pkgPath}/*.php" );
		}

		return self::$filepaths[ $pkgPath ];
	}

	public function loadPackageFiles( string $pkgPath ): array {
		$files = [];
		foreach ( $this->getPackageFilepaths( $pkgPath ) as $filepath ) {
			// Strip trailing whitespace
			$code = trim( file_get_contents( $filepath, false ) );
			$files[ basename( $filepath ) ] = $code;
		}

		return $files;
	}

	public function packageFilepath( Options $options ): string {
		$pkgName = basename($this->pkgPath);
		return $options->hasAltDirectory()
			? Filepath::join( $options->getAltDirectory(), $pkgName )
			: $this->pkgPath;
	}

	/**
	 * @throws \Exception
	 */
	public function generatePackage( string $pkgName, Options $options, $pkgType = null ): PackageType {
		if ( !$options->allowDiskWrite() ) {
			goto end;
		}

		// Get the output path for the package being either in the temp dir,
		// or in the package dir, depending on $options->useTmpDir setting.
		$outPath = $this->packageFilepath($options);

		$pkgTypes = is_null( $pkgType )
			? $options->getTypePriority()
			: [ $pkgType ];

		// Now loop through in package type priority order to see which
		// type we can create a package for.
		foreach ( $pkgTypes as $type ) {
			switch ( $type ) {
				case PackageType::PHPKG:
				case PackageType::PHAR:
				case PackageType::ZIP:
					$object = $type->dispenseFormatObject();
					$object->generatePackage( $this, $options );
					$pkgType = $type;
					break 2;
				case PackageType::APCU:
				case PackageType::TAR:
				case PackageType::PHP:
					break;
			}
		}
	end:
		if ( is_null( $pkgType ) ) {
			$pkgType = PackageType::PHP;
		}

		return $pkgType;
	}

	/**
	 * @param string $filepath
	 *
	 * @return resource|false
	 * @throws \Exception
	 */
	public function transformPhpFile( string $filepath, string $code ): mixed {
		$parser = new FileParser( $filepath, $this->package );

		$parse    = $parser->parse( $code );
		$replacer = new Replacer( $filepath, $this->package );
		$replacer->replace( $parse );
		return implode( '', array_map( function ( \PhpToken $token ): string {
			return $token->text;
		}, $parse->tokens ) );
	}

	/**
	 * @param string $filepath
	 * @param mixed $handle
	 *
	 * @return void
	 */
	public function loadPhpFile( string $filepath, mixed $handle ): void {
		require( sprintf( "%s://%d/%s",
			FileStream::PROTOCOL,
			$handle,
			$filepath
		) );
	}

	/**
	 * @param string $pattern
	 * @param mixed ...$args
	 *
	 * @return void
	 */
	public function parseError( int $line, string $pattern, mixed ...$args ): void {
		parseError( "Invalid PHPKG; " . $pattern, $this->pkgPath, $line, ...$args );
	}

	/**
	 * Write parameter to php://memory and return handle
	 *
	 * @param string $code
	 *
	 * @return resource
	 * @throws \Exception
	 */
	public function memWrite( string $code ): mixed {
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

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 */
	private function loadZipPackage( string $pkgName, Options $options ): string {

		throw new \Exception( "implement me" );

		return PackageType::ZIP->getFilepath( $this->pkgPath, $options );
	}

	/**
	 * @param string $pkgName
	 * * @param string $filepath
	 * * @param Options $options
	 * *
	 * * @return string
	 *
	 * @throws \Exception
	 */
	private function loadTarPackage( string $pkgName, Options $options ): string {

		throw new \Exception( "implement me" );

		return PackageType::TAR->getFilepath( $this->pkgPath, $options );
	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function loadApcuPackage( string $pkgName, Options $options ): string {

		throw new \Exception( "implement me" );

		return PackageType::APCU->getFilepath( $this->pkgPath, $options );
	}

	/**
	 * @param string $pkgPath
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function loadPhpPackage( string $pkgPath ): string {
		foreach ( $this->getPackageFilepaths( $pkgPath ) as $filepath ) {

			$code   = file_get_contents( $filepath, false );
			$code = $this->transformPhpFile( $filepath, $code );
			$handle = memWrite($code);
			Handles::setHandle( $handle );
			$this->loadPhpFile( $filepath, $handle );
			Handles::releaseHandle( $handle );
		}

		return $pkgPath;
	}

	/**
	 * @param mixed $value
	 * @param int $len
	 *
	 * @return string
	 */
	private function padLeft( mixed $value, int $len ): string {
		return str_pad( (string) $value, $len, ' ', STR_PAD_LEFT );
	}

}

