# GET warehouses
GET http://{{hostnport}}/api/index.php/warehouses
HTTP 200

# GET sorted warehouses
GET http://{{hostnport}}/api/index.php/warehouses?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted warehouses
GET http://{{hostnport}}/api/index.php/warehouses?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/warehouses?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/warehouses?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/warehouses?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/warehouses?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/warehouses?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET warehouse with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/warehouses/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No warehouse with id=0 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/warehouses
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: label field missing"}}

# POST topic required
POST http://{{hostnport}}/api/index.php/warehouses
{ "id" : 42, "label" : "automatic test using 10_warehouses.hurl"}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}

# POST type_template field required
POST http://{{hostnport}}/api/index.php/warehouses
{ "id" : 42, "label" : "automatic test using 10_warehouses.hurl", "topic" : "automatic test using 10_warehouses.hurl" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}

# POST No id in post request data
POST http://{{hostnport}}/api/index.php/warehouses
{ "id" : 42, "label" : "automatic test using 10_warehouses.hurl", "topic" : "automatic test using 10_warehouses.hurl", "type_template" : "all" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/warehouses/
HTTP 405

# DELETE warehouse with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/warehouses/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No warehouse with id=0 can exist"}}

# PUT
PUT http://{{hostnport}}/api/index.php/warehouses/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/warehouses/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No warehouse with id=0 can exist"}}

# GET products in warehouse with ID non integer ID - which should not exist
GET http://{{hostnport}}/api/index.php/warehouses/0products
HTTP 400
{"error":{"code":400,"message":"Bad Request: invalid value specified for `id`"}}

# GET products in warehouse with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/warehouses/0/products
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400
jsonpath "$.error.message" == "Bad Request: Invalid value specified for `id`. Minimum required value is 1."

# GET warehouses DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/warehouses
DOLAPIENTITY: 1
HTTP 200

# GET warehouses DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/warehouses
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# http://localhost:8080/api/index.php/warehouses?sortfield=t.rowid&sortorder=ASC&limit=1

# Capture a warehouse ID number
GET http://{{hostnport}}/api/index.php/warehouses?limit=1
HTTP 200
[Captures]
warehouse_id: jsonpath "$[0]['id']"

# GET products in warehouse with captured ID - which should exist
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products
HTTP 200

# GET products in warehouse with captured ID - limit 1
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?limit=1
HTTP 200


# GET products in warehouse with captured ID - with stockdata
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includestockdata=1
HTTP 200

# GET products in warehouse with captured ID - no stockdata
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includestockdata=0
HTTP 200


# GET products in warehouse with captured ID - with subproducts
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includesubproducts=true
HTTP 200

# GET products in warehouse with captured ID - no subproducts
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includesubproducts=false
HTTP 200


# GET products in warehouse with captured ID - with includeparentid
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includeparentid=true
HTTP 200

# GET products in warehouse with captured ID - no includeparentid
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includeparentid=false
HTTP 200


# GET products in warehouse with captured ID - with includetrans
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includetrans=true
HTTP 200

# GET products in warehouse with captured ID - no includetrans
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?includetrans=false
HTTP 200



# GET products with pagination_data=false
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?pagination_data=false
HTTP 200

# GET products with pagination_data=true
GET http://{{hostnport}}/api/index.php/warehouses/{{warehouse_id}}/products?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" exists
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100
