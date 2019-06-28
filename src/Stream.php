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
	 * property containing a boolean telling if we're working with big or small endians
	 *
	 * @access public
	 * @var    boolean $bigendian Boolean flag telling wheter to use bigendian
	 */
	public $bigendian;

	/**
	 * constructor method taking the stream resource as the only argument
	 *
	 * @access public
	 * @param  boolean $bigendian Boolean flag telling wheter to use bigendian
	 * @return void
	 */
	public function __construct(boolean $bigendian = true) {
		$this->bigendian = $bigendian;
	}

	/**
	 * convenience function reading a single byte from the stream
	 *
	 * @access public
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readByte(bool $asBinaryString = false) {
		return $this->readBytes(1, $asBinaryString);
	}

	/**
	 * convenience function writing a single byte to the stream
	 *
	 * @access public
	 * @param  string|integer $writeByte Binary data to write to the stream
	 * @param  bool $isBinaryString Boolean flag determing wheter the data is a binary string or a number
	 * @return void
	 */
	public function writeByte($writeByte, bool $isBinaryString = false) {
		return $this->writeBytes($writeByte, $isBinaryString);
	}

	/**
	 * method used to read bytes directly from the stream
	 *
	 * @access public
	 * @param  int $len Number of bytes to read
	 * @param  bool $asBinaryString Boolean flag determing wheter to return a binary string or a number
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBytes(int $len, bool $asBinaryString = false) {
		if($len === 0) {
			return ($asBinaryString ? "" : 0);
		}

		//if no byte has been started simply read whole bytes
		if($this->currentbyte === null) {
			$readBytes = $this->rbytes($len);
			if($asBinaryString) {
				return $readBytes;
			} else {
				$ret = new BitArray::fromBinary($readBytes);
				return $ret->getValue();
			}
		} else {
			return $this->readBits($len * 8, $asBinaryString);
		}
	}

	/**
	 * method used to write bytes directly to the stream
	 *
	 * @access public
	 * @param  string|integer $writeBytes Binary data to write to the stream
	 * @param  bool $isBinaryString Boolean flag determing wheter the data is a binary string or a number
	 * @return void
	 */
	public function writeBytes($writeBytes, bool $isBinaryString = false) {
		//if no byte has been started simply read whole bytes
		if($this->currentbyte === null) {
			if($isBinaryString) {
				$this->wbytes($writeBytes);
			} else {
				$arr = new BitArray::fromInteger($writeBytes);
				$this->wbytes($arr->getBinary());
			}
		} else {
			return $this->writeBits($writeBytes, $asBinaryString);
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
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBits(int $len, bool $asBinaryString = false) {
		if($len === 0) {
			return ($asBinaryString ? "" : 0);
		}

		//if no byte has been started and the number is even, simply use the byte reading method
		if($this->currentbyte === null && $len % 8 == 0) {
			return $this->readBytes($len / 8, $asBinaryString);
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
				$ret->push($this->currentbyte & $bitmask, $len);
			} else {
				$ret->unshift($this->currentbyte & $bitmask, $len);
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
				$ret->push($this->currentbyte & $bitmask, $bitsremaining);
			} else {
				$ret->unshift($this->currentbyte & $bitmask, $bitsremaining);
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
						$ret->push(ord($this->rbytes(1)), 8);
					} else {
						$ret->unshift(ord($this->rbytes(1)), 8);
					}
				}

				//reduce len to the rest of the requested number
				$len = $len % 8;
			}

			//read a new byte to get the rest required
			$newbyte = $this->readBits($len);
			if($bigendian) {
				$ret->push($newbyte, $len);
			} else {
				$ret->unshift($newbyte, $len);
			}
		}

		if($this->byteshift === 8) {
			//delete the cached byte
			$this->currentbyte = null;
		}

		return $ret->getValue();
	}

	/**
	 * wrapper method around our stream to write bits,
	 * using out internal byte cache to save the started byte
	 *
	 * @access public
	 * @param  string|integer $writeBytes Binary data to write to the stream
	 * @param  bool $isBinaryString Boolean flag determing wheter the data is a binary string or a number
	 * @return void
	 */
	public function writeBits($writeBytes, bool $isBinaryString = false) {
		if($isBinaryString) {
			$size = strlen($writeBytes) * 8;
		} else {
			$size = BitArray::integerSize($writeBytes);
		}

		//if no byte has been started and it's an even number of bits, simply use the byte writing method
		if($this->currentbyte === null && $size % 8 == 0) {
			return $this->writeBytes($writeBytes, $isBinaryString);
		}

		if($isBinaryString) {
			$bitArray = BitArray::fromBinary($writeBytes);
		} else {
			$bitArray = BitArray::fromInteger($writeBytes);
		}

		if($this->currentbyte !== null) {
			//$this->byteshift will be the empty bits in our started byte
			$this->currentbyte <<= $this->byteshift;
			if($this->bigendian) {
				$this->currentbyte |= $bitArray->shift($this->byteshift);
			} else {
				$this->currentbyte |= $bitArray->pop($this->byteshift);
			}

			$this->wbytes(chr($this->currentbyte));
			$this->currentbyte = null;
			$this->byteshift = 0;
		}

		//first write as much as we can in entire bytes
		if($bitArray->size >= 8) {
			$atonceSize = $bitArray->size - ($bitArray->size % 8);
			while (($atonceSize / 8) > PHP_INT_SIZE) {
				if($this->bigendian) {
					$atonceData = $bitArray->shift(PHP_INT_SIZE);
				} else {
					$atonceData = $bitArray->pop(PHP_INT_SIZE);
				}

				$atonceData = pack("C*", $atonceData);

				//if we have small endian, we need to reverse it
				if(!$this->bigendian) {
					$atonceData = strrev($ret);
				}

				$this->wbytes($atonceData);
				$atonceSize -= PHP_INT_SIZE * 8;
			}

			if($this->bigendian) {
				$atonceData = $bitArray->shift($atonceSize);
			} else {
				$atonceData = $bitArray->pop($atonceSize);
			}

			$atonceData = pack("C*", $atonceData);

			//if we have small endian, we need to reverse it
			if(!$this->bigendian) {
				$atonceData = strrev($ret);
			}

			$this->wbytes($atonceData);
		}

		if($bitArray->size > 0) {
			$this->currentbyte = $bitArray->getValue();
			//$this->byteshift should be the empty bits in our started byte
			$this->byteshift = $bitArray->size;
		}
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
	 * wrapper method around writeBits() to write a boolean (bit length 1) to the byte stream
	 *
	 * @access public
	 * @param  boolean $bool Boolean flag to be written to the stream
	 * @return void
	 */
	public function writeBoolean(boolean $bool) {
		$this->writeBits(($bool ? 1 : 0));
	}

	/**
	 * wrapper method around unpack() to read a number from the stream
	 * to be used by all the number reading methods with their unpack codes as argument
	 *
	 * @access private
	 * @param  integer $sizeInBytes Size of the number in bytes
	 * @param  string $unpackCode The code to be used to unpack the data
	 * @return number that was read from the stream
	 */
	private function unpackNumber(int $sizeInBytes, string $unpackCode) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			$data = $this->readBits($sizeInBytes * 8, true);
		} else {
			$data = $this->rbytes($sizeInBytes);
		}

		$ret = unpack($unpackCode, $data);
		return $ret[1];
	}

	/**
	 * wrapper method around pack() to write a number from the stream
	 * to be used by all the number reading methods with their pack codes as argument
	 *
	 * @access private
	 * @param  number $data The number to be packed
	 * @param  string $packCode The code to be used to unpack the data
	 * @return void
	 */
	private function packNumber($data, string $packCode) {
		//check if we are in the middle of a byte
		if($this->currentbyte !== null) {
			return $this->writeBits($data);
		}

		//write with pack instead (faster?!?)
		$writeBytes = pack($packCode, $data);
		$this->writeBytes($writeBytes);
	}

	/**
	 * wrapper method around unpackNumber() to read an unsigned 8 bit integer from the byte stream
	 *
	 * @access public
	 * @return byte as an unsigned 8 bit integer
	 */
	public function readUInt8() {
		return $this->unpackNumber(1, "C");
	}

	/**
	 * wrapper method around packNumber() to write an unsigned 8 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an unsigned 8 bit integer
	 * @return void
	 */
	public function writeUInt8(int $number) {
		return $this->packNumber($number, "C");
	}

	/**
	 * wrapper method around unpackNumber() to read an signed 8 bit integer from the byte stream
	 *
	 * @access public
	 * @return number as an signed 8 bit integer
	 */
	public function readSInt8() {
		return $this->unpackNumber(1, "c");
	}

	/**
	 * wrapper method around packNumber() to write an signed 8 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an signed 8 bit integer
	 * @return void
	 */
	public function writeSInt8(int $number) {
		return $this->packNumber($number, "c");
	}

	/**
	 * wrapper method around unpackNumber() to read an unsigned 16 bit integer from the byte stream
	 *
	 * @access public
	 * @return byte as an unsigned 16 bit integer
	 */
	public function readUInt16() {
		return $this->unpackNumber(2, ($this->bigendian ? "n" : "v"));
	}

	/**
	 * wrapper method around packNumber() to write an unsigned 16 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an unsigned 16 bit integer
	 * @return void
	 */
	public function writeUInt16(int $number) {
		return $this->packNumber($number, ($this->bigendian ? "n" : "v"));
	}

	/**
	 * wrapper method around unpackNumber() to read an signed 16 bit integer from the byte stream
	 *
	 * @access public
	 * @return number as an signed 16 bit integer
	 */
	public function readSInt16() {
		$data = $this->unpackNumber(2, "s");

		if(static::machineUsesLittleEndian() && $this->bigendian) {
			return static::convertEndian($data);
		} else {
			return $data;
		}
	}

	/**
	 * wrapper method around packNumber() to write an signed 16 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an signed 16 bit integer
	 * @return void
	 */
	public function writeSInt16(int $number) {
		if(static::machineUsesLittleEndian() && $this->bigendian) {
			$number = static::convertEndian($number);
		}

		return $this->packNumber($number, "s");
	}

	/**
	 * wrapper method around unpackNumber() to read an unsigned 32 bit integer from the byte stream
	 *
	 * @access public
	 * @return byte as an unsigned 32 bit integer
	 */
	public function readUInt32() {
		return $this->unpackNumber(4, ($this->bigendian ? "N" : "V"));
	}

	/**
	 * wrapper method around packNumber() to write an unsigned 32 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an unsigned 32 bit integer
	 * @return void
	 */
	public function writeUInt32(int $number) {
		return $this->packNumber($number, ($this->bigendian ? "N" : "V"));
	}

	/**
	 * wrapper method around unpackNumber() to read an signed 32 bit integer from the byte stream
	 *
	 * @access public
	 * @return number as an signed 32 bit integer
	 */
	public function readSInt32() {
		$data = $this->unpackNumber(4, "l");

		if(static::machineUsesLittleEndian() && $this->bigendian) {
			return static::convertEndian($data);
		} else {
			return $data;
		}
	}

	/**
	 * wrapper method around packNumber() to write an signed 32 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an signed 32 bit integer
	 * @return void
	 */
	public function writeSInt32(int $number) {
		if(static::machineUsesLittleEndian() && $this->bigendian) {
			$number = static::convertEndian($number);
		}

		return $this->packNumber($number, "l");
	}

	/**
	 * wrapper method around unpackNumber() to read an unsigned 64 bit integer from the byte stream
	 *
	 * @access public
	 * @return byte as an unsigned 64 bit integer
	 */
	public function readUInt64() {
		return $this->unpackNumber(4, ($this->bigendian ? "J" : "P"));
	}

	/**
	 * wrapper method around packNumber() to write an unsigned 64 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an unsigned 64 bit integer
	 * @return void
	 */
	public function writeUInt64(int $number) {
		return $this->packNumber($number, ($this->bigendian ? "J" : "P"));
	}

	/**
	 * wrapper method around unpackNumber() to read an signed 64 bit integer from the byte stream
	 *
	 * @access public
	 * @return number as an signed 64 bit integer
	 */
	public function readSInt64() {
		$data = $this->unpackNumber(8, "q");

		if(static::machineUsesLittleEndian() && $this->bigendian) {
			return static::convertEndian($data);
		} else {
			return $data;
		}
	}

	/**
	 * wrapper method around packNumber() to write an signed 64 bit integer to the byte stream
	 *
	 * @access public
	 * @param  integer $number The number as an signed 64 bit integer
	 * @return void
	 */
	public function writeSInt64(int $number) {
		if(static::machineUsesLittleEndian() && $this->bigendian) {
			$number = static::convertEndian($number);
		}

		return $this->packNumber($number, "q");
	}

	/**
	 * wrapper method around unpackNumber() to read a float
	 *
	 * @access public
	 * @return read 32 bit float number from the stream
	 */
	public function readFloat() {
		return $this->unpackNumber(4, ($this->bigendian ? "G" : "g"));
	}

	/**
	 * wrapper method around packNumber() to write an signed 64 bit integer to the byte stream
	 *
	 * @access public
	 * @param  float $number The number as an signed 64 bit integer
	 * @return void
	 */
	public function writeFloat($number) {
		return $this->packNumber($number, ($this->bigendian ? "G" : "g"));
	}

	/**
	 * wrapper method around unpackNumber() to read a double
	 *
	 * @access public
	 * @return read 64 bit double number from the stream
	 */
	public function readDouble() {
		return $this->unpackNumber(4, ($this->bigendian ? "E" : "e"));
	}

	/**
	 * wrapper method around packNumber() to write a double to the stream
	 *
	 * @access public
	 * @param  double $number The number as a 64 bit double
	 * @return void
	 */
	public function writeDouble($number) {
		return $this->packNumber($number, ($this->bigendian ? "E" : "e"));
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
	 * function used to skip the rest of the started byte and then writing bytes
	 *
	 * @access public
	 * @param  string $data The binary data to be written
	 * @param  bool $isBinaryString Boolean flag determing wheter the data is a binary string or a number
	 * @return string with the read bytes
	 */
	public function writeAlignedString(string $data, bool $isBinaryString = true) {
		$this->wbytes(chr($this->currentbyte));
		$this->currentbyte = null;
		$this->byteshift = 0;
		return $this->writeBytes($data, $isBinaryString);
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
	 * small helper method detecting the machine byte order
	 * returns true if the machine uses SMALL_ENDIAN
	 *
	 * @access public
	 * @return boolean true or false if the machine order is small endian or not
	 */
	public static function machineUsesLittleEndian() {
		$testint = 0x00FF;
		$p = pack('S', $testint);
		return $testint === current(unpack('v', $p));
	}

	/**
	 * Converts the endianess of a number from big to little or vise-versa
	 *
	 * @access public
	 * @param  int $value The value to be converted
	 * @return int with the converted value
	 */
	public static function convertEndian($value) {
		$data = dechex($value);
		if (strlen($data) <= 2) {
			return $value;
		}
		$unpack = unpack("H*", strrev(pack("H*", $data)));
		$converted = hexdec($unpack[1]);
		return $converted;
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
