# GET emailtemplates
GET http://{{hostnport}}/api/index.php/emailtemplates
HTTP 200

# GET sorted emailtemplates
GET http://{{hostnport}}/api/index.php/emailtemplates?sortfield=e.rowid&sortorder=ASC
HTTP 200

# GET sorted emailtemplates
GET http://{{hostnport}}/api/index.php/emailtemplates?sortfield=e.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/emailtemplates?limit=100&page=1
HTTP 200

# GET with fk_user
GET http://{{hostnport}}/api/index.php/emailtemplates?fk_user=0
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/emailtemplates?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/emailtemplates?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/emailtemplates?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET mailing with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/emailtemplates/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id=0 and label are empty"}}

# POST {}
POST http://{{hostnport}}/api/index.php/emailtemplates
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: label field missing"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/emailtemplates/
HTTP 405

# DELETE mailing with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/emailtemplates/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Email Template with id 0 not found"}}

# PUT
PUT http://{{hostnport}}/api/index.php/emailtemplates/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/emailtemplates/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: email template with id=0 not found"}}


