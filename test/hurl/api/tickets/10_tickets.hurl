# GET tickets
GET http://{{hostnport}}/api/index.php/tickets
HTTP 200

# GET sorted tickets
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.rowid&sortticket=ASC
HTTP 200

# GET sorted tickets
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.rowid&sortticket=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/tickets?limit=100&page=1
HTTP 200

# GET with page
GET http://{{hostnport}}/api/index.php/tickets?page=0
HTTP 200

# GET with loadcontacts=0
GET http://{{hostnport}}/api/index.php/tickets?loadcontacts=0
HTTP 200

# GET with loadcontacts=1
GET http://{{hostnport}}/api/index.php/tickets?loadcontacts=1
HTTP 200


# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/tickets?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/tickets?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/tickets?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET ticket with ID 0 - which should not exist - but it does :-(
GET http://{{hostnport}}/api/index.php/tickets/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}

# POST {}
POST http://{{hostnport}}/api/index.php/tickets
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: subject field missing"}}

# POST topic required
POST http://{{hostnport}}/api/index.php/tickets
{ "subject" : "42"}
HTTP 400
{"error":{"code":400,"message":"Bad Request: message field missing"}}
# this output should be adjusted for the real output when the check is done for required fields


# DELETE
DELETE http://{{hostnport}}/api/index.php/tickets/
HTTP 405

DELETE http://{{hostnport}}/api/index.php/tickets/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}



# PUT
PUT http://{{hostnport}}/api/index.php/tickets/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/tickets/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}



# GET tickets DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/tickets
DOLAPIENTITY: 1
HTTP 200

# GET tickets DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/tickets
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"


POST http://{{hostnport}}/api/index.php/tickets/newmessage
{ "42" : "" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: track_id field missing"}}

POST http://{{hostnport}}/api/index.php/tickets/newmessage
{ "track_id" : "42" }
HTTP 400
{"error":{"code":400,"message":"Bad Request: message field missing"}}

POST http://{{hostnport}}/api/index.php/tickets/newmessage
{ "track_id" : "42", "message" : "42" }
HTTP 404
{"error":{"code":404,"message":"Not Found: Ticket not found"}}

GET http://{{hostnport}}/api/index.php/tickets/ref/
HTTP 400
{"error":{"code":400,"message":"Bad Request: invalid value specified for `id`"}}

GET http://{{hostnport}}/api/index.php/tickets/ref/42
HTTP 404
{"error":{"code":404,"message":"Not Found: Ticket not found"}}

GET http://{{hostnport}}/api/index.php/tickets/track_id/
HTTP 400
{"error":{"code":400,"message":"Bad Request: invalid value specified for `id`"}}

GET http://{{hostnport}}/api/index.php/tickets/track_id/42
HTTP 404
{"error":{"code":404,"message":"Not Found: Ticket not found"}}

POST http://{{hostnport}}/api/index.php/tickets/0/contact/0/0
{ "42" : ""}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}

DELETE http://{{hostnport}}/api/index.php/tickets/0/contact/0/0
{ "42" : ""}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Error: id < 1"}}
