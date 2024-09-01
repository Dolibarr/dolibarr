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


// Show information
print "\n";
print "*** PHP Version : ".PHP_VERSION." - Dolibarr Version : ".DOL_VERSION."\n";

$a="Wéarning: Permanently added '1.2.3.4' (ECDSA) to the list of known hosts.<br>
receiving file list ... done<br>
rsync: send_files failed to open &quot;aaa: Permission denied (13)<br>
<br>
Number of files: 15,726 (reg: 14,418, dir: 1,308)<br>
Number of created files: 0<br>
Number of deleted files: 0<br>
Number of regular files transferred: 0<br>
Total file size: 210,285,070 bytes<br>
Total transferred file size: 0 bytes<br>
Literal data: 0 bytes<br>
Matched data: 0 bytes<br>
File list size: 408,056<br>
File list generation time: 0.163 seconds<br>
File list transfer time: 0.000 seconds<br>
Total bytes sent: 820<br>
Total bytes received: 408,215<br>
<br>
sent 820 bytes  received 408,215 bytes  272,690.00 bytes/sec<br>
total size is 210,285,070  speedup is 514.10<br>
rsync error: some files/attrs were not transferred (see previous errors) (code 23) at main.c(1682) [generator=3.1.3]";

print $a;

print "\n\n\n";

//print dolGetFirstLineOfText($a, 7);
print dol_escape_htmltag($a, 1, 1);

print "\n";

//print print_r(unserialize(serialize($object)));
