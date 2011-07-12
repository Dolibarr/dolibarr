mxn.register('{{api_id}}', {	

Mapstraction: {
	
	init: function(element, api) {		
		var me = this;
		
		// TODO: Add provider code
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

	setCenterAndZoom: function(point, zoom) { 
		var map = this.maps[this.api];
		var pt = point.toProprietary(this.api);
		
		// TODO: Add provider code
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
		
		// TODO: Add provider code
		
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
		var zoom;
		
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

	addTileLayer: function(tile_url, opacity, copyright_text, min_zoom, max_zoom, map_type) {
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