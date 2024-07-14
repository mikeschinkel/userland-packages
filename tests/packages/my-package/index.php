<?php

use PackageOnly\MyClass;

function main() {
	echo "We are starting here.\n";

	$c = new MyClass();
	echo $c->prop;

	echo "And we are ending here.\n";

}
