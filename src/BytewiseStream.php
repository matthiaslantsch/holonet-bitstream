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
 * The BytewiseStream is a wrapper around an opened file stream, that allows for bytewise reading/writing
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class BytewiseStream extends StreamResource {

	/**
	 * method used to read bytes directly from the stream
	 *
	 * @access public
	 * @param  integer $len Number of bytes to read
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function rbytes($len) {
		if($len === 0) {
			return 0;
		}

		if($this->eof()) {
			return false;
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
				$ret->append(parent::rbytes($reads), $reads * 8);
			}
			return $ret->getValue();
		}

		return parent::rbytes($len);
	}

	/**
	 * wrapper method around unpack() to read an unsigned 8 bit integer from the byte stream
	 *
	 * @access public
	 * @return the next byte as an unsigned 8 bit integer or false if the stream is finished already
	 */
	public function ruint8() {
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
	 * @param  boolean bigendian | boolean determing wheter a big endian should be used
	 * @return the next two bytes as an unsigned 16 bit integer or false if the stream is finished already
	 */
	public function ruint16($bigendian = true) {
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
	 * @param  boolean bigendian | boolean determing wheter a big endian should be used
	 * @return the next 4 bytes as an unsigned 32 bit integer or false if the stream is finished already
	 */
	public function ruint32($bigendian = true) {
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
	public function ruint64() {
		$higher = $this->runint32();
		$lower = $this->runint32();
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
	public function rcstring() {
		$ret = "";
		while(ord($byte = $this->rbytes(1)) != 0) {
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
	public function wcstring(string $str) {
		$this->wbytes("{$str}\0");
	}

}
