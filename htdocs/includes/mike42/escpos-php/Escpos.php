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
 * This class generates ESC/POS printer control commands for compatible printers.
 * See README.md for a summary of compatible printers and supported commands, and
 * basic usage.
 * 
 * See example/demo.php for a detailed print-out demonstrating the range of commands
 * implemented in this project.
 * 
 * Note that some functions have not been implemented:
 * 		- Set paper sensors
 * 		- Select print colour
 * 
 * Please direct feature requests, bug reports and contributions to escpos-php
 * on Github:
 * 		- https://github.com/mike42/escpos-php
 */
require_once(dirname(__FILE__) . "/src/EscposImage.php");
require_once(dirname(__FILE__) . "/src/PrintBuffer.php");
require_once(dirname(__FILE__) . "/src/EscposPrintBuffer.php");
require_once(dirname(__FILE__) . "/src/PrintConnector.php");
require_once(dirname(__FILE__) . "/src/WindowsPrintConnector.php");
require_once(dirname(__FILE__) . "/src/FilePrintConnector.php");
require_once(dirname(__FILE__) . "/src/NetworkPrintConnector.php");
require_once(dirname(__FILE__) . "/src/AbstractCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/DefaultCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/SimpleCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/EposTepCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/StarCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/P822DCapabilityProfile.php");
require_once(dirname(__FILE__) . "/src/CodePage.php");
require_once(dirname(__FILE__) . "/src/ImagePrintBuffer.php");

class Escpos {
	/* ASCII codes */
	const NUL = "\x00";
	const LF = "\x0a";
	const ESC = "\x1b";
	const FS = "\x1c";
	const FF = "\x0c";
	const GS = "\x1d";
	const DLE = "\x10";
	const EOT = "\x04";

	/* Barcode types */
	const BARCODE_UPCA = 65;
	const BARCODE_UPCE = 66;
	const BARCODE_JAN13 = 67;
	const BARCODE_JAN8 = 68;
	const BARCODE_CODE39 = 69;
	const BARCODE_ITF = 70;
	const BARCODE_CODABAR = 71;
	const BARCODE_CODE93 = 72;
	const BARCODE_CODE128 = 73;
	
	/* Barcode HRI (human-readable interpretation) text position */
	const BARCODE_TEXT_NONE = 0;
	const BARCODE_TEXT_ABOVE = 1;
	const BARCODE_TEXT_BELOW = 2;
	
	/* Cut types */
	const CUT_FULL = 65;
	const CUT_PARTIAL = 66;
	
	/* Fonts */
	const FONT_A = 0;
	const FONT_B = 1;
	const FONT_C = 2;
	
	/* Image sizing options */
	const IMG_DEFAULT = 0;
	const IMG_DOUBLE_WIDTH = 1;
	const IMG_DOUBLE_HEIGHT = 2;
	
	/* Justifications */
	const JUSTIFY_LEFT = 0;
	const JUSTIFY_CENTER = 1;
	const JUSTIFY_RIGHT = 2;
	
	/* Print mode constants */
	const MODE_FONT_A = 0;
	const MODE_FONT_B = 1;
	const MODE_EMPHASIZED = 8;
	const MODE_DOUBLE_HEIGHT = 16;
	const MODE_DOUBLE_WIDTH = 32;
	const MODE_UNDERLINE = 128;
	
	/* QR code error correction levels */
	const QR_ECLEVEL_L = 0;
	const QR_ECLEVEL_M = 1;
	const QR_ECLEVEL_Q = 2;
	const QR_ECLEVEL_H = 3;
	
	/* QR code models */
	const QR_MODEL_1 = 1;
	const QR_MODEL_2 = 2;
	const QR_MICRO = 3;
	
	/* Printer statuses */
	const STATUS_PRINTER = 1;
	const STATUS_OFFLINE_CAUSE = 2;
	const STATUS_ERROR_CAUSE = 3;
	const STATUS_PAPER_ROLL = 4;
	const STATUS_INK_A = 7;
	const STATUS_INK_B = 6;
	const STATUS_PEELER = 8;
	
	/* Underline */
	const UNDERLINE_NONE = 0;
	const UNDERLINE_SINGLE = 1;
	const UNDERLINE_DOUBLE = 2;
	
