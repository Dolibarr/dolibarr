mxn.register('{{api_id}}', {

Geocoder: {
	
	init: function() {		
		var me = this;
		
		// TODO: Add provider code
	},
	
	geocode: function(address){
		var mapstraction_geocoder = this;		
		
		// TODO: Add provider code
	},
	
	geocode_callback: function(response){
		var return_location = {};
		
		// TODO: Add provider code
		//return_location.street = '';
		//return_location.locality = '';
		//return_location.region = '';
		//return_location.country = '';
		//return_location.point = new mxn.LatLonPoint(...);
		
		this.callback(return_location);
	}
}
});