#!/usr/bin/env php
<?php

$path = __DIR__ . '/';


$res=@include_once $path.'/../htdocs/master.inc.php';
$res=@include_once $path.'/../../htdocs/master.inc.php';
if (! $res) {
	@include_once '../../master.inc.php';
}
if (! $res) {
	@include_once '../master.inc.php';
}
if (! $res) {
	@include_once './master.inc.php';
}


print "Decode with dol_decode a value crypted with dol_encode:.... in conf.php file\n";

print dol_decode('123456789');

print "\n";

//print print_r(unserialize(serialize($object)));
