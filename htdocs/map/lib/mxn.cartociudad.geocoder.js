mxn.register('cartociudad', {	

Geocoder: {
	
	init: function() {		
		this.geocoders[this.api] = new metodosCartociudad();
	},
	
	geocode: function(address){
		var return_location = {};
		var mapstraction_geodocer = this;
		
		address.error = 0; //creamos una variable para devolver errores
			
		this.geocoders[this.api].queryNomenclator(address);
			
		if (address.error !== 0) {
			this.error_callback(address);
		}
		else {
			this.geocoders[this.api].addressToMapstraction(address);
			this.callback(address);
		}
	},
	
	geocode_callback: function(response, mapstraction_geocoder){
		var return_location = {};
		
		// TODO: Add provider code
	}
}
});