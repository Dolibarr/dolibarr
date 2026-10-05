# GET users/groups
GET http://{{hostnport}}/api/index.php/users/groups
HTTP 200

# GET sorted users/groups
GET http://{{hostnport}}/api/index.php/users/groups?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted users/groups
GET http://{{hostnport}}/api/index.php/users/groups?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/users/groups?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/users/groups?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/users/groups?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/users/groups?pagination_data=false
HTTP 200

# GET group with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/users/groups/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No usergroup with id=0 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/users/groups
{}
HTTP 500
{"error":{"code":500,"message":"Internal Server Error: Error creating","0":null,"1":"Field 'Name' is required"}}

# POST {}
POST http://{{hostnport}}/api/index.php/users/groups
{ "id" : 42 }
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/users/groups/
HTTP 400

# PUT
PUT http://{{hostnport}}/api/index.php/users/groups/
{}
HTTP 400

# PUT
PUT http://{{hostnport}}/api/index.php/users/groups/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No usergroup with id=0 can exist"}}

# GET users/groups DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/users/groups
DOLAPIENTITY: 1
HTTP 200

# GET users/groups DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/users/groups
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"
