#!/usr/bin/php -q
<?php

include("geoip.inc");

$gi = geoip_open("/usr/local/share/GeoIP/GeoIPNetSpeed.dat",GEOIP_STANDARD);

$netspeed = geoip_country_id_by_addr($gi,"24.24.24.24");

//print $n . "\n";
if ($netspeed == GEOIP_UNKNOWN_SPEED){
  print "Unknown\n";
}else if ($netspeed == GEOIP_DIALUP_SPEED){
  print "Dailup\n";
}else if ($netspeed == GEOIP_CABLEDSL_SPEED){
  print "Cable/DSL\n";
}else if ($netspeed == GEOIP_CORPORATE_SPEED){
  print "Corporate\n";
}

geoip_close($gi);

?>
