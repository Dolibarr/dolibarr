/*
Copyright (c) 2011 Tom Carden, Steve Coast, Mikel Maron, Andrew Turner, Henri Bergius, Rob Moran, Derek Fowler
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * Neither the name of the Mapstraction nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
mxn.register('geocommons', {	

	Mapstraction: {

		// These methods can be called anytime but will only execute
		// once the map has loaded. 
		deferrable: {
			applyOptions: true,
			resizeTo: true,
			addControls: true,
			addSmallControls: true,
			addLargeControls: true,
			addMapTypeControls: true,
			dragging: true,
			setCenterAndZoom: true,
			getCenter: true,
			setCenter: true,
			setZoom: true,
			getZoom: true,
			getZoomLevelForBoundingBox: true,
			setMapType: true,
			getMapType: true,
			getBounds: true,
			setBounds: true,
			addTileLayer: true,
			toggleTileLayer: true,
			getPixelRatio: true,
			mousePosition: true
		},

		init: function(element, api) {		
			var me = this;
			this.element = element;
			this.loaded[this.api] = false; // Loading will take a little bit.
			F1.Maker.core_host = f1_core_host;
			F1.Maker.finder_host = f1_finder_host;
			F1.Maker.maker_host = f1_maker_host;

			// we don't use this object but assign it to dummy for JSLint
			var dummy = new F1.Maker.Map({
				dom_id: this.element.id,
				flashvars: {},				
				onload: function(map){
					me.maps[me.api] = map.swf; // Get the actual Flash object
					me.loaded[me.api] = true;					 
					for (var i = 0; i < me.onload[me.api].length; i++) {
						me.onload[me.api][i]();
					}
				}
			});
			
		  },

		applyOptions: function(){
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		resizeTo: function(width, height){	
			var map = this.maps[this.api];
			map.setSize(width,height);
		},

		addControls: function( args ) {
			var map = this.maps[this.api];
			map.showControl("Zoom", args.zoom || false);
			map.showControl("Layers", args.layers || false);
			map.showControl("Styles", args.styles || false); 
			map.showControl("Basemap", args.map_type || false);
			map.showControl("Legend", args.legend || false, "open"); 
			// showControl("Legend", true, "close"); 
		},

		addSmallControls: function() {
			var map = this.maps[this.api];
			this.addControls({
				zoom:   'small',
				legend: "open"
			});
			// showControl("Zoom", args.zoom);
			// showControl("Legend", args.legend, "open"); 
		},

		addLargeControls: function() {
			var map = this.maps[this.api];
			this.addControls({
				zoom:   'large',
				layers: true,
				legend: "open"
			});
		},

		addMapTypeControls: function() {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		dragging: function(on) {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		setCenterAndZoom: function(point, zoom) { 
			var map = this.maps[this.api];
			map.setCenterZoom(point.lat, point.lon,zoom);
		},

		getCenter: function() {
			var map = this.maps[this.api];
			var point = map.getCenterZoom()[0];
			return new mxn.LatLonPoint(point.lat,point.lon);
		},

		setCenter: function(point, options) {
			var map = this.maps[this.api];
			map.setCenter(point.lat, point.lon);			
		},

		setZoom: function(zoom) {
			var map = this.maps[this.api];
			map.setZoom(zoom);
		},

		getZoom: function() {
			var map = this.maps[this.api];
			return map.getZoom();
		},

		getZoomLevelForBoundingBox: function( bbox ) {
			var map = this.maps[this.api];
			// NE and SW points from the bounding box.
			var ne = bbox.getNorthEast();
			var sw = bbox.getSouthWest();
			var zoom;

			// TODO: Add provider code

			return zoom;
		},

		setMapType: function(type) {
			var map = this.maps[this.api];
			switch(type) {
				case mxn.Mapstraction.ROAD:
				map.setMapProvider("OpenStreetMap (road)");
				break;
				case mxn.Mapstraction.SATELLITE:
				map.setMapProvider("BlueMarble");
				break;
				case mxn.Mapstraction.HYBRID:
				map.setMapProvider("Google Hybrid");
				break;
				default:
				map.setMapProvider(type);
			}	 
		},

		getMapType: function() {
			var map = this.maps[this.api];
			
			// TODO: I don't thick this is correct -Derek
			switch(map.getMapProvider()) {
				case "OpenStreetMap (road)":
					return mxn.Mapstraction.ROAD;
				case "BlueMarble":
					return mxn.Mapstraction.SATELLITE;
				case "Google Hybrid":
					return mxn.Mapstraction.HYBRID;
				default:
					return null;
			}	

		},

		getBounds: function () {
			var map = this.maps[this.api];
			var extent = map.getExtent();
			return new mxn.BoundingBox( extent.northWest.lat, extent.southEast.lon, extent.southEast.lat, extent.northWest.lon);
		},

		setBounds: function(bounds){
			var map = this.maps[this.api];
			var sw = bounds.getSouthWest();
			var ne = bounds.getNorthEast();
			map.setExtent(ne.lat,sw.lat,ne.lon,sw.lon);

		},

		addImageOverlay: function(id, src, opacity, west, south, east, north, oContext) {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},
		
		// URL in this case is either a Maker map ID or the full URL to the Maker Map
		addOverlay: function(url, autoCenterAndZoom) {
			var map = this.maps[this.api];
			var match;

			if(typeof(url) === "number") {
				map.loadMap(url);
				return;
			}
			// Try if we've been given either a string of the ID or a URL
			match = url.match(/^(\d+)$/);
			if(match !== null){
				match = url.match(/^.*?maps\/(\d+)(\?\(\[?(.*?)\]?\))?$/);
			}

			map.loadMap(match[1]);
		},

		addTileLayer: function(tile_url, opacity, copyright_text, min_zoom, max_zoom) {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		toggleTileLayer: function(tile_url) {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		getPixelRatio: function() {
			var map = this.maps[this.api];

			// TODO: Add provider code	
		},

		mousePosition: function(element) {
			var map = this.maps[this.api];

			// TODO: Add provider code	
		},
		addMarker: function(marker, old) {
			var map = this.maps[this.api];
			var pin = marker.toProprietary(this.api);
			// TODO: Add provider code
			// map.addOverlay(pin);
			return pin;
		},

		removeMarker: function(marker) {
			var map = this.maps[this.api];
			// TODO: Add provider code

		},

		declutterMarkers: function(opts) {
			var map = this.maps[this.api];

			// TODO: Add provider code
		},

		addPolyline: function(polyline, old) {
			var map = this.maps[this.api];
			var pl = polyline.toProprietary(this.api);
			// TODO: Add provider code			
			// map.addOverlay(pl);
			return pl;
		},

		removePolyline: function(polyline) {
			var map = this.maps[this.api];
			// TODO: Add provider code
		}
		
	},

	LatLonPoint: {

		toProprietary: function() {
			// TODO: Add provider code
			return {};
		},

		fromProprietary: function(googlePoint) {
			// TODO: Add provider code
		}

	},

	Marker: {

		toProprietary: function() {
			// TODO: Add provider code
			return {};
		},

		openBubble: function() {		
			// TODO: Add provider code
		},

		hide: function() {
			// TODO: Add provider code
		},

		show: function() {
			// TODO: Add provider code
		},

		update: function() {
			// TODO: Add provider code
		}

	},

	Polyline: {

		toProprietary: function() {
			return {};
			// TODO: Add provider code
		},

		show: function() {
			// TODO: Add provider code
		},

		hide: function() {
			// TODO: Add provider code
		}

	}

});