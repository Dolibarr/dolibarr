# Check mailing
GET http://{{hostnport}}/comm/mailing/list.php?leftmenu=mailing
HTTP 200
[Asserts]
body not contains "Enter login details"
body contains "Mass EMails"
