# GET vatpayments
GET http://{{hostnport}}/api/index.php/vatpayments
HTTP 200

# GET sorted vatpayments ASC
GET http://{{hostnport}}/api/index.php/vatpayments?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted vatpayments DESC
GET http://{{hostnport}}/api/index.php/vatpayments?sortfield=t.rowid&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/vatpayments?limit=100&page=0
HTTP 200

# GET with properties=id%2Clabel
GET http://{{hostnport}}/api/index.php/vatpayments?properties=id%2Clabel
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/vatpayments?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/vatpayments?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET VAT payment with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/vatpayments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: VAT payment not found"}}

# GET payments sub-resource of a non-existent declaration (empty list)
GET http://{{hostnport}}/api/index.php/vatpayments/0/payments
HTTP 200

# GET all payments (collection). Must not be mistaken for /{id} with id=payments.
GET http://{{hostnport}}/api/index.php/vatpayments/payments
HTTP 200

# GET a single payment with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/vatpayments/payments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: VAT payment not found"}}

# POST {} - missing mandatory field datep
POST http://{{hostnport}}/api/index.php/vatpayments
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: datep field missing"}}

# POST missing mandatory field datev
POST http://{{hostnport}}/api/index.php/vatpayments
{ "datep": 1783468800 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: datev field missing"}}

# POST payment {} - missing mandatory field datepaye
POST http://{{hostnport}}/api/index.php/vatpayments/1/payments
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: datepaye field missing"}}

# DELETE without id
DELETE http://{{hostnport}}/api/index.php/vatpayments/
HTTP 405

# DELETE VAT payment with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/vatpayments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: VAT payment not found"}}

# PUT without id
PUT http://{{hostnport}}/api/index.php/vatpayments/
{}
HTTP 405

# PUT VAT payment with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/vatpayments/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: VAT payment not found"}}

# GET vatpayments DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/vatpayments
DOLAPIENTITY: 1
HTTP 200

# GET vatpayments DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/vatpayments
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# --- Full CRUD + payments happy path (disabled on purpose) ---
# Disabled in CI because it creates real records and requires an existing bank
# account id (fk_account/accountid) and payment type id (type_payment/paiementtype).
# Kept as executable documentation of the flow.
#
# POST http://{{hostnport}}/api/index.php/vatpayments
# { "datep": 1783468800, "datev": 1783468800, "amount": 330, "label": "Created via API", "type_payment": 4, "fk_account": 1 }
# HTTP 200
# [Captures]
# vat_id: jsonpath "$"
#
# POST http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
# { "datepaye": 1783468800, "amount": 100, "paiementtype": 4, "accountid": 1, "num_payment": "VAT-CHQ1" }
# HTTP 200
# [Captures]
# vat_payment_id: jsonpath "$"
#
# GET http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
# HTTP 200
#
# DELETE http://{{hostnport}}/api/index.php/vatpayments/payments/{{vat_payment_id}}
# HTTP 200
#
# DELETE http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}
# HTTP 200
