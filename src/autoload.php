<?php
spl_autoload_register(function (string $class) {
	if (DIRECTORY_SEPARATOR==='/') {
		$class = str_replace( '\\', '/', $class );
	}
	if (file_exists($fp = sprintf( "%s%s%s.php",
		__DIR__,
		DIRECTORY_SEPARATOR,
		$class))) {
		include $fp;
	}
});
