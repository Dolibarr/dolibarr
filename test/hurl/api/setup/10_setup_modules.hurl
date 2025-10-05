# Check status
GET http://localhost:8080/api/index.php/setup/modules
HTTP 200
[Asserts]
jsonpath "$" count > 2
