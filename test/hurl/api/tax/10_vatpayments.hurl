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

# --- Payment happy path: a VAT declaration paid in full, with its bank line ---
# Uses its own bank account, removed at the end.

POST http://{{hostnport}}/api/index.php/bankaccounts
{ "ref": "HURLVAT", "label": "Hurl VAT payments", "type": 1, "currency_code": "EUR", "country_id": 1 }
HTTP 200
[Captures]
vat_bank_id: jsonpath "$"

POST http://{{hostnport}}/api/index.php/vatpayments
{ "datep": 1785931200, "datev": 1785456000, "amount": 330, "label": "Hurl VAT declaration" }
HTTP 200
[Captures]
vat_id: jsonpath "$"

# POST payment without accountid while module bank is on
POST http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
{ "datepaye": "2026-08-05", "amount": 330, "paiementtype": 2 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: accountid field missing"}}

# POST payment with a datepaye that is not a date
POST http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
{ "datepaye": "not a date", "amount": 330, "paiementtype": 2, "accountid": {{vat_bank_id}} }
HTTP 400
{"error":{"code":400,"message":"Bad Request: datepaye must be a timestamp or a date YYYY-MM-DD"}}

# POST payment on a VAT declaration that does not exist
POST http://{{hostnport}}/api/index.php/vatpayments/0/payments
{ "datepaye": "2026-08-05", "amount": 330, "paiementtype": 2, "accountid": {{vat_bank_id}} }
HTTP 404
{"error":{"code":404,"message":"Not Found: VAT declaration not found"}}

# POST payment on a bank account that does not exist: refused, nothing is kept
POST http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
{ "datepaye": "2026-08-05", "amount": 330, "paiementtype": 2, "accountid": 999999999 }
HTTP 400
[Asserts]
jsonpath "$.error.message" contains "Add payment to bank error"

GET http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
HTTP 200
[Asserts]
jsonpath "$" count == 0

# POST payment of the full amount: the declaration is closed and the bank line exists
POST http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}/payments
{ "datepaye": "2026-08-05", "amount": 330, "paiementtype": 2, "accountid": {{vat_bank_id}}, "num_payment": "HURL-VAT" }
HTTP 200
[Captures]
vat_payment_id: jsonpath "$"

GET http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}
HTTP 200
[Asserts]
jsonpath "$.paye" == "1"

GET http://{{hostnport}}/api/index.php/vatpayments/payments/{{vat_payment_id}}
HTTP 200
[Captures]
vat_bank_line_id: jsonpath "$.bank_line"
[Asserts]
jsonpath "$.bank_account" == "{{vat_bank_id}}"

GET http://{{hostnport}}/api/index.php/bankaccounts/lines/{{vat_bank_line_id}}
HTTP 200
[Asserts]
jsonpath "$.label" == "(VATPayment)"
jsonpath "$.fk_account" == "{{vat_bank_id}}"
jsonpath "$.fk_type" == "VIR"
jsonpath "$.amount" toFloat == -330

DELETE http://{{hostnport}}/api/index.php/vatpayments/payments/{{vat_payment_id}}
HTTP 200

DELETE http://{{hostnport}}/api/index.php/vatpayments/{{vat_id}}
HTTP 200

DELETE http://{{hostnport}}/api/index.php/bankaccounts/{{vat_bank_id}}
HTTP 200
