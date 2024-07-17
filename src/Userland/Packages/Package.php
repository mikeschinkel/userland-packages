<?php

namespace Userland\Packages;

class Package {
	public string $pkgOnlyText;
	public function __construct(
		public string $name,
		public string $filepath,
	) {
		$this->pkgOnlyText = Replacer::PACKAGE_ONLY . '_' . Packages::getNum($this->name);
	}
}
