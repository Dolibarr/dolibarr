<?php
/* Copyright (C) 2024       Frédéric France			<frederic.france@free.fr>
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
	 * @param string $centroidjson  json of geometric center of object
	 * @param string $markertype type of marker, point, multipts, linestrg, polygon
	 *
	 * @return string
	 */
	public function getHtml($htmlname, $geojson, $centroidjson, $markertype)
	{
		global $langs;

		$out = '<input id="' . $htmlname . '" name="' . $htmlname . '" size="100" value="' . htmlentities($geojson, ENT_QUOTES) . '"/>';
		$out .= '<div id="map_' . $htmlname . '" style="width: 600px; height: 350px;"></div>';
		if ($geojson != '{}') {
			// OpenLayers it's "longitude, latitude".
			// inverting coordinates
			$tmp = json_decode($geojson);
			$tmp2 = new stdClass();
			$tmp2->type = $tmp->type;
			$tmp2->coordinates = [];
			if ($tmp->type == 'Point') {
				$tmp2->coordinates = [$tmp->coordinates[1], $tmp->coordinates[0]];
			} elseif ($tmp->type == 'Polygon') {
				foreach ($tmp->coordinates as $polygon) {
					$polyg = [];
					foreach ($polygon as $key => $value) {
						$polyg[] = [$value[1], $value[0]];
					}
					$tmp2->coordinates[] = $polyg;
				}
			} else {
				foreach ($tmp->coordinates as $key => $value) {
					$tmp2->coordinates[] = [$value[1], $value[0]];
				}
			}
			$geojson = json_encode($tmp2);
		}
		if ($centroidjson != '{}') {
			if (null === json_decode($centroidjson)) {
				$centroidjson = '{}';
			} else {
				// OpenLayers it's "longitude, latitude".
				// inverting coordinates
				$tmp = json_decode($centroidjson);
				$tmp2 = new stdClass();
				$tmp2->type = $tmp->type;
				$tmp2->coordinates = [];
				if ($tmp->type == 'Point') {
					$tmp2->coordinates = [$tmp->coordinates[1], $tmp->coordinates[0]];
				} else {
					foreach ($tmp->coordinates as $key => $value) {
						$tmp2->coordinates[] = [$value[1], $value[0]];
					}
				}
				$centroidjson = json_encode($tmp2);
			}
		}
		$out .= '
		<script>
			var geoms = JSON.parse(\'' . $geojson . '\');
			var centroid = JSON.parse(\'' . $centroidjson . '\');
			var markerType = "' . $markertype . '";
			var map = L.map("map_' . $htmlname . '");
			console.log(markerType);
			console.log(geoms);
			map.pm.addControls({
				position: \'topleft\',
				dragMode: false,
				drawMarker: false,
				drawCircle:false,
				drawCircleMarker: false,
				drawText: false,
				drawRectangle: false,
				editMode: true,
				removalMode: true,
				rotateMode: false,
				customControls: false,
			});
			console.log(centroid);
			if (centroid.type == "Point") {
				map.setView(centroid.coordinates, 11);
			} else {
				map.setView([48.852, 2.351], 12);
			}
			if (markerType == "point" && Object.keys(geoms).length != 0) {
				//map.setView(geoms.coordinates, 17);
				L.marker(geoms.coordinates).addTo(map);
				// disableMarkers();
				map.pm.addControls({
					drawMarker: false,
					drawPolyline: false,
					drawPolygon: false,
				});
			} else if (markerType == "multipts" && Object.keys(geoms).length != 0) {
				L.multipoint(geoms.coordinates).addTo(map);
			} else if (markerType == "linestrg" && Object.keys(geoms).length != 0) {
				L.polyline(geoms.coordinates).addTo(map);
			} else if (markerType == "polygon" && Object.keys(geoms).length != 0) {
				L.polygon(geoms.coordinates).addTo(map);
			} else if (Object.keys(geoms).length === 0) {
				// map.setView([48.852, 2.351], 12);
			}
			var tiles = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
				maxZoom: 19,
				attribution: \'&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>\'
			}).addTo(map);
			map.pm.setLang("' . ($langs->shortlang ?? 'en') . '");

			enableMarker(markerType);
			// if (geoms && geoms.type == "Point") {
			// 	L.marker([geoms.coordinates[1], geoms.coordinates[0]]).addTo(map);
			// 	disableMarkers();
			// }
			map.on("pm:drawend", (e) => {
				disableMarkers();
				generateGeoJson();
				console.log("pm:drawend");
				console.log(e);
			});
			map.on("pm:markerdragend", (e) => {
				disableMarkers();
				generateGeoJson();
				console.log("pm:markerdragend");
				console.log(e);
			});
			map.on("pm:remove", (e) => {
				enableMarker();
				console.log(e);
				$("#' . $htmlname . '").val ("{}");
			});
			map.on("pm:edit", (e) => {
				console.log("pm:edit");
				generateGeoJson();
			});
			map.on("pm:create", (e) => {
				console.log("pm:create");
				generateGeoJson();
			});
			map.on("pm:globaleditmodetoggled", (e) => {
				generateGeoJson();
				console.log(e);
			});
			function enableMarker(type) {
				console.log("enable : " + type);
				if (type == "point") {
					map.pm.addControls({
						drawMarker: true
					});
				};
			}
			function disableMarkers(){
				map.pm.addControls({
					drawMarker: false,
					drawPolyline: false,
					drawPolygon: false,
				});
			}

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
