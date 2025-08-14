<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$composer_autoload = __DIR__ . "/../vendor/autoload.php";
require_once($composer_autoload);

/**
 * Used in many of the tests to to output known-correct
 * strings for use in tests.
 */
function friendlyBinary($in)
{
    if (is_array($in)) {
        $out = array();
        foreach ($in as $line) {
            $out[] = friendlyBinary($line);
        }
        return "[" . implode(", ", $out) . "]";
    }
    if (strlen($in) == 0) {
        return $in;
    }
    /* Print out binary data with PHP \x00 escape codes,
	 for builting test cases. */
    $chars = str_split($in);
    foreach ($chars as $i => $c) {
        $code = ord($c);
        if ($code < 32 || $code > 126) {
            $chars[$i] = "\\x" . bin2hex($c);
        }
    }
    return implode($chars);
}
