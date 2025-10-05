# GET Login should fail
GET http://localhost:8080/api/index.php/login?login=foo&password=bar
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403
jsonpath "$.error.message" matches "Forbidden: Access denied"
