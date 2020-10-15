const { Before, After } = require('cucumber');
const { client } = require('nightwatch-api');
const fetch = require('node-fetch');
let initialUsers = {};
let dolApiKey = '';

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

Before(async function getDolApiKey() {
	const header = {}
	const adminUsername = client.globals.adminUsername;
	const adminPassword = client.globals.adminPassword;
	const params = new URLSearchParams()
	params.set('login', adminUsername)
	params.set('password', adminPassword)
	const apiKey = client.globals.backend_url + `api/index.php/login?${params.toString()}`;
	header['Accept'] = 'application/json'
	await fetch(apiKey, {
		method: 'GET',
		headers: header
	})
		.then(async (response) => {
			const jsonResponse = await response.json()
			dolApiKey = jsonResponse['success']['token']
			client.globals.dolApiKey = dolApiKey
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
