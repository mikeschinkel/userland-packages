<?php
require "../src/autoload.php";
UserlandPackages::register();

require "phpkg://english-pkg/";
require "phpkg://french-pkg/";

$english = new English();
$english->greeting();

$french = new French();
$french->salutation();
