# Check mailing
GET http://{{hostnport}}/comm/mailing/list.php?leftmenu=mailing
HTTP 200
[Asserts]
body not contains "Enter login details"
body contains "Mass EMails"
body contains "Ref."
body contains "Title"
body contains "Creation date"
body contains "No. of EMails"
body contains "Date of latest sending"
body contains "Status"
