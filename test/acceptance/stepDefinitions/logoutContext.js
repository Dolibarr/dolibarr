const { When, Then } = require('cucumber')
const { client } = require('nightwatch-api')

When('the user opens the user profile using the webUI', function () {
    return client.page.logoutPage().userOpensTheUserProfile();
});

When('the user logs out using the webUI', function () {
    return client.page.logoutPage().userLogsOut();
});

Then('the user should be logged out of the account', function () {
    return client.page.loginPage().waitForLoginPage();
});
