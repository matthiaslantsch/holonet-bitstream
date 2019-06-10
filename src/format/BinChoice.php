<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinChoice class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use RuntimeException;
use holonet\bitstream\Stream;

/**
 * BinChoice is used to execute different parsing behaviours based on previously parsed data
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinChoice extends BinNode {

	/**
	* property containing the substruct tree we are working with
	*
	* @access public
	* @var    BinNode $tree A node definitions with items to be read if the condition is met
	*/
	public $tree;

	/**
	* property containing the map for behaviours
	*
	* @access public
	* @var    mixed $map The map for behaviours
	*/
	public $map;

	/**
	 * constructor method for the BinOptional definition object
	 *
	 * @access public
	 * @param  BinNode $tree A node definitions with items in this struct
	 * @param  mixed $map The map for behaviours
	 * @return void
	 */
	public function __construct(BinNode $tree, array $map) {
		$this->tree = $tree;
		$this->map = $map;
	}

	/**
	 * parse() method checking the condition and reading the tree if given
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed read data translated by this class
	 */
	public function parse(Stream $stream) {
		$mappingValue = $this->tree->parse($stream);

		if(!array_key_exists($mappingValue, $this->map)) {
			throw new RuntimeException(
				"Unknown mapping value for choicenode: {$mappingValue}, choices are "
				.json_encode(array_keys($this->map)), 1000
			);
		}

		if($this->map[$mappingValue] instanceof BinNode) {
			return $this->map[$mappingValue]->parse($stream);
		} else {
			return $this->map[$mappingValue];
		}
	}

}
