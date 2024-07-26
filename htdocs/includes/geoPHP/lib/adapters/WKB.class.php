<?php
/*
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/WKB encoder/decoder
 *
 */
class WKB extends GeoAdapter
{

	private $dimension = 2;
	private $z = false;
	private $m = false;

	/**
	 * Read WKB into geometry objects
	 *
	 * @param string $wkb
	 *   Well-known-binary string
	 * @param bool $is_hex_string
	 *   If this is a hexedecimal string that is in need of packing
	 *
	 * @return Geometry
	 */
	public function read($wkb, $is_hex_string = false)
	{
		if ($is_hex_string) {
			$wkb = pack('H*', $wkb);
		}

		if (empty($wkb)) {
			throw new Exception('Cannot read empty WKB geometry. Found ' . gettype($wkb));
		}

		$mem = fopen('php://memory', 'r+');
		fwrite($mem, $wkb);
		fseek($mem, 0);

		$geometry = $this->getGeometry($mem);
		fclose($mem);
		return $geometry;
	}

	function getGeometry(&$mem)
	{
		$base_info = unpack("corder/ctype/cz/cm/cs", fread($mem, 5));
		if ($base_info['order'] !== 1) {
			throw new Exception('Only NDR (little endian) SKB format is supported at the moment');
		}

		if ($base_info['z']) {
			$this->dimension++;
			$this->z = true;
		}
		if ($base_info['m']) {
			$this->dimension++;
			$this->m = true;
		}

		// If there is SRID information, ignore it - use EWKB Adapter to get SRID support
		if ($base_info['s']) {
			fread($mem, 4);
		}

		switch ($base_info['type']) {
			case 1:
			return $this->getPoint($mem);
			case 2:
			return $this->getLinstring($mem);
			case 3:
		return $this->getPolygon($mem);
			case 4:
		return $this->getMulti($mem, 'point');
			case 5:
		return $this->getMulti($mem, 'line');
			case 6:
		return $this->getMulti($mem, 'polygon');
			case 7:
		return $this->getMulti($mem, 'geometry');
		}
	}

	function getPoint(&$mem)
	{
		$point_coords = unpack("d*", fread($mem, $this->dimension*8));
		if (!empty($point_coords)) {
			return new Point($point_coords[1], $point_coords[2]);
		} else {
			return new Point(); // EMPTY point
		}
	}

	function getLinstring(&$mem)
	{
		// Get the number of points expected in this string out of the first 4 bytes
		$line_length = unpack('L', fread($mem, 4));

		// Return an empty linestring if there is no line-length
		if (!$line_length[1]) return new LineString();

		// Read the nubmer of points x2 (each point is two coords) into decimal-floats
		$line_coords = unpack('d*', fread($mem, $line_length[1]*$this->dimension*8));

		// We have our coords, build up the linestring
		$components = array();
		$i = 1;
		$num_coords = count($line_coords);
		while ($i <= $num_coords) {
			$components[] = new Point($line_coords[$i], $line_coords[$i+1]);
			$i += 2;
		}
		return new LineString($components);
	}

	function getPolygon(&$mem)
	{
		// Get the number of linestring expected in this poly out of the first 4 bytes
		$poly_length = unpack('L', fread($mem, 4));

		$components = array();
		$i = 1;
		while ($i <= $poly_length[1]) {
			$components[] = $this->getLinstring($mem);
			$i++;
		}
		return new Polygon($components);
	}

	function getMulti(&$mem, $type)
	{
		// Get the number of items expected in this multi out of the first 4 bytes
		$multi_length = unpack('L', fread($mem, 4));

		$components = array();
		$i = 1;
		while ($i <= $multi_length[1]) {
			$components[] = $this->getGeometry($mem);
			$i++;
		}
		switch ($type) {
			case 'point':
			return new MultiPoint($components);
			case 'line':
			return new MultiLineString($components);
			case 'polygon':
		return new MultiPolygon($components);
			case 'geometry':
		return new GeometryCollection($components);
		}
	}

	/**
	 * Serialize geometries into WKB string.
	 *
	 * @param Geometry $geometry
	 *
	 * @return string The WKB string representation of the input geometries
	 */
	public function write(Geometry $geometry, $write_as_hex = false)
	{
		// We always write into NDR (little endian)
		$wkb = pack('c', 1);

		switch ($geometry->getGeomType()) {
			case 'Point';
				$wkb .= pack('L', 1);
				$wkb .= $this->writePoint($geometry);
			break;
			case 'LineString';
				$wkb .= pack('L', 2);
				$wkb .= $this->writeLineString($geometry);
			break;
			case 'Polygon';
				$wkb .= pack('L', 3);
				$wkb .= $this->writePolygon($geometry);
		break;
			case 'MultiPoint';
				$wkb .= pack('L', 4);
				$wkb .= $this->writeMulti($geometry);
		break;
			case 'MultiLineString';
				$wkb .= pack('L', 5);
				$wkb .= $this->writeMulti($geometry);
		break;
			case 'MultiPolygon';
				$wkb .= pack('L', 6);
				$wkb .= $this->writeMulti($geometry);
		break;
			case 'GeometryCollection';
				$wkb .= pack('L', 7);
				$wkb .= $this->writeMulti($geometry);
		break;
		}

		if ($write_as_hex) {
			$unpacked = unpack('H*', $wkb);
			return $unpacked[1];
		} else {
			return $wkb;
		}
	}

	function writePoint($point)
	{
		// Set the coords
		if (!$point->isEmpty()) {
			$wkb = pack('dd', $point->x(), $point->y());
			return $wkb;
		} else {
			return '';
		}
	}

	function writeLineString($line)
	{
		// Set the number of points in this line
		$wkb = pack('L', $line->numPoints());

		// Set the coords
		foreach ($line->getComponents() as $point) {
			$wkb .= pack('dd', $point->x(), $point->y());
		}

		return $wkb;
	}

	function writePolygon($poly)
	{
		// Set the number of lines in this poly
		$wkb = pack('L', $poly->numGeometries());

		// Write the lines
		foreach ($poly->getComponents() as $line) {
			$wkb .= $this->writeLineString($line);
		}

		return $wkb;
	}

	function writeMulti($geometry)
	{
		// Set the number of components
		$wkb = pack('L', $geometry->numGeometries());

		// Write the components
		foreach ($geometry->getComponents() as $component) {
			$wkb .= $this->write($component);
		}

		return $wkb;
	}
}
