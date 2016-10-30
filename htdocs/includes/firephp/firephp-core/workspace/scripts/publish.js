
var PINF_LOADER = require("pinf/loader"),
	SANDBOX = PINF_LOADER.getSandbox(),
	FILE = require("modules/file"),
	Q = require("modules/q"),
	SYSTEM = require("modules/system"),
	BUILD = require("./build"),
	JSON = require("modules/json"),
	SOURCEMINT_CLIENT = false;

exports.main = function()
{
	module.load({
		id: "private-registry.appspot.com/cadorn.com/github/com.cadorn.baby/projects/sourcemint/packages/client-js/",
		descriptor: {
			main: "lib/client.js"
		}
	}, function(id)
	{
		SOURCEMINT_CLIENT = require(id);

		publish();
	});
}

function publish()
{
	var buildPath = BUILD.getBuildPath(),
		info = JSON.decode(FILE.read(buildPath + "/info.json")),
		descriptor = JSON.decode(FILE.read(FILE.dirname(FILE.dirname(FILE.dirname(module.id))) + "/package.json"));

	var bundles = {};
	bundles["firephp-core.zip"] = {
		"type": "zip",
		"options": {
			"archivePath": buildPath + "/FirePHPCore-" + info.version + ".zip",
		}
	};

	var packages = [
	    {
	    	"uid": descriptor.uid,
	    	"stream": "stable",
	    	"version": info.version,
	    	"bundles": bundles
	    }
	];

	try
	{
		Q.when(SOURCEMINT_CLIENT.publish(packages), function(info)
		{
			module.print("\0green(Published:\n");
			console.log(info);
			module.print("\0)");
		}, function(e)
		{
			throw e;
		});	
	}
	catch(e)
	{
		console.error("Error: " + e);
	}
}
