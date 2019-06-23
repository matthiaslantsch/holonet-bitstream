<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the ResourceStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

use InvalidArgumentException;

/**
 * The ResourceStream class is used to wrap around a php stream resource in an
 * object oriented manner
 * this class can be used with any php stream resource
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class ResourceStream extends Stream {

	/**
	 * property containing an array with all the modes that are readable
	 *
	 * @access protected
	 * @var    array $readableModes An array with readable modes for the opened resource
	 */
	protected static $readableModes = array(
		'r', 'r+', 'w+', 'a+', 'x+', 'c+', 'rb', 'r+b', 'w+b', 'a+b', 'x+b', 'c+b', 'rt', 'r+t', 'w+t', 'a+t', 'x+t', 'c+t'
	);

	/**
	 * property containing an array with all the modes that are writable
	 *
	 * @access protected
	 * @var    array $readableModes An array with writable modes for the opened resource
	 */
	protected static $writableModes = array(
		'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'r+b', 'wb', 'w+b', 'ab', 'a+b', 'xb', 'x+b', 'cb', 'c+b', 'r+t', 'wt', 'w+t', 'at', 'a+t', 'xt', 'x+t', 'ct', 'c+t'
	);

	/**
	 * constructor method taking the stream resource as an argument
	 *
	 * @access public
	 * @param  resource $stream The opened stream resource to work with
	 * @param  boolean $bigendian Boolean flag telling wheter to use bigendian
	 * @return void
	 */
	public function __construct($stream, boolean $bigendian = true) {
		parent::__construct($bigendian);
		if (!is_resource($stream)) {
			throw new InvalidArgumentException('A Stream object requires a stream resource as constructor argument');
		}
		$this->stream = $stream;
		$this->isOpen = true;
	}

	/**
	 * destructor method cleaning up on object destruction
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
	}

	/**
	 * small method returning an array with metadata about the contained resource
	 *
	 * @access public
	 * @return array with metadata about the opened stream
	 */
	public function metadata() {
		return stream_get_meta_data($this->stream);
	}

	/**
	 * small method returning a specific metadata value for a given key
	 *
	 * @access public
	 * @param  string $key The key to return the metadata value for
	 * @return array with metadata about the opened stream
	 */
	public function metadataForKey(string $key) {
		$metadata = $this->metadata();
		if (isset($metadata[$key])) {
			return $metadata[$key];
		}
	}

	/**
	 * get the URI contained in the metadata of the stream
	 *
	 * @access public
	 * @return string with the uri that was opened in this stream
	 */
	public function uri() {
		return $this->metadataForKey('uri');
	}

	/**
	 * get the stream type contained in the metadata of the stream
	 *
	 * @access public
	 * @return string with the stream type of the stream that was opened in this stream
	 */
	public function streamtype() {
		return $this->metadataForKey('stream_type');
	}

	/**
	 * get the wrapper type contained in the metadata of the stream
	 *
	 * @access public
	 * @return string with the wrapper type of the stream that was opened in this stream
	 */
	public function wrappertype() {
		return $this->metadataForKey('wrapper_type');
	}

	/**
	 * get the wrapper data contained in the metadata of the stream
	 *
	 * @access public
	 * @return string with the wrapper data of the stream that was opened in this stream
	 */
	public function wrapperdata() {
		return $this->metadataForKey('wrapper_data');
	}

	/**
	 * return true or false if the contained resource is readable or not
	 *
	 * @access public
	 * @return boolean if this stream is readable or not
	 */
	public function isReadable() {
		return in_array($this->metadataForKey('mode'), self::$readableModes);
	}

	/**
	 * return true or false if the contained resource is writable or not
	 *
	 * @access public
	 * @return boolean if this stream is writable or not
	 */
	public function isWritable() {
		return in_array($this->metadataForKey('mode'), self::$writableModes);
	}

	/**
	 * return true or false if the contained resource is seekable or not
	 *
	 * @access public
	 * @return boolean if this stream is seekable or not
	 */
	public function isSeekable() {
		return $this->metadataForKey('seekable');
	}

	/**
	 * Read data from the stream.
	 * Binary-safe.
	 *
	 * @access protected
	 * @param  integer $length Maximum number of bytes to read. Defaults to self::$bufferSize.
	 * @return string The data read from the stream
	 */
	protected function rbytes($length = null) {
		$this->assertReadable();
		if (null == $length) {
			$length = $this->bufferSize;
		}
		$ret = fread($this->stream, $length);
		if (false === $ret) {
			throw new RuntimeException('Cannot read stream');
		}

		return $ret;
	}

	/**
	 * Read one line from the stream.
	 * Binary-safe. Reading ends when length bytes have been read, when the
	 * string specified by ending is found (which is not included in the return
	 *  value), or on EOF (whichever comes first).
	 *
	 * @access public
	 * @param  integer $length Maximum number of bytes to read. Defaults to self::$bufferSize.
	 * @param  string $ending Line ending to stop at. Defaults to "\n".
	 * @return string The line read from the stream
	 */
	public function rline($length = null, $ending = "\n") {
		$this->assertReadable();
		if (null == $length) {
			$length = $this->bufferSize;
		}
		$ret = stream_get_line($this->stream, $length, $ending);
		if (false === $ret) {
			throw new RuntimeException('Cannot read stream');
		}

		return $ret;
	}

	/**
	 * Read the remaining data from the stream until its end.
	 * Binary-safe.
	 *
	 * @access public
	 * @return string The data read from the stream
	 */
	public function getContents() {
		$this->assertReadable();
		return stream_get_contents($this->stream);
	}

	/**
	 * Write data to the stream.
	 * Binary-safe.
	 *
	 * @access protected
	 * @param  string $string The string that is to be written.
	 * @param  integer $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 * @return integer Number of bytes written
	 */
	protected function wbytes($string, $length = null) {
		$this->assertWritable();
		if (null === $length) {
			$ret = fwrite($this->stream, $string);
		} else {
			$ret = fwrite($this->stream, $string, $length);
		}
		if (false === $ret) {
			throw new RuntimeException('Cannot write on stream');
		}

		return $ret;
	}

	/**
	* Check whether the stream is positioned at the end.
	*
	* @access public
	* @return boolean determing wheter we're at the eof or not
	*/
	public function eof() {
		return feof($this->stream);
	}

	/**
	 * Get the position of the file pointer
	 *
	 * @access public
	 * @return integer with the current cursor position
	 */
	public function offset() {
		$this->assertSeekable();
		$ret = ftell($this->stream);
		if (false === $ret) {
			throw new RuntimeException('Cannot get offset of stream');
		}

		return $ret;
	}

	/**
	 * Read the content of this stream and write it to another stream, by chunks of $bufferSize
	 *
 	 * @access public
	 * @param  mixed $stream The other stream to copy to (could be resource or object)
	 * @return integer Number of piped bytes
 	 */
	public function pipe($stream) {
		if($stream instanceof self::$class) {
			return stream_copy_to_stream($this->getResource(), $stream->getResource());
		} elseif(is_resource($stream)) {
			return stream_copy_to_stream($this->getResource(), $stream);
		} else {
			throw new InvalidArgumentException("Can only pipe to another resource or another ResourceStream object");
		}
	}

	/**
	* Move the file pointer to a new position
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
	public function seek($offset, $whence = SEEK_SET) {
		$this->assertSeekable();
		if (false === fseek($this->stream, $offset, $whence)) {
			throw new RuntimeException('Cannot seek on stream');
		}
	}

	/**
	 * rewind() method that puts the cursor at the beginning of the stream
	 *
	 * @access public
	 * @return void
	 */
	public function rewind() {
		$this->assertSeekable();
		if (false === rewind($this->stream)) {
			throw new RuntimeException('Cannot rewind stream');
		}
	}

	/**
	 * close the stream resource
	 *
	 * @access public
	 * @return boolean determing wheter closing was successfull
	 */
	public function close() {
		if (!$this->isOpen) {
			throw new LogicException('Stream is already closed');
		}
		if ($ret = fclose($this->stream)) {
			$this->isOpen = false;
		}

		return $ret;
	}

	/**
	 * Small helper function asserting that the enclosed stream resource is readable
	 *
	 * @access public
	 * @return void
	 */
	protected function assertReadable() {
		if (!$this->isOpen) {
			throw new LogicException('Cannot read from a closed stream');
		}
		if (!$this->isReadable()) {
			throw new LogicException(sprintf('Cannot read on a non readable stream (current mode is %s)', $this->metadataForKey('mode')));
		}
	}

	/**
	 * Small helper function asserting that the enclosed stream resource is writable
	 *
	 * @access public
	 * @return void
	 */
	protected function assertWritable() {
		if (!$this->isOpen) {
			throw new LogicException('Cannot write on a closed stream');
		}
		if (!$this->isWritable()) {
			throw new LogicException(sprintf('Cannot write on a non-writable stream (current mode is %s)', $this->metadataForKey('mode')));
		}
	}

	/**
	 * Small helper function asserting that the enclosed stream resource is seekable
	 *
	 * @access public
	 * @return void
	 */
	protected function assertSeekable() {
		if (!$this->isOpen) {
			throw new LogicException('Cannot seek on a closed stream');
		}
		if (!$this->isSeekable()) {
			throw new LogicException('Cannot seek on a non-seekable stream');
		}
	}

}
