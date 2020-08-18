const { Given, When, Then } = require('cucumber')
const { client } = require('nightwatch-api')

Given('the user has browsed to the login page', function () {
    return client.page.loginPage().navigate();
});

When('user logs in with username {string} and password {string}', function (username, password) {
    return client.page.loginPage().userLogsInWithUsernameAndPassword(username, password);
});

Then('the user should be logged in successfully', function () {
    return client.page.loginPage().successfulLogin();
});

Then('the user should not be logged in successfully', function () {
    return client.page.loginPage().unsuccessfulLogin;
});

Then('error message {string} should be displayed in the webUI', function (errorMessage) {
    return client.page.loginPage().errorMessageDisplay(errorMessage);
});
