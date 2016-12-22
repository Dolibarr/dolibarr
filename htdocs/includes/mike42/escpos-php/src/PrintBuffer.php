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
 * Print buffers manage newlines and character encoding for the target printer.
 * They are used as a swappable component: text or image-based output.
 * 
 * - Text output (EscposPrintBuffer) is the fast default, and is recommended for
 *   most people, as the text output can be more directly manipulated by ESC/POS
 *   commands.
 * - Image output (ImagePrintBuffer) is designed to accept more encodings than the
 *   physical printer supports, by rendering the text to small images on-the-fly.
 *   This takes a lot more CPU than sending text, but is necessary for some users.
 * - If your use case fits outside these, then a further speed/flexibility trade-off
 *   can be made by printing directly from generated HTML or PDF.
 */
interface PrintBuffer {
	/**
	 * Cause the buffer to send any partial input and wait on a newline.
	 * If the printer is already on a new line, this does nothing.
	 */
	function flush();

	/**
	 * Used by Escpos to check if a printer is set.
	 */
	function getPrinter();

	/**
	 * Used by Escpos to hook up one-to-one link between buffers and printers.
	 * 
	 * @param Escpos $printer New printer
	 */
	function setPrinter(Escpos $printer = null);

	/**
	 * Accept UTF-8 text for printing.
	 * 
	 * @param string $text Text to print
	 */
	function writeText($text);

	/**
	 * Accept 8-bit text in the current encoding and add it to the buffer.
	 * 
	 * @param string $text Text to print, already the target encoding.
	 */
	function writeTextRaw($text);
}
?>