<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the StreamResource class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The StreamResource is an object oriented wrapper around a stream resource
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class StreamResource {

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

		$this->stream = $stream;
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
				return gztell($this->stream);
				break;
			case 'bz':
				return bztell($this->stream);
				break;
			case 'f':
			default:
				return ftell($this->stream);
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
				return gzseek($this->stream);
			case 'bz':
				return bzseek($this->stream);
			case 'f':
			default:
				return fseek($this->stream);
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
				return gzrewind($this->stream);
			case 'bz':
				return bzrewind($this->stream);
			case 'f':
			default:
				return rewind($this->stream);
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
				return gzeof($this->stream);
			case 'bz':
				return bzeof($this->stream);
			case 'f':
			default:
				return feof($this->stream);
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
				return gzclose($this->stream);
			case 'bz':
				return bzclose($this->stream);
			case 'f':
			default:
				return fclose($this->stream);
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
	public function rbytes($len) {
		switch ($this->encoding) {
			case 'gz':
				return gzread($this->stream, $len);
			case 'bz':
				return bzread($this->stream, $len);
			case 'f':
			default:
				return fread($this->stream, $len);
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
	public function wbytes($bytes) {
		switch ($this->encoding) {
			case 'gz':
				return gzwrite($this->stream, $bytes);
			case 'bz':
				return bzwrite($this->stream, $bytes);
			case 'f':
			default:
				return fwrite($this->stream, $bytes);
		}
	}

}
