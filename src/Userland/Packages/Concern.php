<?php

namespace Userland\Packages;

class Concern {
	function __construct(
		public int $tokenType,
		public \PhpToken $token,
		public array $extra = [],
	) {
	}
}