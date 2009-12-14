<?php
// This source file must be UTF-8 encoded

$filename='filewithé';

print 'Test to create a file on disk'."\n";
print 'ENV[LANG]='.$_ENV["LANG"]."\n";
print 'ENV[LANGUAGE]='.$_ENV["LANGUAGE"]."\n";

// Si LANG contient UTF8, system en UTF8, pas de conversion requise pour fopen
$s=fopen('/tmp/'.$filename,'w');
fclose($s);

print 'Files has been created. Check its name from your explorer'."\n";
?>