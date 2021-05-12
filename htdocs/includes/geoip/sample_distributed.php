#!/usr/bin/php -q
<?php

include("geoipcity.inc");
include("Net/DNS.php");

# replace LICENSE_KEY_HERE with your license key
$l = "LICENSE_KEY_HERE";
$ip = "24.24.24.24";

if ($l == "LICENSE_KEY_HERE") {
  print "Error, must edit sample_distributed.php to replace LICENSE_KEY_HERE\n";
  exit;
}

$str = getdnsattributes($l,$ip);
$r = getrecordwithdnsservice($str);
print "country code: " . $r->country_code . "\n";
print "country code3: " . $r->country_code3 . "\n";
print "country name: " . $r->country_name . "\n";
print "city: " . $r->city . "\n";
print "region: " . $r->region . "\n";
print "region name: " . $r->regionname . "\n";
print "postal_code: " . $r->postal_code . "\n";
print "latitude: " . $r->latitude . "\n";
print "longitude: " . $r->longitude . "\n";
print "area code: " . $r->areacode . "\n";
print "dma code: " . $r->dmacode . "\n";
print "isp: " . $r->isp . "\n";
print "org: " . $r->org . "\n";
?>
