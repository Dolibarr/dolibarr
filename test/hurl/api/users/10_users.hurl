# GET users
GET http://{{hostnport}}/api/index.php/users
HTTP 200

# GET sorted users
GET http://{{hostnport}}/api/index.php/users?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted users
GET http://{{hostnport}}/api/index.php/users?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/users?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/users?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/users?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/users?pagination_data=false
HTTP 200

# GET user with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/users/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No user with id=0 can exist"}}

# GET user with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/users/0/groups
HTTP 400
{"error":{"code":400,"message":"Bad Request: No user with id=0 can exist"}}

# GET user with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/users/0/notifications
HTTP 400
{"error":{"code":400,"message":"Bad Request: No user with id=0 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/users
{}
HTTP 500
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 500
jsonpath "$.error.message" == "Internal Server Error: login field missing"

# DELETE
DELETE http://{{hostnport}}/api/index.php/users/
HTTP 405

# DELETE user with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/users/0
HTTP 404
{"error":{"code":404,"message":"Not Found: User not found"}}

# PUT
PUT http://{{hostnport}}/api/index.php/users/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/users/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: Account not found"}}

# GET users DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/users
DOLAPIENTITY: 1
HTTP 200

# GET users DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/users
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"