(function(){

/**
 * Initialise our provider. This function should only be called 
 * from within mapstraction code, not exposed as part of the API.
 * @private
 */
var init = function() {
	this.invoker.go('init');
};

/**
 * Geocoder instantiates a geocoder with some API choice
 * @name mxn.Geocoder
 * @constructor
 * @param {String} api The API to use, currently only 'mapquest' is supported
 * @param {Function} callback The function to call when a geocode request returns (function(waypoint))
 * @param {Function} error_callback The optional function to call when a geocode request fails
 * @exports Geocoder as mxn.Geocoder
 */
var Geocoder = mxn.Geocoder = function (api, callback, error_callback) {
	this.api = api;
	this.geocoders = {};
	this.callback = callback;
	this.error_callback = error_callback || function(){};
	  
	// set up our invoker for calling API methods
	this.invoker = new mxn.Invoker(this, 'Geocoder', function(){ return this.api; });
	init.apply(this);
};

mxn.addProxyMethods(Geocoder, [
	
	/**
	 * Geocodes the provided address.
	 * @name mxn.Geocoder#geocode
	 * @function
	 * @param {Object} address Address hash, keys are: street, locality, region, country.
	 */
	'geocode',
	
	'geocode_callback'

]);

/**
 * Change the geocoding API in use
 * @name mxn.Geocoder#swap
 * @param {String} api The API to swap to
 */
Geocoder.prototype.swap = function(api) {
	if (this.api == api) { return; }

	this.api = api;
	if (!this.geocoders.hasOwnProperty(this.api)) {
		init.apply(this);
	}
};

})();