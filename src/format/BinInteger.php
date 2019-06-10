<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinInteger class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinInteger is reprensenting an number in the binary structure
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinInteger extends BinNode {

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
	 * property containing constant to be added to the parsed value
	 *
	 * @access public
	 * @var    int $constant Constant to be added to the parsed value
	 */
	public $constant;

	/**
	 * property containing an endian string
	 *
	 * @access public
	 * @var    string $endian String with the endian type
	 */
	public $endian;

	/**
	 * constructor method for the BinInteger definition object
	 *
	 * @access public
	 * @param  mixed $size Parameter to determine the size of our integer
	 * @param  int $constant Constant to be added to the parsed value
	 * @param  string $endian String with the endian type
	 * @return void
	 */
	public function __construct($size, int $constant = 0, $endian = "big_endian") {
		$this->size = $size;
		$this->constant = $constant;
		$this->endian = $endian;
	}

	/**
	 * parse() method reading the number from the stream
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed scalar value read from the stream
	 */
	public function parse(Stream $stream) {
		if($this->size === 0) {
			return $this->constant;
		}
		//echo "   integer at {$stream->offset()}\n";
		$size = $this->resolveSizeDefinition($stream, $this->size);
		//echo "       size => {$size}\n";


		return ($stream->readBits($size, false, ($this->endian === "big_endian")) + $this->constant);
	}

}
