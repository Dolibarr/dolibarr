GET http://{{hostnport}}/public/ticket/view.php
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "<!-- public view ticket -->"
body contains "btn_view_ticket"
body contains "view_ticket"
body contains "form_view_ticket"
body contains "/public/ticket/view.php"


GET http://{{hostnport}}/public/ticket/view.php?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8
[Asserts]
body contains "<!-- public view ticket -->"
body contains "btn_view_ticket"
body contains "view_ticket"
body contains "form_view_ticket"
body contains "/public/ticket/view.php"


POST http://{{hostnport}}/public/ticket/view.php
HTTP 403
Content-Type: text/html; charset=UTF-8

POST http://{{hostnport}}/public/ticket/view.php?projectid=42
HTTP 403
Content-Type: text/html; charset=UTF-8
