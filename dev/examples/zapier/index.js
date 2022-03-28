/*jshint esversion: 6 */
const triggerAction = require('./triggers/action');
const triggerOrder = require('./triggers/order');
const triggerThirdparty = require('./triggers/thirdparty');
const triggerContact = require('./triggers/contact');
const triggerTicket = require('./triggers/ticket');
const triggerUser = require('./triggers/user');
const triggerMember = require('./triggers/member');

const searchThirdparty = require('./searches/thirdparty');
const searchContact = require('./searches/contact');
const searchMember = require('./searches/member');

const createThirdparty = require('./creates/thirdparty');
const createContact = require('./creates/contact');
const createMember = require('./creates/member');

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
        [triggerContact.key]: triggerContact,
        [triggerTicket.key]: triggerTicket,
        [triggerUser.key]: triggerUser,
        [triggerMember.key]: triggerMember,
    },

    // If you want your searches to show up, you better include it here!
    searches: {
        [searchThirdparty.key]: searchThirdparty,
        [searchContact.key]: searchContact,
        [searchMember.key]: searchMember,
    },

    // If you want your creates to show up, you better include it here!
    creates: {
        [createThirdparty.key]: createThirdparty,
        [createContact.key]: createContact,
        [createMember.key]: createMember,
    }
};

// Finally, export the app.
module.exports = App;
