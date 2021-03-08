/*jshint esversion: 6 */
// create a particular thirdparty by name
const createThirdparty = async (z, bundle) => {
    const apiurl = bundle.authData.url  + '/api/index.php/thirdparties';

    const response = await z.request({
        method: 'POST',
        url: apiurl,
        body: JSON.stringify({
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
            client: bundle.inputData.client,
            fournisseur: bundle.inputData.fournisseur,
            code_client: bundle.inputData.code_client,
            code_fournisseur: bundle.inputData.code_fournisseur,
            sens: 'fromzapier'
        })
    });
    const result = z.JSON.parse(response.content);
    // api returns an integer when ok, a json when ko
    return result.response || {id: response};
};

module.exports = {
    key: 'thirdparty',
    noun: 'Thirdparty',

    display: {
        label: 'Create Thirdparty',
        description: 'Creates a thirdparty.'
    },

    operation: {
        inputFields: [
            {key: 'name', required: true},
            {key: 'name_alias', required: false},
            {key: 'address', required: false},
            {key: 'zip', required: false},
            {key: 'town', required: false},
            {key: 'email', required: false},
            {key: 'client', type: 'integer', required: false},
            {key: 'fournisseur', type: 'integer', required: false},
            {key: 'code_client', required: false},
            {key: 'code_fournisseur', required: false}
        ],
        perform: createThirdparty,

        sample: {
            id: 1,
            name: 'DUPOND',
            name_alias: 'DUPOND Ltd',
            address: 'Rue des Canaries',
            zip: '34090',
            town: 'MONTPELLIER',
            phone: '0123456789',
            fax: '2345678901',
            email: 'robot@domain.com',
            client: 1,
            fournisseur: 0,
            code_client: 'CU1903-1234',
            code_fournisseur: 'SU1903-2345'
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
            {key: 'email', label: 'Email'},
            {key: 'client', type: "integer", label: 'Customer/Prospect 0/1/2/3'},
            {key: 'fournisseur', type: "integer", label: 'Supplier 0/1'},
            {key: 'code_client', label: 'Customer code'},
            {key: 'code_fournisseur', label: 'Supplier code'}
        ]
    }
};
