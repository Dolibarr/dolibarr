# Check list of open Organized events
GET http://{{hostnport}}/eventorganization/conferenceorbooth_list.php
HTTP 200
[Asserts]
body contains "Conferences or Booths"
