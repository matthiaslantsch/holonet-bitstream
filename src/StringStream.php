<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the StringStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The StringStream class is used to wrap around a php string and offering it as
 * an opened stream resource
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class StringStream extends Stream {

	/**
	 * property containing an offset integer
	 *
	 * @access private
	 * @var    string $offset The offset our reader is at in our stream
	 */
	private $offset = 0;

	/**
	 * constructor method taking the string with the content as the only argument
	 *
	 * @access public
	 * @param  string $content The string to work with
	 * @return void
	 */
	public function __construct(string $content) {
		$this->stream = $content;
		$this->isOpen = true;
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
		if (null == $length) {
			$length = $this->bufferSize;
		}

		$ret = substr($this->stream, $this->offset, $length);
		$this->offset += $length;
		return $ret;
	}

	/**
	 * Read one line from the string.
	 * Binary-safe. Reading ends when length bytes have been read, when the
	 * string specified by ending is found (which is not included in the return
	 *  value), or on end of string (whichever comes first).
	 *
	 * @access public
	 * @param  integer $length Maximum number of bytes to read. Defaults to self::$bufferSize.
	 * @param  string $ending Line ending to stop at. Defaults to "\n".
	 * @return string The line read from the stream
	 */
	public function rline($length = null, $ending = "\n") {
		//@todo couldn't we find some less memory intensive way to do this?
		if (null == $length) {
			$length = $this->bufferSize;
		}

		$maxstring = substr($this->stream, $this->offset, $length);
		if(($ret = strstr($maxstring, $ending, true)) === false) {
			$this->offset += $length;
			return $maxstring;
		} else {
			$this->offset += strlen($ret);
			return $ret;
		}
	}

	/**
	 * Read the remaining data from the string until its end.
	 * Binary-safe.
	 *
	 * @access public
	 * @return string The rest of the contained string to the end
	 */
	public function getContents() {
		$this->offset = strlen($this->stream);
		return substr($this->stream, $this->offset);
	}

	/**
	 * Write data to the string at the current offset.
	 * Binary-safe.
	 *
	 * @access protected
	 * @param  string $string The string that is to be written.
	 * @param  integer $length If the length argument is given, writing will stop after length bytes have been written or the end of string is reached, whichever comes first.
	 * @return integer Number of bytes written
	 */
	protected function wbytes($string, $length = null) {
		if($length !== null && strlen($string) > $length) {
			$string = substr($string, 0, $length);
		}

		$this->stream = substr_replace($this->stream, $string, $this->offset, strlen($string));

		return strlen($string);
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
			$stream->stream .= $this->getContents();
			return strlen($this->stream);
		} else {
			throw new InvalidArgumentException("Can only pipe to another StringStream object");
		}
	}

	/**
	 * Get the position of the offset pointer
	 *
	 * @access public
	 * @return integer with the current cursor position
	 */
	public function offset() {
		return $this->offset;
	}

	/**
	* Move the offset counter to a new position
	* The new position, measured in bytes from the beginning of the string,
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
		if($whence === SEEK_SET) {
			$this->offset = $offset;
		} elseif($whence === SEEK_CUR) {
			$this->offset += $offset;
		} elseif($whence === SEEK_END) {
			$this->offset = strlen($this->stream) + $offset;
		}
	}

	/**
	 * rewind() method that puts the cursor at the beginning of the string
	 *
	 * @access public
	 * @return void
	 */
	public function rewind() {
		$this->offset = 0;
	}

	/**
	 * "close" the stream
	 *
	 * @access public
	 * @return boolean true to mark the stream as closed
	 */
	public function close() {
		$this->isOpen = false;
		return true;
	}

	/**
	 * return true because our string stream is always readable
	 *
	 * @access public
	 * @return boolean true
	 */
	public function isReadable() {
		return true;
	}

	/**
	 * return true because our string stream is always writable
	 *
	 * @access public
	 * @return boolean true
	 */
	public function isWritable() {
		return true;
	}

	/**
	 * return true because our string stream is always seekable
	 *
	 * @access public
	 * @return boolean true
	 */
	public function isSeekable() {
		return true;
	}

	/**
	* Check whether the stream is positioned at the end of our string
	*
	* @access public
	* @return boolean determing wheter we're at the end of the string or not
	*/
	public function eof() {
		return $this->offset >= (strlen($this->stream) - 1);
	}

}
