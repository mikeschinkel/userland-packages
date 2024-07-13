<?php
declare( strict_types=1 );

namespace FileOnly {
	class Baz {
		function hello(): void {
			echo "Yo Dawg!\n";
		}
	}
}

namespace {
	use FileOnly\Baz;
	class Foo {
		public Baz $prop;
		public function __construct() {
			$this->prop = new Baz();
		}
		public function newBaz():Baz {
			return new Baz();
		}
	}
}

