# Check status
GET http://{{hostnport}}/api/index.php/setup/company
HTTP 200
[Asserts]
jsonpath "$.id" == 0     # Check the value of the id
jsonpath "$.status" == 1
jsonpath "$.module" == "societe"
