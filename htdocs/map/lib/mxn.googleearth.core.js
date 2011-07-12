mxn.register('googleearth', {	

Mapstraction: {
	
	init: function(element, api) {		
		var me = this;		

		this.maps[api] = null;
				
		google.earth.createInstance(
			element, 
			function initCallback(object) {
				me.maps[api] = object;
				me.maps[api].getWindow().setVisibility(true);
				initMap();
			}, 
			function failureCallback(object) {
				throw 'Failed to create Google Earth map';
			}
		);
				
	},
	
	applyOptions: function(){
		var map = this.maps[this.api];
		
		// TODO: Add provider code
	},

	resizeTo: function(width, height){	
		// TODO: Add provider code
	},

	addControls: function( args ) {
		var map = this.maps[this.api];
	
		// TODO: Add provider code
	},

	addSmallControls: function() {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
	},

	addLargeControls: function() {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
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
		var pt = point.toProprietary(this.api);
		
		var lookAt = map.getView().copyAsLookAt(map.ALTITUDE_RELATIVE_TO_GROUND);

		// Set new latitude and longitude values
		lookAt.setLatitude(point.lat);
		lookAt.setLongitude(point.lon);
		lookAt.setRange(lookAt.getRange() / 7.0);		
		
		// Update the view in Google Earth
		map.getView().setAbstractView(lookAt);
	},
	
	addMarker: function(marker, old) {
		var map = this.maps[this.api];
		var pin = marker.toProprietary(this.api);
		
		// TODO: Add provider code
		
		return pin;
	},

	removeMarker: function(marker) {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
	},

	removeAllMarkers: function() {
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
		
		return pl;
	},

	removePolyline: function(polyline) {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
	},
	
	getCenter: function() {
		var point;
		var map = this.maps[this.api];
		
		var lookAt = map.getView().copyAsLookAt(map.ALTITUDE_RELATIVE_TO_GROUND);
		point = new mxn.LatLonPoint(lookAt.getLatitude(),lookAt.getLongitude());
		return point;
	},

	setCenter: function(point, options) {
		var map = this.maps[this.api];
		var pt = point.toProprietary(this.api);
		if(options && options.pan) { 
			// TODO: Add provider code
		}
		else { 
			// TODO: Add provider code
		}
	},

	setZoom: function(zoom) {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
		
	},
	
	getZoom: function() {
		var map = this.maps[this.api];
		var zoom = 5;
		
		// TODO: Add provider code
		
		return zoom;
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
				// TODO: Add provider code
				break;
			case mxn.Mapstraction.SATELLITE:
				// TODO: Add provider code
				break;
			case mxn.Mapstraction.HYBRID:
				// TODO: Add provider code
				break;
			default:
				// TODO: Add provider code
		}	 
	},

	getMapType: function() {
		var map = this.maps[this.api];
		
		// TODO: Add provider code

		//return mxn.Mapstraction.ROAD;
		//return mxn.Mapstraction.SATELLITE;
		//return mxn.Mapstraction.HYBRID;

	},

	getBounds: function () {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
		
		//return new mxn.BoundingBox( ,  ,  ,  );
	},

	setBounds: function(bounds){
		var map = this.maps[this.api];
		var sw = bounds.getSouthWest();
		var ne = bounds.getNorthEast();
		
		// TODO: Add provider code
		
	},

	addImageOverlay: function(id, src, opacity, west, south, east, north, oContext) {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
	},

	setImagePosition: function(id, oContext) {
		var map = this.maps[this.api];
		var topLeftPoint; var bottomRightPoint;

		// TODO: Add provider code

		//oContext.pixels.top = ...;
		//oContext.pixels.left = ...;
		//oContext.pixels.bottom = ...;
		//oContext.pixels.right = ...;
	},
	
	addOverlay: function(url, autoCenterAndZoom) {
		var map = this.maps[this.api];
		
		// TODO: Add provider code
		
	},

	addTileLayer: function(tile_url, opacity, copyright_text, min_zoom, max_zoom) {
		var map = this.maps[this.api];
		var me = this;
		
		google.earth.fetchKml(map, tile_url, function(kmlObject) {
			if (kmlObject) {
				map.getFeatures().appendChild(kmlObject);
				me.tileLayers.push( [tile_url, kmlObject, true] );
			}
			else {
				throw 'Invalid KML file';
			}
		});
	},

	toggleTileLayer: function(tile_url) {
		var map = this.maps[this.api];
		
		for (var f=0; f<this.tileLayers.length; f++) {
			if(this.tileLayers[f][0] == tile_url) {
				if(this.tileLayers[f][2]) {
					map.getFeatures().removeChild(this.tileLayers[f][1]); 
					this.tileLayers[f][2] = false;
				}
				else {
					map.getFeatures().appendChild(this.tileLayers[f][1]); 
					this.tileLayers[f][2] = true;
				}
			}
		}
	},

	getPixelRatio: function() {
		var map = this.maps[this.api];

		// TODO: Add provider code	
	},
	
	mousePosition: function(element) {
		var map = this.maps[this.api];

		// TODO: Add provider code	
	}
},

LatLonPoint: {
	
	toProprietary: function() {
		// TODO: Add provider code
	},

	fromProprietary: function(googlePoint) {
		// TODO: Add provider code
	}
	
},

Marker: {
	
	toProprietary: function() {
		// TODO: Add provider code
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