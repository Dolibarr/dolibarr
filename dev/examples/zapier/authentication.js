/*jshint esversion: 6 */
const test = (z , bundle) => {
    const url = bundle.authData.url+'/api/index.php/status';
    // Normally you want to make a request to an endpoint that is either specifically designed to test auth, or one that
    // every user will have access to, such as an account or profile endpoint like /me.
    // In this example, we'll hit httpbin, which validates the Authorization Header against the arguments passed in the URL path
    const promise = z.request({
        url: url,
    });

    // This method can return any truthy value to indicate the credentials are valid.
    // Raise an error to show
    return promise.then((response) => {
        if (response.status === 400) {
            throw new Error('400 -The Session Key you supplied is invalid');
        }
        if (response.status === 403) {
            throw new Error('403 -The Session Key you supplied is invalid');
        }
        return response;
    });
};

// To include the session key header on all outbound requests, simply define a function here.
// It runs runs before each request is sent out, allowing you to make tweaks to the request in a centralized spot
const includeSessionKeyHeader = (request, z, bundle) => {
    if (bundle.authData.sessionKey) {
        request.headers = request.headers || {};
        request.headers['DOLAPIKEY'] = bundle.authData.sessionKey;
    }
    return request;
};

// If we get a response and it is a 401, we can raise a special error telling Zapier to retry this after another exchange.
const sessionRefreshIf401 = (response, z, bundle) => {
    if (bundle.authData.sessionKey) {
        if (response.status === 401) {
            throw new z.errors.RefreshAuthError('Session apikey needs refreshing.');
        }
    }
    return response;
};

const getSessionKey = async (z, bundle) => {
    const url = bundle.authData.url + '/api/index.php/login';

    const response = await z.request({
        url: url,
        method: 'POST',
        body: {
            login: bundle.authData.login,
            password: bundle.authData.password,
        },
    });

    // if (response.status === 401) {
    //     throw new Error('The login/password you supplied is invalid');
    // }
    const json = JSON.parse(response.content);
    return {
        sessionKey: json.success.token || '',
    };
};

module.exports = {
    config: {
        type: 'session',
        sessionConfig: {
            perform: getSessionKey
        },
        // Define any auth fields your app requires here. The user will be prompted to enter this info when
        // they connect their account.
        fields: [
            {
                key: 'url',
                label: 'Url of service without trailing-slash',
                required: true,
                type: 'string'
            },
            {
                key: 'login',
                label: 'Login',
                required: true,
                type: 'string'
            },
            {
                key: 'password',
                label: 'Password',
                required: true,
                type: 'password'
            }
        ],
        // The test method allows Zapier to verify that the credentials a user provides are valid. We'll execute this
        // method whenever a user connects their account for the first time.
        test,
        // The method that will exchange the fields provided by the user for session credentials.
        // assuming "login" is a key returned from the test
        connectionLabel: '{{login}}'
    },
    befores: [includeSessionKeyHeader],
    afters: [sessionRefreshIf401],
};
