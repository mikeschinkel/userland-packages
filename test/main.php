<?php

declare(strict_types=1);

include __DIR__ . '/../src/autoload.php';

UserlandPackages::setDefaultOptions(new \UserlandPackages\Options(
	useTmpDir: false,
	usePackageFile: true,
	allowDiskWrite: true,
	allowPackageGen: true,
	allowBackup: true,
));

//include("phpkg://packages/foo/bar/baz/");
//
//$bar = new Bar();
//echo $bar->prop."\n";

include("phpkg://packages/my-package/");

$foo = new Foo();
$foo->prop->hello();
echo "========\n";
main();
echo "========\n";

$baz = $foo->newBaz();
$baz->hello();
echo get_class($baz);

//$baz = new Baz();
//$baz->hello();

