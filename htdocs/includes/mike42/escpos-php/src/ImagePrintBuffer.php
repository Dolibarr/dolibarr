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
 * This class renders text to small images on-the-fly. It attempts to mimic the
 * behaviour of text output, whilst supporting any fonts & character encodings
 * which your system can handle. This class currently requires Imagick.
 */
class ImagePrintBuffer implements PrintBuffer {
	private $printer;
	
	function __construct() {
		if(!EscposImage::isImagickLoaded()) {
			throw new Exception("ImagePrintBuffer requires the imagick extension");
		}
	}

	function flush() {
		if($this -> printer == null) {
			throw new LogicException("Not attached to a printer.");
		}
	}

	function getPrinter() {
		return $this -> printer;
	}

	function setPrinter(Escpos $printer = null) {
		$this -> printer = $printer;
	}

	function writeText($text) {
		if($this -> printer == null) {
			throw new LogicException("Not attached to a printer.");
		}
		if($text == null) {
			return;
		}
		$text = trim($text, "\n");
		/* Create Imagick objects */
		$image = new Imagick();
		$draw = new ImagickDraw();
		$color = new ImagickPixel('#000000');
		$background = new ImagickPixel('white');

		/* Create annotation */
		//$draw -> setFont('Arial');// (not necessary?)
		$draw -> setFontSize(24); // Size 21 looks good for FONT B
		$draw -> setFillColor($color);
		$draw -> setStrokeAntialias(true);
		$draw -> setTextAntialias(true);
		$metrics = $image -> queryFontMetrics($draw, $text);
		$draw -> annotation(0, $metrics['ascender'], $text);

		/* Create image & draw annotation on it */
		$image -> newImage($metrics['textWidth'], $metrics['textHeight'], $background);
		$image -> setImageFormat('png');
		$image -> drawImage($draw);
		//$image -> writeImage("test.png");
		
		/* Save image */
		$escposImage = new EscposImage();
		$escposImage -> readImageFromImagick($image);
		$size = Escpos::IMG_DEFAULT;
		$this -> printer -> bitImage($escposImage, $size);
	}

	function writeTextRaw($text) {
		if($this -> printer == null) {
			throw new LogicException("Not attached to a printer.");
		}
		$this -> printer -> getPrintConnector() -> write($data);
	}
}
