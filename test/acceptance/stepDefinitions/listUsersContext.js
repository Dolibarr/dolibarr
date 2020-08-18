const { When, Then } = require('cucumber')
const { client } = require('nightwatch-api')

When('admin browses to the list of users page', function () {
    return client.page.homePage().adminBrowsesToUsersPage();
});

Then('following user should be listed in the users list', async function (dataTable) {
    await client.page.listUsersPage().userShouldBeDisplayed(dataTable);
});

Then('number of created users should be {int}', function (number) {
    return client.page.listUsersPage().totalNumberOfUsers(number);
});
