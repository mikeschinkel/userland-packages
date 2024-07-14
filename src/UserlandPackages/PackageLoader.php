<?php
declare( strict_types=1 );

namespace UserlandPackages;

use JetBrains\PhpStorm\NoReturn;

class PackageLoader {
	const string PHPKG_PREFIX = "//PHPKG ";
	const string PHPKG_MANIFEST_PREFIX = self::PHPKG_PREFIX."manifest=";
	const string PHPKG_OPEN_TAG = "<?php /* GENERATED BY UserlandPackages — DO NOT EDIT!!! */\n".self::PHPKG_MANIFEST_PREFIX;
	const string PHPKG_FILE_PREFIX = self::PHPKG_PREFIX."file=";
	const string PHPKG_CHECKSUM_PREFIX = self::PHPKG_PREFIX."checksum=";

	private PackageType $packageType;
	private Package $package;
	private static array $handles = [];
	private string $pkgPath;

	/**
	 * @throws \Exception
	 */
	public function loadPackage( string $pkgName, string $root, Options $options = null ): string {
		$filepath      = null;
		$path          = Filepath::join( $root, $pkgName );
		$this->package = new Package( $pkgName, $path );
		if ( is_null( $options ) ) {
			$options = new Options();
		}
		if ( ! $options->usePackageFile ) {
			$pkgType = PackageType::PHP;
		} else {
			$pkgType = ! $options->hasPackageType()
				? $this->selectPackageType( $options, $path )
				: $options->packageType;
		}
		if ( $pkgType === PackageType::PHP && $options->allowPackageGen ) {
			$pkgType = $this->generatePackage( $options, $path );
		}

		/** @noinspection PhpUnhandledExceptionInspection */
		$filepath = match ( $pkgType ) {
			PackageType::PHPKG => $this->loadPhpkgPackage( $pkgName, $path, $options ),
			PackageType::PHAR  => $this->loadPharPackage( $pkgName, $path, $options ),
			PackageType::ZIP   => $this->loadZipPackage( $pkgName, $path, $options ),
			PackageType::TAR   => $this->loadTarPackage( $pkgName, $path, $options ),
			PackageType::APCU  => $this->loadApcuPackage( $pkgName, $path, $options ),
			PackageType::PHP   => $this->loadPhpPackage( $pkgName, $path ),
		};
		end:
		Packages::addPackage( $this->package );
		$options->packageType = $pkgType;

		return $filepath;
	}

	/**
	 * @param Options $options
	 * @param string $pkgPath
	 *
	 * @return PackageType
	 */
	public function selectPackageType( Options $options, string $pkgPath ): PackageType {
		$pkgType = null;
		foreach ( $options->typePriority as $type ) {
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
					$filepath = $type->getFilepath( $pkgPath );
					if ( file_exists( $filepath ) ) {
						$pkgType = $type;
						break 2;
					}
			}
		}
		if ( is_null( $pkgType ) ) {
			$pkgType = PackageType::PHP;
		}

