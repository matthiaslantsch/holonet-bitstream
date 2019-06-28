<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the BitArrayTest PHPUnit test class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream\tests;

use PHPUnit\Framework\TestCase;
use holonet\bitstream\BitArray;

/**
 * The BitArrayTest tests the BitArray class with some of the most common usecases
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class BitArrayTest extends TestCase {

	/**
	 * @covers \holonet\bitstream\BitArray::integerSize()
	 */
	public function testIntegerSize() {
		$this->assertEquals(3, BitArray::integerSize(bindec("100")));
		$this->assertEquals(8, BitArray::integerSize(bindec("11111111")));
	}

	/**
	 * @covers \holonet\bitstream\BitArray::push()
	 */
	public function testAppend() {
		$smallBitArr = new BitArray(12);

		//append 110
		$smallBitArr->push(bindec("110"));
		$this->assertEquals(decbin($smallBitArr->getValue()), "110");

		//append 11
		$smallBitArr->push(bindec("11"));
		$this->assertEquals(decbin($smallBitArr->getValue()), "11011");
	}

	/**
	 * @covers \holonet\bitstream\BitArray::push()
	 */
	public function testAppendGmp() {
		//test with gmp
		$bigBitArr = new BitArray((PHP_INT_SIZE * 8) + 1);

		//append 110
		$bigBitArr->push(bindec("110"));
		$this->assertEquals(decbin($bigBitArr->getValue()), "110");

		//append 11
		$bigBitArr->push(bindec("11"));
		$this->assertEquals(decbin($bigBitArr->getValue()), "11011");
	}

	/**
	 * @covers \holonet\bitstream\BitArray::unshift()
	 */
	public function testPrepend() {
		$smallBitArr = new BitArray(12);

		//prepend 110
		$smallBitArr->unshift(bindec("110"));
		$this->assertEquals(decbin($smallBitArr->getValue()), "110");

		//prepend 11
		$smallBitArr->unshift(bindec("11"));
		$this->assertEquals(decbin($smallBitArr->getValue()), "11110");
	}

	/**
	 * @covers \holonet\bitstream\BitArray::unshift()
	 */
	public function testPrependGmp() {
		//test with gmp
		$bigBitArr = new BitArray((PHP_INT_SIZE * 8) + 1);

		//prepend 110
		$bigBitArr->unshift(bindec("110"));
		$this->assertEquals(decbin($bigBitArr->getValue()), "110");

		//prepend 11
		$bigBitArr->unshift(bindec("11"));
		$this->assertEquals(decbin($bigBitArr->getValue()), "11110");
	}

	/**
	 * @covers \holonet\bitstream\BitArray::pop()
	 */
	public function testPop() {
		$smallBitArr = BitArray::fromNumber(bindec("10111011011101011"));

		//pop 01011 off the end
		$this->assertEquals("1011", decbin($smallBitArr->pop(5)));

		//pop 110110111
		$this->assertEquals("110110111", decbin($smallBitArr->pop(9)));
	}

	/**
	 * @covers \holonet\bitstream\BitArray::pop()
	 */
	public function testPopGmp() {
		$bigBitArr = new BitArray((PHP_INT_SIZE * 8) + 1);
		$bigBitArr->push(bindec("10111011011101011"));

		//pop 01011 off the end
		$this->assertEquals("1011", decbin($bigBitArr->pop(5)));

		//pop 110110111
		$this->assertEquals("110110111", decbin($bigBitArr->pop(9)));
	}

	/**
	 * @covers \holonet\bitstream\BitArray::shift()
	 */
	public function testShift() {
		$smallBitArr = BitArray::fromNumber(bindec("10111011011101011"));

		//shift 10111 off the start
		$this->assertEquals("10111", decbin($smallBitArr->shift(5)));

		//shift 011011101 of the start
		$this->assertEquals("11011101", decbin($smallBitArr->shift(9)));
	}

	/**
	 * @covers \holonet\bitstream\BitArray::shift()
	 */
	public function testShiftGmp() {
		$bigBitArr = new BitArray((PHP_INT_SIZE * 8) + 1);
		$bigBitArr->push(bindec("10111011011101011"));

		//shift 10111 off the start
		$this->assertEquals("10111", decbin($bigBitArr->shift(5)));

		//shift 011011101 of the start
		$this->assertEquals("11011101", decbin($bigBitArr->shift(9)));
	}

	/**
	 * @covers \holonet\bitstream\BitArray::fromBinary()
	 */
	public function testFromBinary() {
		//16896 / 66
		$data = "\x42\x00";

		$arrBigEnd = BitArray::fromBinary($data);
		$this->assertEquals(16896, $arrBigEnd->getValue());

		$arrSmalEnd = BitArray::fromBinary($data, false);
		$this->assertEquals(66, $arrSmalEnd->getValue());
	}

	/**
	 * @covers \holonet\bitstream\BitArray::fromNumber()
	 */
	public function testFromNumber() {
		//101010 => 42
		$bitarr = BitArray::fromNumber(42);

		$this->assertEquals(6, $bitarr->size);
		$this->assertEquals(42, $bitarr->value);
	}

}
