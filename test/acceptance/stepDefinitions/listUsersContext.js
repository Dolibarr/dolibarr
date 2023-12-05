const {When, Then} = require('cucumber');
const {client} = require('nightwatch-api');
const {getDolApiKey, getUsers} = require('../setup');
const assert = require('assert');

When('the administrator browses to the list of users page using the webUI', function () {
    return client.page.homePage().browsedToListOfUsers();
});

Then('following users should be displayed in the users list', function (dataTable) {
    return client.page.listUsersPage().listOfUsersDisplayed(dataTable);
});

Then('the number of created users should be {int}', function (number) {
    return client.page.listUsersPage().numberOfUsersDisplayed(number);
});

When('the admin gets the list of all users using the API', function () {
    return getUsers();
});

Then('the user list returned by API should be following', function (dataTable) {
    return theUsersShouldBe(dataTable);
});

When('user {string} with password {string} tries to list all users using the API', async function (login, password) {
    const userDolApikey = await getDolApiKey(login, password);
    return getUsers(userDolApikey);
});

Then('the error message should be {string}', function (errorMessage) {
    return getErrorMessage(errorMessage);
});

const theUsersShouldBe = async function (dataTable) {
    const expectedUsers = dataTable.hashes();
    let users = {};
    const json_response = await client.globals.response.json();

    for (const expectedUser of expectedUsers) {
        let found;
        for (const user of json_response) {
            users["login"] = user.login;
            users["last name"] = user.lastname;
            found = false;
            if (expectedUser["login"] === users.login && expectedUser["last name"] === users["last name"]) {
                found = true;
                break;
            } else {
                found = false;
            }
        }
        assert.strictEqual(found, true);
    }
};

const getErrorMessage = async function (expectedErrorMessage) {
    const json_response = await client.globals.response.json();
    const actualErrorMessage = json_response['error']['message'];
    return assert.strictEqual(actualErrorMessage, expectedErrorMessage,
        `the expected response message was ${expectedErrorMessage} but got ${actualErrorMessage}`);
};
