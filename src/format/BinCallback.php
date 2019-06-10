<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinCallback class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinCallback is used so the user can still operate on the stream itself,
 * when the objects provided by the engine do not suffice
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinCallback extends BinNode {

	/**
	* property containing the callback to be called on the stream
	*
	* @access public
	* @var    callable $callback The callback to be called
	*/
	public $callback;

	/**
	 * constructor method for the BinCallback definition object
	 *
	 * @access public
	 * @param  callable $callback The callback to be called
	 * @return void
	 */
	public function __construct(callable $callback) {
		$this->callback = $callback;
	}

	/**
	 * parse() method reading the tree and applying the translation to it
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed read data by the callback, if any
	 */
	public function parse(Stream $stream) {
		$this->data = call_user_func($this->callback, $stream);
		return $this->data;
	}

}
