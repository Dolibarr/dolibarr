mxn.register('yandex', {

Geocoder: {
	
	init: function() {		
		var me = this;
	},
	
	geocode: function(address){
		var mapstraction_geocoder = this;		
		
		if (!address.hasOwnProperty('address') || address.address === null || address.address === '') {
			address.address = [ address.street, address.locality, address.region, address.country ].join(', ');
		}
		var geocoder = new YMaps.Geocoder(address.address, { results: 1 });
		YMaps.Events.observe(geocoder, geocoder.Events.Load, function (response) {
			if (response.found > 0) {
				mapstraction_geocoder.geocode_callback(response.get(0));
			} else {
				mapstraction_geocoder.error_callback(response);
			}
		});
 
		YMaps.Events.observe(geocoder, geocoder.Events.Fault, function (error) {
			mapstraction_geocoder.error_callback(error.message);
		});
	},
	
	geocode_callback: function(response){

		var return_location = { street: '', locality: '', region: '', country: '' };
		
		var locLev;
		if ((locLev = response.AddressDetails.Country)) {
			return_location.country = locLev.CountryName;
			if ((locLev = locLev.AdministrativeArea)) {
				return_location.region = locLev.AdministrativeAreaName;
				if ((locLev = locLev.Locality)) {
					return_location.locality = locLev.LocalityName;
					if ((locLev = locLev.Thoroughfare)) {
						return_location.street = locLev.ThoroughfareName;
					}
				}
			}
		}

		ypoint = response.getGeoPoint();
		return_location.point = new mxn.LatLonPoint(ypoint.getX(), ypoint.getY());
		this.callback(return_location);
	}
}
});
