<?php
/**
 * This file is part of the bitstream package
 * (c) Matthias Lantsch
 *
 * class file for the NetworkStream class
 *
 * @package holonet bitstream library
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\bitstream;

/**
 * The NetworkStream class is used to wrap around a php socket connection
 *
 * @author  matthias.lantsch
 * @package holonet\bitstream
 */
class NetworkStream extends ResourceStream {

	/**
	 * static instanciator method that works with an address
	 *
	 * @access public
	 * @param  string $address The network address to open
	 * @param  float $timeout The allowed timeout in seconds (optional)
	 * @param  integer $flags An integer with bit masks for the connect function (optional)
	 * @param  resource $context The context for the stream to be opened (optional)
	 * @return void
	 */
	public static function create(string $address, float $timeout = null, $flags = null, $context = null) {
		if(gettype($context) === 'resource') {
			return new static(stream_socket_client($address, $errno, $errstr, $timeout, $flags, $context));
		}

		return new static(stream_socket_client($address, $errno, $errstr, $timeout, $flags));
	}

	/**
	 * small method returning the name of the opened socket
	 *
	 * @access public
	 * @return string with the opened socket name
	 */
	public function name($remote = true) {
		return stream_socket_get_name($this->stream, $remote);
	}

}
