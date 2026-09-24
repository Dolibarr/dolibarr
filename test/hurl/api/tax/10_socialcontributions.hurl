# GET socialcontributions
GET http://{{hostnport}}/api/index.php/socialcontributions
HTTP 200

# GET sorted socialcontributions ASC
GET http://{{hostnport}}/api/index.php/socialcontributions?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted socialcontributions DESC
GET http://{{hostnport}}/api/index.php/socialcontributions?sortfield=t.rowid&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/socialcontributions?limit=100&page=0
HTTP 200

# GET with properties=id%2Clabel
GET http://{{hostnport}}/api/index.php/socialcontributions?properties=id%2Clabel
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/socialcontributions?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/socialcontributions?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET social contribution with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/socialcontributions/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Social contribution not found"}}

# GET payments sub-resource of a non-existent contribution (empty list)
GET http://{{hostnport}}/api/index.php/socialcontributions/0/payments
HTTP 200

# GET all payments (collection). Must not be mistaken for /{id} with id=payments.
GET http://{{hostnport}}/api/index.php/socialcontributions/payments
HTTP 200

# GET a single payment with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/socialcontributions/payments/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Payment not found"}}

# POST {} - missing mandatory field fk_type
POST http://{{hostnport}}/api/index.php/socialcontributions
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: fk_type field missing"}}

# POST missing mandatory field date_ech
POST http://{{hostnport}}/api/index.php/socialcontributions
{ "fk_type": 1 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: date_ech field missing"}}

# POST payment {} - missing mandatory field datepaye
POST http://{{hostnport}}/api/index.php/socialcontributions/1/payments
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: datepaye field missing"}}

# DELETE without id
DELETE http://{{hostnport}}/api/index.php/socialcontributions/
HTTP 405

# DELETE social contribution with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/socialcontributions/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Social contribution not found"}}

# PUT without id
PUT http://{{hostnport}}/api/index.php/socialcontributions/
{}
HTTP 405

# PUT social contribution with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/socialcontributions/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: Social contribution not found"}}

# GET socialcontributions DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/socialcontributions
DOLAPIENTITY: 1
HTTP 200

# GET socialcontributions DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/socialcontributions
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# --- Payment happy path: a contribution paid in full, with its bank line ---
# Uses its own bank account and contribution type 1 (TAXSECU), removed at the end.

POST http://{{hostnport}}/api/index.php/bankaccounts
{ "ref": "HURLSC", "label": "Hurl social contributions", "type": 1, "currency_code": "EUR", "country_id": 1 }
HTTP 200
[Captures]
sc_bank_id: jsonpath "$"

POST http://{{hostnport}}/api/index.php/socialcontributions
{ "fk_type": 1, "date_ech": 1785931200, "period": 1785456000, "amount": 150, "label": "Hurl social contribution" }
HTTP 200
[Captures]
sc_id: jsonpath "$"

# POST payment without accountid while module bank is on
POST http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}/payments
{ "datepaye": "2026-08-05", "amount": 150, "paiementtype": 3 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: accountid field missing"}}

# POST payment with a date that is neither a timestamp nor YYYY-MM-DD
POST http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}/payments
{ "datepaye": "05/08/2026", "amount": 150, "paiementtype": 3, "accountid": {{sc_bank_id}} }
HTTP 400
{"error":{"code":400,"message":"Bad Request: datepaye must be a timestamp or a date YYYY-MM-DD"}}

# POST payment on a contribution that does not exist
POST http://{{hostnport}}/api/index.php/socialcontributions/0/payments
{ "datepaye": "2026-08-05", "amount": 150, "paiementtype": 3, "accountid": {{sc_bank_id}} }
HTTP 404
{"error":{"code":404,"message":"Not Found: Social contribution not found"}}

# POST payment on a bank account that does not exist: refused, nothing is kept
POST http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}/payments
{ "datepaye": "2026-08-05", "amount": 150, "paiementtype": 3, "accountid": 999999999 }
HTTP 400
[Asserts]
jsonpath "$.error.message" contains "Add payment to bank error"

GET http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}/payments
HTTP 200
[Asserts]
jsonpath "$" count == 0

# POST payment of the full amount: the contribution is closed and the bank line exists
POST http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}/payments
{ "datepaye": "2026-08-05", "amount": 150, "paiementtype": 3, "accountid": {{sc_bank_id}}, "num_payment": "HURL-SC" }
HTTP 200
[Captures]
sc_payment_id: jsonpath "$"

GET http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}
HTTP 200
[Asserts]
jsonpath "$.paye" == "1"

GET http://{{hostnport}}/api/index.php/socialcontributions/payments/{{sc_payment_id}}
HTTP 200
[Captures]
sc_bank_line_id: jsonpath "$.bank_line"
[Asserts]
jsonpath "$.bank_account" == "{{sc_bank_id}}"

GET http://{{hostnport}}/api/index.php/bankaccounts/lines/{{sc_bank_line_id}}
HTTP 200
[Asserts]
jsonpath "$.label" == "(SocialContributionPayment)"
jsonpath "$.fk_account" == "{{sc_bank_id}}"
jsonpath "$.fk_type" == "PRE"
jsonpath "$.amount" toFloat == -150

DELETE http://{{hostnport}}/api/index.php/socialcontributions/payments/{{sc_payment_id}}
HTTP 200

DELETE http://{{hostnport}}/api/index.php/socialcontributions/{{sc_id}}
HTTP 200

DELETE http://{{hostnport}}/api/index.php/bankaccounts/{{sc_bank_id}}
HTTP 200
