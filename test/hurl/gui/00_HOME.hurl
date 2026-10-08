# Check login page
GET http://{{hostnport}}/
HTTP 200
[Asserts]
body contains "Enter login details"
body not contains "titleforhome"
