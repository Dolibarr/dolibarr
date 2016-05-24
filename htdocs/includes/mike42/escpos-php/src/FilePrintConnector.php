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
 * PrintConnector for passing print data to a file.
 */
class FilePrintConnector implements PrintConnector {
	/**
	 * @var resource The file pointer to send data to.
	 */
	protected $fp;

	/**
	 * Construct new connector, given a filename
	 * 
	 * @param string $filename
	 */
	public function __construct($filename) {
		$this -> fp = fopen($filename, "wb+");
		if($this -> fp === false) {
			throw new Exception("Cannot initialise FilePrintConnector.");
		}
	}

	public function __destruct() {
		if($this -> fp !== false) {
			trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
		}
	}

	/**
	 * Close file pointer
	 */
	public function finalize() {
		fclose($this -> fp);
		$this -> fp = false;
	}
	
	/* (non-PHPdoc)
	 * @see PrintConnector::read()
	 */
	public function read($len) {
		rewind($this -> fp);
		return fgets($this -> fp, $len + 1);
	}
	
	/**
	 * Write data to the file
	 * 
	 * @param string $data
	 */
	public function write($data) {
		fwrite($this -> fp, $data);
	}
}
