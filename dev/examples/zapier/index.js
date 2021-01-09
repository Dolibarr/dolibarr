/*jshint esversion: 6 */
const triggerAction = require('./triggers/action');
const triggerOrder = require('./triggers/order');
const triggerThirdparty = require('./triggers/thirdparty');
const triggerTicket = require('./triggers/ticket');
const triggerUser = require('./triggers/user');

const searchThirdparty = require('./searches/thirdparty');

const createThirdparty = require('./creates/thirdparty');

const {
    config: authentication,
    befores = [],
    afters = [],
} = require('./authentication');

// To include the session key header on all outbound requests, simply define a function here.
// It runs runs before each request is sent out, allowing you to make tweaks to the request in a centralized spot
// const includeSessionKeyHeader = (request, z, bundle) => {
//     if (bundle.authData.sessionKey) {
//         request.headers = request.headers || {};
//         request.headers['DOLAPIKEY'] = bundle.authData.sessionKey;
//     }
//     return request;
// };

// If we get a response and it is a 401, we can raise a special error telling Zapier to retry this after another exchange.
// const sessionRefreshIf401 = (response, z, bundle) => {
//     if (bundle.authData.sessionKey) {
//         if (response.status === 401) {
//             throw new z.errors.RefreshAuthError('Session apikey needs refreshing.');
//         }
//     }
//     return response;
// };

// We can roll up all our behaviors in an App.
const App = {
    // This is just shorthand to reference the installed dependencies you have. Zapier will
    // need to know these before we can upload
    version: require('./package.json').version,
    platformVersion: require('zapier-platform-core').version,

    authentication: authentication,

    // beforeRequest & afterResponse are optional hooks into the provided HTTP client
    beforeRequest: [
        ...befores
    ],

    afterResponse: [
        ...afters
    ],

    // If you want to define optional resources to simplify creation of triggers, searches, creates - do that here!
    resources: {
    },

    // If you want your trigger to show up, you better include it here!
    triggers: {
        [triggerAction.key]: triggerAction,
        [triggerOrder.key]: triggerOrder,
        [triggerThirdparty.key]: triggerThirdparty,
        [triggerTicket.key]: triggerTicket,
        [triggerUser.key]: triggerUser,
    },

    // If you want your searches to show up, you better include it here!
    searches: {
        [searchThirdparty.key]: searchThirdparty,
    },

    // If you want your creates to show up, you better include it here!
    creates: {
        [createThirdparty.key]: createThirdparty,
    }
};

// Finally, export the app.
module.exports = App;
