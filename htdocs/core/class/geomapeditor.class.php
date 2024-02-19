<?php
/* Copyright (C) 2024       Frédéric France			<frederic.france@netlogic.fr>
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
 *       \file       htdocs/core/class/geomapeditor.class.php
 *       \brief      Class to manage a leaflet map width geometrics objects
 */

/**
 *      Class to manage a Leaflet map width geometrics objects
 */
class GeoMapEditor
{
	/**
	 * __contruct
	 *
	 * @return void
	 */
	public function __construct()
	{
	}

	/**
	 * getHtml
	 *
	 * @param string $htmlname htmlname
	 * @param string $geojson  json of geometric objects
	 *
	 * @return string
	 */
	public function getHtml($htmlname, $geojson)
	{
		global $langs;

		$out = '<input id="' . $htmlname . '" name="' . $htmlname . '" size="100" value="' . htmlentities($geojson, ENT_QUOTES) . '"/>';
		$out .= '<div id="map_' . $htmlname . '" style="width: 600px; height: 350px;"></div>';
		$out .= '
		<script>
			var geoms = JSON.parse(\'' . $geojson . '\');
			console.log(geoms);
			if (Object.keys(geoms).length === 0) {
				var map = L.map("map_' . $htmlname . '").setView([49.505, -0.09], 13);
			} else {
				var map = L.map("map_' . $htmlname . '").setView([geoms.coordinates[1], geoms.coordinates[0]], 14);
			}
			if (geoms && geoms.type == "Point") {
				L.marker([geoms.coordinates[1], geoms.coordinates[0]]).addTo(map);
				map.pm.addControls({
					drawMarker: false,
					drawPolyline: false,
					drawRectangle: false,
					drawPolygon: false,
				});
			}
			map.on("pm:drawend", (e) => {
				map.pm.addControls({
					drawMarker: false,
					drawPolyline: false,
					drawRectangle: false,
					drawPolygon: false,
				});
				generateGeoJson();
				console.log("pm:drawend");
				console.log(e);
			});
			map.on("pm:markerdragend", (e) => {
				map.pm.addControls({
					drawMarker: false,
					drawPolyline: false,
					drawRectangle: false,
					drawPolygon: false,
				});
				console.log("pm:markerdragend");
				console.log(e);
			});
			map.on("pm:remove", (e) => {
				map.pm.addControls({
					drawMarker: true,
					drawPolyline: true,
					drawRectangle: true,
					drawPolygon: true,
				});
				console.log(e);
				$("#' . $htmlname . '").val ("{}");
			});

			map.on("pm:create", (e) => {
				console.log("pm:create");
				generateGeoJson();
			});
			map.on("pm:globaleditmodetoggled", (e) => {
				console.log(e);
			});
			var tiles = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
				maxZoom: 19,
				attribution: \'&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>\'
			}).addTo(map);
			map.pm.setLang("'.($langs->shortlang ?? 'en').'");
			// remove controls not needed
			map.pm.addControls({
				position: \'topleft\',
				dragMode: false,
				drawCircle:false,
				drawCircleMarker: false,
				drawText: false,
				editMode: true,
				removalMode: true,
				rotateMode: true,
				customControls: false,
			});

			function generateGeoJson(){
				var fg = L.featureGroup();
				var layers = findLayers(map);
				layers.forEach(function(layer){
					fg.addLayer(layer);
				});
				console.log(fg.toGeoJSON());
				$("#' . $htmlname . '").val (JSON.stringify(fg.toGeoJSON().features[0].geometry));
			}
			function findLayers(map) {
				// https://stackoverflow.com/questions/62887120/leafletjs-geoman-into-json-data
				var layers = [];
				map.eachLayer(layer => {
					if (
						layer instanceof L.Polyline || // Don"t worry about Polygon and Rectangle they are included in Polyline
						layer instanceof L.Marker ||
						layer instanceof L.Circle ||
						layer instanceof L.CircleMarker
					) {
						layers.push(layer);
					}
				});
				// filter out layers that don"t have the leaflet-geoman instance
				layers = layers.filter(layer => !!layer.pm);
				// filter out everything that"s leaflet-geoman specific temporary stuff
				layers = layers.filter(layer => !layer._pmTempLayer);
				return layers;
			}
		</script>';

		return $out;
	}
}
