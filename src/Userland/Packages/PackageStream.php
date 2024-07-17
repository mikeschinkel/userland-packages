<?php

namespace Userland\Packages;

class PackageStream {
	const string PROTOCOL = "phpkg";

	public mixed $context;
	private int $position;

	/**
	 * @throws \Exception
	 */
	public function stream_open(string $stream, $mode, $options, &$opened_path) {
		$root = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
		$root = dirname($root[0]['file'] ?? '');
		if ($root==='') {
			$msg = sprintf("Unexpected error: debug_backtrace()[0]['file'] is empty or does not exist for stream '%s'.", $stream);
			throw new \Exception($msg);
		}
		$url = parse_url($stream);
		$pkgName = $url[ 'host' ] ?? '';
		if ($pkgName==="") {
			$msg = sprintf("Package '%s' path not specified", $stream);
			throw new \Exception($msg);
		}
		if (strlen($url['path'] ?? '')>1) { // e.g. not '' or '/'
			$pkgName .= $url[ 'path' ];
		}
		if (str_ends_with($pkgName, '/')) {
			$pkgName = substr($pkgName, 0,strlen($pkgName) - 1);
		}
		if (!Packages::packageLoaded($pkgName)) {
			$loader = new PackageLoader();
			$opened_path = $loader->loadPackage(
				$pkgName,
				$root,
				\UserlandPackages::getDefaultOptions()
			);
			$this->position = 0;
		}
		return true;
	}

	public function stream_stat() {
		// Return a stat array similar to what stat() returns
		return [
			'dev' => 0,
			'ino' => 0,
			'mode' => 0100644, // File type and permissions
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 0,
			'size' => 4096,
			'atime' => time(),
			'mtime' => time(),
			'ctime' => time(),
			'blksize' => 4096,
			'blocks' => 1,
		];
	}
	public function stream_set_option($option, $arg1, $arg2) {
		return true;
	}
	public function stream_read($count) {
		return "";
	}

	/**
	 * @throws \Exception
	 */
	public function stream_write($data){
		$s = sprintf("%s not designed to write", PackageStream::class);
		throw new \Exception($s);
	}
	public function stream_tell() {
		return $this->position;
	}
	public function stream_eof() {
		return true;
	}
	public function stream_seek($offset, $when) {
		return false;
	}

	/**
	 * @throws \Exception
	 */
	public static function register() {
		if (!stream_wrapper_register(self::PROTOCOL, PackageStream::class)) {
			throw new \Exception(sprintf("Failed to register protocol %s://", self::PROTOCOL));
		}
	}
}
