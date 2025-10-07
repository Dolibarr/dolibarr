# Check status
GET http://{{hostnport}}/api/index.php/setup/modules
HTTP 200
[Asserts]
jsonpath "$" count > 2
