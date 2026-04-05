# Check list of open Organized events
GET http://{{hostnport}}/eventorganization/conferenceorbooth/list.php
HTTP 200
[Asserts]
body contains "Conferences or Booths"
