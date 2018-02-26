<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the ByteStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The ByteStream is an object oriented wrapper around a stream resource
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class ByteStream {

	/**
	 * property containing an identifier for the correct compression method used to open the stream
	 * this class supports:
	 *  - gz => gzip (gzopen...)
	 *  - bz => bzip (bzopen...)
	 *  - f => normal file (fopen...)
	 *
	 * @access private
	 * @var    string $encoding String identifier for the encoding type
	 */
	private $encoding;

	/**
	 * property containing the stream ressource
	 *
	 * @access public
	 * @var    ressource $stream The opened stream ressource to read/write from/to
	 */
	public $stream;

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
		die(var_dump(stream_get_meta_data($stream)));
		if(is_resource($stream) === false) {
			throw new InvalidArgumentException(
				sprintf(
					'Argument must be a valid resource type. %s given.',
					gettype($stream)
				)
			);
		}

		if(!in_array($encoding, ["gz", "bz", "f"])) {
			throw new InvalidArgumentException("Encoding must be a valid encoding (gz, bz or f). {$encoding} given.");
		}

		$this->bytestream = $stream;
		$this->encoding = $encoding;
	}

	/**
	 * wrapper method for the tell() function
	 * returns the current position in the stream
	 *
	 * @access public
	 * @return integer|boolean Integer with current position of false on error
	 */
	public function tell() {
		switch ($this->encoding) {
			case 'gz':
				return gztell($this->bytestream);
				break;
			case 'bz':
				return bztell($this->bytestream);
				break;
			case 'f':
			default:
				return ftell($this->bytestream);
				break;
		}
	}

	/**
	 * wrapper method for the seek() function
	 * seeks to a certain offset in the file
	 *
	 * @access public
	 * @return integer Upon success, returns 0; otherwise, returns -1
	 */
	public function seek() {
		switch ($this->encoding) {
			case 'gz':
				return gzseek($this->bytestream);
			case 'bz':
				return bzseek($this->bytestream);
			case 'f':
			default:
				return fseek($this->bytestream);
		}
	}

	/**
	 * wrapper method for the rewind() function
	 * seeks to a certain offset in the file
	 *
	 * @access public
	 * @return boolean Returns true on success or false on failure
	 */
	public function rewind() {
		switch ($this->encoding) {
			case 'gz':
				return gzrewind($this->bytestream);
			case 'bz':
				return bzrewind($this->bytestream);
			case 'f':
			default:
				return rewind($this->bytestream);
		}
	}

	/**
	 * wrapper method for the eof() function
	 * tests if the stream is at the EOF
	 *
	 * @access public
	 * @return boolean Returns true if the pointer is at the EOF or an error occured
	 */
	public function eof() {
		switch ($this->encoding) {
			case 'gz':
				return gzeof($this->bytestream);
			case 'bz':
				return bzeof($this->bytestream);
			case 'f':
			default:
				return feof($this->bytestream);
		}
	}

	/**
	 * wrapper method for the close() function
	 * closes the stream
	 * uses the internal __destruct() method
	 *
	 * @access public
	 * @return boolean Returns true on success or false on failure
	 */
	public function close() {
		$this->__destruct();
	}

	/**
	 * destructor method for the stream wrapper
	 * closes the stream properly with the correct encoding
	 *
	 * @access public
	 * @return boolean Returns true on success or false on failure
	 */
	public function __destruct() {
		switch ($this->encoding) {
			case 'gz':
				return gzclose($this->bytestream);
			case 'bz':
				return bzclose($this->bytestream);
			case 'f':
			default:
				return fclose($this->bytestream);
		}
	}

	/**
	 * wrapper method for the read() function
	 * reads a specified number of bytes from the stream
	 *
	 * @access public
	 * @param  integer $len Number of bytes to be read
	 * @return string Binary data that was read from the stream
	 */
	private function rbytes($len) {
		switch ($this->encoding) {
			case 'gz':
				return gzread($this->bytestream, $len);
			case 'bz':
				return bzread($this->bytestream, $len);
			case 'f':
			default:
				return fread($this->bytestream, $len);
		}
	}

	/**
	 * wrapper method for the write() function
	 * writes bytes to the stream
	 *
	 * @access public
	 * @param  string $bytes Binary data to be written to the stream
	 * @return integer Number of bytes written to the stream
	 */
	private function wbytes($bytes) {
		switch ($this->encoding) {
			case 'gz':
				return gzwrite($this->bytestream, $bytes);
			case 'bz':
				return bzwrite($this->bytestream, $bytes);
			case 'f':
			default:
				return fwrite($this->bytestream, $bytes);
		}
	}

}
