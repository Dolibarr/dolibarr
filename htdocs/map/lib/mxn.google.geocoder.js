mxn.register('google', {	

Geocoder: {
	
	init: function() {		
		this.geocoders[this.api] = new GClientGeocoder();
	},
	
	geocode: function(address){
		var me = this;

		if (!address.hasOwnProperty('address') || address.address === null || address.address === '') {
			address.address = [ address.street, address.locality, address.region, address.country ].join(', ');
		}
		
		this.geocoders[this.api].getLocations(address.address, function(response) {
			me.geocode_callback(response);
		});
	},
	
	geocode_callback: function(response){
		var return_location = {};

		if (typeof(response) === 'undefined' || !response.hasOwnProperty('Status') || response.Status.code != 200) {
			this.error_callback(response);
		} 
		else {
			return_location.street = '';
			return_location.locality = '';
			return_location.region = '';
			return_location.country = '';

			var place = response.Placemark[0];
			var working = place.AddressDetails.Country.AdministrativeArea;
			if(working !== null) {
				return_location.region = working.AdministrativeAreaName;
				if(working.SubAdministrativeArea !== null) {
					working = working.SubAdministrativeArea;
					if(working.Locality !== null) {
						working = working.Locality;
						return_location.locality = working.LocalityName;
						if(working.Thoroughfare !== null) {
							return_location.street = working.Thoroughfare.ThoroughfareName;
						}
					}
				}
			}
			
			return_location.country = place.AddressDetails.Country.CountryNameCode;
			return_location.point = new mxn.LatLonPoint(place.Point.coordinates[1],	place.Point.coordinates[0]);
			
			this.callback(return_location);
		}
	}
}
});