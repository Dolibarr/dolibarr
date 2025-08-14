<?php
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintBuffers\ImagePrintBuffer;

$profile = CapabilityProfile::load("default");
// This is a quick demo of currency symbol issues in #39.

/* Option 1: Native ESC/POS characters, depends on printer and is buggy. */
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector, $profile);
$printer -> text("€ 9,95\n");
$printer -> text("£ 9.95\n");
$printer -> text("$ 9.95\n");
$printer -> text("¥ 9.95\n");
$printer -> cut();
$printer -> close();

/* Option 2: Image-based output (formatting not available using this output). */
$buffer = new ImagePrintBuffer();
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector, $profile);
$printer -> setPrintBuffer($buffer);
$printer -> text("€ 9,95\n");
$printer -> text("£ 9.95\n");
$printer -> text("$ 9.95\n");
$printer -> text("¥ 9.95\n");
$printer -> cut();
$printer -> close();

/*
 Option 3: If the printer is configured to print in a specific code
 page, you can set up a CapabilityProfile which either references its
 iconv encoding name, or includes all of the available characters.

 Here, we make use of CP858 for its inclusion of currency symbols which
 are not available in CP437. CP858 has good printer support, but is not
 included in all iconv builds.
*/
class CustomCapabilityProfile extends CapabilityProfile
{
    function getCustomCodePages()
    {
        /*
		 * Example to print in a specific, user-defined character set
		 * on a printer which has been configured to use i
		 */
        return array(
        'CP858' => "ÇüéâäàåçêëèïîìÄÅ" .
                "ÉæÆôöòûùÿÖÜø£Ø×ƒ" .
                "áíóúñÑªº¿®¬½¼¡«»" .
                "░▒▓│┤ÁÂÀ©╣║╗╝¢¥┐" .
                "└┴┬├─┼ãÃ╚╔╩╦╠═╬¤" .
                "ðÐÊËÈ€ÍÎÏ┘┌█▄¦Ì▀" .
                "ÓßÔÒõÕµþÞÚÛÙýÝ¯´" .
                " ±‗¾¶§÷¸°¨·¹³²■ ");
    }
    
    function getSupportedCodePages()
    {
        return array(
                0 => 'custom:CP858');
    }
}

$connector = new FilePrintConnector("php://stdout");
$profile = CustomCapabilityProfile::getInstance();
$printer = new Printer($connector, $profile);
$printer -> text("€ 9,95\n");
$printer -> text("£ 9.95\n");
$printer -> text("$ 9.95\n");
$printer -> text("¥ 9.95\n");

$printer -> cut();
$printer -> close();
