mxn.register('googlev3', {	

Geocoder: {
	
	init: function() {
		this.geocoders[this.api] = new google.maps.Geocoder();
	},
	
	geocode: function(address){
		var me = this;

		if (!address.hasOwnProperty('address') || address.address === null || address.address === '') {
			address.address = [ address.street, address.locality, address.region, address.country ].join(', ');
		}
		
		this.geocoders[this.api].geocode({'address': address.address }, function(results, status) {
			me.geocode_callback(results, status);
		});
	},
	
	geocode_callback: function(results, status){
		var return_location = {};

		if (status != google.maps.GeocoderStatus.OK) {
			this.error_callback(status);
		} 
		else {
			return_location.street = '';
			return_location.locality = '';
			return_location.region = '';
			return_location.country = '';

			var place = results[0];
			
			for (var i = 0; i < place.address_components.length; i++) {
				var addressComponent = place.address_components[i];
				for (var j = 0; j < addressComponent.types.length; j++) {
					var componentType = addressComponent.types[j];
					switch (componentType) {
						case 'country':
							return_location.country = addressComponent.long_name;
							break;
						case 'administrative_area_level_1':
							return_location.region = addressComponent.long_name;
							break;
						case 'locality':
							return_location.locality = addressComponent.long_name;
							break;
						case 'street_address':
							return_location.street = addressComponent.long_name;
							break;
					}
				}
			}
			
			return_location.point = new mxn.LatLonPoint(place.geometry.location.lat(), place.geometry.location.lng());
			
			this.callback(return_location);
		}
	}
}
});