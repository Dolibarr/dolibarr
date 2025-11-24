# GET proposals
GET http://{{hostnport}}/api/index.php/proposals
HTTP 200

# GET sorted proposals
GET http://{{hostnport}}/api/index.php/proposals?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted proposals
GET http://{{hostnport}}/api/index.php/proposals?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/proposals?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/proposals?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/proposals?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/proposals?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/proposals?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET proposal with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/proposals/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No proposal with id=0 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/proposals
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: socid field missing"}}

# POST topic required
POST http://{{hostnport}}/api/index.php/proposals
{ "socid" : "22"}
HTTP 500
{"error":{"code":500,"message":"Internal Server Error: Error creating order","0":"Date of proposal is required"}}
# this output should be adjusted for the real output when the check is done for required fields

# DELETE
DELETE http://{{hostnport}}/api/index.php/proposals/
HTTP 405

# DELETE proposal with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/proposals/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No proposal with id=0 can exist"}}

# PUT
PUT http://{{hostnport}}/api/index.php/proposals/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/proposals/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No proposal with id=0 can exist"}}



# GET proposals DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/proposals
DOLAPIENTITY: 1
HTTP 200

# GET proposals DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/proposals
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"

# Capture a proposal ID number
GET http://{{hostnport}}/api/index.php/proposals?limit=1
HTTP 200
[Captures]
proposal_id: jsonpath "$[0]['id']"
proposal_socid: jsonpath "$[0]['socid']"

# POST random type and source
POST http://{{hostnport}}/api/index.php/proposals/{{ proposal_id }}/contact/{{ proposal_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "random_source_989100dd0af388a9" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Source=random_source_989100dd0af388a9 and Type=random_type_65f0be7e9d538a6a not found in dictionary with active proposal contact types"}}

# POST internal source and "random" type
POST http://{{hostnport}}/api/index.php/proposals/{{ proposal_id }}/contact/{{ proposal_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "internal" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Type=random_type_65f0be7e9d538a6a and Source=internal not found in dictionary with active proposal contact types"}}

# POST external source and "random" type
POST http://{{hostnport}}/api/index.php/proposals/{{ proposal_id }}/contact/{{ proposal_socid }}/random_type_65f0be7e9d538a6a
{ "source" : "external" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Combo of Type=random_type_65f0be7e9d538a6a and Source=external not found in dictionary with active proposal contact types"}}
