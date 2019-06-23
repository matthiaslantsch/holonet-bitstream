<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinaryFormatComposer class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinaryFormatComposer to be used to write a binary file according to a format description tree
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinaryFormatComposer {

	/**
	 * property containing the actual stream we're working with
	 *
	 * @access protected
	 * @var    Stream $stream The actual stream we're working with
	 */
	protected $stream;

	/**
	 * property containing a BinNode object telling us how to compose our file
	 *
	 * @access protected
	 * @var    BinNode $tree The node from which to start composing
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
	 * compose() method starting the composing proccess
	 *
	 * @access public
	 * @param  array $data The data to be written
	 * @return mixed resulting binary string from the given tree and data
	 */
	public function compose(array $data) {
		if(!$this->stream->isWritable()) {
			throw new RuntimeException("Cannot write format file to non writable stream", 1005);
		}

		$this->tree->compose($this->stream, $data);
	}

}