		return $pkgType;
	}

	public function getPackageFilepaths( string $pkgPath ): array {
		return glob( "{$pkgPath}/*.php" );
	}

	public function loadPackageFiles( string $pkgPath ): array {
		$files = [];
		foreach ( $this->getPackageFilepaths( $pkgPath ) as $filepath ) {
			// Strip trailing whitespace
			$code = trim( file_get_contents( $filepath,false ) );
			// Strip opening and closing PHP tags
			if (str_starts_with( $code, '<?php' )) {
				$code = ltrim(substr($code, 5));
			}
			if (str_ends_with( $code, '?>' )) {
				$code = rtrim(substr($code, 0, -2));
			}
			$files[basename($filepath)] = $code;
		}

		return $files;
	}

	/**
	 * @param string $pkgPath
	 *
	 * @return string
	 */
	public function generatePhpkgPackage( string $pkgPath ): string {
		$fileContents = $this->loadPackageFiles( $pkgPath );
		$manifest = (object)['files'=>[]];
		$sizes = [];

		// Create a minimal manifest with positions==0 so we can determine how long the
		// manifest is as a string ignoring the lengths of the position values.
		// Also loop through to get code to write for each file.
		foreach ( $fileContents as $filename => $code ) {
			$manifest->files[] = $filename;
			$obj = (object)[
				'name' => $filename,
				'size' => strlen( $code )+1,
			];
			$sizes[$filename] = $obj;
			$code = sprintf("//PHPKG file=%s\n%s\n",json_encode($obj),$code);
			$fileContents[ $filename ]    = $code;
		}
		$code = null;

		$package = sprintf( "%s%s\n%s",
			self::PHPKG_OPEN_TAG,
			json_encode($manifest),
			implode( '', $fileContents ),
		);;
		return sprintf("%s%s%s\n",$package, self::PHPKG_CHECKSUM_PREFIX, sha1($package));
	}

	public function generatePackage( Options $options, string $pkgPath ): PackageType {
		$pkgType = null;
		// Get the output path for the package being either in the temp dir,
		// or in the package dir, depending on $options->useTmpDir setting.
		$outPath = $options->useTmpDir
			? Filepath::join( sys_get_temp_dir(), $pkgPath )
			: $pkgPath;

		// Now loop through in package type priority order to see which
		// type we can create a package for.
		foreach ( $options->typePriority as $type ) {
			switch ( $type ) {
				case PackageType::PHPKG:
					if ( $options->allowDiskWrite ) {
						$content = $this->generatePhpkgPackage( $pkgPath );
						file_put_contents( $type->getFilepath($outPath), $content );
						$pkgType = $type;
						break 2;
					}
				case PackageType::APCU:
				case PackageType::PHAR:
				case PackageType::ZIP:
				case PackageType::TAR:
				case PackageType::PHP:
					// Do nothing, just here for coverage
			}
		}
		if ( is_null( $pkgType ) ) {
			$pkgType = PackageType::PHP;
		}

		return $pkgType;
	}


	/**
	 * @param int $handleNo
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getHandle( int $handleNo ): mixed {
		if ( ! isset( self::$handles[ $handleNo ] ) ) {
			throw new \Exception( "Handle $handleNo does not exist" );
		}

		return self::$handles[ $handleNo ];
	}

	/**
	 * @param int $handleNo
	 * @param mixed $resource
	 *
	 * @return void
	 */
	private static function setHandle( mixed $handleNo ): void {
		self::$handles[ (int) $handleNo ] = $handleNo;
	}

	/**
	 * @param int $handleNo
	 *
	 * @return void
	 */
	private static function releaseHandle( mixed $handleNo ): void {
		unset( self::$handles[ (int) $handleNo ] );
	}

	/**
	 * @param string $filepath
	 *
	 * @return resource|false
	 * @throws \Exception
	 */
	private function transformPhpFile( string $filepath,string $code,bool $stripOpenTag ): mixed {
		$parser = new FileParser( $filepath, $this->package );
		
		$parse    = $parser->parse( "<?php\n{$code}" );
		$replacer = new Replacer( $filepath, $this->package );
		$replacer->replace( $parse );
		$code = implode( '', array_map( function ( \PhpToken $token ): string {
			return $token->text;
		}, $parse->tokens ) );
		if ($stripOpenTag && str_starts_with( $code, '<?php' )) {
			$code = ltrim(substr($code, 5));
		}
		return $this->memWrite($code);
	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 */
	private function loadPhpFile( string $filepath, mixed $handle ): void {
		require( sprintf( "%s://%d/%s",
			FileStream::PROTOCOL,
			$handle,
			$filepath
		) );
	}

	/**
	 * @param string $phpkg
	 *
	 * @return string
	 */
	private function parseAndLoadPhpkg( string $phpkg, string $pkgPath ): string {
		$pkgFilepath = PackageType::PHPKG->getFilepath($pkgPath);
		$line = 1;
		$pkgHandle = $this->memWrite( $phpkg );
		unset($phpkg);
		$openTag = fgets($pkgHandle);
		if (!str_starts_with(self::PHPKG_OPEN_TAG,$openTag)) {
			$this->parseError($line,"unable to open tag; expected '%s', got '%s' instead for %s.",
				self::PHPKG_OPEN_TAG,
				substr($openTag,strlen(self::PHPKG_OPEN_TAG)),
				$pkgFilepath
			);
		}
		unset($openTag);
		$line++;
		$manifest = fgets($pkgHandle);
		if (!str_starts_with($manifest,self::PHPKG_MANIFEST_PREFIX)) {
			$this->parseError($line,"unable to parse manifest; expected '%s', got '%s' instead for %s.",
				self::PHPKG_MANIFEST_PREFIX,
				substr($manifest,strlen(self::PHPKG_MANIFEST_PREFIX)),
				$pkgFilepath
			);
		}
		$manifest = substr($manifest,strlen(self::PHPKG_MANIFEST_PREFIX));
		$decodedManifest = json_decode($manifest);
		if (!is_object($decodedManifest)||!is_array($decodedManifest->files)) {
			$this->parseError($line,"unable to decode manifest; got '%s' for %s.",$manifest,$pkgFilepath);
		}
		unset($manifest);
		foreach($decodedManifest->files as $index => $filename ) {
			$filepath = Filepath::join($pkgPath,$filename);
			$line++;
			$file = fgets($pkgHandle);
			if (!str_starts_with($file,self::PHPKG_FILE_PREFIX)) {
				$this->parseError($line,"unable to parse file header; expected '%s', got '%s' instead for %s of %s.",
					self::PHPKG_FILE_PREFIX,
					substr($file,strlen(self::PHPKG_FILE_PREFIX)),
					$filename,$pkgFilepath
				);
			}
			$file = json_decode(substr($file,strlen(self::PHPKG_FILE_PREFIX)));
			if (is_null($file)) {
				$this->parseError($line," unable to decode file header for %s of %s.",$filename,$pkgFilepath );
			}
			if (!property_exists($file,'name')) {
				$this->parseError($line,"corrupt file header for %s.",$filename,$pkgFilepath );
			}
			if ($file->name!=$filename) {
				$this->parseError($line,"mismatch in manifest and file header of %s of %s.",$filename,$pkgFilepath );
			}
			$code = fread($pkgHandle, $file->size);
			
			$handle = $this->transformPhpFile( $filepath, $code, false );
			self::setHandle( $handle );
			$this->loadPhpFile( $filepath, $handle );
			self::releaseHandle( $handle );
			$line+= substr_count($code,"\n");
		}
		return $pkgFilepath;
	}

	/**
	 * @param string $pattern
	 * @param mixed ...$args
	 *
	 * @return void
	 */
	#[NoReturn]
	private function parseError(int $line,string $pattern, mixed ...$args):void {
		\UserlandPackages::parseError("Invalid PHPKG; ".$pattern, $this->pkgPath, $line, ...$args);
		exit(1);
	}

	/**
	 * Write parameter to php://memory and return handle
	 *
	 * @param string $code
	 *
	 * @return resource
	 */
	private function memWrite( string $code ): mixed {
		$handle = fopen( 'php://memory', 'r+' );
		if ( $handle === false ) {
			
			throw new \Exception("error opening php://memory for storing code");
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
	 * @param string $pkgPath
	 * @param Options $options
	 *
	 * @return string
	 */
	private function loadPhpkgPackage( string $pkgName, string $pkgPath, Options $options ): string {
		$phpkg = file_get_contents(PackageType::PHPKG->getFilepath( $pkgPath,$options ),false);
		// TODO: Separate load out from parse.
		return $this->parseAndLoadPhpkg($phpkg,$pkgPath,$options);
	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 */
	private function loadPharPackage( string $pkgName, string $filepath, Options $options ): string {
		
		throw new \Exception( "implement me" );

		return PackgeType::PHAR->getFilepath( $path,$options );
	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 */
	private function loadZipPackage( string $pkgName, string $filepath, Options $options ): string {
		
		throw new \Exception( "implement me" );

		return PackgeType::ZIP->getFilepath( $path,$options );
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
	private function loadTarPackage( string $pkgName, string $filepath, Options $options ): string {
		
		throw new \Exception( "implement me" );

		return PackgeType::TAR->getFilepath( $path,$options );
	}

	/**
	 * @param string $pkgName
	 * @param string $filepath
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function loadApcuPackage( string $pkgName, string $filepath, Options $options ): string {
		
		throw new \Exception( "implement me" );

		return PackgeType::ACPU->getFilepath( $path,$options );
	}

	/**
	 * @param string $pkgName
	 * @param string $pkgPath
	 * @param Options $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function loadPhpPackage( string $pkgName, string $pkgPath ): string {
		foreach ( $this->getPackageFilepaths( $pkgPath ) as $filepath ) {
			
			$code   = file_get_contents( $filepath, false );
			$handle = $this->transformPhpFile( $filepath, $code, true );
			self::setHandle( $handle );
			$this->loadPhpFile( $filepath, $handle );
			self::releaseHandle( $handle );
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

