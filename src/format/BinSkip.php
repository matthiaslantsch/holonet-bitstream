<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinSkip class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinSkip can be used to skip unknown bytes while parsing
 * Will throw an error when attempting to write to it though, since
 * we wouldn't know how to write those bytes
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinSkip extends BinNode {

	/**
	* property containing the definition for the size
	* has to be handled at parsetime so we can read sizes from the byte stream
	* at the right time
	*
	* @access public
	* @var    mixed $size Parameter to determine the size of our array
	*/
	public $size;

	/**
	 * property containing a flag marking wheter to align before parsing or not
	 *
	 * @access public
	 * @var    boolean $alignBeforeParsing Flag marking wheter to align before parsing or not
	 */
	public $alignBeforeParsing = false;

	/**
	 * constructor method for the BinSkip definition object
	 *
	 * @access public
	 * @param  mixed $size Parameter to determine the size of our array
	 * @param  boolean $alignBeforeParsing Flag marking wheter to align before parsing or not
	 * @return void
	 */
	public function __construct($size, bool $alignBeforeParsing = false) {
		$this->size = $size;
		$this->alignBeforeParsing = $alignBeforeParsing;
	}

	/**
	 * parse() method skiping the given amount of bytes
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed array of object if given
	 */
	public function parse(Stream $stream) {
		$sizeActual = $this->resolveSizeDefinition($stream, $this->size);

		if($this->alignBeforeParsing) {
			$stream->align();
		}

		$stream->readBytes($sizeActual);
	}

}
