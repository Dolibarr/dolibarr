# Check ticket index
GET http://{{hostnport}}/ticket/index.php
HTTP 200
[Asserts]
body contains "mod-ticket page-dashboard"

# Check New ticket
GET http://{{hostnport}}/ticket/card.php
HTTP 200
[Asserts]
body contains "mod-ticket page-card"

# Create ticket
GET http://{{hostnport}}/ticket/card.php?action=create
HTTP 200
[Asserts]
body contains "mod-ticket page-card"
body contains "form_create_ticket"


# Check ticket list
GET http://{{hostnport}}/ticket/list.php
HTTP 200
[Asserts]
body contains "mod-ticket page-list"
body not contains "ticket_url_public_interface"

# Check ticket list
GET http://{{hostnport}}/ticket/stats/index.php
HTTP 200
[Asserts]
body contains "mod-ticket page-stats"


# Check ticket list
GET http://{{hostnport}}/ticket/list.php?mode=common
HTTP 200
[Asserts]
body contains "mod-ticket page-list"
body contains "mode=kanban"
body contains "mode=common"

# Check ticket list
GET http://{{hostnport}}/ticket/list.php?mode=kanban
HTTP 200
[Asserts]
body contains "mod-ticket page-list"
body contains "mode=kanban"
body contains "mode=common"


# Check ticket list
GET http://{{hostnport}}/ticket/list.php?projectid=
HTTP 200
[Asserts]
body contains "mod-ticket page-list"
body not contains "ticket_url_public_interface"

# Check ticket list
GET http://{{hostnport}}/ticket/list.php?projectid=1
HTTP 200
[Asserts]
body contains "ticket_url_public_interface"

# Check ticket list
GET http://{{hostnport}}/ticket/list.php?socid=
HTTP 200
[Asserts]
body contains "mod-ticket page-list"

