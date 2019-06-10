<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the Stream base class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * The Stream base class should be extended to offer a stream resource over a
 * streamlined object oriented interface
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
abstract class Stream {

	/**
	 * property containing bit masks used to extract a certain number of bits
	 *
	 * @access private
	 * @var    array $includeBitmask Array with bitmasks used to extract a byte partially
	 */
	private static $includeBitmask = [0x01, 0x03, 0x07, 0x0F, 0x1F, 0x3F, 0x7F, 0xFF];

	/**
	 * property containing the started byte to continue reading/writing from there
	 *
	 * @access private
	 * @var    char $currentbyte The started byte
	 */
	private $currentbyte;

	/**
	 * property containing the number of bits already used from the started byte
	 *
	 * @access private
	 * @var    integer $byteshift Number of bits already used
	 */
	private $byteshift = 0;

	/**
	 * property containing an integer with the buffer size
	 *
	 * @access protected
	 * @var    integer $bufferSize The size of the buffer for this stream
	 */
	protected $bufferSize = 4096;

	/**
	 * property containing the actual stream we're working with
	 * could be a resource or an object
	 *
	 * @access protected
	 * @var    resource|string|object $stream The actual stream we're working with (depends on the implementation)
	 */
	protected $stream;

	/**
	 * property containing a boolean marking the stream as open or not
	 *
	 * @access protected
	 * @var    boolean $isOpen Boolean flag marking this stream as open
	 */
	protected $isOpen;

	/**
	 * convenience function reading a single byte from the stream
	 *
	 * @access public
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readByte(bool $asBinaryString = false) {
		if($asBinaryString) {
			return $this->rbytes(1);
		} else {
			return ord($this->rbytes(1));
		}
	}

	/**
	 * method used to read bytes directly from the stream
	 *
	 * @access public
	 * @param  int $len Number of bytes to read
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @param  bool $bigendian Boolean determing wheter a big endian should be used
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBytes(int $len, bool $asBinaryString = false, bool $bigendian = true) {
		if($len === 0) {
			return ($asBinaryString ? "" : 0);
		}

		//if no byte has been started simply read whole bytes
		if($this->currentbyte === null) {
			if($asBinaryString) {
				return $this->rbytes($len);
			} else {
				$ret = new BitArray($len * 8);
				for ($i = 0; $i < $len; $i++) {
					//append the read 1 byte to the bit array
					if($bigendian) {
						$ret->append(ord($this->rbytes(1)), 8);
					} else {
						$ret->prepend(ord($this->rbytes(1)), 8);
					}
				}
				return $ret->getValue();
			}
		} else {
			return $this->readBits($len * 8, $asBinaryString, $bigendian);
		}
	}

	/**
	 * wrapper method around our stream to read a specified number of bits,
	 * using out internal byte cache to save the started byte
	 * will just return read bytes if no byte is started, to save performance
	 *
	 * @access public
	 * @param  integer $len Number of bits to read
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @param  bool $bigendian Boolean determing wheter a big endian should be used
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBits(int $len, bool $asBinaryString = false, bool $bigendian = true) {
		if($len === 0) {
			return ($asBinaryString ? "" : 0);
		}

		//if no byte has been started and the number is even, simply use the byte reading method
		if($this->currentbyte === null && $len % 8 == 0) {
			return $this->readBytes($len / 8, $asBinaryString, $bigendian);
		}

		$ret = new BitArray($len);

		if($this->currentbyte === null) {
			//no byte has been started yet
			//start a byte in the internal cache
			$this->currentbyte = ord($this->rbytes(1));
			$this->byteshift = 0;
		}

		if($len <= 8 && $this->byteshift + $len <= 8) {
			//get the bitmask e.g. 00000111 for 3
			$bitmask = self::$includeBitmask[$len - 1];

			//can be satisfied with the remaining bits
			if($bigendian) {
				$ret->append($this->currentbyte & $bitmask, $len);
			} else {
				$ret->prepend($this->currentbyte & $bitmask, $len);
			}

			//shift by len
			$this->currentbyte >>= $len;
			$this->byteshift += $len;
		} else {
			//read the remaining bits first
			$bitsremaining = 8 - $this->byteshift;
			//get the bitmask e.g. 00000111 for 3
			$bitmask = self::$includeBitmask[$bitsremaining - 1];
			if($bigendian) {
				$ret->append($this->currentbyte & $bitmask, $bitsremaining);
			} else {
				$ret->prepend($this->currentbyte & $bitmask, $bitsremaining);
			}

			//decrease len by the amount bits remaining
			$len -= $bitsremaining;

			//set the internal byte cache to null
			$this->currentbyte = null;

			if($len > 8) {
				//read entire bytes as far as possible
				for ($i = intval($len / 8); $i > 0; $i--) {
					if($this->eof()) {
						//no more bytes
						return false;
					}
					if($bigendian) {
						$ret->append(ord($this->rbytes(1)), 8);
					} else {
						$ret->prepend(ord($this->rbytes(1)), 8);
					}
				}

				//reduce len to the rest of the requested number
				$len = $len % 8;
			}

			//read a new byte to get the rest required
			$newbyte = $this->readBits($len);
			if($bigendian) {
				$ret->append($newbyte, $len);
			} else {
				$ret->prepend($newbyte, $len);
			}
		}

		if($this->byteshift === 8) {
			//delete the cached byte
			$this->currentbyte = null;
		}

		//var_dump("Read int of size {$len} at offset {$this->offset()}: {$ret->getValue()}");
		return $ret->getValue();
	}

	/**
	 * wrapper method around readBits() to read a boolean (bit length 1) from the byte stream
	 *
	 * @access public
	 * @return read boolean
	 */
	public function readBoolean() {
		return ($this->readBits(1) === 1);
	}

