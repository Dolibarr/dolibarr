const { Given, When, Then, Before, After } = require('cucumber')
const { client } = require('nightwatch-api')
const fetch = require('node-fetch')
let initialUsers = {}

Given('the admin has logged in using the webUI', async function () {
    await client.page.loginPage().navigate().waitForLoginPage()
    await client.page.loginPage().userLogsInWithUsernameAndPassword(client.globals.adminUsername, client.globals.adminPassword)
    return client.page.loginPage().userIsLoggedIn(client.globals.adminUsername)
});

Given('the admin has browsed to the new users page', function () {
    return client.page.homePage().adminBrowsesNewusersPage();
});

When('the admin creates user with following details', function (dataTable) {
    return client.page.addUsersPage().adminCreatesUser(dataTable);
});

Then('new user {string} should be created', function (lastname) {
    return client.page.addUsersPage().newUserCreation(lastname);
});

Then('message {string} should be displayed in the webUI', function (message) {
    return client.page.addUsersPage().messageDisplayed(message);
});

Then('message {string} shouldnot be displayed in the webUI', function (message) {
    return client.page.addUsersPage().messageNotDisplayed(message);
});

Then('new user {string} should not be created', function (message) {
    return client.page.addUsersPage().userNotCreated(message);
});

Given('the user has been created with following details', function (dataTable) {
    return adminHasCreatedUser(dataTable);
});

Given('the admin has created following users', function (dataTable) {
    return adminHasCreatedUser(dataTable);
});

const adminHasCreatedUser = async function (dataTable) {
    const header = {}
    const url = client.globals.backend_url + "api/index.php/users"
    header['Accept'] = 'application/json'
    header['DOLAPIKEY'] = client.globals.dolApiKey
    header['Content-Type'] = 'application/json'
    header['Accept'] = 'application/json'
    const users = dataTable.hashes()
    for (const user of users) {
        await fetch(url, {
            method: 'POST',
            headers: header,
            body: JSON.stringify(
                {
                    login: user['login'],
                    lastname: user['lastname'],
                    password: user['password']
                }
            )
        })
            .then((response) => {
                if (response.status < 200 || response.status >= 400) {
                    throw new Error('failed to create user: ' + user['login'] + ' ' + response.statusText);
                }
                return response.text();
            });
    }
}

const getUser = async function () {
    const header = {}
    const url = client.globals.backend_url + "api/index.php/users"
    const users = {}
    header['Accept'] = 'application/json'
    header['DOLAPIKEY'] = client.globals.dolApiKey
    await fetch(url, {
        method: 'GET',
        headers: header
    })
        .then(async (response) => {
            const jsonResponse = await response.json()
            for (const user of jsonResponse) {
                users[user.id] = user.id
            }
        })
    return (users)
}

Before(async () => {
    initialUsers = await getUser();
})

After(async () => {
    const finalUsers = await getUser();
    const header = {}
    const url = client.globals.backend_url + "api/index.php/users/"
    header['Accept'] = 'application/json'
    header['DOLAPIKEY'] = client.globals.dolApiKey
    let found
    for (const finalUser in finalUsers) {
        for (const initialUser in initialUsers) {
            found = false;
            if (initialUser === finalUser) {
                found = true
                break
            }
        }
        if (!found) {
            await fetch(url + finalUser, {
                method: 'DELETE',
                headers: header
            })
                .then(res => {
                    if (res.status < 200 || res.status >= 400) {
                        throw new Error("Failed to delete user: " + res.statusText)
                    }
                })
        }
    }
})
