<?php

$a = microtime(true);

$i = 0;
while ($i < 1000000) {
	$key = '1234567890111213141516171819'.$i;
	if ($i == 1000) {
		$key = 'MAIN_MODULE_AAAAAiiiiiiiiiiiiiiiiiiiiiiiiiiiii';
	}

	//if (preg_match('/^MAIN_MODULE_/', $key)) {
	//if (substr($key, 0, 12) == 'MAIN_MODULE_') {
	if (strpos($key, 'MAIN_MODULE_') === 0) {
		print "Found\n";
	}
	$i++;
}

$b = microtime(true);

print $b - $a."\n";
