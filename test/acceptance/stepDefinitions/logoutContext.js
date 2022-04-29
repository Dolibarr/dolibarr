const {When, Then} = require('cucumber');
const {client} = require('nightwatch-api');

When('the user opens the user profile using the webUI', function () {
    return client.page.logoutPage().userOpensProfile();
});

When('the user logs out using the webUI', function () {
    return client.page.logoutPage().userLogsOut();
});

Then('the user should be logged out successfully', function () {
    return client.page.loginPage().waitForLoginPage();
});
