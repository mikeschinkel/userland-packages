<?php

namespace UserlandPackages;
class FileStream {
	const string PROTOCOL = 'phpkg-file';

	public mixed $context;
	private int $position=0;
	private mixed $handle=0;
	private int $bufferMode;
	private int $bufferSize;
	private int $blockSize = 4096;
	private bool $blocking;
	private array $readTimeout;
	private array $writeBuffer;

	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string|null $opened_path
	 *
	 * @return true
	 * @throws \Exception
	 */
	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): true {
		$url = parse_url($path);
		$this->handle = (int)($url["host"]??'0');
		if (is_resource($this->handle)) {
			throw new \Exception("Not a resource: {$this->handle}!");
		}
		$this->handle = Handles::getHandle( $this->handle );
		$opened_path = (string)($url["path"]??'');
		$opened_path = substr($opened_path,1);
		if ($opened_path==='') {
			throw new \Exception(sprintf('No filename specified for stream. Expected %s://{$handle}/{$filepath}',self::PROTOCOL));
		}
		$this->position = 0;
		return true;
	}

	public function stream_read(int $toRead) {
		if ($this->bufferMode == STREAM_BUFFER_FULL) {
			// Read with buffering
			$toRead = min( $toRead, $this->bufferSize );
		}
		$result = fread($this->handle, $toRead);
		$this->position += strlen($result);
		return $result;
	}

	public function stream_write(string $data){
		$written= fwrite($this->handle, $data);
		$this->position += $written;
		return $written;
	}

	public function stream_set_option(int $option, mixed $arg1, mixed $arg2):bool {
		switch ($option) {
			case STREAM_OPTION_BLOCKING:
				// Implement setting the blocking mode
				$this->blocking = (bool) $arg1;
				return true;

			case STREAM_OPTION_READ_TIMEOUT:
				// Implement setting the read timeout
				$this->readTimeout = [ 'sec' => $arg1, 'usec' => $arg2 ];
				return true;

			case STREAM_OPTION_READ_BUFFER:
				// Handle read buffer
				if ($arg1 == STREAM_BUFFER_NONE) {
					$this->bufferMode = STREAM_BUFFER_NONE;
					$this->bufferSize = 0;
				} elseif ($arg1 == STREAM_BUFFER_FULL) {
					$this->bufferMode = STREAM_BUFFER_FULL;
					$this->bufferSize = (int) $arg2;
				}
				return true;

			case STREAM_OPTION_WRITE_BUFFER:
				// Implement setting the write buffer
				if ( $arg1 == STREAM_BUFFER_NONE ) {
					$this->writeBuffer = [ 'mode' => STREAM_BUFFER_NONE, 'size' => 0 ];
				} else if ( $arg1 == STREAM_BUFFER_FULL ) {
					$this->writeBuffer = [ 'mode' => STREAM_BUFFER_FULL, 'size' => (int) $arg2 ];
				}
				return true;

			default:
				return false;
		}
	}

	public function stream_close() {
		fclose($this->handle);
		$this->handle = 0;
		$this->position = 0;
	}

	public function stream_stat() {
		return fstat($this->handle);
	}

	public function stream_tell():int {
		return $this->position;
	}

	public function stream_eof():bool {
		return $this->position >= ftell($this->handle);
	}

	public function stream_seek($offset, $mode):bool {
		$size = ftell($this->handle);
		switch ($mode) {
			case SEEK_SET: $newPos = $offset; break;
			case SEEK_CUR: $newPos = $this->position + $offset; break;
			case SEEK_END: $newPos = $size + $offset; break;
			default: return false;
		}
		$result = ($newPos >=0 && $newPos <=$size);
		if ($result) {
			$this->position = $newPos;
		}
		return $result;
	}

	/**
	 * @throws \Exception
	 */
	public static function register():void {
		if (!stream_wrapper_register(self::PROTOCOL, self::class)) {
			throw new \Exception(sprintf("Failed to register protocol %s://",self::PROTOCOL));
		}
	}
}


//PackageFS::runExample();