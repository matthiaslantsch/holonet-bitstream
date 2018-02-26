<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BitwiseStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The BitwiseStream is a wrapper around an opened file stream, that allows for bitwise reading/writing
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class BitwiseStream extends BytewiseStream {

	/**
	 * property containing bit masks used to extract a certain number of bits
	 *
	 * @access private
	 * @var    array $includeBitmask Array with bitmasks used to extract a byte partially
	 */
	private static $includeBitmask = [0x01, 0x03, 0x07, 0x0F, 0x1F, 0x3F, 0x7F, 0xFF];

	/**
	 * property containing the started byte to continue reading/writing from there
	 *
	 * @access private
	 * @var    char $currentbyte The started byte
	 */
	private $currentbyte;

	/**
	 * property containing the number of bits already used from the started byte
	 *
	 * @access private
	 * @var    integer $byteshift Number of bits already used
	 */
	private $byteshift = 0;

	/**
	 * overwritten method from the base class used to read bytes using the readBits() method
	 *
	 * @access public
	 * @param  integer $len Number of bytes to read
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBytes($len) {
		return $this->readBits($len * 8);
	}

	/**
	 * wrapper method around our stream to read a specified number of bits,
	 * using out internal byte cache to save the started byte
	 * will just return read bytes if no byte is started, to save performance
	 *
	 * @access public
	 * @param  integer $len Number of bits to read
	 * @return string|boolean Binary read data from the byte stream or false if the stream is finished already
	 */
	public function readBits($len) {
		if($len === 0) {
			return 0;
		}

		//if no byte has been started and the number is even, simply use the parent reading method
		if($this->currentbyte === null && $len % 8 == 0) {
			return parent::readBytes($len);
		}

		$ret = new BitArray($len);

		if($this->currentbyte === null) {
			//no byte has been started yet
			//start a byte in the internal cache
			$this->currentbyte = ord($this->bytestream->rbytes(1));
			$this->byteshift = 0;
		}

		if($len <= 8 && $this->byteshift + $len <= 8) {
			//get the bitmask e.g. 00000111 for 3
			$bitmask = self::$includeBitmask[$len - 1];

			//can be satisfied with the remaining bits
			$ret->append($this->currentbyte & $bitmask, $len);

			//shift by len
			$this->currentbyte >>= $len;
			$this->byteshift += $len;
		} else {
			//read the remaining bits first
			$bitsremaining = 8 - $this->byteshift;
			$ret->append($this->readBits($bitsremaining), $bitsremaining);

			//decrease len by the amount bits remaining
			$len -= $bitsremaining;

			//set the internal byte cache to null
			$this->currentbyte = null;

			if($len > 8) {
				//read entire bytes as far as possible
				for ($i = intval($len / 8); $i > 0; $i--) {
					if($this->bytestream->eof()) {
						//no more bytes
						return false;
					}
					$byte = $this->bytestream->rbytes(1);
					$ret->append($byte, 8);
				}

				//reduce len to the rest of the requested number
				$len = $len % 8;
			}

			//read a new byte to get the rest required
			$newbyte = $this->readBits($len);
			$ret->append($newbyte, $len);
		}

		if($this->byteshift === 8) {
			//delete the cached byte
			$this->currentbyte = null;
		}

		return $ret->getValue();
	}

	/**
	 * wrapper method around readBits() to read a boolean (bit length 1) from the byte stream
	 *
	 * @access public
	 * @return read boolean
	 */
	public function readBoolean() {
		return ($this->readBits(1) === 1);
	}

}