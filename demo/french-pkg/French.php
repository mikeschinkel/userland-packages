<?php
use PackageOnly\A;
use PackageOnly\B;
class French {
	public A $b;
	public B $c;
	public function __construct() {
		$this->b = new A();
		$this->c = new B();
	}
	public function salutation():void {
		$this->b->salutation();
		echo ' ';
		$this->c->salutation();
		echo "\n";
	}
}