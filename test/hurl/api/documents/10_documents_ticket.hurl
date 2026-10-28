# GET documents list for ticket id=1
# Returns 200 if documents exist, 404 if ticket or documents not found
GET http://{{hostnport}}/api/index.php/documents?modulepart=ticket&id=1
HTTP *
[Asserts]
status >= 200
status < 500

# GET documents list with missing modulepart should return 400
GET http://{{hostnport}}/api/index.php/documents?id=1
HTTP 400

# GET documents list with missing id and ref should return 400
GET http://{{hostnport}}/api/index.php/documents?modulepart=ticket
HTTP 400

# GET documents list for a non-existent ticket id should return 404
GET http://{{hostnport}}/api/index.php/documents?modulepart=ticket&id=999999999
HTTP 404
