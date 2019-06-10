<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * Class file for the InvalidFormatException class
 *
 * @package holonet bitstream library
 * @license http://opensource.org/licenses/gpl-license.php  GNU Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */
namespace holonet\bitstream\format;

use Exception;

/**
 * The InvalidFormatException class is used to throw an error about a binary format definition
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream\format
 */
class InvalidFormatException extends Exception {

	/**
	 * constructor method for the exception
	 *
	 * @access public
	 * @param  string $msg Error message
	 * @param  int $errorcode Error code
	 * @return void
	 */
	public function __construct($msg, $errorcode) {
		parent::__construct($msg, $errorcode);
	}

}
