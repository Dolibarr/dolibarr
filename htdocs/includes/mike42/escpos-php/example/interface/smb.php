<?php
/* Change to the correct path if you copy this example! */
require_once(dirname(__FILE__) . "/../../Escpos.php");

/**
 * Install the printer using USB printing support, and the "Generic / Text Only" driver,
 * then share it.
 * 
 * Use a WindowsPrintConnector with the share name to print. This works on either
 * Windows or Linux.
 * 
 * Troubleshooting: Fire up a command prompt/terminal, and ensure that (if your printer is
 * shared as "Receipt Printer"), the following commands work.
 * 
 * Windows: (use an appropriate "net use" command if you need authentication)
 * 	echo "Hello World" > testfile
 *  ## If you need authentication, use "net use" to hook up the printer:
 * 	# net use "\\computername\Receipt Printer" /user:Guest
 * 	# net use "\\computername\Receipt Printer" /user:Bob secret
 * 	# net use "\\computername\Receipt Printer" /user:workgroup\Bob secret
 * 	copy testfile "\\computername\Receipt Printer"
 * 	del testfile
 * 
 * GNU/Linux:
 * 	# No authentication
 * 	echo "Hello World" | smbclient "//computername/Receipt Printer" -c "print -" -N
 * 	# Guest login
 * 	echo "Hello World" | smbclient "//computername/Receipt Printer" -U Guest -c "print -" -N
 *  # Basic username/password
 * 	echo "Hello World" | smbclient "//computername/Receipt Printer" secret -U "Bob" -c "print -"
 * 	# Including domain name
 * 	echo "Hello World" | smbclient "//computername/Receipt Printer" secret -U "workgroup\\Bob" -c "print -"
 */
try {
	// Enter the share name for your printer here, as a smb:// url format
	$connector = null;
	//$connector = new WindowsPrintConnector("smb://computername/Receipt Printer");
	//$connector = new WindowsPrintConnector("smb://Guest@computername/Receipt Printer");
	//$connector = new WindowsPrintConnector("smb://FooUser:secret@computername/workgroup/Receipt Printer");
	//$connector = new WindowsPrintConnector("smb://User:secret@computername/Receipt Printer");
	
	/* Print a "Hello world" receipt" */
	$printer = new Escpos($connector);
	$printer -> text("Hello World!\n");
	$printer -> cut();
	
	/* Close printer */
	$printer -> close();
} catch(Exception $e) {
	echo "Couldn't print to this printer: " . $e -> getMessage() . "\n";
}
