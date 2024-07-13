<?php
use PackageOnly\A;
use PackageOnly\B;
class English {
	public A $b;
	public B $c;
	public function __construct() {
		$this->b = new A();
		$this->c = new B();
	}
	public function greeting():void {
		$this->b->greeting();
		echo ' ';
		$this->c->greeting();
		echo "\n";
	}
}