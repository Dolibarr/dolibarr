<?php
/**
 * escpos-php, a Thermal receipt printer library, for use with
 * ESC/POS compatible printers.
 *
 * Copyright (c) 2014-2015 Michael Billington <michael.billington@gmail.com>,
 * 	incorporating modifications by:
 *  - Roni Saha <roni.cse@gmail.com>
 *  - Gergely Radics <gerifield@ustream.tv>
 *  - Warren Doyle <w.doyle@fuelled.co>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * Print connector that writes to nowhere, but allows the user to retrieve the
 * buffered data. Used for testing.
 */
final class DummyPrintConnector implements PrintConnector {
	/**
	 * @var array Buffer of accumilated data.
	 */
	private $buffer;

	/**
	 * @var string data which the printer will provide on next read
	 */
	private $readData;

	/**
	 * Create new print connector
	 */
	public function __construct() {
		$this -> buffer = array();
	}

	public function __destruct() {
		if($this -> buffer !== null) {
			trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
		}
	}

	public function finalize() {
		$this -> buffer = null;
	}

	/**
	 * @return string Get the accumulated data that has been sent to this buffer.
	 */
	public function getData() {
		return implode($this -> buffer);
	}

	/* (non-PHPdoc)
	 * @see PrintConnector::read()
	 */
	public function read($len) {
		return $len >= strlen($this -> readData) ? $this -> readData : substr($this -> readData, 0, $len);
	}

	public function write($data) {
		$this -> buffer[] = $data;
	}
}
