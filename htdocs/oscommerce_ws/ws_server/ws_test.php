<?php
set_magic_quotes_runtime(0);

// Soap Server.
require_once('./lib/nusoap.php');

// Create the soap Object
$s = new soap_server;

// Register a method available for clients
$s->register('hello');

function hello($name){

$returnedString = "Hello de Tetiaroa ".$name." !";
return $returnedString;

}

// Return the results.
$s->service($HTTP_RAW_POST_DATA);
?>