	/**
	 * @var PrintBuffer The printer's output buffer.
	 */
	private $buffer;
	
	/**
	 * @var PrintConnector
	 * @CHANGE
	 */
	protected $connector;
	// private $connector;
	
	/**
	 * @var AbstractCapabilityProfile
	 */
	private $profile;
	
	/**
	 * @var int Current character code table
	 */
	private $characterTable;

	/**
	 * Construct a new print object
	 *
	 * @param PrintConnector $connector The PrintConnector to send data to. If not set, output is sent to standard output.
	 * @param AbstractCapabilityProfile $profile Supported features of this printer. If not set, the DefaultCapabilityProfile will be used, which is suitable for Epson printers.
	 * @throws InvalidArgumentException
	 */
	function __construct(PrintConnector $connector = null, AbstractCapabilityProfile $profile = null) {
		if(is_null($connector)) {
			if(php_sapi_name() == 'cli') {
				$connector = new FilePrintConnector("php://stdout");
			} else {
				throw new InvalidArgumentException("Argument passed to Escpos::__construct() must implement interface PrintConnector, null given.");
			}
		}
		/* Set connector */
		$this -> connector = $connector;
		
		/* Set capability profile */
		if($profile === null) {
			$profile = DefaultCapabilityProfile::getInstance();
		}
		$this -> profile = $profile;
		/* Set buffer */
		$buffer = new EscposPrintBuffer();
		$this -> buffer = null;
		$this -> setPrintBuffer($buffer);
		$this -> initialize();
	}
	
	/**
	 * Print a barcode.
	 *
	 * @param string $content The information to encode.
	 * @param int $type The barcode standard to output. If not specified, `Escpos::BARCODE_CODE39` will be used. Note that some barcode formats only support specific lengths or sets of characters.
	 * @throws InvalidArgumentException Where the length or characters used in $content is invalid for the requested barcode format.
	 */
	function barcode($content, $type = self::BARCODE_CODE39) {
		/* Validate input */
		self::validateInteger($type, 65, 73, __FUNCTION__, "Barcode type");
		$len = strlen($content);
		switch($type) {
			case self::BARCODE_UPCA:
				self::validateInteger($len, 11, 12, __FUNCTION__, "UPCA barcode content length");
				self::validateStringRegex($content, __FUNCTION__, "/^[0-9]{11,12}$/", "UPCA barcode content");
				break;
			case self::BARCODE_UPCE:
				self::validateIntegerMulti($len, array(array(6, 8), array(11, 12)), __FUNCTION__, "UPCE barcode content length");
				self::validateStringRegex($content, __FUNCTION__, "/^([0-9]{6,8}|[0-9]{11,12})$/",  "UPCE barcode content");
				break;
			case self::BARCODE_JAN13:
				self::validateInteger($len, 12, 13, __FUNCTION__, "JAN13 barcode content length");
				self::validateStringRegex($content, __FUNCTION__, "/^[0-9]{12,13}$/", "JAN13 barcode content");
				break;
			case self::BARCODE_JAN8:
				self::validateInteger($len, 7, 8, __FUNCTION__, "JAN8 barcode content length");
				self::validateStringRegex($content, __FUNCTION__, "/^[0-9]{7,8}$/", "JAN8 barcode content");
				break;
			case self::BARCODE_CODE39:
				self::validateInteger($len, 1, 255, __FUNCTION__, "CODE39 barcode content length"); // 255 is a limitation of the "function b" command, not the barcode format.
				self::validateStringRegex($content, __FUNCTION__, "/^([0-9A-Z \$\%\+\-\.\/]+|\*[0-9A-Z \$\%\+\-\.\/]+\*)$/", "CODE39 barcode content");
				break;
			case self::BARCODE_ITF:
				self::validateInteger($len, 2, 255, __FUNCTION__, "ITF barcode content length"); // 255 is a limitation of the "function b" command, not the barcode format.
				self::validateStringRegex($content, __FUNCTION__, "/^([0-9]{2})+$/", "ITF barcode content");
				break;
			case self::BARCODE_CODABAR:
				self::validateInteger($len, 1, 255, __FUNCTION__, "Codabar barcode content length"); // 255 is a limitation of the "function b" command, not the barcode format.
				self::validateStringRegex($content, __FUNCTION__, "/^[A-Da-d][0-9\$\+\-\.\/\:]+[A-Da-d]$/", "Codabar barcode content");
				break;
			case self::BARCODE_CODE93:
				self::validateInteger($len, 1, 255, __FUNCTION__, "Code93 barcode content length"); // 255 is a limitation of the "function b" command, not the barcode format.
				self::validateStringRegex($content, __FUNCTION__, "/^[\\x00-\\x7F]+$/", "Code93 barcode content");
				break;
			case self::BARCODE_CODE128:
				self::validateInteger($len, 1, 255, __FUNCTION__, "Code128 barcode content length"); // 255 is a limitation of the "function b" command, not the barcode format.
				// The CODE128 encoder is quite complex, so only a very basic header-check is applied here.
				self::validateStringRegex($content, __FUNCTION__, "/^\{[A-C][\\x00-\\x7F]+$/", "Code128 barcode content");
				break;
		}
 		if(!$this -> profile -> getSupportsBarcodeB()) {
			// A simpler barcode command which supports fewer codes
			self::validateInteger($type, 65, 71, __FUNCTION__);
			$this -> connector -> write(self::GS . "k" . chr($type - 65) . $content . self::NUL);
			return;
 		}
 		// More advanced function B, used in preference
 		$this -> connector -> write(self::GS . "k" . chr($type) . chr(strlen($content)) . $content);
	}
	
