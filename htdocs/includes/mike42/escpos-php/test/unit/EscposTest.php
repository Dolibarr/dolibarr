<?php
use Mike42\Escpos\Printer;
use Mike42\Escpos\CapabilityProfiles\SimpleCapabilityProfile;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\EscposImage;

class EscposTest extends PHPUnit_Framework_TestCase
{
    protected $printer;
    protected $outputConnector;

    protected function setup()
    {
        /* Print to nowhere- for testing which inputs are accepted */
        $this -> outputConnector = new DummyPrintConnector();
        $this -> printer = new Printer($this -> outputConnector);
    }

    protected function checkOutput($expected = null)
    {
        /* Check those output strings */
        $outp = $this -> outputConnector -> getData();
        if ($expected === null) {
            echo "\nOutput was:\n\"" . friendlyBinary($outp) . "\"\n";
        }
        $this -> assertEquals($expected, $outp);
    }

    protected function tearDown()
    {
        $this -> outputConnector -> finalize();
    }

    protected function requireGraphicsLibrary()
    {
        if (!EscposImage::isGdLoaded() && !EscposImage::isImagickLoaded()) {
            // If the test is about to do something which requires a library,
            // something must throw an exception.
            $this -> setExpectedException('Exception');
        }
    }

    public function testInitializeOutput()
    {
        $this -> checkOutput("\x1b\x40");
    }

    public function testTextStringOutput()
    {
        $this -> printer -> text("The quick brown fox jumps over the lazy dog\n");
        $this -> checkOutput("\x1b@The quick brown fox jumps over the lazy dog\n");
    }

    public function testTextDefault()
    {
        $this -> printer -> text();
        $this -> checkOutput("\x1b@");
    }
    
    public function testTextChinese()
    {
        // Switch to chinese print mode, GBK output, switch back to alphanumeric.
        $this -> printer -> textChinese("示例文本打印机!\n");
        $this -> checkOutput("\x1b@\x1c&\xca\xbe\xc0\xfd\xce\xc4\xb1\xbe\xb4\xf2\xd3\xa1\xbb\xfa!\x0a\x1c.");
    }
    
    public function testTextRaw()
    {
        // Under raw output, the raw bytes are sent to the printer, so typing a UTF-8 euro literally causes \xE2 \x82 \xAC to be sent.
        // Under text(), this would cause a code-page change (to a page that contains a Euro symbol), and single byte.
        $this -> printer -> textRaw("€\n");
        $this -> checkOutput("\x1b@\xe2\x82\xac\x0a");
    }

    public function testTextString()
    {
        $this -> printer -> text("String");
        $this -> printer -> text(123);
        $this -> printer -> text();
        $this -> printer -> text(null);
        $this -> printer -> text(1.2);
        $this -> printer -> text(new FooBar("FooBar"));
        $this -> checkOutput("\x1b@String1231.2FooBar");
    }

