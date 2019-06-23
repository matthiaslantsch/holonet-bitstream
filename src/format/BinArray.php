<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinArray class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use RuntimeException;
use holonet\bitstream\Stream;

/**
 * BinArray is reprensenting a binary array structure
 * contains a series of parsing instructions and will return an array
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinArray extends BinNode {

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
	 * property containing the substruct tree we are working with
	 *
	 * @access public
	 * @var    BinNode $tree A node definitions with items in this struct
	 */
	public $tree;

	/**
	 * constructor method for the BinArray definition object
	 *
	 * @access public
	 * @param  mixed $size Parameter to determine the size of our array
	 * @param  BinNode $tree A Node to be parsed size times
	 * @return void
	 */
	public function __construct($size, BinNode $tree) {
		$this->size = $size;
		$this->tree = $tree;
	}

	/**
	 * parse() method reading the subtree and parsing the structure on a stream
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return mixed array of subtrees read
	 */
	public function parse(Stream $stream) {
		if($this->size === "read_all") {
			$ret = array();

			while (!$stream->eof()) {
				//parse the tree until the stream does not offer data anymore
				$ret[] = $this->tree->parse($stream);
			}

			return $ret;
		} else {
			$sizeActual = $this->resolveSizeDefinition($stream, $this->size);
			$ret = array();

			while ($sizeActual--) {
				//parse the tree once per subitem
				$ret[] = $this->tree->parse($stream);
			}

			return $ret;
		}
	}

	/**
	 * compose() method reading the subtree and composing the given data to a binary string
	 *
	 * @access public
	 * @param  Stream $stream The stream object to write to
	 * @param  array $data The data to be written
	 * @return void
	 */
	public function compose(Stream $stream, array $data) {
		$sizeCheck = $this->composeSizeDefinition($stream, $this->size, count($data));

		//check if we have too many or too little items
		if($sizeCheck !== null && $sizeCheck !== count($data)) {
			throw new RuntimeException("Error writing BinArray, expected a fixed number of {$sizeCheck} items, got ".count($data), 1006);
		}

		foreach ($data as $entry) {
			$this->tree->compose($stream, $entry);
		}
	}

}
