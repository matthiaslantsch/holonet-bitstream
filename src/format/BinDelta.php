<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinDelta class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinDelta is used to read a node repeatetly and delta it to the previously read
 * instances
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinDelta extends BinNode {

	/**
	* property containing the substruct tree we are working with
	*
	* @access public
	* @var    BinNode $tree A node definitions with items to be read if the condition is met
	*/
	public $tree;

	/**
	* property containing the total of all the deltas read so far
	*
	* @access public
	* @var    mixed $total The total of all read instances so far
	*/
	public $total;

	/**
	 * constructor method for the BinDelta definition object
	 *
	 * @access public
	 * @param  BinNode $tree A node definitions with items to be read again and again
	 * @return void
	 */
	public function __construct(BinNode $tree) {
		$this->tree = $tree;
		if($this->tree instanceof BinBlob) {
			$this->total = "";
		} elseif($this->tree instanceof BinArray) {
			$this->total = array();
		} elseif($this->tree instanceof BinBoolean) {
			$this->total = true;
		} else {
			//just handle it like an integer
			$this->total = 0;
		}
	}

	/**
	 * parse() method parsing the substructure, adding it to
	 * the delta and returning the result
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed read data added to the old values
	 */
	public function parse(Stream $stream) {
		$read = $this->tree->parse($stream);
		if($this->tree instanceof BinBlob) {
			$this->total .= $read;
		} elseif($this->tree instanceof BinArray) {
			$this->total = array_merge($this->total, $read);
		} elseif($this->tree instanceof BinBoolean) {
			$this->total = ($this->total && $read);
		} else {
			$this->total += intval($read);
		}
		return $this->total;
	}

}