	/**
	 * Print an image, using the older "bit image" command. This creates padding on the right of the image,
	 * if its width is not divisible by 8.
	 * 
	 * Should only be used if your printer does not support the graphics() command.
	 * 
	 * @param EscposImage $img The image to print
	 * @param EscposImage $size Size modifier for the image.
	 */
	function bitImage(EscposImage $img, $size = self::IMG_DEFAULT) {
		self::validateInteger($size, 0, 3, __FUNCTION__);
		$header = self::dataHeader(array($img -> getWidthBytes(), $img -> getHeight()), true);
		$this -> connector -> write(self::GS . "v0" . chr($size) . $header);
		$this -> connector -> write($img -> toRasterFormat());
	}
	
	/**
	 * Close the underlying buffer. With some connectors, the
	 * job will not actually be sent to the printer until this is called.
	 */
	function close() {
		$this -> connector -> finalize();
	}
	
	/**
	 * Cut the paper.
	 *
	 * @param int $mode Cut mode, either Escpos::CUT_FULL or Escpos::CUT_PARTIAL. If not specified, `Escpos::CUT_FULL` will be used.
	 * @param int $lines Number of lines to feed
	 */
	function cut($mode = self::CUT_FULL, $lines = 3) {
		// TODO validation on cut() inputs
		$this -> connector -> write(self::GS . "V" . chr($mode) . chr($lines));
	}
	
	/**
	 * Print and feed line / Print and feed n lines.
	 * 
	 * @param int $lines Number of lines to feed
	 */
	function feed($lines = 1) {
		self::validateInteger($lines, 1, 255, __FUNCTION__);
		if($lines <= 1) {
			$this -> connector -> write(self::LF);
		} else {
			$this -> connector -> write(self::ESC . "d" . chr($lines));
		}
	}

	/**
	 * Some printers require a form feed to release the paper. On most printers, this 
	 * command is only useful in page mode, which is not implemented in this driver.
	 */
	function feedForm() {
		$this -> connector -> write(self::FF);	
	}

	/**
	 * Print and reverse feed n lines.
	 *
	 * @param int $lines number of lines to feed. If not specified, 1 line will be fed.
	 */
	function feedReverse($lines = 1) {
		self::validateInteger($lines, 1, 255, __FUNCTION__);
		$this -> connector -> write(self::ESC . "e" . chr($lines));
	}

	/**
	 * @return number
	 */
	function getCharacterTable() {
		return $this -> characterTable;
	}
	
	/**
	 * @return PrintBuffer
	 */
	function getPrintBuffer() {
		return $this -> buffer;
	}

