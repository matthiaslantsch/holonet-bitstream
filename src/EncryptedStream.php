<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the EncryptedStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

use RuntimeException;
use Defuse\Crypto\Crypto;

/**
 * The EncryptedStream uses the defuse/php-encryption library to read and
 * write to and from an encrypted stream
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class EncryptedStream extends BitwiseStream {

	/**
	 * constructor method for the stream wrapper
	 * will take a stream ressource as an argument
	 *
	 * @access public
	 * @param  ressource $stream The opened bytestream to work with
	 * @param  string $encoding Name of the encoding used to open the file (gz, bz or f)
	 * @return void
	 */
	public function __construct($stream, $encoding = "f") {
		parent::__construct($stream, $encoding);
		if(!class_exists("Defuse\Crypto\Crypto")) {
			//@TODO throw a badenvironment exception instead
			throw new RuntimeException("Cannot use the encrypted stream class without installing the defuse/php-encrypt library", 100);
		}
	}

	/**
	 * method used to read bytes directly from the stream
	 *
	 * @access public
	 * @param  integer $len Number of bytes to read
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function rbytes($len, string $password) {
		if($len === 0) {
			return 0;
		}

		if($this->eof()) {
			return false;
		}

		$ret = parent::rbytes($len);
		return Crypto::decrypt($ret, $password);
	}

	/**
	 * wrapper method around unpack() to read an unsigned 8 bit integer from the byte stream
	 *
	 * @access public
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return the next byte as an unsigned 8 bit integer or false if the stream is finished already
	 */
	public function ruint8(string $password) {
		//read with unpack instead (faster?!?)
		$byte = $this->rbytes(1, $password);
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
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return the next two bytes as an unsigned 16 bit integer or false if the stream is finished already
	 */
	public function ruint16($bigendian = true, string $password) {
		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(2, $password);
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
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return the next 4 bytes as an unsigned 32 bit integer or false if the stream is finished already
	 */
	public function ruint32($bigendian = true, string $password) {
		//read with unpack instead (faster?!?)
		$bytes = $this->rbytes(4, $password);
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
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return the next 8 bytes as an unsigned 64 bit integer or false if the stream is finished already
	 */
	public function ruint64(string $password) {
		$higher = $this->runint32(true, $password);
		$lower = $this->runint32(true, $password);
		if($higher === false || $lower === false) {
			return false;
		}

		return ($higher << 32 | $lower);
	}

	/**
	 * wrapper method used to read a null terminated (cstring) string from the stream
	 *
	 * @access public
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return string containing read bytes until the next null byte
	 */
	public function rcstring(string $password) {
		$ret = "";
		while(ord($byte = $this->rbytes(1, $password)) != 0) {
			$ret .= $byte;
		}
		return $ret;
	}

	/**
	 * wrapper method used to write a null terminated (cstring) string to the stream
	 *
	 * @access public
	 * @param  string $str The string that should be written to the stream
	 * @param  string $password The password that should be used to decrypt the input stream
	 * @return void
	 */
	public function wcstring(string $str, string $password) {
		$this->wbytes(Crypto::encrypt("{$str}\0", $password));
	}

}
