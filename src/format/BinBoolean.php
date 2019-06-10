<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BinBoolean class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\format;

use holonet\bitstream\Stream;

/**
 * BinBoolean is reprensenting an bit boolean in the binary format
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class BinBoolean extends BinNode {

	/**
	 * parse() method reading the number from the stream
	 *
	 * @access public
	 * @param  Stream $strean The stream object to read from
	 * @return mixed scalar value read from the stream
	 */
	public function parse(Stream $stream) {
		return boolval($stream->readBits(1));
	}

}
