<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BytewiseStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The BitwiseStream is a wrapper around an opened file stream, that allows for bytewise reading/writing
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class BytewiseStream {

	/**
	 * property containing the stream ressource
	 *
	 * @access protected
	 * @var    Stream $bytestream The opened stream wrapper object to read/write from/to
	 */
	protected $bytestream;

	/**
	 * constructor method for the stream wrapper
	 * will take a stream ressource as an argument
	 *
	 * @access public
	 * @param  Stream $stream Opened Stream object
	 * @return void
	 */
	public function __construct(ByteStream $stream) {
		$this->bytestream = $stream;
	}

	/**
	 * method used to read bytes directly from the stream
	 *
	 * @access public
	 * @param  integer $len Number of bytes to read
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBytes($len) {
		if($len === 0) {
			return 0;
		}

		if($len > PHP_INT_SIZE) {
			$ret = new BitArray($len * 8);
			//read 20 bytes at a time
			for ($i = 0; $i < $len; $i += 20) {
				if($len < 20) {
					$reads = $len;
				} else {
					$reads = 20;
				}

				//append the read 20 bytes to the bit array
				$ret->append($this->bytestream->rbytes($len), $reads * 8);
			}
			return $ret->getValue();
		}

		return $this->bytestream->rbytes($len);
	}

	/**
	 * wrapper method around unpack() to read an unsigned 8 bit integer from the byte stream
	 *
	 * @access public
	 * @return the next byte as an unsigned 8 bit integer or false if the stream is finished already
	 */
	public function readUInt8() {
		//check if we are in the middle of a byte
		if($this->nextbyte !== null) {
			return $this->readBits(8);
		}

		//read with unpack instead (faster?!?)
		$byte = $this->bytestream->readByte();
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
	 * @param  boolean bigendian | boolean determing wheter a big endian should be used
	 * @return the next two bytes as an unsigned 16 bit integer or false if the stream is finished already
	 */
	public function readUInt16($bigendian = true) {
		//check if we are in the middle of a byte
		if($this->nextbyte !== null) {
			return $this->readBits(16);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->bytestream->readBytes(2);
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
	 * @param  boolean bigendian | boolean determing wheter a big endian should be used
	 * @return the next 4 bytes as an unsigned 32 bit integer or false if the stream is finished already
	 */
	public function readUInt32($bigendian = true) {
		//check if we are in the middle of a byte
		if($this->nextbyte !== null) {
			return $this->readBits(32);
		}

		//read with unpack instead (faster?!?)
		$bytes = $this->bytestream->readBytes(4);
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
	 * wrapper method used to read a null terminated (cstring) string from the stream
	 *
	 * @access public
	 * @return string containing read bytes until the next null byte
	 */
	public function readCString() {
		$ret = "";
		while(ord($byte = $this->readByte()) != 0) {
			$ret .= $byte;
		}
		return $ret;
	}

}
