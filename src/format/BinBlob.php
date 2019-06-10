<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinBlob class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinBlob is reprensenting an number in the binary structure
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinBlob extends BinNode {

	/**
	* property containing the definition for the size
	* has to be handled at parsetime so we can read sizes from the byte stream
	* at the right time
	*
	* @access public
	* @var    mixed $size Parameter to determine the size of our integer
	*/
	public $size;

	/**
	 * property containing a class name if the data should be returned in an object
	 *
	 * @access public
	 * @var    string $class The class to return the data in
	 */
	public $class;

	/**
	 * constructor method for the BinBlob definition object
	 *
	 * @access public
	 * @param  mixed $size Parameter to determine the size of our blob
	 * @param  string $class Optional parameter to allow for the parsing into an object
	 * @return void
	 */
	public function __construct($size, string $class = null) {
		$this->size = $size;
		$this->class = $class;
	}

	/**
	 * parse() method reading the string from the stream
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed scalar value read from the stream
	 */
	public function parse(Stream $stream) {
		//echo "   blob at {$stream->offset()}\n";
		$size = $this->resolveSizeDefinition($stream, $this->size);
		//echo "       size => {$size}\n";

		$ret = $stream->readAlignedString($size);
		if($this->class !== null) {
			return new $this->class($ret);
		} else {
			return $ret;
		}
	}

}
