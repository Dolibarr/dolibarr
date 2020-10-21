const {Given, When, Then} = require('cucumber');
const {client} = require('nightwatch-api');
const fetch = require('node-fetch');
const assert = require('assert');
let response;
let Login = {};

Given('the administrator has browsed to the new users page', function () {
    return client.page.homePage().browsedToNewUserPage();
});

When('the admin creates user with following details', function (datatable) {
    return client.page.addUsersPage().adminCreatesUser(datatable);
});

Then('new user {string} should be created', function (lastname) {
    return client.page.addUsersPage().newUserShouldBeCreated(lastname);
});

Then('message {string} should be displayed in the webUI', function (message) {
    return client.page.addUsersPage().noPermissionMessage(message);
});

Then('message {string} should not be displayed in the webUI', function (message) {
    return client.page.addUsersPage().noPermissionDefinedMessageNotShown(message);
});

Then('new user {string} should not be created', function (lastname) {
    return client.page.addUsersPage().userNotCreated(lastname);
});

Given('a user has been created with following details', function (dataTable) {
    return adminHasCreatedUser(dataTable);
});

Given('the admin has created the following users', function (dataTable) {
    return adminHasCreatedUser(dataTable);
});

When('the admin creates user with following details using API', function (dataTable) {
    return adminCreatesUserWithAPI(dataTable);
});

Given('the user with login {string} does not exist', async function (login) {
    await userDoesNotExist(login);
});

Then('the response status code should be {string}', function (expectedStatusCode) {
    return getStatusCode(expectedStatusCode);
});

Then('user with login {string} should exist', function (login) {
    return userShouldExist(login);
});

Then('the response message should be {string}', function (expectedResponseMessage) {
    return getResponseMessage(expectedResponseMessage);
});

const createUserRequest = function (login, lastname, password) {
    const header = {};
    const url = client.globals.backend_url + 'api/index.php/users';
    header['Accept'] = 'application/json';
    header['DOLAPIKEY'] = client.globals.dolApiKey;
    header['Content-Type'] = 'application/json';
    return fetch(url, {
        method: 'POST',
        headers: header,
        body: JSON.stringify(
            {
                login: login,
                lastname: lastname,
                pass: password
            }
        )
    });
};

const adminCreatesUserWithAPI = function (dataTable) {
    const userDetails = dataTable.rowsHash();
    return createUserRequest(userDetails['login'], userDetails['last name'], userDetails['password'])
        .then((res) => {
            response = res;
        });
};

const adminHasCreatedUser = async function (dataTable) {
    const userDetails = dataTable.hashes();
    for (const user of userDetails) {
        await createUserRequest(user['login'], user['last name'], user['password'])
            .then((response) => {
                if (response.status < 200 || response.status >= 400) {
                    throw new Error('Failed to create user: ' + user['login'] +
                        ' ' + response.statusText);
                }
            });
    }
};

const getUsersLogin = async function () {
    const header = {};
    const url = client.globals.backend_url + 'api/index.php/users/';
    header['Accept'] = 'application/json';
    header['DOLAPIKEY'] = client.globals.dolApiKey;
    header['Content-Type'] = 'application/json';
    await fetch(url, {
        method: 'GET',
        headers: header
    })
        .then(async (response) => {
            const json_response = await response.json();
            for (const user of json_response) {
                Login[user.login] = user.login;
            }
        });
};

const userDoesNotExist = async function (login) {
    await getUsersLogin();
    if (login in Login) {
        Login = {};
        throw new Error(`user ${login} exists`);
    }
    Login = {};
    return;
};

const userShouldExist = async function (login) {
    await getUsersLogin();
    if (login in Login) {
        Login = {};
        return;
    } else {
        Login = {};
        throw new Error(`User ${login} does not Exist`);
    }
};

const getStatusCode = async function (expectedStatusCode) {
    const actualStatusCode = response.status.toString();
    return assert.strictEqual(actualStatusCode, expectedStatusCode,
        `The expected status code was ${expectedStatusCode} but got ${actualStatusCode}`);
};

const getResponseMessage = async function (expectedResponseMessage) {
    const json_response = await response.json();
    const actualResponseMessage = json_response['error']['0'];
    return assert.strictEqual(actualResponseMessage, expectedResponseMessage,
        `the expected response message was ${expectedResponseMessage} but got ${actualResponseMessage}`);
};
