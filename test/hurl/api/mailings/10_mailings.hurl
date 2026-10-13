# GET mass mailings
GET http://{{hostnport}}/api/index.php/mailings
HTTP 200

# GET mass sorted mailings
GET http://{{hostnport}}/api/index.php/mailings?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET mass sorted mailings
GET http://{{hostnport}}/api/index.php/mailings?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/mailings?limit=100&page=1
HTTP 200

# GET with fk_project
GET http://{{hostnport}}/api/index.php/mailings?fk_projects=1
HTTP 200

# GET with properties=id%2Cstatus
GET http://{{hostnport}}/api/index.php/mailings?properties=id%2Cstatus
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/mailings?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/mailings?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET mailing with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/mailings/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}

# POST {}
POST http://{{hostnport}}/api/index.php/mailings
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: title field missing"}}

# DELETE
DELETE http://{{hostnport}}/api/index.php/mailings/
HTTP 405

# DELETE mailing with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/mailings/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}

# PUT
PUT http://{{hostnport}}/api/index.php/mailings/
{}
HTTP 405

# PUT
PUT http://{{hostnport}}/api/index.php/mailings/0
{}
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}

DELETE http://{{hostnport}}/api/index.php/mailings//deleteTargets
HTTP 400

# DELETE targets of mailing with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/mailings/0/deleteTargets
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}


PUT http://{{hostnport}}/api/index.php/mailings//resetTargetsStatus
HTTP 400

# reset target status of mailing with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/mailings/0/resetTargetsStatus
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}


PUT http://{{hostnport}}/api/index.php/mailings//settodraft
HTTP 400

# set mailing to draft with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/mailings/0/settodraft
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}


PUT http://{{hostnport}}/api/index.php/mailings//validate
HTTP 400

# validate mailing with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/mailings/0/validate
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}

GET http://{{hostnport}}/api/index.php/mailings/clone/
HTTP 400

GET http://{{hostnport}}/api/index.php/mailings/clone/0
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing to clone not found, id=0"}}


GET http://{{hostnport}}/api/index.php/mailings//targets
HTTP 400

# get targets of mailing with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/mailings/0/targets
HTTP 404
{"error":{"code":404,"message":"Not Found: Mass mailing not found, id=0"}}

# get a target in a mailing
GET http://{{hostnport}}/api/index.php/mailings/0/getTarget/0
HTTP 404

# get a target in a mailing
PUT http://{{hostnport}}/api/index.php/mailings/0/updateTarget/0
{}
HTTP 404

# Delete a target in a mailing
DELETE http://{{hostnport}}/api/index.php/mailings/0/deleteTarget/0
{}
HTTP 404

# Create a target in a mailing
POST http://{{hostnport}}/api/index.php/mailings/0/createTarget
{}
HTTP 400

# Create a target in a mailing
POST http://{{hostnport}}/api/index.php/mailings/0/createTarget
{
  "statut": "0",
  "status": "0",
  "lastname": "Example",
  "firstname": "Bad",
  "date_modification": 1759051258,
  "tms": 1759051258,
  "fk_mailing": "0",
  "fk_contact": "0",
  "email": "bad@example.com",
  "other": "",
  "tag": "acaa266a6b12a85a7db47a377a10333b",
  "source_url": "",
  "source_id": null,
  "source_type": "file",
  "date_envoi": "",
  "error_text": null
}
HTTP 404

# Create a target in a mailing
POST http://{{hostnport}}/api/index.php/mailings/0/createTarget
{
  "id": "0"
}
HTTP 400
{"error":{"code":400,"message":"Bad Request: fk_mailing field missing"}}

# Create a target in a mailing
POST http://{{hostnport}}/api/index.php/mailings/0/createTarget
{
  "id": "0",
  "fk_mailing" : "0"
}
HTTP 400
{"error":{"code":400,"message":"Bad Request: email field missing"}}

# Create a target in a mailing
POST http://{{hostnport}}/api/index.php/mailings/0/createTarget
{
  "id": "0",
  "email": "bad@example.com",
  "fk_mailing" : "0"
}
HTTP 400
{"error":{"code":400,"message":"Bad Request: Creating with id field is forbidden"}}
