<?php

namespace Userland\Packages;

class Replacer {
	const string FILE_ONLY = 'FileOnly';
	const string PACKAGE_ONLY = 'PackageOnly';

	private static array $nums = [];
	public static string $script_dir;
	private string $fileOnlyText;
	private Package $package;

	public function __construct(
		string $filepath,
		Package $package,
	){
		$this->fileOnlyText = self::FILE_ONLY . '_' . self::getNum($filepath);
		$this->package      = $package;
	}

	public static function initialize():void {
		self::$script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
	}

	public function replace(ParseResult $pr){
		for ($i = count($pr->concerns)-1; $i >= 0; $i--){
			$this->replaceOne($pr, $i);
		}
	}
	private function replaceOne(ParseResult $pr, int $concernNum ){
		$concern = $pr->concerns[$concernNum];
		$text = &$concern->token->text;
		switch ($concern->tokenType){
			case T_NAMESPACE:
				if ( $text===self::FILE_ONLY) {
					$text = $this->fileOnlyText;
				}
				if ( $text===self::PACKAGE_ONLY) {
					$text = $this->package->pkgOnlyText;
				}
				break;
			case T_USE:
				$ns = $text;
				if ($ns[0] === '\\') {
					$ns = substr($ns, 1);
				}
				if ( str_starts_with( $ns, self::FILE_ONLY ) ) {
					$text = $this->replaceSymbol(
						$text,
						self::FILE_ONLY,
						$this->fileOnlyText
					);
				}
				if ( str_starts_with( $ns, self::PACKAGE_ONLY ) ) {
					$text = $this->replaceSymbol(
						$text,
						self::PACKAGE_ONLY,
						$this->package->pkgOnlyText
					);
				}
				break;
		}
	}
	private function replaceSymbol(string $haystack, string $find, string $replace):string{
		return preg_replace(
			'#^(\\\\?)'.$find.'(.*)$#',
			"$1{$replace}$2",
			$haystack,
			1
		);
	}
	private function getNum(string $filepath):string{
		do {
			// Generate a random number between 1,000,000 and 9,999,999
			$num = mt_rand( 10000, 99999 );
		} while ( in_array( $num, self::$nums, true ) ); // Ensure the handle is unique
		$filepath = substr($filepath,strlen(self::$script_dir));
		self::$nums[$filepath] = $num;
		return (string)$num;
	}
}