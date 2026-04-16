# Check list of open Organized events
GET http://{{hostnport}}/eventorganization/conferenceorboothattendee_list.php
HTTP 200
[Asserts]
body contains "List of attendees of event projects"

# Check list of open Organized events for a member
GET http://{{hostnport}}/eventorganization/conferenceorboothattendee_list.php?memberid=1&withmember=1
HTTP 200
[Asserts]
body contains 'fk_member