	/**
	 * @return PrintConnector
	 */
	function getPrintConnector() {
		return $this -> connector;
	}

	/**
	 * @return AbstractCapabilityProfile
	 */
	function getPrinterCapabilityProfile() {
		return $this -> profile;
	}

	/**
	 * @param int $type The type of status to request
	 * @return stdClass Class containing requested status, or null if either no status was received, or your print connector is unable to read from the printer.
	 */
	function getPrinterStatus($type = self::STATUS_PRINTER) {
		self::validateIntegerMulti($type, array(array(1, 4), array(6, 8)), __FUNCTION__);
		// Determine which flags we are looking for
		$statusFlags = array(
				self::STATUS_PRINTER => array(
					4 => "pulseHigh", // connector pin 3, see pulse().
					8 => "offline",
					32 => "waitingForOnlineRecovery",
					64 => "feedButtonPressed"
				),
				self::STATUS_OFFLINE_CAUSE => array(
					4 => "coverOpen",
					8 => "paperManualFeed",
					32 => "paperEnd",
					64 => "errorOccurred"
				),
				self::STATUS_ERROR_CAUSE => array(
					4 => "recoverableError",
					8 => "autocutterError",
					32 => "unrecoverableError",
					64 => "autorecoverableError"
				),
				self::STATUS_PAPER_ROLL => array(
					4 => "paperNearEnd",
					32 => "paperNotPresent"
				),
				self::STATUS_INK_A => array(
					4 => "inkNearEnd",
					8 => "inkEnd",
					32 => "inkNotPresent",
					64 => "cleaning"
				),
				self::STATUS_INK_B => array(
					4 => "inkNearEnd",
					8 => "inkEnd",
					32 => "inkNotPresent"
				),
				self::STATUS_PEELER => array(
					4 => "labelWaitingForRemoval",
					32 => "labelPaperNotDetected"
				)
		);
		$flags = $statusFlags[$type];
		// Clear any previous statuses which haven't been read yet
		$f = $this -> connector -> read(1);
		// Make request
		$reqC = chr($type);
		switch($type) {
			// Special cases: These are two-character requests
			case self::STATUS_INK_A:
				$reqC = chr(7) . chr(1);
				break;
			case self::STATUS_INK_B:
				$reqC = chr(7) . chr(2);
				break;
			case self::STATUS_PEELER:
				$reqC = chr(8) . chr(3);
				break;
		}
		$this -> connector -> write(self::DLE . self::EOT . $reqC);
		// Wait for single-character response
		$f = $this -> connector -> read(1);
		$i = 0;
		while($f === false && $i < 50000) {
			usleep(100);
			$f = $this -> connector -> read(1);
			$i++;
		}
		if($f === false) {
			// Timeout
			return null;
		}
		$ret = new stdClass();
		foreach($flags as $num => $name) {
			$ret -> $name = (ord($f) & $num) != 0;
		}
		return $ret;
	}
	
	/**
	 * Print an image to the printer.
	 * 
	 * Size modifiers are:
	 * - IMG_DEFAULT (leave image at original size)
	 * - IMG_DOUBLE_WIDTH
	 * - IMG_DOUBLE_HEIGHT
	 * 
	 * See the example/ folder for detailed examples.
	 * 
	 * The function bitImage() takes the same parameters, and can be used if
	 * your printer doesn't support the newer graphics commands.
	 * 
	 * @param EscposImage $img The image to print.
	 * @param int $size Output size modifier for the image.
	 */
	function graphics(EscposImage $img, $size = self::IMG_DEFAULT) {
		self::validateInteger($size, 0, 3, __FUNCTION__);
		$imgHeader = self::dataHeader(array($img -> getWidth(), $img -> getHeight()), true);
		$tone = '0';
		$colors = '1';
		$xm = (($size & self::IMG_DOUBLE_WIDTH) == self::IMG_DOUBLE_WIDTH) ? chr(2) : chr(1);
		$ym = (($size & self::IMG_DOUBLE_HEIGHT) == self::IMG_DOUBLE_HEIGHT) ? chr(2) : chr(1);
		$header = $tone . $xm . $ym . $colors . $imgHeader;
		$this -> wrapperSendGraphicsData('0', 'p', $header . $img -> toRasterFormat());
		$this -> wrapperSendGraphicsData('0', '2');
	}
	
