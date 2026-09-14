# GET orders
GET http://{{hostnport}}/api/index.php/orders
HTTP 200

# GET sorted orders
GET http://{{hostnport}}/api/index.php/orders?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted orders
GET http://{{hostnport}}/api/index.php/orders?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/orders?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/orders?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/orders?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/orders?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/orders?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET order with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/orders/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No order with id=0 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/orders
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: socid field missing"}}

# POST topic required
POST http://{{hostnport}}/api/index.php/orders
{ "socid" : "22"}
HTTP 400
{"error":{"code":400,"message":"Bad Request: date field missing"}}
# this output should be adjusted for the real output when the check is done for required fields

# DELETE
DELETE http://{{hostnport}}/api/index.php/orders/
HTTP 405

# DELETE order with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/orders/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No order with id=0 can exist"}}

# PUT
PUT http://{{hostnport}}/api/index.php/orders/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/orders/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No order with id=0 can exist"}}



# GET orders DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/orders
DOLAPIENTITY: 1
HTTP 200

# GET orders DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/orders
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"


# Capture a order ID number
GET http://{{hostnport}}/api/index.php/orders?limit=1
HTTP 200
[Captures]
order_id: jsonpath "$[0]['id']"
order_socid: jsonpath "$[0]['socid']"

# POST random type and source
POST http://{{hostnport}}/api/index.php/orders/{{ order_id }}/contact/{{ order_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "random_source_989100dd0af388a9" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Source=random_source_989100dd0af388a9 and Type=random_type_65f0be7e9d538a6a not found in dictionary with active order contact types"}}

# POST internal source and "random" type
POST http://{{hostnport}}/api/index.php/orders/{{ order_id }}/contact/{{ order_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "internal" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Type=random_type_65f0be7e9d538a6a and Source=internal not found in dictionary with active order contact types"}}

# POST external source and "random" type
POST http://{{hostnport}}/api/index.php/orders/{{ order_id }}/contact/{{ order_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "external" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Type=random_type_65f0be7e9d538a6a and Source=external not found in dictionary with active order contact types"}}
