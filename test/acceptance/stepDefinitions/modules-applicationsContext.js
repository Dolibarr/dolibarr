const { Given, When, Then } = require('cucumber')
const { client } = require('nightwatch-api')

When('the administrator browses to the modulesApplications page', async function () {
	await client.page.homePage().browseToModulesApplicationsPage();
	return client.page.modulesApplicationsPage().browsedToModulesApplicationsPage();
});

Then('the {string} module should be auto-enabled', function (module) {
return client.page.modulesApplicationsPage().moduleIsAutoEnabled(module)
});

Then('the number of activated modules should be {int}', function (number) {
	return client.page.modulesApplicationsPage().assertNumberOfActivatedModules(number)
});

When('the administrator enables the following modules:', function (dataTable) {
	const modules = dataTable.hashes()
	return client.page.modulesApplicationsPage().enableModules(modules);
});

Then('the following modules should be displayed in the navigation bar:', function (dataTable) {
	const modules = dataTable.hashes()
	return client.page.modulesApplicationsPage().assertModulesDisplayedInNavBar(modules);
})
