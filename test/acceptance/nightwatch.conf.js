const admin_username = process.env.ADMIN_USERNAME || 'admin';
const admin_password = process.env.ADMIN_PASSWORD || 'admin';
const launch_url = process.env.LAUNCH_URL || 'http://localhost/dolibarr/htdocs/';
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
				adminPassword : admin_password
			},
			desiredCapabilities : {
				browserName : 'chrome',
				javascriptEnabled : true,
				chromeOptions : {
					args : ['disable-gpu', 'window-size=1280,1024'],
					w3c : false
				}
			}
		}
	}
};
