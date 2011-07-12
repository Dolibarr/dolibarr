mxn.register('mapquest', {	

Geocoder: {
	
	init: function() {		
		//set up the connection to the geocode server
		var proxyServerName = "";
		var proxyServerPort = "";
		var proxyServerPath = "mapquest_proxy/JSReqHandler.php";
		var serverName = "geocode.access.mapquest.com";
		var serverPort = "80";
		var serverPath = "mq";
		
		this.geocoders[this.api] = new MQExec(serverName, serverPath, serverPort, proxyServerName,
			proxyServerPath, proxyServerPort );
	},
	
	geocode: function(address){
		var return_location = {};
		var mapstraction_geodocer = this;
		
		var mqaddress = new MQAddress();
		var gaCollection = new MQLocationCollection("MQGeoAddress");
		
		//populate the address object with the information from the form
		mqaddress.setStreet(address.street);
		mqaddress.setCity(address.locality);
		mqaddress.setState(address.region);
		mqaddress.setPostalCode(address.postalcode);
		mqaddress.setCountry(address.country);

		this.geocoders[this.api].geocode(mqaddress, gaCollection);
		var geoAddr = gaCollection.get(0);
		var mqpoint = geoAddr.getMQLatLng();
		
		return_location.street = geoAddr.getStreet();
		return_location.locality = geoAddr.getCity();
		return_location.region = geoAddr.getState();
		return_location.country = geoAddr.getCountry();
		return_location.point = new LatLonPoint(mqpoint.getLatitude(), mqpoint.getLongitude());
		
		this.callback(return_location);
	},
	
	geocode_callback: function(response){
		throw 'Not used';
	}
}
});