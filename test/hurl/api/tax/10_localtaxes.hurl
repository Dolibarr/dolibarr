# GET localtaxes
GET http://{{hostnport}}/api/index.php/localtaxes
HTTP 200

# GET sorted localtaxes ASC
GET http://{{hostnport}}/api/index.php/localtaxes?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted localtaxes DESC
GET http://{{hostnport}}/api/index.php/localtaxes?sortfield=t.rowid&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/localtaxes?limit=100&page=0
HTTP 200

# GET with properties=id%2Clabel
GET http://{{hostnport}}/api/index.php/localtaxes?properties=id%2Clabel
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/localtaxes?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/localtaxes?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET local tax with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/localtaxes/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Local tax not found"}}

# POST {} - missing mandatory field localtaxtype
POST http://{{hostnport}}/api/index.php/localtaxes
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: localtaxtype field missing"}}

# POST missing mandatory field datep
POST http://{{hostnport}}/api/index.php/localtaxes
{ "localtaxtype": 1 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: datep field missing"}}

# DELETE without id
DELETE http://{{hostnport}}/api/index.php/localtaxes/
HTTP 405

# DELETE local tax with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/localtaxes/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Local tax not found"}}

# PUT without id
PUT http://{{hostnport}}/api/index.php/localtaxes/
{}
HTTP 405

# PUT local tax with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/localtaxes/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: Local tax not found"}}

# GET localtaxes DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/localtaxes
DOLAPIENTITY: 1
HTTP 200

# GET localtaxes DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/localtaxes
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# --- Full CRUD happy path (disabled on purpose) ---
# Disabled in CI because it creates real records. Kept as executable documentation.
#
# POST http://{{hostnport}}/api/index.php/localtaxes
# { "localtaxtype": 1, "datep": 1783468800, "datev": 1783468800, "amount": 45.5, "label": "Created via API" }
# HTTP 200
# [Captures]
# localtax_id: jsonpath "$"
#
# PUT http://{{hostnport}}/api/index.php/localtaxes/{{localtax_id}}
# { "label": "Updated via API", "amount": 47 }
# HTTP 200
#
# DELETE http://{{hostnport}}/api/index.php/localtaxes/{{localtax_id}}
# HTTP 200
