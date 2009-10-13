Requirements

None (only the 'GeoIP.dat' file is needed).  To download a free GeoIP Standard Country
database, go to
http://maxmind.com/download/geoip/database/

Install

Just place the 'geoip.inc' file somewhere according to the 'include_path' directive of
your 'php.ini' file, or just place it in the same directory as your PHP scripts.

Usage

Gets country name by hostname :

include("geoip.inc");

$gi = geoip_open("/usr/local/share/GeoIP/GeoIP.dat",GEOIP_STANDARD);

echo geoip_country_code_by_addr($gi, "24.24.24.24") . "\t" .
     geoip_country_name_by_addr($gi, "24.24.24.24") . "\n";
echo geoip_country_code_by_addr($gi, "80.24.24.24") . "\t" .
     geoip_country_name_by_addr($gi, "80.24.24.24") . "\n";

geoip_close($gi);

Memory Caching:
To enable memory caching, pass GEOIP_SHARED_MEMORY or
GEOIP_MEMORY_CACHE to the second argument of geoip_open

For GEOIP_SHARED_MEMORY, requires php >= 4.0.4,
and --enable-shmop passed at configure time, see
http://us2.php.net/manual/en/ref.shmop.php
In addition, you should call geoip_load_shared_mem
before calling geoip_open.  See sample_city.php for an
example of shared memory caching.

Working with PHP5.
geoip_country_code_by_addr should work
with PHP.  For help with the other
routines, please contact support@maxmind.com

Thanks to Jim Winstead.
