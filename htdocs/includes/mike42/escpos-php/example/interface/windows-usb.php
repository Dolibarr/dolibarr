<?php
/* Change to the correct path if you copy this example! */
require_once(dirname(__FILE__) . "/../../Escpos.php");

/**
 * Install the printer using USB printing support, and the "Generic / Text Only" driver,
 * then share it (you can use a firewall so that it can only be seen locally).
 * 
 * Use a WindowsPrintConnector with the share name to print.
 * 
 * Troubleshooting: Fire up a command prompt, and ensure that (if your printer is shared as
 * "Receipt Printer), the following commands work:
 * 
 * 	echo "Hello World" > testfile
 * 	copy testfile "\\%COMPUTERNAME%\Receipt Printer"
 * 	del testfile
 */
try {
	// Enter the share name for your USB printer here
	$connector = null;
	//$connector = new WindowsPrintConnector("Receipt Printer");

	/* Print a "Hello world" receipt" */
	$printer = new Escpos($connector);
	$printer -> text("Hello World!\n");
	$printer -> cut();
	
	/* Close printer */
	$printer -> close();
} catch(Exception $e) {
	echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}
