#!/usr/bin/env php
<?php

$path = __DIR__ . '/';

$res=@include_once $path.'/../htdocs/master.inc.php';
if (! $res) @include_once '../master.inc.php';
if (! $res) @include_once './master.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


// Generate an object sample
$object = new stdClass();
$object->aaa = 'aaa';
$object->bbb = 'bbb';
$object->thirdparty = new stdClass();
$tmp = new Societe($db);
$tmp->name = 'MyBigCompany';
foreach ($tmp as $key=>$value)
{
	if (!in_array($key, array(
		'name', 'name_alias', 'ref_ext', 'address', 'zip', 'town', 'state_code', 'country_code'
	))) continue; // Discard if not into a dedicated list
	if (!is_object($value)) $object->thirdparty->{$key} = $value;
}


// Show information
print "\n";
print "*** PHP Version : ".PHP_VERSION." - Dolibarr Version : ".DOL_VERSION."\n";

print "*** print_r() of object used to generate the key to hash for blockedlog on the object sample:\n";
print print_r($object, true);
print "*** We build hash(256) of this string:\n";
print hash('sha256', print_r($object, true));
print "\n";

print "*** When it is serialized() to store in db, we got:\n";
print serialize($object);
print "\n";

print "*** And when it is print_r(unserialized()) to reuse it:\n";
print print_r(unserialize(serialize($object)), true);
print "*** We build hash(256) of this string:\n";
print hash('sha256', print_r(unserialize(serialize($object)), true));
print "\n";

print "\n";

//print print_r(unserialize(serialize($object)));
