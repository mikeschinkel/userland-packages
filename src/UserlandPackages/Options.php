<?php

namespace UserlandPackages;
class Options {
	public function __construct(
		public ?object $packageType = null,
		public bool $useTmpDir = true,
		public bool $usePackageFile = false,
		public bool $allowDiskWrite = false,
		public bool $allowPackageGen = false,
		public array $typePriority = [
			PackageType::APCU,
			PackageType::PHPKG,
			PackageType::PHAR,
			PackageType::ZIP,
			PackageType::TAR,
			PackageType::PHP,
		]) {}

public
function hasPackageType(): bool {
	return !is_null( $this->packageType );
}
}