	/**
	 * Initialize printer. This resets formatting back to the defaults.
	 */
	function initialize() {
		$this -> connector -> write(self::ESC . "@");
		$this -> characterTable = 0;
	}
	
	/**
	 * Generate a pulse, for opening a cash drawer if one is connected.
	 * The default settings should open an Epson drawer.
	 *
	 * @param int $pin 0 or 1, for pin 2 or pin 5 kick-out connector respectively.
	 * @param int $on_ms pulse ON time, in milliseconds.
	 * @param int $off_ms pulse OFF time, in milliseconds.
	 */
	function pulse($pin = 0, $on_ms = 120, $off_ms = 240) {
		self::validateInteger($pin, 0, 1, __FUNCTION__);
		self::validateInteger($on_ms, 1, 511, __FUNCTION__);
		self::validateInteger($off_ms, 1, 511, __FUNCTION__);
		$this -> connector -> write(self::ESC . "p" . chr($pin + 48) . chr($on_ms / 2) . chr($off_ms / 2));
	}
	
	/**
	 * Print the given data as a QR code on the printer.
	 * 
	 * @param string $content The content of the code. Numeric data will be more efficiently compacted.
	 * @param int $ec Error-correction level to use. One of Escpos::QR_ECLEVEL_L (default), Escpos::QR_ECLEVEL_M, Escpos::QR_ECLEVEL_Q or Escpos::QR_ECLEVEL_H. Higher error correction results in a less compact code.
	 * @param int $size Pixel size to use. Must be 1-16 (default 3)
	 * @param int $model QR code model to use. Must be one of Escpos::QR_MODEL_1, Escpos::QR_MODEL_2 (default) or Escpos::QR_MICRO (not supported by all printers).
	 */
	function qrCode($content, $ec = self::QR_ECLEVEL_L, $size = 3, $model = self::QR_MODEL_2) {
		self::validateString($content, __FUNCTION__);
		self::validateInteger($ec, 0, 3, __FUNCTION__);
		self::validateInteger($size, 1, 16, __FUNCTION__);
		self::validateInteger($model, 1, 3, __FUNCTION__);
		if($content == "") {
			return;
		}
		if(!$this -> profile -> getSupportsQrCode()) {
			// TODO use software rendering via phpqrcode instead
			throw new Exception("QR codes are not supported on your printer.");
		}
		$cn = '1'; // Code type for QR code
		// Select model: 1, 2 or micro.
		$this -> wrapperSend2dCodeData(chr(65), $cn, chr(48 + $model) . chr(0));
		// Set dot size.
		$this -> wrapperSend2dCodeData(chr(67), $cn, chr($size));
		// Set error correction level: L, M, Q, or H
		$this -> wrapperSend2dCodeData(chr(69), $cn, chr(48 + $ec));
		// Send content & print
		$this -> wrapperSend2dCodeData(chr(80), $cn, $content, '0');
		$this -> wrapperSend2dCodeData(chr(81), $cn, '', '0');
	}

	/**
	 * Switch character table (code page) manually. Used in conjunction with textRaw() to
	 * print special characters which can't be encoded automatically.
	 * 
	 * @param int $table The table to select. Available code tables are model-specific.
	 */
	function selectCharacterTable($table = 0) {
		self::validateInteger($table, 0, 255, __FUNCTION__);
		$supported = $this -> profile -> getSupportedCodePages();
		if(!isset($supported[$table])) {
			throw new InvalidArgumentException("There is no code table $table allowed by this printer's capability profile.");
		}
		$this -> characterTable = $table;
		if($this -> profile -> getSupportsStarCommands()) {
			/* Not an ESC/POS command: STAR printers stash all the extra code pages under a different command. */
			$this -> connector -> write(self::ESC . self::GS . "t" . chr($table));
			return;
		}
		$this -> connector -> write(self::ESC . "t" . chr($table));
	}

