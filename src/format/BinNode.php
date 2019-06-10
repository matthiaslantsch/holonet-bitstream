<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the abstract BinNode base class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinNode class is supposed to represent a node in a binary document
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
abstract class BinNode {

	/**
	 * property containing the data of this binary struct in form of an associative array
	 *
	 * @access protected
	 * @var    array $data Array with the data contained in this struct
	 */
	protected $data;

	/**
	 * resolveSizeDefinition() method turning a size defining parameter into an actual integer
	 *
	 * @access protected
	 * @param  Stream $stream The stream object to read from
	 * @param  mixed $sizeDef Different possible size definitions
	 * @return integer with the size definition
	 */
	protected function resolveSizeDefinition(Stream $stream, $sizeDef) {
		if($sizeDef instanceof BinNode) {
			//echo "       size parsing => \n\t";
			return intval($sizeDef->parse($stream));
		} elseif(is_scalar($sizeDef)) {
			return intval($sizeDef);
		} elseif(is_callable($sizeDef)) {
			return $this->resolveSizeDefinition($sizeDef());
		} else {
			throw new InvalidFormatException("Unknown size definition ".var_export($sizeDef, true), 100);
		}
	}

	/**
	 * force the child class to implement a parse() method
	 * that parses the Node using a stream object
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return void
	 */
	abstract public function parse(Stream $stream);

}
