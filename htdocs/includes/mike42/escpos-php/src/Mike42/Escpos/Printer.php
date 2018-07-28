<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos;

use Exception;
use InvalidArgumentException;
use Mike42\Escpos\PrintBuffers\PrintBuffer;
use Mike42\Escpos\PrintBuffers\EscposPrintBuffer;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\CapabilityProfiles\AbstractCapabilityProfile;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;

/**
 * Main class for ESC/POS code generation
 */
class Printer
{
    /**
     * ASCII null control character
     */
    const NUL = "\x00";

    /**
     * ASCII linefeed control character
     */
    const LF = "\x0a";

    /**
     * ASCII escape control character
     */
    const ESC = "\x1b";

    /**
     * ASCII form separator control character
     */
    const FS = "\x1c";

    /**
     * ASCII form feed control character
     */
    const FF = "\x0c";

    /**
     * ASCII group separator control character
     */
    const GS = "\x1d";

    /**
     * ASCII data link escape control character
     */
    const DLE = "\x10";

    /**
     * ASCII end of transmission control character
     */
    const EOT = "\x04";

    /**
     * Indicates UPC-A barcode when used with Printer::barcode
     */
    const BARCODE_UPCA = 65;

    /**
     * Indicates UPC-E barcode when used with Printer::barcode
     */
    const BARCODE_UPCE = 66;

    /**
     * Indicates JAN13 barcode when used with Printer::barcode
     */
    const BARCODE_JAN13 = 67;

    /**
     * Indicates JAN8 barcode when used with Printer::barcode
     */
    const BARCODE_JAN8 = 68;

    /**
     * Indicates CODE39 barcode when used with Printer::barcode
     */
    const BARCODE_CODE39 = 69;

    /**
     * Indicates ITF barcode when used with Printer::barcode
     */
    const BARCODE_ITF = 70;

    /**
     * Indicates CODABAR barcode when used with Printer::barcode
     */
    const BARCODE_CODABAR = 71;

    /**
     * Indicates CODE93 barcode when used with Printer::barcode
     */
    const BARCODE_CODE93 = 72;

    /**
     * Indicates CODE128 barcode when used with Printer::barcode
     */
    const BARCODE_CODE128 = 73;

    /**
     * Indicates that HRI (human-readable interpretation) text should not be
     * printed, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_NONE = 0;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * above a barcode, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_ABOVE = 1;

    /**
     * Indicates that HRI (human-readable interpretation) text should be printed
     * below a barcode, when used with Printer::setBarcodeTextPosition
     */
    const BARCODE_TEXT_BELOW = 2;

    /**
     * Use the first color (usually black), when used with Printer::setColor
     */
    const COLOR_1 = 0;

    /**
     * Use the second color (usually red or blue), when used with Printer::setColor
     */
    const COLOR_2 = 1;

    /**
     * Make a full cut, when used with Printer::cut
     */
    const CUT_FULL = 65;

    /**
     * Make a partial cut, when used with Printer::cut
     */
    const CUT_PARTIAL = 66;

    /**
     * Use Font A, when used with Printer::setFont
     */
    const FONT_A = 0;

    /**
     * Use Font B, when used with Printer::setFont
     */
    const FONT_B = 1;

    /**
     * Use Font C, when used with Printer::setFont
     */
    const FONT_C = 2;

    /**
     * Use default (high density) image size, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DEFAULT = 0;

    /**
     * Use lower horizontal density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DOUBLE_WIDTH = 1;

    /**
     * Use lower vertical density for image printing, when used with Printer::graphics,
     * Printer::bitImage or Printer::bitImageColumnFormat
     */
    const IMG_DOUBLE_HEIGHT = 2;

    /**
     * Align text to the left, when used with Printer::setJustification
     */
    const JUSTIFY_LEFT = 0;

    /**
     * Center text, when used with Printer::setJustification
     */
    const JUSTIFY_CENTER = 1;

    /**
     * Align text to the right, when used with Printer::setJustification
     */
    const JUSTIFY_RIGHT = 2;

    /**
     * Use Font A, when used with Printer::selectPrintMode
     */
    const MODE_FONT_A = 0;

    /**
     * Use Font B, when used with Printer::selectPrintMode
     */
    const MODE_FONT_B = 1;

