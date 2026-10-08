const {Before, After} = require('cucumber');
const {client} = require('nightwatch-api');
const fetch = require('node-fetch');
let initialUsers = {};
let dolApiKey = '';

const getUsers = async function (api_key = null) {
    const header = {};
    let dolApiKey;
    const url = client.globals.backend_url + 'api/index.php/users';
    if (api_key === null) {
        dolApiKey = client.globals.dolApiKey;
    } else {
        dolApiKey = api_key;
    }
    header['Accept'] = 'application/json';
    header['DOLAPIKEY'] = dolApiKey;
    await fetch(url, {
        method: 'GET',
        headers: header
    })
        .then(async (response) => {
            client.globals.response = response;
        });
};

const getUsersId = async function () {
    const users = {};
    await getUsers();
    const json_response = await client.globals.response.json();
    for (const user of json_response) {
        users[user.id] = user.id;
    }
    return users;
};

const getDolApiKey = async function (login = null, password = null) {
    const header = {};
    if (login === null && password === null) {
        login = client.globals.adminUsername;
        password = client.globals.adminPassword;
    }
    const params = new URLSearchParams();
    params.set('login', login);
    params.set('password', password);
    const apiKey = client.globals.backend_url + `api/index.php/login?${params.toString()}`;
    header['Accept'] = 'application/json';
    await fetch(apiKey, {
        method: 'GET',
        headers: header
    })
        .then(async (response) => {
            const jsonResponse = await response.json();
            dolApiKey = jsonResponse['success']['token'];
            if (login === client.globals.adminUsername && password === client.globals.adminPassword) {
                client.globals.dolApiKey = dolApiKey;
            }
        });
    return dolApiKey;
};

Before(async function getAdminDolApiKey() {
    await getDolApiKey();
});

Before(async () => {
    initialUsers = await getUsersId();
});

After(async () => {
    const finalUsers = await getUsersId();
    const header = {};
    const url = client.globals.backend_url + 'api/index.php/users/';
    header['Accept'] = 'application/json';
    header['DOLAPIKEY'] = client.globals.dolApiKey;
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

module.exports = {getDolApiKey, getUsers};
