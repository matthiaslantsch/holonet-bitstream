<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinStruct class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinStruct is reprensenting a binary array like structure
 * contains a series of parsing instructions and will either return an array
 * or an instance of the given class (second parameter)
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinStruct extends BinNode {

	/**
	 * property containing the substruct tree we are working with
	 * items without key are not being saved into the result
	 *
	 * @access public
	 * @var    array $tree An array with items in this struct
	 */
	public $tree;

	/**
	 * property containing a class name if the data should be returned in an object
	 *
	 * @access public
	 * @var    string $class The class to return the data in
	 */
	public $class;

	/**
	 * constructor method for the BinStruct definition object
	 *
	 * @access public
	 * @param  array $tree An array with items in this struct
	 * @param  string $class Optional parameter to allow for the parsing into an object
	 * @return void
	 */
	public function __construct(array $tree, string $class = null) {
		$this->tree = $tree;
		$this->class = $class;
	}

	/**
	 * parse() method reading the subtree and parsing the structure on a stream
	 *
	 * @access public
	 * @param  Stream $stream The stream object to read from
	 * @return mixed array or object if given
	 */
	public function parse(Stream $stream) {
		$this->data = array();

		foreach ($this->tree as $key => $subnode) {
			if($subnode instanceof BinNode) {
				//echo "reading {$key}\n";
				$result = $subnode->parse($stream);
			} else {
				$result = $subnode;
			}

			if(is_string($key)) {
				$this->setKeyVal($key, $result);
			}
		}

		if($this->class !== null) {
			return new $this->class(...array_values($this->data));
		} else {
			return $this->data;
		}
	}

	/**
	 * compose() method reading the definition and composing the given data to binary
	 *
	 * @access public
	 * @param  Stream $stream The stream object to write to
	 * @param  array $data The array to be written
	 * @return void
	 */
	public function compose(Stream $stream, array $data) {
		$this->data = $data;

		foreach ($this->tree as $key => $subnode) {
			if(is_string($key)) {
				$value = $this->getKeyVal($key);
				if($subnode instanceof BinNode) {
					$subnode->compose($stream, $value);
				}
			} else {
				throw new InvalidFormatException("Cannot write with a format that has BinStruct with ignored members in it", 1020);
			}
		}
	}

	/**
	 * helper function used to recursively set a value with a multilevel key
	 *
	 * @access protected
	 * @param  string $key The key to save the value below
	 * @param  mixed $value The result of the parsing to be set at the key value
	 * @return void
	 */
	protected function setKeyVal(string $key, $value) {
		$position = &$this->data;
		$parts = explode(">>>", $key);
		foreach ($parts as $sublevel) {
			//check if the current cursor position has the next sub index
			if(isset($position[$sublevel])) {
				$position = &$position[$sublevel];
			} else {
				$position[$sublevel] = array();
				$position = &$position[$sublevel];
			}
		}
		if(is_array($position) && is_array($value)) {
			$position = array_merge($value, $position);
		} else {
			$position = $value;
		}
	}

	/**
	 * helper function used to recursively get a value with a multilevel key
	 *
	 * @access protected
	 * @param  string $key The key to get the value below
	 * @return mixed the value under the key
	 */
	protected function getKeyVal(string $key, $value) {
		$position = &$this->data;
		$parts = explode(">>>", $key);
		foreach ($parts as $sublevel) {
			//check if the current cursor position has the next sub index
			if(isset($position[$sublevel])) {
				$position = $position[$sublevel];
			}
		}

		return $position;
	}

}
