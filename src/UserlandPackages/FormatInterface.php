<?php

namespace UserlandPackages;

interface FormatInterface {
	public function generatePackage( PackageLoader $loader, Options $options ): string;
	public function loadPackage( PackageLoader $loader, Options $options ): string;
}