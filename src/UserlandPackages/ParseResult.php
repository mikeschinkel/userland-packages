<?php

namespace UserlandPackages;

class ParseResult {
	public array $concerns = [];

	public function __construct(
		public Package $package,
		public array $tokens = [],
	) {
	}
}