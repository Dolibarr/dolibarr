const { Before, Given, When, Then, After } = require('cucumber');
const { client } = require('nightwatch-api');
const fetch = require('node-fetch');
let initialUsers = {};
let dolApiKey = '';

Given('the administrator has logged in using the webUI', async function () {
	await client.page.loginPage().navigate().waitForLoginPage();
	await client.page.loginPage().userLogsInWithUsernameAndPassword(client.globals.adminUsername, client.globals.adminPassword);
	return client.page.loginPage().userIsLoggedIn(client.globals.adminUsername);
});

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

const getUsers = async function () {
	const header = {};
	const url = client.globals.backend_url + 'api/index.php/users';
	const users = {};
	header['Accept'] = 'application/json';
	header['DOLAPIKEY'] = dolApiKey;
	await fetch(url, {
		method: 'GET',
		headers: header
	})
		.then(async (response) => {
			const json_response = await response.json();
			for (const user of json_response) {
				users[user.id] = user.id;
			}
		});
	return users;
};

const adminHasCreatedUser = async function (dataTable) {
	const header = {};
	const url = client.globals.backend_url + 'api/index.php/users';
	header['Accept'] = 'application/json';
	header['DOLAPIKEY'] = dolApiKey;
	header['Content-Type'] = 'application/json';
	const userDetails = dataTable.hashes();
	for (const user of userDetails) {
		await fetch(url, {
			method: 'POST',
			headers: header,
			body: JSON.stringify(
				{
					login: user['login'],
					lastname: user['last name'],
					pass: user['password']
				}
			)
		})
			.then((response) => {
				if (response.status < 200 || response.status >= 400) {
					throw new Error('Failed to create user: ' + user['login'] +
						' ' + response.statusText);
				}
				return response.text();
			});
	}
};

Before(async () => {
	const header = {}
	const adminUsername = client.globals.adminUsername;
	const adminPassword = client.globals.adminPassword;
	const params = new URLSearchParams()
	params.set('login', adminUsername)
	params.set('password', adminPassword)
	const apiKey = `http://localhost/dolibarr/htdocs/api/index.php/login?${params.toString()}`;
	header['Accept'] = 'application/json'
	await fetch(apiKey, {
		method: 'GET',
		headers: header
	})
		.then(async (response) => {
			const jsonResponse = await response.json()
			dolApiKey = jsonResponse['success']['token']
		})
})
Before(async () => {
	initialUsers = await getUsers();
});

After(async () => {
	const finalUsers = await getUsers();
	const header = {};
	const url = client.globals.backend_url + 'api/index.php/users/';
	header['Accept'] = 'application/json';
	header['DOLAPIKEY'] = dolApiKey;
	let found;
	for (const finaluser in finalUsers) {
		for (const initialuser in initialUsers) {
			found = false;
			if (initialuser === finaluser) {
				found = true;
				break;
			}
		}
		if (!found) {
			await fetch(url + finaluser, {
				method: 'DELETE',
				headers: header
			})
				.then(res => {
					if (res.status < 200 || res.status >= 400) {
						throw new Error("Failed to delete user: " + res.statusText);
					}
				});
		}
	}
});
