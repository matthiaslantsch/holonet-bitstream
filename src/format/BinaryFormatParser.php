<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinaryFormatParser class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinaryFormatParser to be used to read a binary file according to a format description tree
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinaryFormatParser {

	/**
	 * property containing the actual stream we're working with
	 *
	 * @access protected
	 * @var    Stream $stream The actual stream we're working with
	 */
	protected $stream;

	/**
	 * property containing a BinNode object telling us how to parse our file
	 *
	 * @access protected
	 * @var    BinNode $tree The node from which to start parsing
	 */
	protected $tree;

	/**
	 * constructor method for the stream wrapper
	 * will take a stream object as an argument
	 *
	 * @access public
	 * @param  Stream $stream The opened bytestream to work with
	 * @param  BinNode $tree The node from which to start parsing
	 * @return void
	 */
	public function __construct(Stream $stream, BinNode $tree) {
		$this->stream = $stream;
		$this->tree = $tree;
	}

	/**
	 * parse() method starting the parsing proccess
	 *
	 * @access public
	 * @return mixed resulting data from the given tree
	 */
	public function parse() {
		$ret = $this->tree->parse($this->stream);

		if(!$this->stream->eof()) {
			//throw new InvalidFormatException("Did not use all bytes while decoding file with format", 1000);
		}

		return $ret;
	}

}
