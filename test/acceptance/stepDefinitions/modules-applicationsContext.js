const { Given, When, Then } = require('cucumber')
const { client } = require('nightwatch-api')

When('the administrator browses to the modulesApplications page', async function () {
	await client.page.homePage().browseToModulesApplicationsPage();
	return client.page.modulesApplicationsPage().browsedToModulesApplicationsPage();
});

Then('the {string} module should be auto-enabled', function (module) {

});

