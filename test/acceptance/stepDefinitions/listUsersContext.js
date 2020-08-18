const { When, Then } = require('cucumber');
const { client } = require('nightwatch-api');

When('the administrator browses to the list of users page using the webUI', function () {
	return client.page.homePage().browsedToListOfUsers();
});

Then('following users should be displayed in the users list', function (dataTable) {
	return client.page.listUsersPage().listOfUsersDisplayed(dataTable);
});

Then('the number of created users should be {int}', function (number) {
	return client.page.listUsersPage().numberOfUsersDisplayed(number);
});
