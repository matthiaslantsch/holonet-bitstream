<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinTranslate class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinTranslate is used to perform a sorting / changing operation
 * on a result of a node read
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinTranslate extends BinNode {

	/**
	* property containing the substruct tree we are working with
	*
	* @access public
	* @var    BinNode $tree A node definitions with items in this struct
	*/
	public $tree;

	/**
	* property containing the definition for translation
	* can be a callable or an array for value mapping
	*
	* @access public
	* @var    mixed $translation The translation definition
	*/
	public $translation;

	/**
	 * constructor method for the BinTranslate definition object
	 *
	 * @access public
	 * @param  BinNode $tree A node definitions with items in this struct
	 * @param  mixed $translation The translation definition
	 * @return void
	 */
	public function __construct(BinNode $tree, $translation) {
		$this->tree = $tree;
		$this->translation = $translation;
	}

	/**
	 * parse() method reading the tree and applying the translation to it
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return mixed read data translated by this class
	 */
	public function parse(Stream $stream) {
		$readData = $this->tree->parse($stream);

		if(is_callable($this->translation)) {
			$this->data = call_user_func($this->translation, $readData);
		} elseif(is_array($this->translation)) {
			//assume it's a map for translation
			if(isset($this->translation[$readData])) {
				$this->data = $this->translation[$readData];
			} else {
				//@TODO fail silently?!?!?
				$this->data = $readData;
			}
		} else {
			throw new InvalidFormatException("Unknown translation definition ".var_export($this->translation, true), 102);
		}

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
		if(is_callable($this->translation)) {
			throw new InvalidFormatException("Cannot write with a format that has BinTranslate with a read callback in it", 1030);
		} elseif(is_array($this->translation)) {
			//assume it's a map for translation
			if(($writeData = array_search($data, $this->translation)) === false) {
				//@TODO fail silently??
				$writeData = $readData;
			}
		} else {
			throw new InvalidFormatException("Unknown translation definition ".var_export($this->translation, true), 102);
		}

		$this->tree->compose($stream, $writeData);
	}

}
