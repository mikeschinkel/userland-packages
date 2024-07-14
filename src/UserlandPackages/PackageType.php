<?php

namespace UserlandPackages;

enum PackageType:string {
	// PHP means no special handling. The package file is just a (collections of) PHP file(s) in a directory.
	case PHP = 'php';

	// PHPKG means pre-parsed PHP files concatenated into one file designed to be directly loaded.
	case PHPKG = 'phpkg';

	// PHAR means pre-parsed PHP files designed to be directly loaded as a .phar.
	case PHAR = 'phar';

	// ZIP means pre-parsed PHP files as a ZIP file designed to be indirectly loaded.
	case ZIP = 'zip';

	// TAR means pre-parsed PHP files as a TAR file designed to be indirectly loaded.
	case TAR = 'tar';

	// APCU means pre-parsed PHP files loaded into APCu and designed to be indirectly loaded.
	case APCU = 'apcu';

	/**
	 * @param string $path
	 * @param Options $options
	 * @param string $ext
	 *
	 * @return string
	 */
	public function getFilepath( string $path, Options $options ):string {
		return Filepath::join($path,sprintf("%s.%s",basename($path),$this->value));
	}

}

