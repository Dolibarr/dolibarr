<?php
// This source file must be UTF-8 encoded

$filename='filewithé';

print 'Test to create a file on disk'."\n";
$s=fopen('/tmp/'.$filename,'w');
fclose($s);

print 'Files has been created. Check its name from your explorer'."\n";
?>