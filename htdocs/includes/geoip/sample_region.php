#!/usr/bin/php -q
<?php

// This code demonstrates how to lookup the country and region by IP Address
// It is designed to work with GeoIP Region available from MaxMind

include("geoip.inc");
include("geoipregionvars.php");

$gi = geoip_open("/usr/local/share/GeoIP/GeoIPRegion.dat",GEOIP_STANDARD);

list ($countrycode,$region) = geoip_region_by_addr($gi,"24.24.24.24");
print $countrycode . " " . $region . " " . $GEOIP_REGION_NAME[$countrycode][$region] . "\n";
list ($countrycode,$region) = geoip_region_by_addr($gi,"80.24.24.24");
print $countrycode . " " . $region . " " . $GEOIP_REGION_NAME[$countrycode][$region] . "\n";
list ($countrycode,$region) = geoip_region_by_addr($gi,"199.243.137.184");
print $countrycode . " " . $region . " " . $GEOIP_REGION_NAME[$countrycode][$region] . "\n";
geoip_close($gi);

?>
