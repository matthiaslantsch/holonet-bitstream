<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BitArray class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The BitArray is used to abstract the reading of bytes
 * primarily used to automatically read into a gmp number if too many
 * bytes are being read
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class BitArray {

	/**
	 * property containing a flag marking this BitArray as a gmp number or not
	 *
	 * @access private
	 * @var    boolean $gmp Boolean flag marking this as a gmp number or not
	 */
	private $gmp;

	/**
	 * property containing the actual value (might me a normal integer or a gmp number)
	 *
	 * @access private
	 * @var    integer|gmp $value The property actually holding the binary value
	 */
	private $value;

	/**
	 * constructor method taking the size of the new array in bits
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the new binary array in bits
	 * @return void
	 */
	public function __construct($sizeInBits) {
		$this->gmp = (($sizeInBits / 8) > PHP_INT_SIZE);
	}

	/**
	 * append binary data to the BitArray
	 * essentially shifts the current integer $sizeInBits to the left and or's the bits onto the end
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the new binary data in bits
	 * @return void
	 */
	public function append($bits, $sizeInBits) {
		if($this->value === null) {
			if($this->gmp) {
				$this->value = gmp_init($bits, 10);
			} else {
				$this->value = $bits;
			}
		} else {
			if($this->gmp) {
				$this->value = gmp_or($this->gmp_shiftl($this->value, $sizeInBits), $bits);
			} else {
				$this->value = ($this->value << $sizeInBits) | $bits;
			}
		}
	}

	/**
	 * prepend binary data to the BitArray
	 * essentially shifts the current integer $sizeInBits to the right and or's the bits onto the start
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the new binary data in bits
	 * @return void
	 */
	public function prepend($bits, $sizeInBits) {
		if($this->value === null) {
			if($this->gmp) {
				$this->value = gmp_init($this->readBits($bits), 10);
			} else {
				$this->value = $bits;
			}
		} else {
			if($this->gmp) {
				$this->value = gmp_or($this->gmp_shiftr($this->value, $sizeInBits), $bits);
			} else {
				$this->value = ($this->value >> $sizeInBits) | $bits;
			}
		}
	}

	/**
	 * method used to bit shift a gmp number to the left
	 *
	 * @access private
	 * @param  integer $x The qmp number to be shifted
	 * @return integer $n The number of bits to shift the number to the left
	 * @return qmp number shifted n digits to the left
	 */
	private function gmp_shiftl($x, $n) {
		return(gmp_mul($x,gmp_pow(2,$n)));
	}

	/**
	 * method used to bit shift a gmp number to the right
	 *
	 * @access private
	 * @param  integer $x The qmp number to be shifted
	 * @return integer $n The number of bits to shift the number to the right
	 * @return qmp number shifted n digits to the right
	 */
	private function gmp_shiftr($x,$n) {
		return(gmp_div($x,gmp_pow(2,$n)));
	}

	/**
	 * method used generate a hex code from the contained bytes
	 *
	 * @access private
	 * @return string hex code representation of the contained bytes
	 */
	public function getHexCode() {
		if($this->gmp) {
			return gmp_strval($this->value, 16);
		} else {
			return bin2hex($this->value);
		}
	}

	/**
	 * method used return the actual finished read value
	 *
	 * @access private
	 * @return mixed read binary data
	 */
	public function getValue() {
		if($this->gmp) {
			return gmp_strval($this->value);
		} else {
			return $this->value;
		}
	}

}
