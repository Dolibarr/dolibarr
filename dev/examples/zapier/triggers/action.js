const subscribeHook = (z, bundle) => {
    // `z.console.log()` is similar to `console.log()`.
    z.console.log('suscribing hook!');

    // bundle.targetUrl has the Hook URL this app should call when an action is created.
    const data = {
        url: bundle.targetUrl,
        event: bundle.event,
        module: 'action',
        action: bundle.inputData.action
    };

    const url = bundle.authData.url  + '/api/index.php/zapierapi/hook';

    // You can build requests and our client will helpfully inject all the variables
    // you need to complete. You can also register middleware to control this.
    const options = {
        url: url,
        method: 'POST',
        body: JSON.stringify(data)
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
        url: bundle.authData.url  + '/api/index.php/zapierapi/hook/' + bundle.subscribeData.id,
        method: 'DELETE',
    };

    // You may return a promise or a normal data structure from any perform method.
    return z.request(options).then((response) => JSON.parse(response.content));
};

const getAction = (z, bundle) => {
    // bundle.cleanedRequest will include the parsed JSON object (if it's not a
    // test poll) and also a .querystring property with the URL's query string.
    const action = {
        id: bundle.cleanedRequest.id,
        ref: bundle.cleanedRequest.ref,
        ref_client: bundle.cleanedRequest.ref_client,
        name: bundle.cleanedRequest.name,
        firstname: bundle.cleanedRequest.firstname,
        usertodo__name: bundle.cleanedRequest.usertodo__name,
        location: bundle.cleanedRequest.location,
        label: bundle.cleanedRequest.label,
        authorId: bundle.cleanedRequest.authorId,
        createdAt: bundle.cleanedRequest.createdAt,
        module: bundle.cleanedRequest.module,
        datep: bundle.cleanedRequest.datep,
        datef: bundle.cleanedRequest.datef,
        fulldayevent: bundle.cleanedRequest.fulldayevent,
        transparency: bundle.cleanedRequest.transparency,
        icalname: bundle.cleanedRequest.icalname,
        icalcolor: bundle.cleanedRequest.icalcolor,
        note: bundle.cleanedRequest.note,
        note_public: bundle.cleanedRequest.note_public,
        note_private: bundle.cleanedRequest.note_private,
        action: bundle.cleanedRequest.action
    };

    return [action];
};

const getFallbackRealAction = (z, bundle) => {
    // For the test poll, you should get some real data, to aid the setup process.
    const module = bundle.inputData.module;
    const options = {
        url: bundle.authData.url  + '/api/index.php/agendaevents/0',
    };

    return z.request(options).then((response) => [JSON.parse(response.content)]);
};

// const getActionsChoices = (z, bundle) => {
//     // For the test poll, you should get some real data, to aid the setup process.
//     const module = bundle.inputData.module;
//     const options = {
//         url: bundle.authData.url  + '/api/index.php/zapierapi/getactionschoices/actions',
//     };

//     return z.request(options).then((response) => JSON.parse(response.content));
// };

// We recommend writing your actions separate like this and rolling them
// into the App definition at the end.
module.exports = {
    key: 'action',

    // You'll want to provide some helpful display labels and descriptions
    // for users. Zapier will put them into the UX.
    noun: 'Action',
    display: {
        label: 'New Agenda',
        description: 'Trigger when a new agenda with action is done in Dolibarr.'
    },

    // `operation` is where the business logic goes.
    operation: {

        // `inputFields` can define the fields a user could provide,
        // we'll pass them in as `bundle.inputData` later.
        inputFields: [
            {
                key: 'action',
                type: 'string',
                helpText: 'Which action of agenda this should trigger on.',
                choices: {
                    create: "Create",
                    modify: "Modify",
                    delete: "Delete",
                }
            }
        ],

        type: 'hook',

        performSubscribe: subscribeHook,
        performUnsubscribe: unsubscribeHook,

        perform: getAction,
        performList: getFallbackRealAction,

        // In cases where Zapier needs to show an example record to the user, but we are unable to get a live example
        // from the API, Zapier will fallback to this hard-coded sample. It should reflect the data structure of
        // returned records, and have obviously dummy values that we can show to any user.
        sample: {
            id: 1,
            createdAt: 1472069465,
            name: 'Best Spagetti Ever',
            authorId: 1,
            action: 'create'
        },

        // If the resource can have fields that are custom on a per-user basis, define a function to fetch the custom
        // field definitions. The result will be used to augment the sample.
        // outputFields: () => { return []; }
        // Alternatively, a static field definition should be provided, to specify labels for the fields
        outputFields: [
            {key: 'id', label: 'ID'},
            {key: 'createdAt', label: 'Created At'},
            {key: 'name', label: 'Name'},
            {key: 'usertodo__name', label: 'UserToDo Name'},
            {key: 'authorId', label: 'Author ID'},
            {key: 'action', label: 'Action'}
        ]
    }
};
