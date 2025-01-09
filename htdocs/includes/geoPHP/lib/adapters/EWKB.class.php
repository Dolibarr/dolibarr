<?php
/**
 * EWKB (Extended Well Known Binary) Adapter
 */
class EWKB extends WKB
{

	/**
	 * Read WKB binary string into geometry objects
	 *
	 * @param string $wkb An Extended-WKB binary string
	 *
	 * @return Geometry
	 */
	public function read($wkb, $is_hex_string = false)
	{
		if ($is_hex_string) {
			$wkb = pack('H*', $wkb);
		}

		// Open the wkb up in memory so we can examine the SRID
		$mem = fopen('php://memory', 'r+');
		fwrite($mem, $wkb);
		fseek($mem, 0);
		$base_info = unpack("corder/ctype/cz/cm/cs", fread($mem, 5));
		if ($base_info['s']) {
			$srid = current(unpack("Lsrid", fread($mem, 4)));
		} else {
			$srid = null;
		}
		fclose($mem);

		// Run the wkb through the normal WKB reader to get the geometry
		$wkb_reader = new WKB();
		$geom = $wkb_reader->read($wkb);

		// If there is an SRID, add it to the geometry
		if ($srid) {
			$geom->setSRID($srid);
		}

		return $geom;
	}

	/**
	 * Serialize geometries into an EWKB binary string.
	 *
	 * @param Geometry $geometry
	 *
	 * @return string The Extended-WKB binary string representation of the input geometries
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
}
