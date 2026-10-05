# Check login page
GET http://{{hostnport}}/
HTTP 200
[Asserts]
body not contains "Enter login details"
body contains "titleforhome"
