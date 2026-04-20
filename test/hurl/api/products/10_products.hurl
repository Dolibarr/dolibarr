# GET products
GET http://{{hostnport}}/api/index.php/products
HTTP 200

# GET sorted products
GET http://{{hostnport}}/api/index.php/products?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted products
GET http://{{hostnport}}/api/index.php/products?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/products?limit=100&page=1
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/products?properties=id%2Cstatus
HTTP 200
[Captures]
total_count: jsonpath "$" count

[Asserts]
jsonpath "$[*].id" count == {{total_count}}
jsonpath "$[*].status" count == {{total_count}}
jsonpath "$[*].fk_project" count == 0


# GET with properties=id%2Cstatus%2Cfk_project
GET http://{{hostnport}}/api/index.php/products?properties=id%2Cstatus%2Cfk_project
HTTP 200
[Captures]
total_count: jsonpath "$" count

[Asserts]
jsonpath "$[*].id" count == {{total_count}}
jsonpath "$[*].status" count == {{total_count}}
jsonpath "$[*].id" count == {{total_count}}
jsonpath "$[*].fk_project" count == {{total_count}}

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/products?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/products?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100


# GET group with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/products/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: bad value for parameter id, ref, ref_ext or barcode"}}

# POST {}
POST http://{{hostnport}}/api/index.php/products
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: ref field missing"}}

# POST { "ref" : 42 }
POST http://{{hostnport}}/api/index.php/products
{ "ref" : 42 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: label field missing"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/products/
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/products/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/products/0
{}
HTTP 500
{"error":{"code":500,"message":"Internal Server Error: Error updating product","0":"ErrorWrongParameters","1":"ErrorBadRef"}}

# GET products DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/products
DOLAPIENTITY: 1
HTTP 200

# GET products DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/products
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"
