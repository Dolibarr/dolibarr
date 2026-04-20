/*jshint esversion: 6 */
// create a particular member by name
const createMember = async (z, bundle) => {
    const apiurl = bundle.authData.url  + '/api/index.php/members';

    const response = await z.request({
        method: 'POST',
        url: apiurl,
        body: {
            name: bundle.inputData.name,
            name_alias: bundle.inputData.name_alias,
            ref_ext: bundle.inputData.ref_ext,
            ref_int: bundle.inputData.ref_int,
            address: bundle.inputData.address,
            zip: bundle.inputData.zip,
            town: bundle.inputData.town,
            country_code: bundle.inputData.country_code,
            country_id: bundle.inputData.country_id,
            country: bundle.inputData.country,
            phone: bundle.inputData.phone,
            email: bundle.inputData.email,
            sens: 'fromzapier'
        }
    });
    const result = z.JSON.parse(response.content);
    // api returns an integer when ok, a json when ko
    return result.response || {id: response};
};

module.exports = {
    key: 'member',
    noun: 'Member',

    display: {
        label: 'Create Member',
        description: 'Creates a member.'
    },

    operation: {
        inputFields: [
            {key: 'name', required: true},
            {key: 'name_alias', required: false},
            {key: 'address', required: false},
            {key: 'zip', required: false},
            {key: 'town', required: false},
            {key: 'email', required: false}
        ],
        perform: createMember,

        sample: {
            id: 1,
            name: 'DUPOND',
            name_alias: 'DUPOND Ltd',
            address: 'Rue des Canaries',
            zip: '34090',
            town: 'MONTPELLIER',
            phone: '0123456789',
            fax: '2345678901',
            email: 'robot@domain.com'
        },

        outputFields: [
            {key: 'id', type: "integer", label: 'ID'},
            {key: 'name', label: 'Name'},
            {key: 'name_alias', label: 'Name alias'},
            {key: 'address', label: 'Address'},
            {key: 'zip', label: 'Zip'},
            {key: 'town', label: 'Town'},
            {key: 'phone', label: 'Phone'},
            {key: 'fax', label: 'Fax'},
            {key: 'email', label: 'Email'}
        ]
    }
};
