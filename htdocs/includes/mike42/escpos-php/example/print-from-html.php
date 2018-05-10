<?php
require_once(dirname(__FILE__)."/../Escpos.php");
/*
 * Due to its complxity, escpos-php does not support HTML input. To print HTML,
 * either convert it to calls on the Escpos() object, or rasterise the page with
 * wkhtmltopdf, an external package which is designed to handle HTML efficiently.
 *
 * This example is provided to get you started.
 *
 * Note: Depending on the height of your pages, it is suggested that you chop it
 * into smaller sections, as printers simply don't have the buffer capacity for
 * very large images.
 *
 * As always, you can trade off quality for capacity by halving the width
 * (550 -> 225 below) and printing w/ Escpos::IMG_DOUBLE_WIDTH | Escpos::IMG_DOUBLE_HEIGHT
 */
try {
	/* Set up command */
	$source = "http://en.m.wikipedia.org/wiki/ESC/P";
	$width = 550;
	$dest = tempnam(sys_get_temp_dir(), 'escpos') . ".png";
	$cmd = sprintf("wkhtmltoimage -n -q --width %s %s %s",
		escapeshellarg($width),
		escapeshellarg($source),
		escapeshellarg($dest));
	
	/* Run wkhtmltoimage */
	ob_start();
	system($cmd); // Can also use popen() for better control of process
	$outp = ob_get_contents();
	ob_end_clean();
	if(!file_exists($dest)) {
		throw new Exception("Command $cmd failed: $outp");
	}

	/* Load up the image */
	try {
		$img = new EscposImage($dest);
	} catch(Exception $e) {
		unlink($dest);
		throw $e;
	}
	unlink($dest);

	/* Print it */
	$printer = new Escpos(); // Add connector for your printer here.
	$printer -> bitImage($img); // bitImage() seems to allow larger images than graphics() on the TM-T20.
	$printer -> cut();
	$printer -> close();
} catch(Exception $e) {
	echo $e -> getMessage();
}

