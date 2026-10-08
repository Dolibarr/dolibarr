# GET setup/dictionary/countries
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries
HTTP 200

# GET sorted setup/dictionary/countries
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?sortfield=code&sortorder=ASC
HTTP 200

# GET reverse sorted setup/dictionary/countries
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?sortfield=code&sortorder=DSC
HTTP 200

# GET reverse sorted setup/dictionary/countries
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?sortfield=code&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?limit=100&page=1
HTTP 200

# GET with filter that should give no answer
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?filter=foobar
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$" count == 0

# GET with filter that should give no answer
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?filter=foobar
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists


# GET with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}

# POST {}
POST http://{{hostnport}}/api/index.php/setup/dictionary/countries
{}
HTTP 405
{"error":{"code":405,"message":"Method Not Allowed"}}


# DELETE
DELETE http://{{hostnport}}/api/index.php/setup/dictionary/countries/
HTTP 405

# DELETE mailing with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/setup/dictionary/countries/0
HTTP 404
{"error":{"code":404,"message":"Not Found"}}


# PUT
PUT http://{{hostnport}}/api/index.php/setup/dictionary/countries/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/setup/dictionary/countries/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found"}}


# GET setup/dictionary/countries DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries
DOLAPIENTITY: 1
HTTP 200

# GET setup/dictionary/countries DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# GET id of first element
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?limit=1&page=0
HTTP 200
[Asserts]
jsonpath "$[0].id" exists
[Captures]
template-id: jsonpath "$[0]['id']"


# GET with no Code
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/
HTTP 400
{"error":{"code":400,"message":"Bad Request: invalid value specified for `id`"}}

# GET with no ISO
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/
HTTP 400
{"error":{"code":400,"message":"Bad Request: invalid value specified for `id`"}}
