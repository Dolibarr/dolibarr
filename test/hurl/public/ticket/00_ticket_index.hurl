GET http://{{hostnport}}/public/ticket/
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "publicnewticketform"
body contains "/public/ticket/create_ticket.php?action=create&token="
body contains "list.php"
body contains "view.php"
body not contains "projectid=42"

GET http://{{hostnport}}/public/ticket/?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "publicnewticketform"
body contains "/public/ticket/create_ticket.php?action=create&projectid=42&token="
body contains "list.php?projectid=42"
body contains "view.php?projectid=42"

GET http://{{hostnport}}/public/ticket/index.php
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "publicnewticketform"
body contains "/public/ticket/create_ticket.php?action=create&token="
body contains "list.php"
body contains "view.php"
body not contains "projectid=42"

GET http://{{hostnport}}/public/ticket/index.php?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "publicnewticketform"
body contains "/public/ticket/create_ticket.php?action=create&projectid=42&token="
body contains "list.php?projectid=42"
body contains "view.php?projectid=42"
