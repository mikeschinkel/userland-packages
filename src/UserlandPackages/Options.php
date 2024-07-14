<?php

namespace UserlandPackages;
class Options {
	public const int RETAIN_BACKUP_DAYS = 30;

	public function __construct(
		public ?object $packageType = null,
		public bool $useTmpDir = true,
		public bool $usePackageFile = false,
		public bool $allowDiskWrite = false,
		public bool $allowPackageGen = false,
		public bool $allowBackup = false,
		public string $alternateDir = '',
		public ?int $retainBackupDays = null,
		public array $typePriority = [
			PackageType::APCU,
			PackageType::PHPKG,
			PackageType::PHAR,
			PackageType::ZIP,
			PackageType::TAR,
			PackageType::PHP,
		]
	) {
		if ( ! $this->allowDiskWrite ) {
			$this->allowPackageGen = false;
			$this->allowBackup     = false;
			$this->useTmpDir       = false;
			$this->alternateDir    = '';
		}
		if ( ! $this->allowBackup && is_null($this->retainBackupDays) ) {
			$this->retainBackupDays = self::RETAIN_BACKUP_DAYS;
		}
	}

	public
	function hasPackageType(): bool {
		return ! is_null( $this->packageType );
	}
}