	/**
	 * Select print mode(s).
	 * 
	 * Several MODE_* constants can be OR'd together passed to this function's `$mode` argument. The valid modes are:
	 *  - MODE_FONT_A
	 *  - MODE_FONT_B
	 *  - MODE_EMPHASIZED
	 *  - MODE_DOUBLE_HEIGHT
	 *  - MODE_DOUBLE_WIDTH
	 *  - MODE_UNDERLINE
	 * 
	 * @param int $mode The mode to use. Default is Escpos::MODE_FONT_A, with no special formatting. This has a similar effect to running initialize().
	 */
	function selectPrintMode($mode = self::MODE_FONT_A) {
		$allModes = self::MODE_FONT_B | self::MODE_EMPHASIZED | self::MODE_DOUBLE_HEIGHT | self::MODE_DOUBLE_WIDTH | self::MODE_UNDERLINE;
		if(!is_integer($mode) || $mode < 0 || ($mode & $allModes) != $mode) {
			throw new InvalidArgumentException("Invalid mode");
		}

		$this -> connector -> write(self::ESC . "!" . chr($mode));
	}
	
	/**
	 * Set barcode height.
	 *
	 * @param int $height Height in dots. If not specified, 8 will be used.
	 */
	function setBarcodeHeight($height = 8) {
		self::validateInteger($height, 1, 255, __FUNCTION__);
		$this -> connector -> write(self::GS . "h" . chr($height));
	}
	
	
	/**
	 * Set the position for the Human Readable Interpretation (HRI) of barcode characters.
	 * 
	 * @param position $position. Use Escpos::BARCODE_TEXT_NONE to hide the text (default), or any combination of Escpos::BARCODE_TEXT_TOP and Escpos::BARCODE_TEXT_BOTTOM flags to display the text.
	 */
	function setBarcodeTextPosition($position = self::BARCODE_TEXT_NONE) {
		self::validateInteger($position, 0, 3, __FUNCTION__, "Barcode text position");
		$this -> connector -> write(self::GS . "H" . chr($position));
	}
	
	/**
	 * Turn double-strike mode on/off.
	 *
	 * @param boolean $on true for double strike, false for no double strike
	 */
	function setDoubleStrike($on = true) {
		self::validateBoolean($on, __FUNCTION__);
		$this -> connector -> write(self::ESC . "G". ($on ? chr(1) : chr(0)));
	}
	
	/**
	 * Turn emphasized mode on/off.
	 *
	 *  @param boolean $on true for emphasis, false for no emphasis
	 */
	function setEmphasis($on = true) {
		self::validateBoolean($on, __FUNCTION__);
		$this -> connector -> write(self::ESC . "E". ($on ? chr(1) : chr(0)));
	}
	
	/**
	 * Select font. Most printers have two fonts (Fonts A and B), and some have a third (Font C).
	 *
	 * @param int $font The font to use. Must be either Escpos::FONT_A, Escpos::FONT_B, or Escpos::FONT_C.
	 */
	function setFont($font = self::FONT_A) {
		self::validateInteger($font, 0, 2, __FUNCTION__);
		$this -> connector -> write(self::ESC . "M" . chr($font));
	}
	
	/**
	 * Select justification.
	 *
	 * @param int $justification One of Escpos::JUSTIFY_LEFT, Escpos::JUSTIFY_CENTER, or Escpos::JUSTIFY_RIGHT.
	 */
	function setJustification($justification = self::JUSTIFY_LEFT) {
		self::validateInteger($justification, 0, 2, __FUNCTION__);
		$this -> connector -> write(self::ESC . "a" . chr($justification));
	}
	
	/**
	 * Attach a different print buffer to the printer. Buffers are responsible for handling text output to the printer.
	 * 
	 * @param PrintBuffer $buffer The buffer to use.
	 * @throws InvalidArgumentException Where the buffer is already attached to a different printer.
	 */
	function setPrintBuffer(PrintBuffer $buffer) {
		if($buffer === $this -> buffer) {
			return;
		}
		if($buffer -> getPrinter() != null) {
			throw new InvalidArgumentException("This buffer is already attached to a printer.");
		}
		if($this -> buffer !== null) {
			$this -> buffer -> setPrinter(null);
		}
		$this -> buffer = $buffer;
		$this -> buffer -> setPrinter($this);
	}
	
