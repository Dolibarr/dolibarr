<?php

/**
 * Geometry abstract class
 */
abstract class Geometry
{
	private   $geos = null;
	protected $srid = null;
	protected $geom_type;

	// Abtract: Standard
	// -----------------
	abstract public function area();
	abstract public function boundary();
	abstract public function centroid();
	abstract public function length();
	abstract public function y();
	abstract public function x();
	abstract public function numGeometries();
	abstract public function geometryN($n);
	abstract public function startPoint();
	abstract public function endPoint();
	abstract public function isRing();            // Mssing dependancy
	abstract public function isClosed();          // Missing dependancy
	abstract public function numPoints();
	abstract public function pointN($n);
	abstract public function exteriorRing();
	abstract public function numInteriorRings();
	abstract public function interiorRingN($n);
	abstract public function dimension();
	abstract public function equals($geom);
	abstract public function isEmpty();
	abstract public function isSimple();

	// Abtract: Non-Standard
	// ---------------------
	abstract public function getBBox();
	abstract public function asArray();
	abstract public function getPoints();
	abstract public function explode();
	abstract public function greatCircleLength(); //meters
	abstract public function haversineLength(); //degrees


	// Public: Standard -- Common to all geometries
	// --------------------------------------------
	public function SRID()
	{
		return $this->srid;
	}

	public function setSRID($srid)
	{
		if ($this->geos()) {
			$this->geos()->setSRID($srid);
		}
		$this->srid = $srid;
	}

	public function envelope()
	{
		if ($this->isEmpty()) return new Polygon();

		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->envelope());
		}

		$bbox = $this->getBBox();
		$points = array (
		new Point($bbox['maxx'], $bbox['miny']),
		new Point($bbox['maxx'], $bbox['maxy']),
		new Point($bbox['minx'], $bbox['maxy']),
		new Point($bbox['minx'], $bbox['miny']),
		new Point($bbox['maxx'], $bbox['miny']),
		);

		$outer_boundary = new LineString($points);
		return new Polygon(array($outer_boundary));
	}

	public function geometryType()
	{
		return $this->geom_type;
	}

	// Public: Non-Standard -- Common to all geometries
	// ------------------------------------------------

	// $this->out($format, $other_args);
	public function out()
	{
		$args = func_get_args();

		$format = array_shift($args);
		$type_map = geoPHP::getAdapterMap();
		$processor_type = $type_map[$format];
		$processor = new $processor_type();

		array_unshift($args, $this);
		$result = call_user_func_array(array($processor, 'write'), $args);

		return $result;
	}


	// Public: Aliases
	// ---------------
	public function getCentroid()
	{
		return $this->centroid();
	}

	public function getArea()
	{
		return $this->area();
	}

	public function getX()
	{
		return $this->x();
	}

	public function getY()
	{
		return $this->y();
	}

	public function getGeos()
	{
		return $this->geos();
	}

	public function getGeomType()
	{
		return $this->geometryType();
	}

	public function getSRID()
	{
		return $this->SRID();
	}

	public function asText()
	{
		return $this->out('wkt');
	}

	public function asBinary()
	{
		return $this->out('wkb');
	}

	// Public: GEOS Only Functions
	// ---------------------------
	public function geos()
	{
		// If it's already been set, just return it
		if ($this->geos && geoPHP::geosInstalled()) {
			return $this->geos;
		}
		// It hasn't been set yet, generate it
		if (geoPHP::geosInstalled()) {
			$reader = new GEOSWKBReader();
			$this->geos = $reader->readHEX($this->out('wkb', true));
		} else {
			$this->geos = false;
		}
		return $this->geos;
	}

	public function setGeos($geos)
	{
		$this->geos = $geos;
	}

	public function pointOnSurface()
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->pointOnSurface());
		}
	}

	public function equalsExact(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->equalsExact($geometry->geos());
		}
	}

	public function relate(Geometry $geometry, $pattern = null)
	{
		if ($this->geos()) {
			if ($pattern) {
				return $this->geos()->relate($geometry->geos(), $pattern);
			} else {
				return $this->geos()->relate($geometry->geos());
			}
		}
	}

	public function checkValidity()
	{
		if ($this->geos()) {
			return $this->geos()->checkValidity();
		}
	}

	public function buffer($distance)
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->buffer($distance));
		}
	}

	public function intersection(Geometry $geometry)
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->intersection($geometry->geos()));
		}
	}

	public function convexHull()
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->convexHull());
		}
	}

	public function difference(Geometry $geometry)
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->difference($geometry->geos()));
		}
	}

	public function symDifference(Geometry $geometry)
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->symDifference($geometry->geos()));
		}
	}

	// Can pass in a geometry or an array of geometries
	public function union(Geometry $geometry)
	{
		if ($this->geos()) {
			if (is_array($geometry)) {
				$geom = $this->geos();
				foreach ($geometry as $item) {
					$geom = $geom->union($item->geos());
				}
				return geoPHP::geosToGeometry($geom);
			} else {
				return geoPHP::geosToGeometry($this->geos()->union($geometry->geos()));
			}
		}
	}

	public function simplify($tolerance, $preserveTopology = false)
	{
		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->simplify($tolerance, $preserveTopology));
		}
	}

	public function disjoint(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->disjoint($geometry->geos());
		}
	}

	public function touches(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->touches($geometry->geos());
		}
	}

	public function intersects(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->intersects($geometry->geos());
		}
	}

	public function crosses(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->crosses($geometry->geos());
		}
	}

	public function within(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->within($geometry->geos());
		}
	}

	public function contains(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->contains($geometry->geos());
		}
	}

	public function overlaps(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->overlaps($geometry->geos());
		}
	}

	public function covers(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->covers($geometry->geos());
		}
	}

	public function coveredBy(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->coveredBy($geometry->geos());
		}
	}

	public function distance(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->distance($geometry->geos());
		}
	}

	public function hausdorffDistance(Geometry $geometry)
	{
		if ($this->geos()) {
			return $this->geos()->hausdorffDistance($geometry->geos());
		}
	}

	public function project(Geometry $point, $normalized = null)
	{
		if ($this->geos()) {
			return $this->geos()->project($point->geos(), $normalized);
		}
	}

	// Public - Placeholders
	// ---------------------
	public function hasZ()
	{
		// geoPHP does not support Z values at the moment
		return false;
	}

	public function is3D()
	{
		// geoPHP does not support 3D geometries at the moment
		return false;
	}

	public function isMeasured()
	{
		// geoPHP does not yet support M values
		return false;
	}

	public function coordinateDimension()
	{
		// geoPHP only supports 2-dimensional space
		return 2;
	}

	public function z()
	{
		// geoPHP only supports 2-dimensional space
		return null;
	}

	public function m()
	{
		// geoPHP only supports 2-dimensional space
		return null;
	}
}
