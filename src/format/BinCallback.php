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
	* property containing the callback to be called on the stream to read the file
	*
	* @access public
	* @var    callable $readCallback The callback to be called when reading
	*/
	public $readCallback;

	/**
	* property containing the callback to be called on the stream
	*
	* @access public
	* @var    callable $writeCallback The callback to be called to write
	*/
	public $writeCallback;

	/**
	 * constructor method for the BinCallback definition object
	 *
	 * @access public
	 * @param  callable $readCallback The callback to be called when reading
	 * @param  callable $writeCallback The callback to be called to write
	 * @return void
	 */
	public function __construct(callable $readCallback, callable $writeCallback = null) {
		$this->readCallback = $readCallback;
		$this->writeCallback = $writeCallback;
	}

	/**
	 * parse() method reading the tree and applying the translation to it
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return mixed read data by the callback, if any
	 */
	public function parse(Stream $stream) {
		$this->data = call_user_func($this->readCallback, $stream);
		return $this->data;
	}

	/**
	 * compose() method reading the definition and composing the given data to a binary string
	 *
	 * @access public
	 * @param  Stream $stream The stream object to write to
	 * @param  mixed $data The boolean to be written
	 * @return void
	 */
	public function compose(Stream $stream, $data) {
		if($this->writeCallback === null) {
			throw new InvalidFormatException("Unknown size definition ".var_export($sizeDef, true), 100);
		}
		call_user_func($this->writeCallback, array($stream, $data));
	}

}