    /**
     * Use text emphasis, when used with Printer::selectPrintMode
     */
    const MODE_EMPHASIZED = 8;

    /**
     * Use double height text, when used with Printer::selectPrintMode
     */
    const MODE_DOUBLE_HEIGHT = 16;

    /**
     * Use double width text, when used with Printer::selectPrintMode
     */
    const MODE_DOUBLE_WIDTH = 32;

    /**
     * Underline text, when used with Printer::selectPrintMode
     */
    const MODE_UNDERLINE = 128;

    /**
     * Indicates error correction level L when used with Printer::qrCode
     */
    const QR_ECLEVEL_L = 0;

    /**
     * Indicates error correction level M when used with Printer::qrCode
     */
    const QR_ECLEVEL_M = 1;

    /**
     * Indicates error correction level Q when used with Printer::qrCode
     */
    const QR_ECLEVEL_Q = 2;

    /**
     * Indicates error correction level H when used with Printer::qrCode
     */
    const QR_ECLEVEL_H = 3;

    /**
     * Indicates QR model 1 when used with Printer::qrCode
     */
    const QR_MODEL_1 = 1;

    /**
     * Indicates QR model 2 when used with Printer::qrCode
     */
    const QR_MODEL_2 = 2;

    /**
     * Indicates micro QR code when used with Printer::qrCode
     */
    const QR_MICRO = 3;

    /**
     * Indicates a request for printer status when used with
     * Printer::getPrinterStatus (experimental)
     */
    const STATUS_PRINTER = 1;

    /**
     * Indicates a request for printer offline cause when used with
     * Printer::getPrinterStatus (experimental)
     */
    const STATUS_OFFLINE_CAUSE = 2;

    /**
     * Indicates a request for error cause when used with Printer::getPrinterStatus
     * (experimental)
     */
    const STATUS_ERROR_CAUSE = 3;

    /**
     * Indicates a request for error cause when used with Printer::getPrinterStatus
     * (experimental)
     */
    const STATUS_PAPER_ROLL = 4;

    /**
     * Indicates a request for ink A status when used with Printer::getPrinterStatus
     * (experimental)
     */
    const STATUS_INK_A = 7;

    /**
     * Indicates a request for ink B status when used with Printer::getPrinterStatus
     * (experimental)
     */
    const STATUS_INK_B = 6;

    /**
     * Indicates a request for peeler status when used with Printer::getPrinterStatus
     * (experimental)
     */
    const STATUS_PEELER = 8;

    /**
     * Indicates no underline when used with Printer::setUnderline
     */
    const UNDERLINE_NONE = 0;

    /**
     * Indicates single underline when used with Printer::setUnderline
     */
    const UNDERLINE_SINGLE = 1;

    /**
     * Indicates double underline when used with Printer::setUnderline
     */
    const UNDERLINE_DOUBLE = 2;

    /**
     * @var PrintBuffer $buffer
     *  The printer's output buffer.
     */
    private $buffer;

    /**
     * @var PrintConnector $connector
     *  Connector showing how to print to this printer
     */
    private $connector;

    /**
     * @var AbstractCapabilityProfile $profile
     *  Profile showing supported features for this printer
     */
    private $profile;

    /**
     * @var int $characterTable
     *  Current character code table
     */
    private $characterTable;

