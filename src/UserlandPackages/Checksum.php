<?php declare(strict_types=1);

namespace UserlandPackages;

class Checksum {
	public function __construct(
		public string $value='',
	) {
		if (!empty($value)) {
			$this->value = sha1($value);
		}
	}
	public function append(string $value):void {
		$this->value .= sha1($value);
	}
	public function prepend(string $value):void {
		$this->value = sha1($value) . $this->value;
	}
	public function getSum():string {
		return sha1($this->value);
	}
}