<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the FileStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The FileStream class is used to wrap around a php stream resource that's opened
 * over a file on the file system
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class FileStream extends ResourceStream {

	/**
	 * static instanciator method that works with a filename
	 *
	 * @access public
	 * @param  string $file The filename to work with
	 * @param  string $mode The mode to open the file
	 * @param  resource $context The context for the stream to be opened (optional)
	 * @return void
	 */
	public static function create(string $file, string $mode = 'r+', $context = null) {
		if(gettype($context) === 'resource') {
			return new self(fopen($file, $mode, false, $context));
		}

		return new static(fopen($file, $mode, false));
	}

}
