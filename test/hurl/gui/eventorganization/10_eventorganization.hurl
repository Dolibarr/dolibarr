# Check list of open Organized events
GET http://{{hostnport}}/projet/list.php?search_usage_event_organization=1&search_status=1
HTTP 200
[Asserts]
body contains "Leads | Projects"

# Check list of draft Organized events
GET http://{{hostnport}}/projet/list.php?search_usage_event_organization=1&search_status=0
HTTP 200
[Asserts]
body contains "Leads | Projects"

# Check list of closed Organized events
GET http://{{hostnport}}/projet/list.php?search_usage_event_organization=1&search_status=2
HTTP 200
[Asserts]
body contains "Leads | Projects"

# GET the page that creates a new Organized event
GET http://{{hostnport}}/projet/card.php?leftmenu=projects&action=create&usage_organize_event=1&usage_opportunity=0
HTTP 200
[Asserts]
body contains "New lead or project"
body contains "Ref."
body contains "Label"
body contains "Parent"
body contains "Usage"
body contains "Third party"
body contains "Status"
body contains "Budget"
body contains "Date (Event)"
body contains "Location"
body contains "Description"
body contains "Tags/categories"
body contains "Visibility"
body contains "Assign me as contact with the type"
body contains "Follow opportunity"
body contains "Follow tasks or time spent"
body contains "Bill the time spent"
body contains "Manage the organization of an event"
