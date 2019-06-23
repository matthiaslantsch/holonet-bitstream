<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinOptional class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinOptional is used to read a node optionally
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinOptional extends BinNode {

	/**
	* property containing the substruct tree we are working with
	*
	* @access public
	* @var    BinNode $tree A node definitions with items to be read if the condition is met
	*/
	public $tree;

	/**
	* property containing the condition
	*
	* @access public
	* @var    mixed $condition The condition
	*/
	public $condition;

	/**
	 * constructor method for the BinOptional definition object
	 *
	 * @access public
	 * @param  BinNode $tree A node definitions with items in this struct
	 * @param  mixed $condition The condition
	 * @return void
	 */
	public function __construct(BinNode $tree, $condition) {
		$this->tree = $tree;
		$this->condition = $condition;
	}

	/**
	 * parse() method checking the condition and reading the tree if given
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return mixed read data translated by this class
	 */
	public function parse(Stream $stream) {
		if(is_callable($this->condition)) {
			$condition = call_user_func($this->condition, $stream);
		} elseif($this->condition instanceof BinNode) {
			$condition = boolval($this->condition->parse($stream));
		} elseif(!is_scalar($this->condition)) {
			throw new InvalidFormatException("Unknown condition definition ".var_export($this->condition, true), 103);
		} else {
			$condition = $this->condition;
		}

		if($condition) {
			return $this->tree->parse($stream);
		}
	}

	/**
	 * compose() method reading the definition and composing the given data to binary
	 *
	 * @access public
	 * @param  Stream $stream The stream object to write to
	 * @param  string $data The integer to be written
	 * @return void
	 */
	public function compose(Stream $stream, string $data) {
		if(is_callable($this->condition)) {
			die("How would I write this");
		} elseif($this->condition instanceof BinNode) {
			$this->condition->compose($stream, ($data !== null ? true : false));
		} elseif(!is_scalar($this->condition)) {
			throw new InvalidFormatException("Unknown condition definition ".var_export($this->condition, true), 103);
		}

		if($data !== null) {
			$this->tree->compose($stream, $data);
		}
	}

}
