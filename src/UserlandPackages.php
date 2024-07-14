<?php 

declare( strict_types=1 );

namespace {

	use UserlandPackages\Filepath;
	use UserlandPackages\Options;
	use UserlandPackages\Replacer;
	use UserlandPackages\PackageStream;
	use UserlandPackages\FileStream;

	class UserlandPackages {
		private static bool $registered = false;
		private static Options $defaultOptions;

		public static function getDefaultOptions(): Options {
			return self::$defaultOptions;
		}
		public static function setDefaultOptions(Options $options): void {
			self::register();
			self::$defaultOptions = $options;
		}

		public static function register(): void {
			if ( self::$registered ) {
				goto end;
			}
			require_once( Filepath::join( __DIR__, self::class, 'functions.php' ) );
			Replacer::initialize();
			PackageStream::register();
			FileStream::register();
			self::$defaultOptions = new Options();
			self::$registered = true;
			end:
		}

		public static function parseError(string $pattern, string $filepath, int $lineNo, ...$args):void {
			$pattern = "UserlandPackages Parse Error: {$pattern} in %s on line %d";
			$args[] = $filepath;
			$args[] = $lineNo;
			fprintf( STDOUT, $pattern,...$args);
		}

		public static function getContext(): mixed {
			$opts   = [
				PackageStream::PROTOCOL => [],
				FileStream::PROTOCOL    => [],
			];
			$params = [];

			return stream_context_create( $opts, $params );
		}
	}
}
