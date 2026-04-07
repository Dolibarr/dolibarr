# Check list of open Organized events
GET http://{{hostnport}}/eventorganization/attendee_list.php
HTTP 200
[Asserts]
body contains "List of attendees of event projects"
