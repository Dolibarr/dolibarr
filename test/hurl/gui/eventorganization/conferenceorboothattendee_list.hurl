# Check list of open Organized events
GET http://{{hostnport}}/eventorganization/conferenceorboothattendee_list.php
HTTP 200
[Asserts]
body contains "List of attendees of event projects"
