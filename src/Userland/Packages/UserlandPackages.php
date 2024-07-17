<?php /** @noinspection PhpIllegalPsrClassPathInspection */

declare( strict_types=1 );

namespace {

	use Userland\Packages\Filepath;
	use Userland\Packages\Options;
	use Userland\Packages\Replacer;
	use Userland\Packages\PackageStream;
	use Userland\Packages\FileStream;

	class UserlandPackages {
		/**
		 * @var bool
		 */
		private static bool $registered = false;

		/**
		 * @var Options
		 */
		private static Options $defaultOptions;

		/**
		 * @return Options
		 */
		public static function getDefaultOptions(): Options {
			return self::$defaultOptions;
		}

		/**
		 * @param Options $options
		 *
		 * @return void
		 * @throws Exception
		 */
		public static function setDefaultOptions(Options $options): void {
			self::register();
			self::$defaultOptions = $options;
		}

		/**
		 * @return void
		 * @throws Exception
		 */
		public static function register(): void {
			if ( self::$registered ) {
				goto end;
			}
			require_once( Filepath::join( __DIR__, 'functions.php' ) );
			Replacer::initialize();
			PackageStream::register();
			FileStream::register();
			self::$defaultOptions = new Options();
			self::$registered = true;
			end:
		}

		/**
		 * @param string $pattern
		 * @param string $filepath
		 * @param int $lineNo
		 * @param ...$args
		 *
		 * @return void
		 */
		public static function parseError(string $pattern, string $filepath, int $lineNo, ...$args):void {
			$pattern = "Userland\Packages Parse Error: {$pattern} in %s on line %d";
			$args[] = $filepath;
			$args[] = $lineNo;
			fprintf( STDOUT, $pattern,...$args);
		}

		/**
		 * @return mixed
		 */
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
