[![Build Status](https://travis-ci.org/phayes/geoPHP.svg?branch=master)](https://travis-ci.org/phayes/geoPHP)

[geophp.net](https://geophp.net "GeoPHP homepage")


GeoPHP is a open-source native PHP library for doing geometry operations. It is written entirely in PHP and 
can therefore run on shared hosts. It can read and write a wide variety of formats: WKT (including EWKT), WKB (including EWKB), GeoJSON, 
KML, GPX, and GeoRSS. It works with all Simple-Feature geometries (Point, LineString, Polygon, GeometryCollection etc.)
and can be used to get centroids, bounding-boxes, area, and a wide variety of other useful information. 

geoPHP also helpfully wraps the GEOS php extension so that applications can get a transparent performance 
increase when GEOS is installed on the server. When GEOS is installed, geoPHP also becomes
fully compliant with the OpenGIS® Implementation Standard for Geographic information. With GEOS you get the 
full-set of openGIS functions in PHP like Union, IsWithin, Touches etc. This means that applications
get a useful "core-set" of geometry operations that work in all environments, and an "extended-set"of operations 
for environments that have GEOS installed. 

See the 'getting started' section below for references and examples of everything that geoPHP can do.

This project is currently looking for co-maintainers. If you think you can help out, please send me a 
message. Forks are also welcome, please issue pull requests and I will merge them into the main branch.

Getting Started
-----------------------

 * The lastest stable version can always be downloaded at: <https://phayes.github.io/bin/current/geoPHP/geoPHP.tar.gz>
 * Read the API Reference at: <https://geophp.net/api.html>
 * Examples
   * Using geoPHP as a GIS format converter: <http://github.com/phayes/geoPHP/wiki/Example-format-converter>
 * Other Interesting Links:
   * Learn about GEOS integration at: <https://geophp.net/geos.html>

Example usage
-------------------------------------------------

```php
<?php
include_once('geoPHP.inc');

// Polygon WKT example
$polygon = geoPHP::load('POLYGON((1 1,5 1,5 5,1 5,1 1),(2 2,2 3,3 3,3 2,2 2))','wkt');
$area = $polygon->getArea();
$centroid = $polygon->getCentroid();
$centX = $centroid->getX();
$centY = $centroid->getY();

print "This polygon has an area of ".$area." and a centroid with X=".$centX." and Y=".$centY;

// MultiPoint json example
print "<br/>";
$json = 
'{
   "type": "MultiPoint",
   "coordinates": [
       [100.0, 0.0], [101.0, 1.0]
   ]
}';

$multipoint = geoPHP::load($json, 'json');
$multipoint_points = $multipoint->getComponents();
$first_wkt = $multipoint_points[0]->out('wkt');

print "This multipoint has ".$multipoint->numGeometries()." points. The first point has a wkt representation of ".$first_wkt;
```
=======
	
More Examples
-------------------------------------------------
	
The Well Known Text (WKT) and Well Known Binary (WKB) support is ideal for integrating with MySQL's or PostGIS's spatial capability. 
Once you have SELECTed your data with `'AsText('geo_field')'` or `'AsBinary('geo_field')'`, you can put it straight into 
geoPHP (can be wkt or wkb, but must be the same as how you extracted it from your database):

    $geom = geoPHP::load($dbRow,'wkt');

You can collect multiple geometries into one (note that you must use wkt for this):

    $geom = geoPHP::load("GEOMETRYCOLLECTION(".$dbString1.",".$dbString2.")",'wkt');

Calling get components returns the sub-geometries within a geometry as an array.

    $geom2 = geoPHP::load("GEOMETRYCOLLECTION(LINESTRING(1 1,5 1,5 5,1 5,1 1),LINESTRING(2 2,2 3,3 3,3 2,2 2))");
    $geomComponents = $geom2->getComponents();    //an array of the two linestring geometries
    $linestring1 = $geomComponents[0]->getComponents();	//an array of the first linestring's point geometries
    $linestring2 = $geomComponents[1]->getComponents();
    echo $linestring1[0]->x() . ", " . $linestring1[0]->y();    //outputs '1, 1'

An alternative is to use the `asArray()` method. Using the above geometry collection of two linestrings, 
    
	$geometryArray = $geom2->asArray();
	echo $geometryArray[0][0][0] . ", " . $geometryArray[0][0][1];    //outputs '1, 1'

Clearly, more complex analysis is possible.
    
	echo $geom2->envelope()->area();


Working with PostGIS
---------------------
geoPHP, through it's EWKB adapter, has good integration with postGIS. Here's an example of reading and writing postGIS geometries

```php
<?php
include_once('geoPHP.inc');
$host =     'localhost';
$database = 'phayes';
$table =    'test';
$column =   'geom';
$user =     'phayes';
$pass =     'supersecret';

$connection = pg_connect("host=$host dbname=$database user=$user password=$pass");

// Working with PostGIS and Extended-WKB
// ----------------------------

// Using asBinary and GeomFromWKB in PostGIS
$result = pg_fetch_all(pg_query($connection, "SELECT asBinary($column) as geom FROM $table"));
foreach ($result as $item) {
  $wkb = pg_unescape_bytea($item['geom']); // Make sure to unescape the hex blob
  $geom = geoPHP::load($wkb, 'ewkb'); // We now a full geoPHP Geometry object
  
  // Let's insert it back into the database
  $insert_string = pg_escape_bytea($geom->out('ewkb'));
  pg_query($connection, "INSERT INTO $table ($column) values (GeomFromWKB('$insert_string'))");
}

// Using a direct SELECT and INSERTs in PostGIS without using wrapping functions
$result = pg_fetch_all(pg_query($connection, "SELECT $column as geom FROM $table"));
foreach ($result as $item) {
  $wkb = pack('H*',$item['geom']);   // Unpacking the hex blob
  $geom = geoPHP::load($wkb, 'ewkb'); // We now have a geoPHP Geometry
  
  // To insert directly into postGIS we need to unpack the WKB
  $unpacked = unpack('H*', $geom->out('ewkb'));
  $insert_string = $unpacked[1];
  pg_query($connection, "INSERT INTO $table ($column) values ('$insert_string')");
}
```


Credit
-------------------------------------------------

Maintainer: Patrick Hayes

Additional Contributors:

 * GeoMemes Research (<http://www.geomemes.com>)
 * HighWire Press (<http://www.highwire.org>) and GeoScienceWorld (<http://www.geoscienceworld.org>)
 * Arnaud Renevier (gisconverter.php) <https://github.com/arenevier/gisconverter.php>
 * Dave Tarc <https://github.com/dtarc>
 * Elliott Hunston (documentation) <https://github.com/ejh>

This library is open-source and dual-licensed under both the Modified BSD License and GPLv2. Either license may be used at your option.           
