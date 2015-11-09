<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
require_once(dirname(__FILE__) . "/../Escpos.php");
require_once(dirname(__FILE__) . "/../src/DummyPrintConnector.php");

/**
 * Used in many of the tests to to output known-correct
 * strings for use in tests. 
 */
function friendlyBinary($in) {
	if(strlen($in) == 0) {
		return $in;
	}
	/* Print out binary data with PHP \x00 escape codes,
	 for builting test cases. */
	$chars = str_split($in);
	foreach($chars as $i => $c) {
		$code = ord($c);
		if($code < 32 || $code > 126) {
			$chars[$i] = "\\x" . bin2hex($c);
		}
	}
	return implode($chars);
}
