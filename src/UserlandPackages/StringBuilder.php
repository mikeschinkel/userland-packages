<?php
declare( strict_types=1 );

namespace UserlandPackages;

class StringBuilder {
	private mixed $handle;
	private int $size = 0;
	public function __construct() {
		$this->handle = fopen('php://memory','r+');
	}
	public function write(string $string):int|false {
		$n = fwrite($this->handle, $string);
		$this->size += $n;
		return $n;
	}
	public function getSize():int {
		return $this->size;
	}
	public function getString():string {
		rewind($this->handle);
		return fread($this->handle,$this->size);
	}
	public function release() {
		fclose($this->handle);
		$this->handle = null;
		$this->size = 0;
	}
}