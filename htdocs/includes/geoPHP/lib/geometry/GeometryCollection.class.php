<?php
/**
 * GeometryCollection: A heterogenous collection of geometries
 */
class GeometryCollection extends Collection
{
	protected $geom_type = 'GeometryCollection';

	// We need to override asArray. Because geometryCollections are heterogeneous
	// we need to specify which type of geometries they contain. We need to do this
	// because, for example, there would be no way to tell the difference between a
	// MultiPoint or a LineString, since they share the same structure (collection
	// of points). So we need to call out the type explicitly.
	public function asArray()
	{
		$array = array();
		foreach ($this->components as $component) {
			$array[] = array(
			'type' => $component->geometryType(),
			'components' => $component->asArray(),
			);
		}
		return $array;
	}

	// Not valid for this geomettry
	public function boundary()
	{
		return null; }
	public function isSimple()
	{
		return null; }
}
