<?php

namespace UserlandPackages;

use JetBrains\PhpStorm\NoReturn;

/**
 *
 */
class FileParser {
	const string FILE_ONLY= 'FileOnly';
	const string PACKAGE_ONLY= 'PackageOnly';

	private int $pos = 0;
	//private string $current_ns = '';
	private ParseResult $result;

	public function __construct(
		public string $filepath,
		Package $package
	) {
		$this->result = new ParseResult($package);
	}
	private function maybeConsume(int|string $tt):void {
		
		$this->maybeCapture($tt);
	}
	private function tokenName(\PHPToken|string $tt=null):string {
		if (is_null($tt)) {
			$tt = $this->result->tokens[$this->pos];
		}
		if ((string)(int)$tt == $tt) {
			return \token_name($tt);
		}
		if (is_string($tt)) {
			return $tt;
		}
		return \token_name($tt->id);
	}
	private function consumeOneOf(int|string ...$tt):void {
		
		$this->captureOneOf(...$tt);
	}
	private function captureOneOf(int|string ...$tt):?string {
		foreach ($tt as $i => $t) {
			$value = $this->maybeCapture( $t );
			if ( ! is_null( $value ) ) {
				return $value;
			}
		}
		foreach ($tt as $i => $t) {
			$tt[$i] = $this->tokenName($t);
		}
		$tt = implode( "' OR '", $tt);
		$this->parseError("'%s' expected; got '%s' instead.", $tt, $this->token()->text);
	}

	private function consume(int|string $tt):void {
		$this->capture($tt);
	}

	/**
	 * @param int|string $tt
	 *
	 * @return string|null
	 */
	private function capture(int|string $tt):?string {
		$this->pos++;
		if ($this->pos >= count($this->result->tokens)) {
			$this->parseError("'%s' expected; got EOF instead.",$this->tokenName());
		}
		$t = $this->result->tokens[$this->pos];
		if (is_string($tt)) {
			if ($tt !== $t->text) {
				$this->parseError("'%s' expected; got '{$tt}' instead.",$this->tokenName(),$tt);
			}
			return $tt;
		}
		if ($tt !== $t->id) {
			$this->parseError("'%s' expected; got '{$tt}' instead.",$this->tokenName(),$tt);
		}
		return $t->text;
	}

	/**
	 * @return \PHPToken
	 */
	private function token():\PHPToken {
		return $this->result->tokens[$this->pos];
	}

	/**
	 * @param int|string $tt
	 *
	 * @return string|null
	 */
	private function maybeCapture(int|string $tt):?string {
		$this->pos++;
		if ($this->pos >= count($this->result->tokens)) {
			$this->pos--;
			return null;
		}
		$t = $this->result->tokens[$this->pos];
		if (is_string($tt)) {
			if ($tt !== $t->text) {
				$this->pos--;
				return null;
			}
			return $tt;
		}
		if ($t->id !== $tt) {
			$this->pos--;
			return null;
		}
		return $t->text;
	}

	/**
	 * @param ParseResult $parse
	 *
	 * @return int
	 */
	private function namespace(ParseResult $parse):int {
		$this->consume(T_WHITESPACE);
		$value = $this->captureOneOf('{', T_STRING, T_NAME_QUALIFIED);
		if ($value==='{') {
			return $this->pos;
		}
		$t = $this->token();
		$printErrorFunc = function(\PhpToken $t, string $ns) {
			$this->parseError("Cannot declare '%s' namespace; '%s' can have no children",$t->text,$ns);
		};
		if ($t->id===T_NAME_QUALIFIED) {
			if (str_starts_with($value,self::FILE_ONLY) ) {
				$printErrorFunc($t,self::FILE_ONLY);
			} else
			if (str_starts_with($value,self::PACKAGE_ONLY)) {
				$printErrorFunc($t,self::PACKAGE_ONLY);
			}
		}
		//$this->current_ns = $t->text;

		$parse->concerns[] = new Concern(T_NAMESPACE, $this->token());
		$this->maybeConsume(T_WHITESPACE);
		$this->consumeOneOf(';','{');
		return $this->pos;
	}

	/**
	 * @param ParseResult $parse
	 *
	 * @return int
	 */
	private function use(ParseResult $parse):int {
		$this->consume(T_WHITESPACE);
		
		$this->consumeOneOf(T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED);
		$ns = $this->token();
		$concern = new Concern(T_USE, $this->token());
		$this->maybeConsume(T_WHITESPACE);
		
		$value = $this->captureOneOf(';',T_AS);
		$parse->concerns[] = $concern;
		if ($value===';') {
			return $this->pos;
		}
		$t = $this->token();
		if ($t->id===T_AS ) {
			$printErrorFunc = function(\PhpToken $t, string $ns) {
				$this->parseError("It is invalid to pair an 'as' with a 'use' statement for '%s' namespace",$ns);
			};
			if (str_starts_with($ns->text,self::FILE_ONLY) ) {
				$printErrorFunc($ns,self::FILE_ONLY);
			} else if (str_starts_with($ns->text,self::PACKAGE_ONLY)) {
				$printErrorFunc($ns,self::PACKAGE_ONLY);
			}
		}
		return $this->pos;
	}

	/**
	 * @param string $code
	 *
	 * @return ParseResult
	 * @throws \Exception
	 */
	public function parse(string $code):ParseResult {
		$this->result->tokens = \PhpToken::tokenize($code);
		for($pos= 0; $pos< count($this->result->tokens);$pos++) {
			$this->pos = $pos;
			$t = $this->result->tokens[$pos];
			switch ($t->id) {
				case T_USE:
					$pos = $this->use($this->result);
					break;
				case T_NAMESPACE:
					$pos = $this->namespace($this->result);
					break;
				case T_NAME_QUALIFIED:
				case T_CLASS:
				case T_STRING:
				case T_FUNCTION:
				case T_CONSTANT_ENCAPSED_STRING:
				case T_WHITESPACE:
				case T_GOTO:
				case T_STATIC:
				case T_PRIVATE:
				case T_VARIABLE:
				case T_OPEN_TAG:
				case T_DOC_COMMENT:
				case T_DOUBLE_COLON:
				case T_DOUBLE_ARROW:
				case T_NEW:
				case T_RETURN:
				case T_PUBLIC:
				case T_DIR:
				case T_REQUIRE_ONCE:
				case T_IF:
				case T_DECLARE:
				case T_LNUMBER:
				case T_ECHO:
				case T_OBJECT_OPERATOR:
				case T_COMMENT:
				case T_PRINT:
				case T_INLINE_HTML:
					// Tokens to ignore
					continue 2;
				default:
					if ($t->id < 255) {
						continue 2;
					}
					throw new \Exception( "Unknown token: {$t}.");

			}
		}
		return $this->result;
	}

	/**
	 * @param string $pattern
	 * @param mixed ...$args
	 *
	 * @return void
	 */
	#[NoReturn]
	private function parseError(string $pattern, mixed ...$args):void {
		\UserlandPackages::parseError($pattern, $this->filepath, $this->token()->line, ...$args);
		exit(1);
	}

}