<?php

/**
 * Polygon: A polygon is a plane figure that is bounded by a closed path,
 * composed of a finite sequence of straight line segments
 */
class Polygon extends Collection
{
	protected $geom_type = 'Polygon';

	// The boundary of a polygin is it's outer ring
	public function boundary()
	{
		return $this->exteriorRing();
	}

	public function area($exterior_only = false, $signed = false)
	{
		if ($this->isEmpty()) return 0;

		if ($this->geos() && $exterior_only == false) {
			return $this->geos()->area();
		}

		$exterior_ring = $this->components[0];
		$pts = $exterior_ring->getComponents();

		$c = count($pts);
		if ((int) $c == '0') return null;
		$a = '0';
		foreach ($pts as $k => $p) {
			$j = ($k + 1) % $c;
			$a = $a + ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
		}

		if ($signed) $area = ($a / 2);
		else $area = abs(($a / 2));

		if ($exterior_only == true) {
			return $area;
		}
		foreach ($this->components as $delta => $component) {
			if ($delta != 0) {
				$inner_poly = new Polygon(array($component));
				$area -= $inner_poly->area();
			}
		}
		return $area;
	}

	public function centroid()
	{
		if ($this->isEmpty()) return null;

		if ($this->geos()) {
			return geoPHP::geosToGeometry($this->geos()->centroid());
		}

		$exterior_ring = $this->components[0];
		$pts = $exterior_ring->getComponents();

		$c = count($pts);
		if ((int) $c == '0') return null;
		$cn = array('x' => '0', 'y' => '0');
		$a = $this->area(true, true);

		// If this is a polygon with no area. Just return the first point.
		if ($a == 0) {
			return $this->exteriorRing()->pointN(1);
		}

		foreach ($pts as $k => $p) {
			$j = ($k + 1) % $c;
			$P = ($p->getX() * $pts[$j]->getY()) - ($p->getY() * $pts[$j]->getX());
			$cn['x'] = $cn['x'] + ($p->getX() + $pts[$j]->getX()) * $P;
			$cn['y'] = $cn['y'] + ($p->getY() + $pts[$j]->getY()) * $P;
		}

		$cn['x'] = $cn['x'] / ( 6 * $a);
		$cn['y'] = $cn['y'] / ( 6 * $a);

		$centroid = new Point($cn['x'], $cn['y']);
		return $centroid;
	}

	/**
	 * Find the outermost point from the centroid
	 *
	 * @returns Point The outermost point
	 */
	public function outermostPoint()
	{
		$centroid = $this->getCentroid();

		$max = array('length' => 0, 'point' => null);

		foreach ($this->getPoints() as $point) {
			$lineString = new LineString(array($centroid, $point));

			if ($lineString->length() > $max['length']) {
				$max['length'] = $lineString->length();
				$max['point'] = $point;
			}
		}

		return $max['point'];
	}

	public function exteriorRing()
	{
		if ($this->isEmpty()) return new LineString();
		return $this->components[0];
	}

	public function numInteriorRings()
	{
		if ($this->isEmpty()) return 0;
		return $this->numGeometries()-1;
	}

	public function interiorRingN($n)
	{
		return $this->geometryN($n+1);
	}

	public function dimension()
	{
		if ($this->isEmpty()) return 0;
		return 2;
	}

	public function isSimple()
	{
		if ($this->geos()) {
			return $this->geos()->isSimple();
		}

		$segments = $this->explode();

		foreach ($segments as $i => $segment) {
			foreach ($segments as $j => $check_segment) {
				if ($i != $j) {
					if ($segment->lineSegmentIntersect($check_segment)) {
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * For a given point, determine whether it's bounded by the given polygon.
	 * Adapted from http://www.assemblysys.com/dataServices/php_pointinpolygon.php
	 * @see http://en.wikipedia.org/wiki/Point%5Fin%5Fpolygon
	 *
	 * @param Point $point
	 * @param boolean $pointOnBoundary - whether a boundary should be considered "in" or not
	 * @param boolean $pointOnVertex - whether a vertex should be considered "in" or not
	 * @return boolean
	 */
	public function pointInPolygon($point, $pointOnBoundary = true, $pointOnVertex = true)
	{
		$vertices = $this->getPoints();

		// Check if the point sits exactly on a vertex
		if ($this->pointOnVertex($point, $vertices)) {
			return $pointOnVertex ? true : false;
		}

		// Check if the point is inside the polygon or on the boundary
		$intersections = 0;
		$vertices_count = count($vertices);

		for ($i=1; $i < $vertices_count; $i++) {
			$vertex1 = $vertices[$i-1];
			$vertex2 = $vertices[$i];
			if ($vertex1->y() == $vertex2->y()
			&& $vertex1->y() == $point->y()
			&& $point->x() > min($vertex1->x(), $vertex2->x())
			&& $point->x() < max($vertex1->x(), $vertex2->x())) {
				// Check if point is on an horizontal polygon boundary
				return $pointOnBoundary ? true : false;
			}
			if ($point->y() > min($vertex1->y(), $vertex2->y())
			&& $point->y() <= max($vertex1->y(), $vertex2->y())
			&& $point->x() <= max($vertex1->x(), $vertex2->x())
			&& $vertex1->y() != $vertex2->y()) {
				$xinters =
				($point->y() - $vertex1->y()) * ($vertex2->x() - $vertex1->x())
				/ ($vertex2->y() - $vertex1->y())
				+ $vertex1->x();
				if ($xinters == $point->x()) {
						// Check if point is on the polygon boundary (other than horizontal)
						return $pointOnBoundary ? true : false;
				}
				if ($vertex1->x() == $vertex2->x() || $point->x() <= $xinters) {
					  $intersections++;
				}
			}
		}
		// If the number of edges we passed through is even, then it's in the polygon.
		if ($intersections % 2 != 0) {
			return true;
		} else {
			return false;
		}
	}

	public function pointOnVertex($point)
	{
		foreach ($this->getPoints() as $vertex) {
			if ($point->equals($vertex)) {
				return true;
			}
		}
	}


	// Not valid for this geometry type
	// --------------------------------
	public function length()
	{
		return null; }
}
