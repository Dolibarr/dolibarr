# Check status
GET http://localhost:8080/api/index.php/setup/company
HTTP 200
[Asserts]
jsonpath "$.id" == 0     # Check the value of the id
jsonpath "$.status" == 1
jsonpath "$.module" == "societe"
