GET http://{{hostnport}}/public/ticket/list.php
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "view_ticketlist"
body contains "btn_view_ticket_list"
body contains "form_view_ticket"
body contains "form_view_ticketlist"
body contains "/public/ticket/list.php"

GET http://{{hostnport}}/public/ticket/list.php?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "view_ticketlist"
body contains "btn_view_ticket_list"
body contains "form_view_ticket"
body contains "form_view_ticketlist"
body contains "/public/ticket/list.php"

POST http://{{hostnport}}/public/ticket/list.php
HTTP 403
Content-Type: text/html; charset=UTF-8

POST http://{{hostnport}}/public/ticket/list.php?projectid=42
HTTP 403
Content-Type: text/html; charset=UTF-8
