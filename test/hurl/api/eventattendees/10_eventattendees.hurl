# GET eventattendees
GET http://{{hostnport}}/api/index.php/eventattendees
HTTP 200

# GET sorted eventattendees
GET http://{{hostnport}}/api/index.php/eventattendees?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted eventattendees
GET http://{{hostnport}}/api/index.php/eventattendees?sortfield=t.rowid&sortorder=DSC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/eventattendees?limit=100&page=1
HTTP 200

# GET without loadlinkedobjects
GET http://{{hostnport}}/api/index.php/eventattendees?loadlinkedobjects=0
HTTP 200

# GET with loadlinkedobjects
GET http://{{hostnport}}/api/index.php/eventattendees?loadlinkedobjects=1
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/eventattendees?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/eventattendees?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/eventattendees?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET mailing with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/eventattendees/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No eventattendee with id<1 can exist"}}

# POST {}
POST http://{{hostnport}}/api/index.php/eventattendees
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: fk_project field missing"}}

# POST fk_project required
POST http://{{hostnport}}/api/index.php/eventattendees
{ "fk_project" : 25}
HTTP 500
{"error":{"code":500,"message":"Internal Server Error: Error creating event attendee","0":null,"1":"Email address  is incorrect"}}

# POST email field also required
POST http://{{hostnport}}/api/index.php/eventattendees
{ "fk_project" : 25, "email": "bad@example.com"}
HTTP 500
{"error":{"code":500,"message":"Internal Server Error: Error creating event attendee","0":null,"1":"Field 'DateOfRegistration' is required"}}

# POST date_subscription field also required
# POST http://{{hostnport}}/api/index.php/eventattendees
# { "fk_project" : 25, "email": "bad@example.com", "date_subscription" : 1775059427}
# HTTP 200
# Disabled because this will actually create an eventatendee

# DELETE
DELETE http://{{hostnport}}/api/index.php/eventattendees/
HTTP 405

# DELETE mailing with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/eventattendees/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No eventattendee with id<1 can exist"}}

# PUT
PUT http://{{hostnport}}/api/index.php/eventattendees/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/eventattendees/0
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: No eventattendee with id<1 can exist"}}


# GET eventattendees DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/eventattendees
DOLAPIENTITY: 1
HTTP 200

# GET eventattendees DOLAPIENTITY: 2
GET http://{{hostnport}}/api/index.php/eventattendees
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid (may be a typo or a wrong entity)"
