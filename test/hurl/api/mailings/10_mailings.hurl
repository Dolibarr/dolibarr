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


# POST {}
POST http://{{hostnport}}/api/index.php/mailings
{}
HTTP 400
{"error":{"code":400,"message":"Bad Request: title field missing"}}
