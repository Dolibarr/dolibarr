GET http://{{hostnport}}/public/ticket/
HTTP 200
Content-Type: text/html; charset=UTF-8

GET http://{{hostnport}}/public/ticket/?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8

GET http://{{hostnport}}/public/ticket/index.php
HTTP 200
Content-Type: text/html; charset=UTF-8

GET http://{{hostnport}}/public/ticket/index.php?projectid=42
HTTP 200
Content-Type: text/html; charset=UTF-8