    /**
     * Construct a new print object
     *
     * @param PrintConnector $connector The PrintConnector to send data to. If not set, output is sent to standard output.
     * @param AbstractCapabilityProfile $profile Supported features of this printer. If not set, the DefaultCapabilityProfile will be used, which is suitable for Epson printers.
     * @throws InvalidArgumentException
     */
    public function __construct(PrintConnector $connector, AbstractCapabilityProfile $profile = null)
    {
        /* Set connector */
        $this -> connector = $connector;
        
        /* Set capability profile */
        if ($profile === null) {
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
     * @param int $type The barcode standard to output. Supported values are
     * `Printer::BARCODE_UPCA`, `Printer::BARCODE_UPCE`, `Printer::BARCODE_JAN13`,
     * `Printer::BARCODE_JAN8`, `Printer::BARCODE_CODE39`, `Printer::BARCODE_ITF`,
     * `Printer::BARCODE_CODABAR`, `Printer::BARCODE_CODE93`, and `Printer::BARCODE_CODE128`.
     * If not specified, `Printer::BARCODE_CODE39` will be used. Note that some
     * barcode formats only support specific lengths or sets of characters, and that
     * available barcode types vary between printers.
     * @throws InvalidArgumentException Where the length or characters used in $content is invalid for the requested barcode format.
     */
    public function barcode($content, $type = Printer::BARCODE_CODE39)
    {
        /* Validate input */
        self::validateInteger($type, 65, 73, __FUNCTION__, "Barcode type");
        $len = strlen($content);
        switch ($type) {
            case self::BARCODE_UPCA:
                self::validateInteger($len, 11, 12, __FUNCTION__, "UPCA barcode content length");
                self::validateStringRegex($content, __FUNCTION__, "/^[0-9]{11,12}$/", "UPCA barcode content");
                break;
            case self::BARCODE_UPCE:
                self::validateIntegerMulti($len, array(array(6, 8), array(11, 12)), __FUNCTION__, "UPCE barcode content length");
                self::validateStringRegex($content, __FUNCTION__, "/^([0-9]{6,8}|[0-9]{11,12})$/", "UPCE barcode content");
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
        if (!$this -> profile -> getSupportsBarcodeB()) {
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
     * See also bitImageColumnFormat().
     *
     * @param EscposImage $img The image to print
     * @param int $size Size modifier for the image. Must be either `Printer::IMG_DEFAULT`
     *  (default), or any combination of the `Printer::IMG_DOUBLE_HEIGHT` and
     *  `Printer::IMG_DOUBLE_WIDTH` flags.
     */
    public function bitImage(EscposImage $img, $size = Printer::IMG_DEFAULT)
    {
        self::validateInteger($size, 0, 3, __FUNCTION__);
        $rasterData = $img -> toRasterFormat();
        $header = Printer::dataHeader(array($img -> getWidthBytes(), $img -> getHeight()), true);
        $this -> connector -> write(self::GS . "v0" . chr($size) . $header);
        $this -> connector -> write($rasterData);
    }

    /**
     * Print an image, using the older "bit image" command in column format.
     *
     * Should only be used if your printer does not support the graphics() or
     * bitImage() commands.
     *
     * @param EscposImage $img The image to print
     * @param int $size Size modifier for the image. Must be either `Printer::IMG_DEFAULT`
     *  (default), or any combination of the `Printer::IMG_DOUBLE_HEIGHT` and
     *  `Printer::IMG_DOUBLE_WIDTH` flags.
     */
    public function bitImageColumnFormat(EscposImage $img, $size = Printer::IMG_DEFAULT)
    {
        $highDensityVertical = ! (($size & self::IMG_DOUBLE_HEIGHT) == Printer::IMG_DOUBLE_HEIGHT);
        $highDensityHorizontal = ! (($size & self::IMG_DOUBLE_WIDTH) == Printer::IMG_DOUBLE_WIDTH);
        // Experimental column format printing
        // This feature is not yet complete and may produce unpredictable results.
        $this -> connector -> write(self::ESC . "3" . chr(16)); // 16-dot line spacing. This is the correct value on both TM-T20 and TM-U220
        // Header and density code (0, 1, 32, 33) re-used for every line
        $densityCode = ($highDensityHorizontal ? 1 : 0) + ($highDensityVertical ? 32 : 0);
        $colFormatData = $img -> toColumnFormat($highDensityVertical);
        $header = Printer::dataHeader(array($img -> getWidth()), true);
        foreach ($colFormatData as $line) {
            // Print each line, double density etc for printing are set here also
            $this -> connector -> write(self::ESC . "*" . chr($densityCode) . $header . $line);
            $this -> feed();
            // sleep(0.1); // Reduces the amount of trouble that a TM-U220 has keeping up with large images
        }
        $this -> connector -> write(self::ESC . "2"); // Revert to default line spacing
    }

    /**
     * Close the underlying buffer. With some connectors, the
     * job will not actually be sent to the printer until this is called.
     */
    public function close()
    {
        $this -> connector -> finalize();
    }
    
    /**
     * Cut the paper.
     *
     * @param int $mode Cut mode, either Printer::CUT_FULL or Printer::CUT_PARTIAL. If not specified, `Printer::CUT_FULL` will be used.
     * @param int $lines Number of lines to feed
     */
    public function cut($mode = Printer::CUT_FULL, $lines = 3)
    {
        // TODO validation on cut() inputs
        $this -> connector -> write(self::GS . "V" . chr($mode) . chr($lines));
    }
    
    /**
     * Print and feed line / Print and feed n lines.
     *
     * @param int $lines Number of lines to feed
     */
    public function feed($lines = 1)
    {
        self::validateInteger($lines, 1, 255, __FUNCTION__);
        if ($lines <= 1) {
            $this -> connector -> write(self::LF);
        } else {
            $this -> connector -> write(self::ESC . "d" . chr($lines));
        }
    }

    /**
     * Some printers require a form feed to release the paper. On most printers, this
     * command is only useful in page mode, which is not implemented in this driver.
     */
    public function feedForm()
    {
        $this -> connector -> write(self::FF);
    }

    /**
     * Print and reverse feed n lines.
     *
     * @param int $lines number of lines to feed. If not specified, 1 line will be fed.
     */
    public function feedReverse($lines = 1)
    {
        self::validateInteger($lines, 1, 255, __FUNCTION__);
        $this -> connector -> write(self::ESC . "e" . chr($lines));
    }

    /**
     * @return number
     */
    public function getCharacterTable()
    {
        return $this -> characterTable;
    }
    
    /**
     * @return PrintBuffer
     */
    public function getPrintBuffer()
    {
        return $this -> buffer;
    }

    /**
     * @return PrintConnector
     */
    public function getPrintConnector()
    {
        return $this -> connector;
    }

    /**
     * @return AbstractCapabilityProfile
     */
    public function getPrinterCapabilityProfile()
    {
        return $this -> profile;
    }

    /**
     * @param int $type The type of status to request. May be any of the Printer::STATUS_* constants
     * @return stdClass Class containing requested status, or null if either no status was received, or your print connector is unable to read from the printer.
     */
    public function getPrinterStatus($type = Printer::STATUS_PRINTER)
    {
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
        switch ($type) {
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
        while (($f === false || strlen($f) == 0) && $i < 50000) {
            usleep(100);
            $f = $this -> connector -> read(1);
            $i++;
        }
        if ($f === false || strlen($f) == 0) {
            // Timeout
            return null;
        }
        $ret = new \stdClass();
        foreach ($flags as $num => $name) {
            $ret -> $name = (ord($f) & $num) != 0;
        }
        return $ret;
    }
    
    /**
     * Print an image to the printer.
     *
     * Size modifiers are:
     * - Printer::IMG_DEFAULT (leave image at original size)
     * - Printer::IMG_DOUBLE_WIDTH
     * - Printer::IMG_DOUBLE_HEIGHT
     *
     * See the example/ folder for detailed examples.
     *
     * The functions bitImage() and bitImageColumnFormat() take the same
     * parameters, and can be used if your printer doesn't support the newer
     * graphics commands.
     *
     * @param EscposImage $img The image to print.
     * @param int $size Size modifier for the image. Must be either `Printer::IMG_DEFAULT`
     *  (default), or any combination of the `Printer::IMG_DOUBLE_HEIGHT` and
     *  `Printer::IMG_DOUBLE_WIDTH` flags.
     */
    public function graphics(EscposImage $img, $size = Printer::IMG_DEFAULT)
    {
        self::validateInteger($size, 0, 3, __FUNCTION__);
        $rasterData = $img -> toRasterFormat();
        $imgHeader = Printer::dataHeader(array($img -> getWidth(), $img -> getHeight()), true);
        $tone = '0';
        $colors = '1';
        $xm = (($size & self::IMG_DOUBLE_WIDTH) == Printer::IMG_DOUBLE_WIDTH) ? chr(2) : chr(1);
        $ym = (($size & self::IMG_DOUBLE_HEIGHT) == Printer::IMG_DOUBLE_HEIGHT) ? chr(2) : chr(1);
        $header = $tone . $xm . $ym . $colors . $imgHeader;
        $this -> wrapperSendGraphicsData('0', 'p', $header . $rasterData);
        $this -> wrapperSendGraphicsData('0', '2');
    }
    
    /**
     * Initialize printer. This resets formatting back to the defaults.
     */
    public function initialize()
    {
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
    public function pulse($pin = 0, $on_ms = 120, $off_ms = 240)
    {
        self::validateInteger($pin, 0, 1, __FUNCTION__);
        self::validateInteger($on_ms, 1, 511, __FUNCTION__);
        self::validateInteger($off_ms, 1, 511, __FUNCTION__);
        $this -> connector -> write(self::ESC . "p" . chr($pin + 48) . chr($on_ms / 2) . chr($off_ms / 2));
    }
    
    /**
     * Print the given data as a QR code on the printer.
     *
     * @param string $content The content of the code. Numeric data will be more efficiently compacted.
     * @param int $ec Error-correction level to use. One of Printer::QR_ECLEVEL_L (default), Printer::QR_ECLEVEL_M, Printer::QR_ECLEVEL_Q or Printer::QR_ECLEVEL_H. Higher error correction results in a less compact code.
     * @param int $size Pixel size to use. Must be 1-16 (default 3)
     * @param int $model QR code model to use. Must be one of Printer::QR_MODEL_1, Printer::QR_MODEL_2 (default) or Printer::QR_MICRO (not supported by all printers).
     */
    public function qrCode($content, $ec = Printer::QR_ECLEVEL_L, $size = 3, $model = Printer::QR_MODEL_2)
    {
        self::validateString($content, __FUNCTION__);
        self::validateInteger($ec, 0, 3, __FUNCTION__);
        self::validateInteger($size, 1, 16, __FUNCTION__);
        self::validateInteger($model, 1, 3, __FUNCTION__);
        if ($content == "") {
            return;
        }
        if (!$this -> profile -> getSupportsQrCode()) {
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
    public function selectCharacterTable($table = 0)
    {
        self::validateInteger($table, 0, 255, __FUNCTION__);
        $supported = $this -> profile -> getSupportedCodePages();
        if (!isset($supported[$table])) {
            throw new InvalidArgumentException("There is no code table $table allowed by this printer's capability profile.");
        }
        $this -> characterTable = $table;
        if ($this -> profile -> getSupportsStarCommands()) {
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
     *  - Printer::MODE_FONT_A
     *  - Printer::MODE_FONT_B
     *  - Printer::MODE_EMPHASIZED
     *  - Printer::MODE_DOUBLE_HEIGHT
     *  - Printer::MODE_DOUBLE_WIDTH
     *  - Printer::MODE_UNDERLINE
     *
     * @param int $mode The mode to use. Default is Printer::MODE_FONT_A, with no special formatting. This has a similar effect to running initialize().
     */
    public function selectPrintMode($mode = Printer::MODE_FONT_A)
    {
        $allModes = Printer::MODE_FONT_B | self::MODE_EMPHASIZED | self::MODE_DOUBLE_HEIGHT | self::MODE_DOUBLE_WIDTH | self::MODE_UNDERLINE;
        if (!is_integer($mode) || $mode < 0 || ($mode & $allModes) != $mode) {
            throw new InvalidArgumentException("Invalid mode");
        }

        $this -> connector -> write(self::ESC . "!" . chr($mode));
    }
    
    /**
     * Set barcode height.
     *
     * @param int $height Height in dots. If not specified, 8 will be used.
     */
    public function setBarcodeHeight($height = 8)
    {
        self::validateInteger($height, 1, 255, __FUNCTION__);
        $this -> connector -> write(self::GS . "h" . chr($height));
    }

    /**
     * Set barcode bar width.
     *
     * @param int $width Bar width in dots. If not specified, 3 will be used.
     *  Values above 6 appear to have no effect.
     */
    public function setBarcodeWidth($width = 3)
    {
        self::validateInteger($width, 1, 255, __FUNCTION__);
        $this -> connector -> write(self::GS . "w" . chr($width));
    }
    
    /**
     * Set the position for the Human Readable Interpretation (HRI) of barcode characters.
     *
     * @param int $position. Use Printer::BARCODE_TEXT_NONE to hide the text (default),
     *  or any combination of Printer::BARCODE_TEXT_ABOVE and Printer::BARCODE_TEXT_BELOW
     *  flags to display the text.
     */
    public function setBarcodeTextPosition($position = Printer::BARCODE_TEXT_NONE)
    {
        self::validateInteger($position, 0, 3, __FUNCTION__, "Barcode text position");
        $this -> connector -> write(self::GS . "H" . chr($position));
    }
    
    /**
     * Turn double-strike mode on/off.
     *
     * @param boolean $on true for double strike, false for no double strike
     */
    public function setDoubleStrike($on = true)
    {
        self::validateBoolean($on, __FUNCTION__);
        $this -> connector -> write(self::ESC . "G". ($on ? chr(1) : chr(0)));
    }

    /**
     * Select print color on printers that support multiple colors.
     *
     * @param int $color Color to use. Must be either Printer::COLOR_1 (default), or Printer::COLOR_2.
     */
    public function setColor($color = Printer::COLOR_1)
    {
        self::validateInteger($color, 0, 1, __FUNCTION__, "Color");
        $this -> connector -> write(self::ESC . "r" . chr($color));
    }

    /**
     * Turn emphasized mode on/off.
     *
     *  @param boolean $on true for emphasis, false for no emphasis
     */
    public function setEmphasis($on = true)
    {
        self::validateBoolean($on, __FUNCTION__);
        $this -> connector -> write(self::ESC . "E". ($on ? chr(1) : chr(0)));
    }
    
    /**
     * Select font. Most printers have two fonts (Fonts A and B), and some have a third (Font C).
     *
     * @param int $font The font to use. Must be either Printer::FONT_A, Printer::FONT_B, or Printer::FONT_C.
     */
    public function setFont($font = Printer::FONT_A)
    {
        self::validateInteger($font, 0, 2, __FUNCTION__);
        $this -> connector -> write(self::ESC . "M" . chr($font));
    }
    
    /**
     * Select justification.
     *
     * @param int $justification One of Printer::JUSTIFY_LEFT, Printer::JUSTIFY_CENTER, or Printer::JUSTIFY_RIGHT.
     */
    public function setJustification($justification = Printer::JUSTIFY_LEFT)
    {
        self::validateInteger($justification, 0, 2, __FUNCTION__);
        $this -> connector -> write(self::ESC . "a" . chr($justification));
    }
    
    /**
     * Attach a different print buffer to the printer. Buffers are responsible for handling text output to the printer.
     *
     * @param PrintBuffer $buffer The buffer to use.
     * @throws InvalidArgumentException Where the buffer is already attached to a different printer.
     */
    public function setPrintBuffer(PrintBuffer $buffer)
    {
        if ($buffer === $this -> buffer) {
            return;
        }
        if ($buffer -> getPrinter() != null) {
            throw new InvalidArgumentException("This buffer is already attached to a printer.");
        }
        if ($this -> buffer !== null) {
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
    public function setReverseColors($on = true)
    {
        self::validateBoolean($on, __FUNCTION__);
        $this -> connector -> write(self::GS . "B" . ($on ? chr(1) : chr(0)));
    }

    /**
     * Set the size of text, as a multiple of the normal size.
     *
     * @param int $widthMultiplier Multiple of the regular height to use (range 1 - 8)
     * @param int $heightMultiplier Multiple of the regular height to use (range 1 - 8)
     */
    public function setTextSize($widthMultiplier, $heightMultiplier)
    {
        self::validateInteger($widthMultiplier, 1, 8, __FUNCTION__);
        self::validateInteger($heightMultiplier, 1, 8, __FUNCTION__);
        $c = pow(2, 4) * ($widthMultiplier - 1) + ($heightMultiplier - 1);
        $this -> connector -> write(self::GS . "!" . chr($c));
    }

    /**
     * Set underline for printed text.
     *
     * Argument can be true/false, or one of UNDERLINE_NONE,
     * UNDERLINE_SINGLE or UNDERLINE_DOUBLE.
     *
     * @param int $underline Either true/false, or one of Printer::UNDERLINE_NONE, Printer::UNDERLINE_SINGLE or Printer::UNDERLINE_DOUBLE. Defaults to Printer::UNDERLINE_SINGLE.
     */
    public function setUnderline($underline = Printer::UNDERLINE_SINGLE)
    {
        /* Map true/false to underline constants */
        if ($underline === true) {
            $underline = Printer::UNDERLINE_SINGLE;
        } elseif ($underline === false) {
            $underline = Printer::UNDERLINE_NONE;
        }
        /* Set the underline */
        self::validateInteger($underline, 0, 2, __FUNCTION__);
        $this -> connector -> write(self::ESC . "-" . chr($underline));
    }
    
    /**
     * Add text to the buffer.
     *
     * Text should either be followed by a line-break, or feed() should be called
     * after this to clear the print buffer.
     *
     * @param string $str Text to print
     */
    public function text($str = "")
    {
        self::validateString($str, __FUNCTION__);
        $this -> buffer -> writeText((string)$str);
    }

    /**
     * Add Chinese text to the buffer. This is a specific workaround for the common Zijang printer- The printer will be switched to a two-byte mode and sent GBK-encoded text.
     *
     * Support for this will be merged into a print buffer.
     *
     * @param string $str Text to print, as UTF-8
     */
    public function textChinese($str = "")
    {
        self::validateString($str, __FUNCTION__);
        $this -> connector -> write(self::FS . "&");
        $str = iconv("UTF-8", "GBK//IGNORE", $str);
        $this -> buffer -> writeTextRaw((string)$str);
        $this -> connector -> write(self::FS . ".");
    }

    /**
     * Add text to the buffer without attempting to interpret chararacter codes.
     *
     * Text should either be followed by a line-break, or feed() should be called
     * after this to clear the print buffer.
     *
     * @param string $str Text to print
     */
    public function textRaw($str = "")
    {
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
    private function wrapperSend2dCodeData($fn, $cn, $data = '', $m = '')
    {
        if (strlen($m) > 1 || strlen($cn) != 1 || strlen($fn) != 1) {
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
    private function wrapperSendGraphicsData($m, $fn, $data = '')
    {
        if (strlen($m) != 1 || strlen($fn) != 1) {
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
    private static function dataHeader(array $inputs, $long = true)
    {
        $outp = array();
        foreach ($inputs as $input) {
            if ($long) {
                $outp[] = Printer::intLowHigh($input, 2);
            } else {
                self::validateInteger($input, 0, 255, __FUNCTION__);
                $outp[] = chr($input);
            }
        }
        return implode("", $outp);
    }
    
    /**
     * Generate two characters for a number: In lower and higher parts, or more parts as needed.
     *
     * @param int $input Input number
     * @param int $length The number of bytes to output (1 - 4).
     */
    private static function intLowHigh($input, $length)
    {
        $maxInput = (256 << ($length * 8) - 1);
        self::validateInteger($length, 1, 4, __FUNCTION__);
        self::validateInteger($input, 0, $maxInput, __FUNCTION__);
        $outp = "";
        for ($i = 0; $i < $length; $i++) {
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
    protected static function validateBoolean($test, $source)
    {
        if (!($test === true || $test === false)) {
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
    protected static function validateInteger($test, $min, $max, $source, $argument = "Argument")
    {
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
    protected static function validateIntegerMulti($test, array $ranges, $source, $argument = "Argument")
    {
        if (!is_integer($test)) {
            throw new InvalidArgumentException("$argument given to $source must be a number, but '$test' was given.");
        }
        $match = false;
        foreach ($ranges as $range) {
            $match |= $test >= $range[0] && $test <= $range[1];
        }
        if (!$match) {
            // Put together a good error "range 1-2 or 4-6"
            $rangeStr = "range ";
            for ($i = 0; $i < count($ranges); $i++) {
                $rangeStr .= $ranges[$i][0] . "-" . $ranges[$i][1];
                if ($i == count($ranges) - 1) {
                    continue;
                } elseif ($i == count($ranges) - 2) {
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
     * @param string $argument the name of the parameter being validated
     * @throws InvalidArgumentException Where the argument is not valid
     */
    protected static function validateString($test, $source, $argument = "Argument")
    {
        if (is_object($test) && !method_exists($test, '__toString')) {
            throw new InvalidArgumentException("$argument to $source must be a string");
        }
    }
    
    /**
     * Throw an exception if the argument doesn't match the given regex.
     *
     * @param string $test the input to test
     * @param string $source the name of the function calling this
     * @param string $regex valid values for this attribute, as a regex
     * @param string $argument the name of the parameter being validated
     * @throws InvalidArgumentException Where the argument is not valid
     */
    protected static function validateStringRegex($test, $source, $regex, $argument = "Argument")
    {
        if (preg_match($regex, $test) === 0) {
            throw new InvalidArgumentException("$argument given to $source is invalid. It should match regex '$regex', but '$test' was given.");
        }
    }
}