	/**
	 * Set black/white reverse mode on or off. In this mode, text is printed white on a black background.
	 * 
	 * @param boolean $on True to enable, false to disable.
	 */
	function setReverseColors($on = true) {
		self::validateBoolean($on, __FUNCTION__);
		$this -> connector -> write(self::GS . "B" . ($on ? chr(1) : chr(0)));
	}

	/**
	 * Set the size of text, as a multiple of the normal size.
	 * 
	 * @param int $widthMultiplier Multiple of the regular height to use (range 1 - 8)
	 * @param int $heightMultiplier Multiple of the regular height to use (range 1 - 8)
	 */
	function setTextSize($widthMultiplier, $heightMultiplier) {
		self::validateInteger($widthMultiplier, 1, 8, __FUNCTION__);
		self::validateInteger($heightMultiplier, 1, 8, __FUNCTION__);
		$c = pow(2,4) * ($widthMultiplier - 1) + ($heightMultiplier - 1);		
		$this -> connector -> write(self::GS . "!" . chr($c));
	}

	/**
	 * Set underline for printed text.
	 * 
	 * Argument can be true/false, or one of UNDERLINE_NONE,
	 * UNDERLINE_SINGLE or UNDERLINE_DOUBLE.
	 * 
	 * @param int $underline Either true/false, or one of Escpos::UNDERLINE_NONE, Escpos::UNDERLINE_SINGLE or Escpos::UNDERLINE_DOUBLE. Defaults to Escpos::UNDERLINE_SINGLE.
	 */
	function setUnderline($underline = self::UNDERLINE_SINGLE) {
		/* Map true/false to underline constants */
		if($underline === true) {
			$underline = self::UNDERLINE_SINGLE;
		} else if($underline === false) {
			$underline = self::UNDERLINE_NONE;
		}
		/* Set the underline */
		self::validateInteger($underline, 0, 2, __FUNCTION__);
		$this -> connector -> write(self::ESC . "-". chr($underline));
	}
	
	/**
	 * Add text to the buffer.
	 *
	 * Text should either be followed by a line-break, or feed() should be called
	 * after this to clear the print buffer.
	 *
	 * @param string $str Text to print
	 */
	function text($str = "") {
		self::validateString($str, __FUNCTION__);
		$this -> buffer -> writeText((string)$str);
	}
	
	/**
	 * Add text to the buffer without attempting to interpret chararacter codes.
	 *
	 * Text should either be followed by a line-break, or feed() should be called
	 * after this to clear the print buffer.
	 *
	 * @param string $str Text to print
	 */
	function textRaw($str = "") {
		self::validateString($str, __FUNCTION__);
		$this -> buffer -> writeTextRaw((string)$str);
	}
	
	/**
	 * Wrapper for GS ( k, to calculate and send correct data length.
	 * 
	 * @param string $fn Function to use
	 * @param string $cn Output code type. Affects available data
	 * @param string $data Data to send.
	 * @param string $m Modifier/variant for function. Often '0' where used.
	 * @throws InvalidArgumentException Where the input lengths are bad.
	 */
	private function wrapperSend2dCodeData($fn, $cn, $data = '', $m = '') {
		if(strlen($m) > 1 || strlen($cn) != 1 || strlen($fn) != 1) {
			throw new InvalidArgumentException("wrapperSend2dCodeData: cn and fn must be one character each.");
		}
		$header = $this -> intLowHigh(strlen($data) + strlen($m) + 2, 2);
		$this -> connector -> write(self::GS . "(k" . $header . $cn . $fn . $m . $data);
	}
	
	/**
	 * Wrapper for GS ( L, to calculate and send correct data length.
	 *
	 * @param string $m Modifier/variant for function. Usually '0'.
	 * @param string $fn Function number to use, as character.
	 * @param string $data Data to send.
	 * @throws InvalidArgumentException Where the input lengths are bad.
	 */
	private function wrapperSendGraphicsData($m, $fn, $data = '') {
		if(strlen($m) != 1 || strlen($fn) != 1) {
			throw new InvalidArgumentException("wrapperSendGraphicsData: m and fn must be one character each.");
		}
		$header = $this -> intLowHigh(strlen($data) + 2, 2);
		$this -> connector -> write(self::GS . "(L" . $header . $m . $fn . $data);
	}
	
