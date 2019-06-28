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
	 * @access public
	 * @var    integer|gmp $value The property actually holding the binary value
	 */
	public $value;

	/**
	 * property containing a counter of the actual size in bits
	 *
	 * @access public
	 * @var    integer $size The size of our number in bits
	 */
	public $size;

	/**
	 * constructor method taking the size of the new array in bits
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the new binary array in bits
	 * @return void
	 */
	public function __construct(int $sizeInBits) {
		$this->gmp = (($sizeInBits / 8) > PHP_INT_SIZE);
		$this->size = 0;
	}

	/**
	 * append binary data to the BitArray
	 * essentially shifts the current integer $sizeInBits to the left and or's the bits onto the end
	 *
	 * @access public
	 * @param  integer $bits The bits to append
	 * @param  integer $sizeInBits The size of the new binary data in bits (if not specified, will be counted)
	 * @return void
	 */
	public function push(int $bits, int $sizeInBits = null) {
		if($sizeInBits === null) {
			$sizeInBits = static::integerSize($bits);
		}

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
		$this->size += $sizeInBits;
	}

	/**
	 * remove binary data from the end of the BitArray
	 * essentially takes $sizeInBits bits from the end of shifts our number to remove them
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the requested binary data in bits
	 * @return integer with the read binary number
	 */
	public function pop(int $sizeInBits) {
		if($this->value === null) {
			return null;
		} else {
			$mask = ((1 << $sizeInBits) - 1);
			if($this->gmp) {
				$ret = gmp_and($this->value, $mask);
				$this->value = $this->gmp_shiftr($this->value, $sizeInBits);
			} else {
				$ret = ($mask & $this->value);
				$this->value >>= $sizeInBits;
			}
		}
		$this->size -= $sizeInBits;
		return $ret;
	}

	/**
	 * prepend binary data to the BitArray
	 * essentially shifts the current integer $sizeInBits to the right and or's the bits onto the start
	 *
	 * @access public
	 * @param  integer $bits The bits to prepend
	 * @param  integer $sizeInBits The size of the new binary data in bits (if not specified, will be counted)
	 * @return void
	 */
	public function unshift(int $bits, int $sizeInBits = null) {
		if($sizeInBits === null) {
			$sizeInBits = static::integerSize($bits);
		}

		if($this->value === null) {
			if($this->gmp) {
				$this->value = gmp_init($bits, 10);
			} else {
				$this->value = $bits;
			}
		} else {
			if($this->gmp) {
				$this->value = gmp_or($this->value, ($bits << $this->size));
			} else {
				$this->value = $this->value | ($bits << $this->size);
			}
		}
		$this->size += $sizeInBits;
	}

	/**
	 * remove binary data from the beginning of the BitArray
	 * essentially shifts the current integer $sizeInBits to the right and or's the bits onto the start
	 *
	 * @access public
	 * @param  integer $sizeInBits The size of the requested binary data in bits
	 * @return integer with the read binary number
	 */
	public function shift(int $sizeInBits) {
		if($this->value === null) {
			return null;
		} else {
			$extractPosFromRight = $this->size - $sizeInBits;
			if($this->gmp) {
				$ret = $this->gmp_shiftr($this->value, $extractPosFromRight);
				$this->value = gmp_xor($this->value, ($ret << $extractPosFromRight));
			} else {
				$ret = $this->value >> $extractPosFromRight;
				$this->value = $this->value ^ ($ret << $extractPosFromRight);
			}
		}
		$this->size -= $sizeInBits;
		return $ret;
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
	 * @access public
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
	 * @access public
	 * @return mixed read binary data
	 */
	public function getValue() {
		if($this->gmp) {
			return gmp_strval($this->value);
		} else {
			return $this->value;
		}
	}

	/**
	 * method used transform the internal number to a binary string
	 *
	 * @access public
	 * @param  boolean $bigendian Boolean flag telling wheter to use bigendian
	 * @return mixed binary data
	 */
	public function getBinary(bool $bigendian = true) {
		if($this->gmp) {
			return gmp_export($this->value, 1, GMP_LSW_FIRST | ($bigendian ? GMP_BIG_ENDIAN : GMP_LITTLE_ENDIAN ));
		} else {
			$ret = pack("C*", $this->value);
			//if we have small endian, we need to reverse it
			if(!$bigendian) {
				$ret = strrev($ret);
			}
			return $ret;
		}
	}

	/**
	 * method used to initialise a BitArray object using a binary string
	 *
	 * @access public
	 * @param  string $binary The binary string to transform into a bit array
	 * @param  boolean $bigendian Boolean flag telling wheter to use bigendian
	 * @return BitArray object from the binary string
	 */
	public static function fromBinary(string $binary, bool $bigendian = true) {
		$ret = new static(strlen($binary) * 8);
		if($ret->gmp) {
			$ret->value = gmp_import($binary, 1, GMP_LSW_FIRST | ($bigendian ? GMP_BIG_ENDIAN : GMP_LITTLE_ENDIAN ));
			$ret->size = strlen($binary) * 8;
		} else {
			for ($i = 0; $i < strlen($binary); $i++) {
				//append the string byte by byte to the number
				$c = $binary[$i];
				if($bigendian) {
					$ret->push(ord($c), 8);
				} else {
					$ret->unshift(ord($c), 8);
				}
			}
			return $ret;
		}
	}

	/**
	 * method used to initialise a BitArray object using a normal number
	 *
	 * @access public
	 * @param  int $number The number to create a bitarray object from
	 * @param  integer $sizeInBits The size of the given number in bits (optional)
	 * @return BitArray object from the number
	 */
	public static function fromNumber(int $number, int $size = null) {
		if($size === null) {
			$size = static::integerSize($number);
		}

		$ret = new static($size);
		$ret->push($number);
		return $ret;
	}

	/**
	 * method used to count the actual number of bits in an integer
	 * @TODO maybe decbin would actually be faster...
	 *
	 * @access public
	 * @param  int $integer The integer to get the size for
	 * @return int with the size in bits
	 */
	public static function integerSize(int $integer) {
		$high = 1;
		$size = 1;

		while ($high < PHP_INT_MAX) {
			if($integer >= $high && $integer < ($high << 1)) {
				return $size;
			}

			$high <<= 1;
			$size++;
		}

		return (PHP_INT_SIZE * 8);
	}

}
