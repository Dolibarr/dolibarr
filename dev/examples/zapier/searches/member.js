module.exports = {
    key: 'member',

    // You'll want to provide some helpful display labels and descriptions
    // for users. Zapier will put them into the UX.
    noun: 'Member',
    display: {
        label: 'Find a Member',
        description: 'Search for member.'
    },

    // `operation` is where we make the call to your API to do the search
    operation: {
        // This search only has one search field. Your searches might have just one, or many
        // search fields.
        inputFields: [
            {
                key: 'lastname',
                type: 'string',
                label: 'Lastname',
                helpText: 'Lastname to limit to the search to (i.e. The company or %company%).'
            },
            {
                key: 'email',
                type: 'string',
                label: 'Email',
                helpText: 'Email to limit to the search to.'
            }
        ],

        perform: async (z, bundle) => {
            const url = bundle.authData.url + '/api/index.php/members/';

            // Put the search value in a query param. The details of how to build
            // a search URL will depend on how your API works.
            let filter = '';
            if (bundle.inputData.lastname) {
                filter = "t.lastname like \'%" + bundle.inputData.name + "%\'";
            }
            if (bundle.inputData.email) {
                if (bundle.inputData.lastname) {
                    filter += " and ";
                }
                filter += "t.email like \'" + bundle.inputData.email + "\'";
            }
            const response = await z.request({
                url: url,
                // this parameter avoid throwing errors and let us manage them
                skipThrowForStatus: true,
                params: {
                    sqlfilters: filter
                }
            });
            //z.console.log(response);
            if (response.status != 200) {
                return [];
            }
            return response.json;
        },

        // In cases where Zapier needs to show an example record to the user, but we are unable to get a live example
        // from the API, Zapier will fallback to this hard-coded sample. It should reflect the data structure of
        // returned records, and have obviously dummy values that we can show to any user.
        sample: {
            id: 1,
            createdAt: 1472069465,
            name: 'DOE',
            firstname: 'John',
            authorId: 1,
        },

        // If the resource can have fields that are custom on a per-user basis, define a function to fetch the custom
        // field definitions. The result will be used to augment the sample.
        // outputFields: () => { return []; }
        // Alternatively, a static field definition should be provided, to specify labels for the fields
        outputFields: [
            {
                key: 'id',
                type: "integer",
                label: 'ID'
            },
            { key: 'createdAt', type: "integer", label: 'Created At' },
            { key: 'name', label: 'Name' },
            { key: 'firstname', label: 'Firstname' },
            { key: 'authorId', type: "integer", label: 'Author ID' },
        ]
    }
};