	/**
	 * Convert widths and heights to characters. Used before sending graphics to set the size.
	 * 
	 * @param array $inputs
	 * @param boolean $long True to use 4 bytes, false to use 2
	 * @return string
	 */
	private static function dataHeader(array $inputs, $long = true) {
		$outp = array();
		foreach($inputs as $input) {
			if($long) {
				$outp[] = Escpos::intLowHigh($input, 2);
			} else {
				self::validateInteger($input, 0 , 255, __FUNCTION__);
				$outp[] = chr($input);
			}
		}
		return implode("", $outp);
	}
	
	/**
	 * Generate two characters for a number: In lower and higher parts, or more parts as needed.
	 * @param int $int Input number
	 * @param int $length The number of bytes to output (1 - 4).
	 */
	private static function intLowHigh($input, $length) {
		$maxInput = (256 << ($length * 8) - 1);
		self::validateInteger($length, 1, 4, __FUNCTION__);
		self::validateInteger($input, 0, $maxInput, __FUNCTION__);
		$outp = "";
		for($i = 0; $i < $length; $i++) {
			$outp .= chr($input % 256);
			$input = (int)($input / 256);
		}
		return $outp;
	}
	
	/**
	 * Throw an exception if the argument given is not a boolean
	 * 
	 * @param boolean $test the input to test
	 * @param string $source the name of the function calling this
	 */
	protected static function validateBoolean($test, $source) {
		if(!($test === true || $test === false)) {
			throw new InvalidArgumentException("Argument to $source must be a boolean");
		}
	}
	
	/**
	 * Throw an exception if the argument given is not an integer within the specified range
	 * 
	 * @param int $test the input to test
	 * @param int $min the minimum allowable value (inclusive)
	 * @param int $max the maximum allowable value (inclusive)
	 * @param string $source the name of the function calling this
	 * @param string $argument the name of the invalid parameter
	 */
	protected static function validateInteger($test, $min, $max, $source, $argument = "Argument") {
		self::validateIntegerMulti($test, array(array($min, $max)), $source, $argument);
	}
	
	/**
	 * Throw an exception if the argument given is not an integer within one of the specified ranges
	 *
	 * @param int $test the input to test
	 * @param arrray $ranges array of two-item min/max ranges.
	 * @param string $source the name of the function calling this
	 * @param string $source the name of the function calling this
	 * @param string $argument the name of the invalid parameter
	 */
	protected static function validateIntegerMulti($test, array $ranges, $source, $argument = "Argument") {
		if(!is_integer($test)) {
			throw new InvalidArgumentException("$argument given to $source must be a number, but '$test' was given.");
		}
		$match = false;
		foreach($ranges as $range) {
			$match |= $test >= $range[0] && $test <= $range[1];
		}
		if(!$match) {
			// Put together a good error "range 1-2 or 4-6"
			$rangeStr = "range ";
			for($i = 0; $i < count($ranges); $i++) {
				$rangeStr .= $ranges[$i][0] . "-" . $ranges[$i][1];
				if($i == count($ranges) - 1) {
					continue;
				} else if($i == count($ranges) - 2) {
					$rangeStr .= " or ";
				} else {
					$rangeStr .= ", ";
				}
			}
			throw new InvalidArgumentException("$argument given to $source must be in $rangeStr, but $test was given.");
		}
	}
	
	/**
	 * Throw an exception if the argument given can't be cast to a string
	 *
	 * @param string $test the input to test
	 * @param string $source the name of the function calling this
	 * @param string $argument the name of the invalid parameter
	 */
	protected static function validateString($test, $source, $argument = "Argument") {
		if (is_object($test) && !method_exists($test, '__toString')) {
			throw new InvalidArgumentException("$argument to $source must be a string");
		}
	}
	
	protected static function validateStringRegex($test, $source, $regex, $argument = "Argument") {
		if(preg_match($regex, $test) === 0) {
			throw new InvalidArgumentException("$argument given to $source is invalid. It should match regex '$regex', but '$test' was given.");
		}
	}
}
