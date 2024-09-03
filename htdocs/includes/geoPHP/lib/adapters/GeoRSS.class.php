<?php
/*
 * Copyright (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/GeoRSS encoder/decoder
 */
class GeoRSS extends GeoAdapter
{
	private $namespace = false;
	private $nss = ''; // Name-space string. eg 'georss:'

	/**
	 * Read GeoRSS string into geometry objects
	 *
	 * @param string $georss - an XML feed containing geoRSS
	 *
	 * @return Geometry|GeometryCollection
	 */
	public function read($gpx)
	{
		return $this->geomFromText($gpx);
	}

	/**
	 * Serialize geometries into a GeoRSS string.
	 *
	 * @param Geometry $geometry
	 *
	 * @return string The georss string representation of the input geometries
	 */
	public function write(Geometry $geometry, $namespace = false)
	{
		if ($namespace) {
			$this->namespace = $namespace;
			$this->nss = $namespace.':';
		}
		return $this->geometryToGeoRSS($geometry);
	}

	public function geomFromText($text)
	{
		// Change to lower-case, strip all CDATA, and de-namespace
		$text = strtolower($text);
		$text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $text);

		// Load into DOMDOcument
		$xmlobj = new DOMDocument();
		@$xmlobj->loadXML($text);
		if ($xmlobj === false) {
			throw new Exception("Invalid GeoRSS: ". $text);
		}

		$this->xmlobj = $xmlobj;
		try {
			$geom = $this->geomFromXML();
		} catch (InvalidText $e) {
			throw new Exception("Cannot Read Geometry From GeoRSS: ". $text);
		} catch (Exception $e) {
			throw $e;
		}

		return $geom;
	}

	protected function geomFromXML()
	{
		$geometries = array();
		$geometries = array_merge($geometries, $this->parsePoints());
		$geometries = array_merge($geometries, $this->parseLines());
		$geometries = array_merge($geometries, $this->parsePolygons());
		$geometries = array_merge($geometries, $this->parseBoxes());
		$geometries = array_merge($geometries, $this->parseCircles());

		if (empty($geometries)) {
			throw new Exception("Invalid / Empty GeoRSS");
		}

		return geoPHP::geometryReduce($geometries);
	}

	protected function getPointsFromCoords($string)
	{
		$coords = array();

		if (empty($string)) {
			return $coords;
		}

		$latlon = explode(' ', $string);
		foreach ($latlon as $key => $item) {
			if (!($key % 2)) {
				// It's a latitude
				$lat = $item;
			} else {
				// It's a longitude
				$lon = $item;
				$coords[] = new Point($lon, $lat);
			}
		}
		return $coords;
	}

	protected function parsePoints()
	{
		$points = array();
		$pt_elements = $this->xmlobj->getElementsByTagName('point');
		foreach ($pt_elements as $pt) {
			if ($pt->hasChildNodes()) {
				$point_array = $this->getPointsFromCoords(trim($pt->firstChild->nodeValue));
			}
			if (!empty($point_array)) {
				$points[] = $point_array[0];
			} else {
				$points[] = new Point();
			}
		}
		return $points;
	}

	protected function parseLines()
	{
		$lines = array();
		$line_elements = $this->xmlobj->getElementsByTagName('line');
		foreach ($line_elements as $line) {
			$components = $this->getPointsFromCoords(trim($line->firstChild->nodeValue));
			$lines[] = new LineString($components);
		}
		return $lines;
	}

	protected function parsePolygons()
	{
		$polygons = array();
		$poly_elements = $this->xmlobj->getElementsByTagName('polygon');
		foreach ($poly_elements as $poly) {
			if ($poly->hasChildNodes()) {
				$points = $this->getPointsFromCoords(trim($poly->firstChild->nodeValue));
				$exterior_ring = new LineString($points);
				$polygons[] = new Polygon(array($exterior_ring));
			} else {
				// It's an EMPTY polygon
				$polygons[] = new Polygon();
			}
		}
		return $polygons;
	}

	// Boxes are rendered into polygons
	protected function parseBoxes()
	{
		$polygons = array();
		$box_elements = $this->xmlobj->getElementsByTagName('box');
		foreach ($box_elements as $box) {
			$parts = explode(' ', trim($box->firstChild->nodeValue));
			$components = array(
			new Point($parts[3], $parts[2]),
			new Point($parts[3], $parts[0]),
			new Point($parts[1], $parts[0]),
			new Point($parts[1], $parts[2]),
			new Point($parts[3], $parts[2]),
			);
			$exterior_ring = new LineString($components);
			$polygons[] = new Polygon(array($exterior_ring));
		}
		return $polygons;
	}

	// Circles are rendered into points
	// @@TODO: Add good support once we have circular-string geometry support
	protected function parseCircles()
	{
		$points = array();
		$circle_elements = $this->xmlobj->getElementsByTagName('circle');
		foreach ($circle_elements as $circle) {
			$parts = explode(' ', trim($circle->firstChild->nodeValue));
			$points[] = new Point($parts[1], $parts[0]);
		}
		return $points;
	}

	protected function geometryToGeoRSS($geom)
	{
		$type = strtolower($geom->getGeomType());
		switch ($type) {
			case 'point':
			return $this->pointToGeoRSS($geom);
			break;
			case 'linestring':
			return $this->linestringToGeoRSS($geom);
			break;
			case 'polygon':
		return $this->PolygonToGeoRSS($geom);
			break;
			case 'multipoint':
			case 'multilinestring':
			case 'multipolygon':
			case 'geometrycollection':
		return $this->collectionToGeoRSS($geom);
			break;
		}
		return $output;
	}

	private function pointToGeoRSS($geom)
	{
		$out = '<'.$this->nss.'point>';
		if (!$geom->isEmpty()) {
			$out .= $geom->getY().' '.$geom->getX();
		}
		$out .= '</'.$this->nss.'point>';
		return $out;
	}

	private function linestringToGeoRSS($geom)
	{
		$output = '<'.$this->nss.'line>';
		foreach ($geom->getComponents() as $k => $point) {
			$output .= $point->getY().' '.$point->getX();
			if ($k < ($geom->numGeometries() -1)) $output .= ' ';
		}
		$output .= '</'.$this->nss.'line>';
		return $output;
	}

	private function polygonToGeoRSS($geom)
	{
		$output = '<'.$this->nss.'polygon>';
		$exterior_ring = $geom->exteriorRing();
		foreach ($exterior_ring->getComponents() as $k => $point) {
			$output .= $point->getY().' '.$point->getX();
			if ($k < ($exterior_ring->numGeometries() -1)) $output .= ' ';
		}
		$output .= '</'.$this->nss.'polygon>';
		return $output;
	}

	public function collectionToGeoRSS($geom)
	{
		$georss = '<'.$this->nss.'where>';
		$components = $geom->getComponents();
		foreach ($geom->getComponents() as $comp) {
			$georss .= $this->geometryToGeoRSS($comp);
		}

		$georss .= '</'.$this->nss.'where>';

		return $georss;
	}
}
