<?php
/* Copyright (C) 2024 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *       \file       htdocs/core/class/dolgeophp.class.php
 *       \brief      Absctraction class to manage a WYSIWYG editor
 */

require_once DOL_DOCUMENT_ROOT.'/includes/geoPHP/geoPHP.inc.php';


/**
 *      Class to manage Geo processing
 *		Usage: $dolgeophp=new DolGeoPHP($db);
 */
class DolGeoPHP
{
	/**
	 * @var DoliDB	$db		Database handler
	 */
	public $db;

	/**
	 *  Create an object to build an HTML area to edit a large string content
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return data from a value
	 *
	 * @param	mixed	$value		Value
	 * @return	array				Centroid
	 */
	public function parseGeoString($value)
	{
		$geom = geoPHP::load($value, 'wkt');
		$geojson = $geom->out('json');
		$centroid = $geom->getCentroid();
		$centroidjson = $centroid->out('json');

		return array('geojson' => $geojson, 'centroid' => $centroid, 'centroidjson' => $centroidjson);
	}

	/**
	 * Return a string with x and y
	 *
	 * @param	mixed	$value		Value
	 * @return	string				X space Y
	 */
	public function getXYString($value)
	{
		$geom = geoPHP::load($value, 'wkt');
		$value = $geom->x().' '.$geom->y();

		return $value;
	}

	/**
	 * Return a string with x and y
	 *
	 * @param	mixed	$value		Value
	 * @return	string				X space Y
	 */
	public function getPointString($value)
	{
		$geom = geoPHP::load($value, 'wkt');
		$value = get_class($geom) . ' : '. $geom->numPoints() . ' Points';

		return $value;
	}

	/**
	 * Return wkt
	 *
	 * @param	string	$geojson	A json string
	 * @return	string				X space Y
	 */
	public function getWkt($geojson)
	{
		$geom = geoPHP::load($geojson, 'json');
		$value_key = $geom->out('wkt');

		return $value_key;
	}
}