    public function testTextObject()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> text(new DateTime());
    }

    public function testFeedDefault()
    {
        $this -> printer -> feed();
        $this -> checkOutput("\x1b@\x0a");
    }

    public function testFeed3Lines()
    {
        $this -> printer -> feed(3);
        $this -> checkOutput("\x1b@\x1bd\x03");
    }

    public function testFeedZero()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feed(0);
    }

    public function testFeedNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feed("ab");
    }

    public function testFeedTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feed(256);
    }

    /* Print mode */
    public function testSelectPrintModeDefault()
    {
        $this -> printer -> selectPrintMode();
        $this -> checkOutput("\x1b@\x1b!\x00");
    }

    public function testSelectPrintModeAcceptedValues()
    {
        /* This iterates over a bunch of numbers, figures out which
           ones contain invalid flags, and checks that the driver
           rejects those, but accepts the good inputs */
        
        for ($i = -1; $i <= 256; $i++) {
            $invalid = ($i < 0) || ($i > 255) || (($i & 2) == 2) || (($i & 4) == 4) || (($i & 64) == 64);
            $failed = false;
            try {
                $this -> printer -> selectPrintMode($i);
            } catch (Exception $e) {
                $failed = true;
            }
            $this -> assertEquals($failed, $invalid);
        }
    }

    /* Underline */
    public function testSetUnderlineDefault()
    {
        $this -> printer -> setUnderline();
        $this -> checkOutput("\x1b@\x1b-\x01");
    }

    public function testSetUnderlineOff()
    {
        $this -> printer -> setUnderline(Printer::UNDERLINE_NONE);
        $this -> checkOutput("\x1b@\x1b-\x00");
    }

    public function testSetUnderlineOn()
    {
        $this -> printer -> setUnderline(Printer::UNDERLINE_SINGLE);
        $this -> checkOutput("\x1b@\x1b-\x01");
    }

    public function testSetUnderlineDbl()
    {
        $this -> printer -> setUnderline(Printer::UNDERLINE_DOUBLE);
        $this -> checkOutput("\x1b@\x1b-\x02");
    }

    public function testSetUnderlineAcceptedValues()
    {
        $this -> printer -> setUnderline(0);
        $this -> printer -> setUnderline(1);
        $this -> printer -> setUnderline(2);
        /* These map to 0 & 1 for interchangeability with setEmphasis */
        $this -> printer -> setUnderline(true);
        $this -> printer -> setUnderline(false);
        $this -> checkOutput("\x1b@\x1b-\x00\x1b-\x01\x1b-\x02\x1b-\x01\x1b-\x00");
    }

    public function testSetUnderlineTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setUnderline(3);
    }

    public function testSetUnderlineNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setUnderline(-1);
    }

    public function testSetUnderlineNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setUnderline("Hello");
    }

    /* Emphasis */
    public function testSetEmphasisDefault()
    {
        $this -> printer -> setEmphasis();
        $this -> checkOutput("\x1b@\x1bE\x01");
    }

    public function testSetEmphasisOn()
    {
        $this -> printer -> setEmphasis(true);
        $this -> checkOutput("\x1b@\x1bE\x01");
    }

    public function testSetEmphasisOff()
    {
        $this -> printer -> setEmphasis(false);
        $this -> checkOutput("\x1b@\x1bE\x00");
    }

    public function testSetEmphasisNonBoolean()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setEmphasis(7);
    }

    /* Double strike */
    public function testSetDoubleStrikeDefault()
    {
        $this -> printer -> setDoubleStrike();
        $this -> checkOutput("\x1b@\x1bG\x01");
    }

    public function testSetDoubleStrikeOn()
    {
        $this -> printer -> setDoubleStrike(true);
        $this -> checkOutput("\x1b@\x1bG\x01");
    }

    public function testSetDoubleStrikeOff()
    {
        $this -> printer -> setDoubleStrike(false);
        $this -> checkOutput("\x1b@\x1bG\x00");
    }

    public function testSetDoubleStrikeNonBoolean()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setDoubleStrike(4);
    }

    /* Font */
    public function testSetFontDefault()
    {
        $this -> printer -> setFont();
        $this -> checkOutput("\x1b@\x1bM\x00");
    }

    public function testSetFontAcceptedValues()
    {
        $this -> printer -> setFont(Printer::FONT_A);
        $this -> printer -> setFont(Printer::FONT_B);
        $this -> printer -> setFont(Printer::FONT_C);
        $this -> checkOutput("\x1b@\x1bM\x00\x1bM\x01\x1bM\x02");
    }

    public function testSetFontNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setFont(-1);
    }


    public function testSetFontTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setFont(3);
    }

    public function testSetFontNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setFont('hello');
    }

    /* Justification */
    public function testSetJustificationDefault()
    {
        $this -> printer -> setJustification();
        $this -> checkOutput("\x1b@\x1ba\x00");
    }

    public function testSetJustificationLeft()
    {
        $this -> printer -> setJustification(Printer::JUSTIFY_LEFT);
        $this -> checkOutput("\x1b@\x1ba\x00");
    }

    public function testSetJustificationRight()
    {
        $this -> printer -> setJustification(Printer::JUSTIFY_RIGHT);
        $this -> checkOutput("\x1b@\x1ba\x02");
    }

    public function testSetJustificationCenter()
    {
        $this -> printer -> setJustification(Printer::JUSTIFY_CENTER);
        $this -> checkOutput("\x1b@\x1ba\x01");
    }

    public function testSetJustificationNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setJustification(-1);
    }


    public function testSetJustificationTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setFont(3);
    }

    public function testSetJustificationNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setJustification('hello');
    }

    /* Reverse feed */
    public function testFeedReverseDefault()
    {
        $this -> printer -> feedReverse();
        $this -> checkOutput("\x1b@\x1be\x01");
    }

    public function testFeedReverse3()
    {
        $this -> printer -> feedReverse(3);
        $this -> checkOutput("\x1b@\x1be\x03");
    }

    public function testFeedReverseNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feedReverse(-1);
    }

    public function testFeedReverseTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feedReverse(256);
    }

    public function testFeedReverseNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> feedReverse('hello');
    }

    /* Cut */
    public function testCutDefault()
    {
        // TODO check what the accepted range of values should be for $line
        // cut($mode = self::CUT_FULL, $lines = 3)
        $this -> printer -> cut();
        $this -> checkOutput("\x1b@\x1dVA\x03");
    }

    /* Set barcode height */
    public function testSetBarcodeHeightDefault()
    {
        $this -> printer -> setBarcodeHeight();
        $this -> checkOutput("\x1b@\x1dh\x08");
    }

    public function testBarcodeHeight10()
    {
        $this -> printer -> setBarcodeHeight(10);
        $this -> checkOutput("\x1b@\x1dh\x0a");
    }

    public function testSetBarcodeHeightNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeHeight(-1);
    }

    public function testSetBarcodeHeightTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeHeight(256);
    }

    public function testSetBarcodeHeightNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeHeight('hello');
    }

    /* Set barcode width */
    public function testSetBarcodeWidthDefault()
    {
        $this -> printer -> setBarcodeWidth();
        $this -> checkOutput("\x1b@\x1dw\x03");
    }
    
    public function testBarcodeWidth1()
    {
        $this -> printer -> setBarcodeWidth(1);
        $this -> checkOutput("\x1b@\x1dw\x01");
    }
    
    public function testSetBarcodeWidthNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeWidth(-1);
    }
    
    public function testSetBarcodeWidthTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeWidth(256);
    }
    
    public function testSetBarcodeWidthNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeWidth('hello');
    }

    /* Barcode text position */
    public function testSetBarcodeTextPositionDefault()
    {
        $this -> printer -> setBarcodeTextPosition();
        $this -> checkOutput("\x1b@\x1dH\x00");
    }
    
    public function testSetBarcodeTextPositionBelow()
    {
        $this -> printer -> setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
        $this -> checkOutput("\x1b@\x1dH\x02");
    }

    public function testSetBarcodeTextPositionBoth()
    {
        $this -> printer -> setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW | Printer::BARCODE_TEXT_ABOVE);
        $this -> checkOutput("\x1b@\x1dH\x03");
    }
    
    public function testSetBarcodeTextPositionNegative()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeTextPosition(-1);
    }
    
    public function testSetBarcodeTextPositionTooLarge()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeTextPosition(4);
    }
    
    public function tesSetBarcodeTextPositionNonInteger()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setBarcodeTextPosition('hello');
    }

    /* Barcode - UPC-A */
    public function testBarcodeUpcaNumeric11Char()
    {
        $this -> printer -> barcode("01234567890", Printer::BARCODE_UPCA);
        $this -> checkOutput("\x1b@\x1dkA\x0b01234567890");
    }
    
    public function testBarcodeUpcaNumeric12Char()
    {
        $this -> printer -> barcode("012345678901", Printer::BARCODE_UPCA);
        $this -> checkOutput("\x1b@\x1dkA\x0c012345678901");
    }
    
    public function testBarcodeUpcaNumeric13Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("0123456789012", Printer::BARCODE_UPCA);
    }
    
    public function testBarcodeUpcaNonNumeric12Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("A12345678901", Printer::BARCODE_UPCA);
    }

    /* Barcode - UPC-E */
    public function testBarcodeUpceNumeric6Char()
    {
        $this -> printer -> barcode("123456", Printer::BARCODE_UPCE);
        $this -> checkOutput("\x1b@\x1dkB\x06123456");
    }

    public function testBarcodeUpceNumeric7Char()
    {
        $this -> printer -> barcode("0123456", Printer::BARCODE_UPCE);
        $this -> checkOutput("\x1b@\x1dkB\x070123456");
    }
    
    public function testBarcodeUpceNumeric8Char()
    {
        $this -> printer -> barcode("01234567", Printer::BARCODE_UPCE);
        $this -> checkOutput("\x1b@\x1dkB\x0801234567");
    }
    
    public function testBarcodeUpceNumeric11Char()
    {
        $this -> printer -> barcode("01234567890", Printer::BARCODE_UPCE);
        $this -> checkOutput("\x1b@\x1dkB\x0b01234567890");
    }
    
    public function testBarcodeUpceNumeric12Char()
    {
        $this -> printer -> barcode("012345678901", Printer::BARCODE_UPCE);
        $this -> checkOutput("\x1b@\x1dkB\x0c012345678901");
    }
    
    public function testBarcodeUpceNumeric9Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("012345678", Printer::BARCODE_UPCE);
    }
    
    public function testBarcodeUpceNonNumeric12Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("A12345678901", Printer::BARCODE_UPCE);
    }

    /* Barcode - JAN13 */
    public function testBarcodeJan13Numeric12Char()
    {
        $this -> printer -> barcode("012345678901", Printer::BARCODE_JAN13);
        $this -> checkOutput("\x1b@\x1dkC\x0c012345678901");
    }
    
    public function testBarcodeJan13Numeric13Char()
    {
        $this -> printer -> barcode("0123456789012", Printer::BARCODE_JAN13);
        $this -> checkOutput("\x1b@\x1dkC\x0d0123456789012");
    }
    
    public function testBarcodeJan13Numeric11Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("01234567890", Printer::BARCODE_JAN13);
    }
    
    public function testBarcodeJan13NonNumeric13Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("A123456789012", Printer::BARCODE_JAN13);
    }
    
    /* Barcode - JAN8 */
    public function testBarcodeJan8Numeric7Char()
    {
        $this -> printer -> barcode("0123456", Printer::BARCODE_JAN8);
        $this -> checkOutput("\x1b@\x1dkD\x070123456");
    }
    
    public function testBarcodeJan8Numeric8Char()
    {
        $this -> printer -> barcode("01234567", Printer::BARCODE_JAN8);
        $this -> checkOutput("\x1b@\x1dkD\x0801234567");
    }
    
    public function testBarcodeJan8Numeric9Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("012345678", Printer::BARCODE_JAN8);
    }
    
    public function testBarcodeJan8NonNumeric8Char()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("A1234567", Printer::BARCODE_JAN8);
    }
    
    /* Barcode - Code39 */
    public function testBarcodeCode39AsDefault()
    {
        $this -> printer -> barcode("1234");
        $this -> checkOutput("\x1b@\x1dkE\x041234");
    }

    public function testBarcodeCode39Text()
    {
        $this -> printer -> barcode("ABC 012", Printer::BARCODE_CODE39);
        $this -> checkOutput("\x1b@\x1dkE\x07ABC 012");
    }
    
    public function testBarcodeCode39SpecialChars()
    {
        $this -> printer -> barcode("$%+-./", Printer::BARCODE_CODE39);
        $this -> checkOutput("\x1b@\x1dkE\x06$%+-./");
    }
    
    public function testBarcodeCode39Asterisks()
    {
        $this -> printer -> barcode("*TEXT*", Printer::BARCODE_CODE39);
        $this -> checkOutput("\x1b@\x1dkE\x06*TEXT*");
    }
    
    public function testBarcodeCode39AsterisksUnmatched()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("*TEXT", Printer::BARCODE_CODE39);
    }
    
    public function testBarcodeCode39AsteriskInText()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("12*34", Printer::BARCODE_CODE39);
    }
    
    public function testBarcodeCode39Lowercase()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("abcd", Printer::BARCODE_CODE39);
    }
    
    public function testBarcodeCode39Empty()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("**", Printer::BARCODE_CODE39);
    }

    /* Barcode - ITF */
    public function testBarcodeItfNumericEven()
    {
        $this -> printer -> barcode("1234", Printer::BARCODE_ITF);
        $this -> checkOutput("\x1b@\x1dkF\x041234");
    }
    
    public function testBarcodeItfNumericOdd()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("123", Printer::BARCODE_ITF);
    }
    
    public function testBarcodeItfNonNumericEven()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("A234", Printer::BARCODE_ITF);
    }

    /* Barcode - Codabar */
    public function testBarcodeCodabarNumeric()
    {
        $this -> printer -> barcode("A012345A", Printer::BARCODE_CODABAR);
        $this -> checkOutput("\x1b@\x1dkG\x08A012345A");
    }
    
    public function testBarcodeCodabarSpecialChars()
    {
        $this -> printer -> barcode("A012$+-./:A", Printer::BARCODE_CODABAR);
        $this -> checkOutput("\x1b@\x1dkG\x0bA012$+-./:A");
    }
    
    public function testBarcodeCodabarNotWrapped()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("012345", Printer::BARCODE_CODABAR);
    }
    
    public function testBarcodeCodabarStartStopWrongPlace()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("012A45", Printer::BARCODE_CODABAR);
    }

    /* Barcode - Code93 */
    public function testBarcodeCode93Valid()
    {
        $this -> printer -> barcode("012abcd", Printer::BARCODE_CODE93);
        $this -> checkOutput("\x1b@\x1dkH\x07012abcd");
    }

    public function testBarcodeCode93Empty()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("", Printer::BARCODE_CODE93);
    }

    /* Barcode - Code128 */
    public function testBarcodeCode128ValidA()
    {
        $this -> printer -> barcode("{A" . "012ABCD", Printer::BARCODE_CODE128);
        $this -> checkOutput("\x1b@\x1dkI\x09{A012ABCD");
    }

    public function testBarcodeCode128ValidB()
    {
        $this -> printer -> barcode("{B" . "012ABCDabcd", Printer::BARCODE_CODE128);
        $this -> checkOutput("\x1b@\x1dkI\x0d{B012ABCDabcd");
    }
    
    public function testBarcodeCode128ValidC()
    {
        $this -> printer -> barcode("{C" . chr(21) . chr(32) . chr(43), Printer::BARCODE_CODE128);
        $this -> checkOutput("\x1b@\x1dkI\x05{C\x15 +");
    }
    
    public function testBarcodeCode128NoCodeSet()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> barcode("ABCD", Printer::BARCODE_CODE128);
    }
    
    /* Pulse */
    function testPulseDefault()
    {
        $this -> printer -> pulse();
        $this -> checkOutput("\x1b@\x1bp0<x");
    }

    function testPulse1()
    {
        $this -> printer -> pulse(1);
        $this -> checkOutput("\x1b@\x1bp1<x");
    }
    
    function testPulseEvenMs()
    {
        $this -> printer -> pulse(0, 2, 2);
        $this -> checkOutput("\x1b@\x1bp0\x01\x01");
    }
    
    function testPulseOddMs()
    {
        $this -> printer -> pulse(0, 3, 3); // Should be rounded down and give same output
        $this -> checkOutput("\x1b@\x1bp0\x01\x01");
    }
    
    function testPulseTooHigh()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> pulse(0, 512, 2);
    }
    
    function testPulseTooLow()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> pulse(0, 0, 2);
    }
    
    function testPulseNotANumber()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> pulse("fish");
    }
    
    /* Set reverse */
    public function testSetReverseColorsDefault()
    {
        $this -> printer -> setReverseColors();
        $this -> checkOutput("\x1b@\x1dB\x01");
    }

    public function testSetReverseColorsOn()
    {
        $this -> printer -> setReverseColors(true);
        $this -> checkOutput("\x1b@\x1dB\x01");
    }

    public function testSetReverseColorsOff()
    {
        $this -> printer -> setReverseColors(false);
        $this -> checkOutput("\x1b@\x1dB\x00");
    }

    public function testSetReverseColorsNonBoolean()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setReverseColors(7);
    }

    /* Bit image print */
    public function testBitImageBlack()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_black.png");
        $this -> printer -> bitImage($img);
        $this -> checkOutput("\x1b@\x1dv0\x00\x01\x00\x01\x00\x80");
    }

    public function testBitImageWhite()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_white.png");
        $this -> printer -> bitImage($img);
        $this -> checkOutput("\x1b@\x1dv0\x00\x01\x00\x01\x00\x00");
    }
    
    public function testBitImageBoth()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_white.png");
        $this -> printer -> bitImage($img);
        $this -> checkOutput("\x1b@\x1dv0\x00\x01\x00\x02\x00\xc0\x00");
    }
    
    public function testBitImageTransparent()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_transparent.png");
        $this -> printer -> bitImage($img);
        $this -> checkOutput("\x1b@\x1dv0\x00\x01\x00\x02\x00\xc0\x00");
    }
    
    /* Bit image column format */
    public function testBitImageColumnFormatBlack()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_black.png");
        $this -> printer -> bitImageColumnFormat($img);
        $this -> checkOutput("\x1b@\x1b3\x10\x1b*!\x01\x00\x80\x00\x00\x0a\x1b2");
    }

    public function testBitImageColumnFormatWhite()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_white.png");
        $this -> printer -> bitImageColumnFormat($img);
        $this -> checkOutput("\x1b@\x1b3\x10\x1b*!\x01\x00\x00\x00\x00\x0a\x1b2");
    }

    public function testBitImageColumnFormatBoth()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_white.png");
        $this -> printer -> bitImageColumnFormat($img);
        $this -> checkOutput("\x1b@\x1b3\x10\x1b*!\x02\x00\x80\x00\x00\x80\x00\x00\x0a\x1b2");
    }

    public function testBitImageColumnFormatTransparent()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_transparent.png");
        $this -> printer -> bitImageColumnFormat($img);
        $this -> checkOutput("\x1b@\x1b3\x10\x1b*!\x02\x00\x80\x00\x00\x80\x00\x00\x0a\x1b2");
    }

    /* Graphics print */
    public function testGraphicsWhite()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_white.png");
        $this -> printer -> graphics($img);
        $this -> checkOutput("\x1b@\x1d(L\x0b\x000p0\x01\x011\x01\x00\x01\x00\x00\x1d(L\x02\x0002");
    }
    
    public function testGraphicsBlack()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/canvas_black.png");
        $this -> printer -> graphics($img);
        $this -> checkOutput("\x1b@\x1d(L\x0b\x000p0\x01\x011\x01\x00\x01\x00\x80\x1d(L\x02\x0002");
    }
        
    public function testGraphicsBoth()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_white.png");
        $this -> printer -> graphics($img);
        $this -> checkOutput("\x1b@\x1d(L\x0c\x000p0\x01\x011\x02\x00\x02\x00\xc0\x00\x1d(L\x02\x0002");
    }
    
    public function testGraphicsTransparent()
    {
        $this -> requireGraphicsLibrary();
        $img = EscposImage::load(dirname(__FILE__)."/resources/black_transparent.png");
        $this -> printer -> graphics($img);
        $this -> checkOutput("\x1b@\x1d(L\x0c\x000p0\x01\x011\x02\x00\x02\x00\xc0\x00\x1d(L\x02\x0002");
    }

    /* PDF417 code */
    public function testPdf417CodeDefaults()
    {
        $this -> printer -> pdf417Code("1234");
        $this -> checkOutput("\x1b@\x1d(k\x03\x000F\x00\x1d(k\x03\x000A\x00\x1d(k\x03\x000C\x03\x1d(k\x03\x000D\x03\x1d(k\x04\x000E1\x01\x1d(k\x07\x000P01234\x1d(k\x03\x000Q0");
    }

    public function testPdf417CodeEmpty()
    {
        $this -> printer -> pdf417Code('');
        $this -> checkOutput("\x1b@"); // No commands actually sent
    }

    public function testPdf417CodeNotSupported()
    {
        $this -> setExpectedException('Exception');
        $profile = SimpleCapabilityProfile::getInstance();
        $this -> printer = new Printer($this -> outputConnector, $profile);
        $this -> printer -> pdf417Code("1234");
    }

    public function testPdf417CodeChangeGeometry()
    {
        // 7-dot wide, 4-times height, 4 data columns
        $this -> printer -> pdf417Code("1234", 7, 4, 4);
        $this -> checkOutput("\x1b@\x1d(k\x03\x000F\x00\x1d(k\x03\x000A\x04\x1d(k\x03\x000C\x07\x1d(k\x03\x000D\x04\x1d(k\x04\x000E1\x01\x1d(k\x07\x000P01234\x1d(k\x03\x000Q0");
    }

    public function testPdf417CodeChangeErrorCorrection()
    {
        $this -> printer -> pdf417Code("1234", 3, 3, 0, 0.5);
        $this -> checkOutput("\x1b@\x1d(k\x03\x000F\x00\x1d(k\x03\x000A\x00\x1d(k\x03\x000C\x03\x1d(k\x03\x000D\x03\x1d(k\x04\x000E1\x05\x1d(k\x07\x000P01234\x1d(k\x03\x000Q0");
    }

    public function testPdf417CodeChangeErrorCorrectionOutOfRange()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> pdf417Code("1234", 3, 3, 0, 5.0);
    }

    public function testPdf417CodeChangeErrorCorrectionInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> pdf417Code("1234", 3, 3, 0, "Foobar");
    }

    public function testPdf417CodeChangeOption()
    {
        // Use the alternate truncated format
        $this -> printer -> pdf417Code("1234", 3, 3, 0, 0.1, Printer::PDF417_TRUNCATED);
        $this -> checkOutput("\x1b@\x1d(k\x03\x000F\x01\x1d(k\x03\x000A\x00\x1d(k\x03\x000C\x03\x1d(k\x03\x000D\x03\x1d(k\x04\x000E1\x01\x1d(k\x07\x000P01234\x1d(k\x03\x000Q0");
    }

    /* QR code */
    public function testQRCodeDefaults()
    {
        // Test will fail if default values change
        $this -> printer -> qrCode("1234");
        $this -> checkOutput("\x1b@\x1d(k\x04\x001A2\x00\x1d(k\x03\x001C\x03\x1d(k\x03\x001E0\x1d(k\x07\x001P01234\x1d(k\x03\x001Q0");
    }
    
    public function testQRCodeDefaultsAreCorrect()
    {
        // Below tests assume that defaults are as written here (output string should be same as above)
        $this -> printer -> qrCode("1234", Printer::QR_ECLEVEL_L, 3, Printer::QR_MODEL_2);
        $this -> checkOutput("\x1b@\x1d(k\x04\x001A2\x00\x1d(k\x03\x001C\x03\x1d(k\x03\x001E0\x1d(k\x07\x001P01234\x1d(k\x03\x001Q0");
    }
    
    public function testQRCodeEmpty()
    {
        $this -> printer -> qrCode('');
        $this -> checkOutput("\x1b@"); // No commands actually sent
    }
    
    public function testQRCodeChangeEC()
    {
        $this -> printer -> qrCode("1234", Printer::QR_ECLEVEL_H);
        $this -> checkOutput("\x1b@\x1d(k\x04\x001A2\x00\x1d(k\x03\x001C\x03\x1d(k\x03\x001E3\x1d(k\x07\x001P01234\x1d(k\x03\x001Q0");
    }
    
    public function testQRCodeChangeSize()
    {
        $this -> printer -> qrCode("1234", Printer::QR_ECLEVEL_L, 7);
        $this -> checkOutput("\x1b@\x1d(k\x04\x001A2\x00\x1d(k\x03\x001C\x07\x1d(k\x03\x001E0\x1d(k\x07\x001P01234\x1d(k\x03\x001Q0");
    }
    
    public function testQRCodeChangeModel()
    {
        $this -> printer -> qrCode("1234", Printer::QR_ECLEVEL_L, 3, Printer::QR_MODEL_1);
        $this -> checkOutput("\x1b@\x1d(k\x04\x001A1\x00\x1d(k\x03\x001C\x03\x1d(k\x03\x001E0\x1d(k\x07\x001P01234\x1d(k\x03\x001Q0");
    }

    /* Feed form - Required on page-mode only printers */
    public function testFeedForm()
    {
        $this -> printer -> feedForm();
        $this -> checkOutput("\x1b@\x0c");
    }

    /* Release */
    public function testRelease()
    {
        $this -> printer -> release();
        $this -> checkOutput("\x1b@\x1b\x71");
    }

    /* Set text size  */
    public function testSetTextSizeNormal()
    {
        $this -> printer -> setTextSize(1, 1);
        $this -> checkOutput("\x1b@\x1d!\x00");
    }

    public function testSetTextSizeWide()
    {
        $this -> printer -> setTextSize(4, 1);
        $this -> checkOutput("\x1b@\x1d!0");
    }

    public function testSetTextSizeNarrow()
    {
        $this -> printer -> setTextSize(1, 4);
        $this -> checkOutput("\x1b@\x1d!\x03");
    }

    public function testSetTextSizeLarge()
    {
        $this -> printer -> setTextSize(4, 4);
        $this -> checkOutput("\x1b@\x1d!3");
    }

    public function testSetTextSizeInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setTextSize(0, 9);
    }
    
    /* Set color */
    public function testSetColorDefault()
    {
        $this -> printer -> setColor(Printer::COLOR_1);
        $this -> checkOutput("\x1b@\x1br\x00");
    }
    
    public function testSetColorAlternative()
    {
        $this -> printer -> setColor(Printer::COLOR_2);
        $this -> checkOutput("\x1b@\x1br\x01");
    }
    
    public function testSetColorInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setColor(3);
    }

    /* Set line spacing */
    public function testSetLineSpacingDefault()
    {
        $this -> printer -> setLineSpacing();
        $this -> checkOutput("\x1b@\x1b2");
    }

    public function testSetLineSpacingInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setLineSpacing(300);
    }

    public function testSetLineSpacingSmaller()
    {
        $this -> printer -> setLineSpacing(16);
        $this -> checkOutput("\x1b@\x1b3\x10");
    }

    public function testSetLineSpacingLarger()
    {
        $this -> printer -> setLineSpacing(32);
        $this -> checkOutput("\x1b@\x1b3\x20");
    }

    /* Set print width  */
    public function testSetPrintWidthDefault()
    {
        $this -> printer -> setPrintWidth();
        $this -> checkOutput("\x1b@\x1dW\x00\x02");
    }

    public function testSetPrintWidthNarrow()
    {
        $this -> printer -> setPrintWidth(400);
        $this -> checkOutput("\x1b@\x1dW\x90\x01");
    }

    public function testSetPrintWidthInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setPrintWidth(0);
    }

    /* Set print left margin  */
    public function testSetPrintLeftMarginDefault()
    {
        $this -> printer -> setPrintLeftMargin();
        $this -> checkOutput("\x1b@\x1dL\x00\x00");
    }

    public function testSetPrintLeftMarginWide()
    {
        $this -> printer -> setPrintLeftMargin(32);
        $this -> checkOutput("\x1b@\x1dL\x20\x00");
    }

    public function testPrintLeftMarginInvalid()
    {
        $this -> setExpectedException('InvalidArgumentException');
        $this -> printer -> setPrintLeftMargin(70000);
        $this -> checkOutput();
    }

    /* Upside-down print */
    public function testSetUpsideDown()
    {
        $this -> printer -> setUpsideDown(true);
        $this -> checkOutput("\x1b@\x1b{\x01");
    }
}

/*
 * For testing that string-castable objects are handled
 */
class FooBar
{
    private $foo;
    public function __construct($foo)
    {
        $this -> foo = $foo;
    }
    
    public function __toString()
    {
        return $this -> foo;
    }
}
