const admin_username = process.env.ADMIN_USERNAME || 'dolibarr';
const admin_password = process.env.ADMIN_PASSWORD || 'password';
const launch_url = process.env.LAUNCH_URL || 'http://localhost/dolibarr/htdocs/';
const dol_api_key = process.env.DOLAPIKEY || 'superadminuser';
module.exports = {
	page_objects_path : './test/acceptance/pageObjects/', // jshint ignore:line
	src_folders : ['test'],

	test_settings : {
		default : {
			selenium_host : '127.0.0.1',
			launchUrl : launch_url,
			globals : {
				backend_url : launch_url,
				adminUsername : admin_username,
				adminPassword : admin_password,
				dolApiKey : dol_api_key
			},
			desiredCapabilities : {
				browserName : 'chrome',
				javascriptEnabled : true,
				chromeOptions : {
					args : ['disable-gpu'],
					w3c : false
				}
			}
		}
	}
};
