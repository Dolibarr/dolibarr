# GET contracts
GET http://{{hostnport}}/api/index.php/contracts
HTTP 200

# GET sorted contracts
GET http://{{hostnport}}/api/index.php/contracts?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted contracts
GET http://{{hostnport}}/api/index.php/contracts?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/contracts?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/contracts?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/contracts?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/contracts?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/contracts?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET contract with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/contracts/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No contract with id=0 can exist"}}
# POST {}
POST http://{{hostnport}}/api/index.php/contracts
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: socid field missing"}}

# POST topic required
POST http://{{hostnport}}/api/index.php/contracts
{ "id" : 42, "socid" : "22"}
HTTP 400
{"error":{"code":400,"message":"Bad Request: date_contrat field missing"}}

# POST type_template field required
POST http://{{hostnport}}/api/index.php/contracts
{ "id" : 42, "socid" : "22", "date_contrat" : "873223122" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: commercial_signature_id field missing"}}

# POST No id in post request data
POST http://{{hostnport}}/api/index.php/contracts
{ "id" : 42, "socid" : "22", "date_contrat" : "873223122", "commercial_signature_id" : "1" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: commercial_suivi_id field missing"}}

# POST No id in post request data
POST http://{{hostnport}}/api/index.php/contracts
{ "id" : 42, "socid" : "22", "date_contrat" : "873223122", "commercial_signature_id" : "1", "commercial_suivi_id" : "1" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/contracts/
HTTP 405

# DELETE contract with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/contracts/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No contract with id=0 can exist"}}

# PUT
PUT http://{{hostnport}}/api/index.php/contracts/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/contracts/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No contract with id=0 can exist"}}



# GET contracts DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/contracts
DOLAPIENTITY: 1
HTTP 200

# GET contracts DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/contracts
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# http://localhost:8080/api/index.php/contracts?sortfield=t.rowid&sortorder=ASC&limit=1

# POST socid 0
POST http://{{hostnport}}/api/index.php/contracts
{
"entity" : "1",
"socid": "0",
"date_contrat": 1759738652,
"commercial_signature_id": 1,
"commercial_suivi_id": 1,
"note_public": "TEST 22.0.2"
}
HTTP 404
{"error":{"code":404,"message":"Not Found: Thirdparty with id=0 not found"}}