	/**
	 * wrapper method around unpack() to read an unsigned 8 bit integer from the byte stream
	 *
	 * @access public
	 * @return the next byte as an unsigned 8 bit integer or false if the stream is finished already
	 */
	public function readUInt8() {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			return $this->readBits(8);
		}

		//read with unpack instead (faster?!?)
		$byte = $this->rbytes(1);
		if($byte === false) {
			return false;
		}

		$ret = unpack("C", $byte);
		return $ret[1];
	}

	/**
	 * wrapper method around unpack() to read an unsigned 16 bit integer from the byte stream
	 *
	 * @access public
	 * @param  boolean $bigendian Boolean determing wheter a big endian should be used
	 * @return the next two bytes as an unsigned 16 bit integer or false if the stream is finished already
	 */
	public function readUInt16($bigendian = true) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			return $this->readBits(16);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(2);
		if($bytes === false) {
			return false;
		}

		$ret = unpack(($bigendian ? "n" : "v"), $bytes);
		return $ret[1];
	}

	/**
	 * wrapper method around unpack() to read an unsigned 32 bit integer from the byte stream
	 *
	 * @access public
	 * @param  boolean $bigendian Boolean determing wheter a big endian should be used
	 * @return the next 4 bytes as an unsigned 32 bit integer or false if the stream is finished already
	 */
	public function readUInt32($bigendian = true) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			return $this->readBits(32);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(4);
		if($bytes === false) {
			return false;
		}

		$ret = unpack(($bigendian ? "N" : "V"), $bytes);
		return $ret[1];
	}

	/**
	 * wrapper method around readUInt32() to read an unsigned 64 bit integer from the byte stream
	 *
	 * @access public
	 * @return the next 8 bytes as an unsigned 64 bit integer or false if the stream is finished already
	 */
	public function readUInt64() {
		$higher = $this->readUInt32();
		$lower = $this->readUInt32();
		if($higher === false || $lower === false) {
			return false;
		}

		return ($higher << 32 | $lower);
	}

	/**
	 * method used to read a 4 byte float number from the stream
	 *
	 * @access public
	 * @param  bool $bigendian Flag determing wheter a big endian should be used
	 * @return read 4 byte float number
	 */
	public function readSFloat(bool $bigendian = true) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			$bytes = $this->readBits(32);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(4);
		if($bytes === false) {
			return false;
		}

		$ret = unpack(($bigendian ? "G" : "g"), $bytes);
		return $ret[1];
	}

	/**
	 * method used to read a 8 byte double number from the stream
	 *
	 * @access public
	 * @param  bool $bigendian Flag determing wheter a big endian should be used
	 * @return read 8 byte float number
	 */
	public function readSDouble(bool $bigendian = true) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			$bytes = $this->readBits(64);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(8);
		if($bytes === false) {
			return false;
		}

		$ret = unpack(($bigendian ? "E" : "e"), $bytes);
		return $ret[1];
	}

	/**
	 * wrapper method used to read a null terminated (cstring) string from the stream
	 *
	 * @access public
	 * @return string containing read bytes until the next null byte
	 */
	public function readCString() {
		$ret = "";
		while(ord($byte = $this->readByte(true)) != 0) {
			$ret .= $byte;
		}
		return $ret;
	}

	/**
	 * wrapper method used to write a null terminated (cstring) string to the stream
	 *
	 * @access public
	 * @param  string $str The string that should be written to the stream
	 * @return void
	 */
	public function writeCString(string $str) {
		$this->wbytes("{$str}\0");
	}

	/**
	* return our interfal flag that is marking the stream as open or not
	*
	* @access public
	* @return boolean if this stream is open or not
	*/
	public function isOpen() {
		return $this->isOpen;
	}

	/**
	 * function used to skip the rest of the started byte and continuing at a new one
	 *
	 * @access public
	 * @return void
	 */
	public function align() {
		$this->currentbyte = null;
		$this->byteshift = 0;
	}

	/**
	 * function used to skip the rest of the started byte and then reading new bytes
	 *
	 * @access public
	 * @param  integer $len The length of the requested string
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @return string with the read bytes
	 */
	public function readAlignedString(int $len, bool $asBinaryString = true) {
		$this->align();
		return $this->readBytes($len, $asBinaryString);
	}

	/**
	 * small method returning the actual object that we are wrapping around
	 * is a different type based on the implementation
	 *
	 * @access public
	 * @return resource|string|object $stream The actual stream we're working with (depends on the implementation)
	 */
	public function stream() {
		return $this->stream;
	}

	/**
	 * force the child class to implement a method to check wheter the stream is
	 * readable or not
	 *
	 * @access public
	 * @return boolean if this stream is readable or not
	 */
	 abstract public function isReadable();

	/**
	 * force the child class to implement a method to check wheter the stream is
	 * writable or not
	 *
	 * @access public
	 * @return boolean if this stream is writable or not
	 */
	 abstract public function isWritable();

	/**
	 * force the child class to implement a method to check wheter the stream is
	 * seekable or not
	 *
	 * @access public
	 * @return boolean if this stream is seekable or not
	 */
	 abstract public function isSeekable();

 	/**
 	 * force the child class to implement a method to check wheter the stream is
 	 * at the EOF or not
 	 *
 	 * @access public
 	 * @return boolean if this stream is at the EOF or not
 	 */
 	 abstract public function eof();

	/**
	 * force the child class to implement a method to read bytes from the stream
	 *
	 * @access protected
	 * @param  integer $length Maximum number of bytes to read. Defaults to self::$bufferSize.
	 * @return string The data read from the stream
	 */
	 abstract protected function rbytes(integer $length);

	/**
	 * force the child class to implement a method to read an entire line from the stream
	 * Reading should end when length bytes have been read, when the
	 * string specified by ending is found (which is not included in the return
	 *  value), or on EOF (whichever comes first).
	 *
	 * @access public
	 * @param  integer $length Maximum number of bytes to read. Defaults to self::$bufferSize.
	 * @param  string $ending Line ending to stop at. Defaults to "\n".
	 * @return string The line read from the stream
	 */
	 abstract public function rline();

	/**
	 * force the child class to implement a getContents() read method
	 * that returns all that's left in the stream
	 *
	 * @access public
	 * @return string The data that has been read
	 */
	abstract public function getContents();

 	/**
 	 * force the child class to implement a pipe() method
 	 * that writes the stream contents to another stream (stream_copy_to_stream style)
 	 *
 	 * @access public
	 * @param  mixed $stream The other stream to copy to (could be resource or object)
	 * @return integer Number of piped bytes
 	 */
 	abstract public function pipe($stream);

	/**
	 * force the child class to implement a offset() method
	 * that returns the current pointer position in the stream
	 *
	 * @access public
	 * @return integer with the current cursor position
	 */
	abstract public function offset();

	/**
	* force the child class to implement a seek() method to move the file
	* pointer to a new position
	* The new position, measured in bytes from the beginning of the file,
	* is obtained by adding $offset to the position specified by $whence.
	*
	* @access public
	* @param  integer $offset
	* @param  integer $whence Accepted values are:
	*			  - SEEK_SET - Set position equal to $offset bytes.
	*			  - SEEK_CUR - Set position to current location plus $offset.
	*			  - SEEK_END - Set position to end-of-file plus $offset.
	* @return void
	 */
	abstract public function seek($offset, $whence = SEEK_SET);

	/**
	 * force the child class to implement a rewind() method
	 * that puts the cursor at the beginning of the stream
	 *
	 * @access public
	 * @return void
	 */
	abstract public function rewind();

	/**
	 * force the child class to implement a close() method
	 * that closes the stream
	 *
	 * @access public
	 * @return boolean determing wheter closing was successfull
	 */
	abstract public function close();

}
