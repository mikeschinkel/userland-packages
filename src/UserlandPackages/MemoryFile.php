<?php

namespace UserlandPackages;

class MemoryFile {
    // Static property to hold items stored in memory and indexed by handle
    private static array $mem = [];

    // newHandle() generates a new unique handle (key) into internal self::$memory array
    public static function createHandle():int {
        do {
            // Generate a random number between 1,000,000 and 9,999,999
            $handle = mt_rand(1000000, 9999999);
        } while (array_key_exists($handle, self::$mem)); // Ensure the handle is unique

        // Add the handle to the memory array
        self::$mem[$handle] = '';   
	
        // Return the unique handle
        return $handle;
    }

	// setValue() sets the value stored by handle in static memory
    public static function write(int $handle, string $value):void {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
	    self::$mem[$handle] = $value;
    }

	// catValue() concatenates to a value stored by handle in static memory
    public static function append(int $handle, string $value):void {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
	    self::$mem[$handle] .= $value;
    } 
	
	// getValue() gets the value stored by handle in static memory 
	public static function readAll(int $handle):?string {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
		return self::$mem[$handle];
	}

	// getSubstrValue() gets a substr of value stored by handle in static memory
	public static function read(int $handle, int $pos, int $count):?string {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
		return substr(self::$mem[$handle],$pos,$count);
	}

	// getSize() gets the string length of the value stored for the handle in static memory
	public static function getSize(int $handle):int {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
		return strlen(self::$mem[$handle]);
	}

	// closeHandle() "closes" the handle and releases related memory
	public static function closeHandle(int $handle):void {
		/** @noinspection PhpUnhandledExceptionInspection */
		self::checkHandle($handle);
		unset(self::$mem[$handle]);
	}

	// closeHandle() "closes" the handle and releases related memory
	public static function checkHandle(int $handle):void {
		if (!array_key_exists($handle, self::$mem)){
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new \Exception("Invalid static memory handle: {$handle}");
		}
	}

	// getMem() returns the internal array (for debugging)
	public static function getMem():array {
		return self::$mem;
	}


}

//PackageFS::runExample();