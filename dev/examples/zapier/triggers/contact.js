const subscribeHook = (z, bundle) => {
    // `z.console.log()` is similar to `console.log()`.
    z.console.log('suscribing hook!');

    // bundle.targetUrl has the Hook URL this app should call when an action is created.
    const data = {
        url: bundle.targetUrl,
        event: bundle.event,
        module: 'contact',
        action: bundle.inputData.action
    };

    const url = bundle.authData.url  + '/api/index.php/zapierapi/hook';

    // You can build requests and our client will helpfully inject all the variables
    // you need to complete. You can also register middleware to control this.
    const options = {
        url: url,
        method: 'POST',
        body: data,
    };

    // You may return a promise or a normal data structure from any perform method.
    return z.request(options).then((response) => JSON.parse(response.content));
};

const unsubscribeHook = (z, bundle) => {
    // bundle.subscribeData contains the parsed response JSON from the subscribe
    // request made initially.
    z.console.log('unsuscribing hook!');

    // You can build requests and our client will helpfully inject all the variables
    // you need to complete. You can also register middleware to control this.
    const options = {
        url: bundle.authData.url + '/api/index.php/zapierapi/hook/' + bundle.subscribeData.id,
        method: 'DELETE',
    };

    // You may return a promise or a normal data structure from any perform method.
    return z.request(options).then((response) => JSON.parse(response.content));
};

const getContact = (z, bundle) => {
    // bundle.cleanedRequest will include the parsed JSON object (if it's not a
    // test poll) and also a .querystring property with the URL's query string.
    const contact = {
        id: bundle.cleanedRequest.id,
        name: bundle.cleanedRequest.name,
        name_alias: bundle.cleanedRequest.name_alias,
        firstname: bundle.cleanedRequest.firstname,
        address: bundle.cleanedRequest.address,
        zip: bundle.cleanedRequest.zip,
        town: bundle.cleanedRequest.town,
        email: bundle.cleanedRequest.email,
        phone_pro: bundle.cleanedRequest.phone_pro,
        phone_perso: bundle.cleanedRequest.phone_perso,
        phone_mobile: bundle.cleanedRequest.phone_mobile,
        authorId: bundle.cleanedRequest.authorId,
        createdAt: bundle.cleanedRequest.createdAt,
        action: bundle.cleanedRequest.action
    };

    return [contact];
};

const getFallbackRealContact = (z, bundle) => {
    // For the test poll, you should get some real data, to aid the setup process.
    const module = bundle.inputData.module;
    const options = {
        url: bundle.authData.url + '/api/index.php/contacts/0',
    };

    return z.request(options).then((response) => [JSON.parse(response.content)]);
};

// const getModulesChoices = (z/*, bundle*/) => {
//     // For the test poll, you should get some real data, to aid the setup process.
//     const options = {
//         url: bundle.authData.url + '/api/index.php/zapierapi/getmoduleschoices',
//     };

//     return z.request(options).then((response) => JSON.parse(response.content));
// };
// const getModulesChoices = () => {
//     return {
//         orders: "Order",
//         invoices: "Invoice",
//         contacts: "Contact",
//         contacts: "Contacts"
//     };
// };

// const getActionsChoices = (z, bundle) => {
//     // For the test poll, you should get some real data, to aid the setup process.
//     const module = bundle.inputData.module;
//     const options = {
//         url:  url: bundle.authData.url + '/api/index.php/zapierapi/getactionschoices/thirparty`,
//     };

//     return z.request(options).then((response) => JSON.parse(response.content));
// };

// We recommend writing your triggers separate like this and rolling them
// into the App definition at the end.
module.exports = {
    key: 'contact',

    // You'll want to provide some helpful display labels and descriptions
    // for users. Zapier will put them into the UX.
    noun: 'Contact',
    display: {
        label: 'New Contact',
        description: 'Triggers when a new contact action is done in Dolibarr.'
    },

    // `operation` is where the business logic goes.
    operation: {

        // `inputFields` can define the fields a user could provide,
        // we'll pass them in as `bundle.inputData` later.
        inputFields: [
            {
                key: 'action',
                required: true,
                type: 'string',
                helpText: 'Which action of contact this should trigger on.',
                choices: {
                    create: "Create",
                    modify: "Modify",
                    validate: "Validate",
                }
            }
        ],

        type: 'hook',

        performSubscribe: subscribeHook,
        performUnsubscribe: unsubscribeHook,

        perform: getContact,
        performList: getFallbackRealContact,

        // In cases where Zapier needs to show an example record to the user, but we are unable to get a live example
        // from the API, Zapier will fallback to this hard-coded sample. It should reflect the data structure of
        // returned records, and have obviously dummy values that we can show to any user.
        sample: {
            id: 1,
            createdAt: 1472069465,
            lastname: 'DOE',
            firstname: 'John',
            authorId: 1,
            action: 'create'
        },

        // If the resource can have fields that are custom on a per-user basis, define a function to fetch the custom
        // field definitions. The result will be used to augment the sample.
        // outputFields: () => { return []; }
        // Alternatively, a static field definition should be provided, to specify labels for the fields
        outputFields: [
            {key: 'id', type: "integer", label: 'ID'},
            {key: 'createdAt', label: 'Created At'},
            {key: 'lastname', label: 'Lastname'},
            {key: 'firstname', label: 'Firstname'},
            {key: 'phone', label: 'Phone pro'},
            {key: 'phone_perso', label: 'Phone perso'},
            {key: 'phone_mobile', label: 'Phone mobile'},
            {key: 'authorId', type: "integer", label: 'Author ID'},
            {key: 'action', label: 'Action'}
        ]
    }
};
