<?php
/* Print-outs using the newer graphics print command */

require_once(dirname(__FILE__) . "/../Escpos.php");
$printer = new Escpos();

try {
	$tux = new EscposImage("resources/tux.png");
	
	$printer -> graphics($tux);
	$printer -> text("Regular Tux.\n");
	$printer -> feed();
	
	$printer -> graphics($tux, Escpos::IMG_DOUBLE_WIDTH);
	$printer -> text("Wide Tux.\n");
	$printer -> feed();
	
	$printer -> graphics($tux, Escpos::IMG_DOUBLE_HEIGHT);
	$printer -> text("Tall Tux.\n");
	$printer -> feed();
	
	$printer -> graphics($tux, Escpos::IMG_DOUBLE_WIDTH | Escpos::IMG_DOUBLE_HEIGHT);
	$printer -> text("Large Tux in correct proportion.\n");
	
	$printer -> cut();
} catch(Exception $e) {
	/* Images not supported on your PHP, or image file not found */
	$printer -> text($e -> getMessage() . "\n");
}

$printer -> close();
?>
