GET http://{{hostnport}}/public/ticket/create_ticket.php?action=create
HTTP 200
Content-Type: text/html; charset=UTF-8

GET http://{{hostnport}}/public/ticket/create_ticket.php?action=create&projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8


POST http://{{hostnport}}/public/ticket/create_ticket.php?action=create
HTTP 403
Content-Type: text/html; charset=UTF-8

POST http://{{hostnport}}/public/ticket/create_ticket.php?action=create&projectid=42
HTTP 403
Content-Type: text/html; charset=UTF-8
