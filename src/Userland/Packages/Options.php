<?php

namespace Userland\Packages;
class Options {
	public const int RETAIN_BACKUP_DAYS = 30;

	public function __construct(
		/**
		 * @var bool When $packageType is not null it will always look for that package type
		 * regardless of how the list of priority has been set.
		 *
		 * Setting $packageType to other than PHP also sets $usePackageFile to true.
		 */
		private ?object $packageType = null,

		/**
		 * @var string Setting $altDirectory caused packages to be written to an alternate
		 * directory, but only when both $allowDiskWrite and $allowPackageGen as set to true.
		 */
		private string $altDirectory = '',

		/**
		 * @var bool Setting $useTmpDir to true forces $altDirectory to get the value that
		 * is returned by `sys_get_temp_dir()`.
      */
		private bool $useTmpDir = false,

		/**
		 * @var bool When $usePackageFile is set to `true` then we will attempt to load
		 * and/or generate a package vs. loading individual PHP files.
		 */
		private bool $usePackageFile = false,

		/**
		 * @var bool Typically $allowDiskWrite should be set to false for production. This
		 * means packages will only load on-the-fly package loading and never be generated.
		 * Presumably the best practice for production is to have packages pre-generated
		 * by a build process but for sites with low performance needs and where security
		 * impacts are low then on-the-fly generation can be used.
		 *
		 * APCu can also be used when $allowDiskWrite is false.
		 *
		 * Setting $allowDiskWrite to false also forces $useTmpDir, $allowPackageGen, and
		 * $allowBackup to false and $altDirectory to empty string.
		 *
		 * @noinspection PhpPropertyCanBeReadonlyInspection
		 */
		private bool $allowDiskWrite = false,

		private bool $allowPackageGen = false,

		private bool $allowBackup = false,

		private bool $verifyChecksum = true,

		private ?int $retainBackupDays = null,

		/**
		 * @var array
		 * @noinspection PhpPropertyCanBeReadonlyInspection
		 */
		private array $typePriority = [
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
			$this->altDirectory    = '';
		}
		if ( ! $this->allowBackup && is_null($this->retainBackupDays) ) {
			$this->retainBackupDays = self::RETAIN_BACKUP_DAYS;
		}
		if ( ! is_null($this->packageType) && $this->packageType!=PackageType::PHP ) {
			$this->usePackageFile = true;
		}
		if ( $this->useTmpDir ) {
			$this->altDirectory = sys_get_temp_dir();
		}
	}

	public function setPackageType( ?object $packageType ): void {
		$this->packageType = $packageType;
	}

	public
	function hasPackageType(PackageType $type=null): bool {
		return !is_null( $this->packageType );
	}

	public function getPackageType(): ?object {
		return $this->packageType;
	}

	public function verifyChecksum(): bool {
		return $this->verifyChecksum;
	}

	public function allowPackageGen(): bool {
		return $this->allowPackageGen;
	}

	public function usePackageFile(): bool {
		return $this->usePackageFile;
	}

	public function hasAltDirectory(): string {
		return trim($this->altDirectory)!=='';
	}

	public function getAltDirectory(): string {
		return $this->altDirectory;
	}

	public function allowBackup(): bool {
		return $this->allowBackup;
	}

	public function isUsePackageFile(): bool {
		return $this->usePackageFile;
	}

	public function getTypePriority(): array {
		return $this->typePriority;
	}

	public function allowDiskWrite(): bool {
		return $this->allowDiskWrite;
	}

}