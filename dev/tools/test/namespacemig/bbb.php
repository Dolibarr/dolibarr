<?php

//use \Aaa as Aaa;

use Dolibarr\Aaa as Aaa;
use function Dolibarr\faaa as faaa;	// Need php 5.6+
//use const Dolibarr\AAA;

//use Bbb as Bbb;

require './main.inc.php';
require './aaa.class.php';
require './bbb.class.php';

$bbb = new Bbb();
$bbb->do();

$aaa = new Aaa();
$aaa->do();

echo $aaa::AAA."\n";
echo $bbb::BBB."\n";

echo Aaa::AAA."\n";
echo Bbb::BBB."\n";

echo faaa()."\n";
echo fbbb()."\n";

echo "globalaaa=$globalaaa\n";
echo "globalbbb=$globalbbb\n";
