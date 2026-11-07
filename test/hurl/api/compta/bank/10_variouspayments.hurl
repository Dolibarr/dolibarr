# GET variouspayments
GET http://{{hostnport}}/api/index.php/variouspayments
HTTP 200

# GET sorted variouspayments ASC
GET http://{{hostnport}}/api/index.php/variouspayments?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted variouspayments DESC
GET http://{{hostnport}}/api/index.php/variouspayments?sortfield=t.rowid&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/variouspayments?limit=100&page=0
HTTP 200

# GET with properties=id%2Clabel
GET http://{{hostnport}}/api/index.php/variouspayments?properties=id%2Clabel
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/variouspayments?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/variouspayments?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET various payment with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/variouspayments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Various payment not found"}}

# POST {} - missing mandatory field datep
POST http://{{hostnport}}/api/index.php/variouspayments
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: datep field missing"}}

# POST missing mandatory field amount
POST http://{{hostnport}}/api/index.php/variouspayments
{ "datep": 1783468800 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: amount field missing"}}

# POST missing mandatory field label
POST http://{{hostnport}}/api/index.php/variouspayments
{ "datep": 1783468800, "amount": 10 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: label field missing"}}

# DELETE without id
DELETE http://{{hostnport}}/api/index.php/variouspayments/
HTTP 405

# DELETE various payment with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/variouspayments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Various payment not found"}}

# PUT without id
PUT http://{{hostnport}}/api/index.php/variouspayments/
{}
HTTP 405

# PUT various payment with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/variouspayments/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: Various payment not found"}}

# GET variouspayments DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/variouspayments
DOLAPIENTITY: 1
HTTP 200

# GET variouspayments DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/variouspayments
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# --- Full CRUD happy path (disabled on purpose) ---
# Disabled in CI because it creates real records and requires an existing bank
# account id (fk_account) and payment type id (type_payment) that are specific
# to each environment. Kept here as executable documentation of the flow.
#
# POST http://{{hostnport}}/api/index.php/variouspayments
# { "datep": 1783468800, "datev": 1783468800, "sens": 1, "amount": 77.5, "type_payment": 4, "fk_account": 1, "label": "Created via API", "accountancy_code": "627" }
# HTTP 200
# [Captures]
# variouspayment_id: jsonpath "$"
#
# PUT http://{{hostnport}}/api/index.php/variouspayments/{{variouspayment_id}}
# { "label": "Updated via API", "amount": 88.88 }
# HTTP 200
#
# DELETE http://{{hostnport}}/api/index.php/variouspayments/{{variouspayment_id}}
# HTTP 200
