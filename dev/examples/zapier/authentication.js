/*jshint esversion: 6 */
const testAuth = (z , bundle) => {
    const url = bundle.authData.url+'/api/index.php/login';
    // Normally you want to make a request to an endpoint that is either specifically designed to test auth, or one that
    // every user will have access to, such as an account or profile endpoint like /me.
    // In this example, we'll hit httpbin, which validates the Authorization Header against the arguments passed in the URL path
    const promise = z.request({
        url: url,
    });

    // This method can return any truthy value to indicate the credentials are valid.
    // Raise an error to show
    return promise.then((response) => {
        if (response.status === 401) {
            throw new Error('The Session Key you supplied is invalid');
        }
        return response;
    });
};

const getSessionKey = (z, bundle) => {
    const url = bundle.authData.url + '/api/index.php/login';

    const promise = z.request({
        method: 'POST',
        url: url,
        body: {
            login: bundle.authData.login,
            password: bundle.authData.password,
        }
    });

    return promise.then((response) => {
        if (response.status === 401) {
            throw new Error('The login/password you supplied is invalid');
        }
        const json = JSON.parse(response.content);
        return {
            sessionKey: json.success.token || 'secret'
        };
    });
};

module.exports = {
    type: 'session',
    // Define any auth fields your app requires here. The user will be prompted to enter this info when
    // they connect their account.
    fields: [
        {
            key: 'url',
            label: 'Url of service',
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
    test: testAuth,
    // The method that will exchange the fields provided by the user for session credentials.
    sessionConfig: {
        perform: getSessionKey
    },
    // assuming "login" is a key returned from the test
    connectionLabel: '{{login}}'
};
