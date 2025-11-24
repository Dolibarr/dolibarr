# Check project index
GET http://{{hostnport}}/projet/index.php
HTTP 200
[Asserts]
body contains "Projects Area"

# Check New lead or project
GET http://{{hostnport}}/projet/card.php
HTTP 200
[Asserts]
body contains "Record not found."

# Create New lead or project
GET http://{{hostnport}}/projet/card.php?action=create
HTTP 200
[Asserts]
body contains "New lead or project"
body contains "Ref"
body contains "Label"
body contains "Usage"
body contains "Follow opportunity"
body contains "Follow tasks or time spent"
body contains "Bill the time spent"
body contains "Manage the organization of an event"
body contains "Status"
body contains "Budget"
body contains "Date"
body contains "Description"
body contains "Tags/categories"
body contains "Visibility"
body contains "Assign me as contact with the type"


# Check project list
GET http://{{hostnport}}/projet/list.php
HTTP 200
[Asserts]
body contains "Leads | Projects"
body contains "Alert"
body contains "Omit sub-projects"

# List open leads
GET http://{{hostnport}}/projet/list.php?search_usage_opportunity=1&search_status=99&search_opp_status=openedopp&contextpage=lead
HTTP 200
[Asserts]
body contains "Leads | Projects"

# List open projects
GET http://{{hostnport}}/projet/list.php?search_usage_opportunity=1&search_status=99&search_opp_status=openedopp&contextpage=project
HTTP 200
[Asserts]
body contains "Leads | Projects"

# Check project statistics
GET http://{{hostnport}}/projet/stats/index.php
HTTP 200
[Asserts]
body contains "Statistics on projects or leads"
body contains "By month/year"
body contains "Year"
body contains "Number of projects"
body contains "Lead amount"
body contains "Average lead amount"
body contains "Weighted lead amount"
body contains "No. of created projects by month"
body contains "Amount of leads by month"
body contains "Weighted amount of leads by month"

# Check project tasks/activities
GET http://{{hostnport}}/projet/activity/index.php
HTTP 200
[Asserts]
body contains "Tasks/activities"
body contains "Activity on project today"
body contains "Activity on project yesterday"
body contains "Time"
body contains "Total"

# Create New task
GET http://{{hostnport}}/projet/tasks.php?action=create
HTTP 200
[Asserts]
body contains "New task"
body contains "Ref"
body contains "Label"
body contains "Assigned to"
body contains "Billable"
body contains "Start date"
body contains "End date"
body contains "Planned workload"
body contains "Declared real progress"
body contains "Budget"
body contains "Description"

# Check task list
GET http://{{hostnport}}/projet/tasks/list.php
HTTP 200
[Asserts]
body contains "Tasks/activities"

# Check project statistics
GET http://{{hostnport}}/projet/tasks/stats/index.php
HTTP 200
[Asserts]
body contains "Statistics on tasks of projects or leads"
body contains "Year"
body contains "Filter"
body contains "Number of tasks"
body contains "No. of created tasks by month"

# Check task time
GET http://{{hostnport}}/projet/tasks/time.php
HTTP 200
[Asserts]
body contains "List of time consumed on task"
body contains "Total"

# Check daily
GET http://{{hostnport}}/projet/activity/perday.php
HTTP 200
[Asserts]
body contains "Input per month"
body contains "Input per week"
body contains "Input per day"
body contains "Total"
body contains "now"
body contains "Task"
body contains "Workload"
body contains "Declared real progress"
body contains "Everybody"
body contains "Time spent"
body contains "Start hour"
body contains "Duration"
body contains "Note"

# Check weekly
GET http://{{hostnport}}/projet/activity/perweek.php
HTTP 200
[Asserts]
body contains "Input per month"
body contains "Input per week"
body contains "Input per day"
body contains "Total"
body contains "now"
body contains "Task"
body contains "Workload"
body contains "Declared real progress"
body contains "Everybody"
body contains "Time spent"
body contains "Mon"
body contains "Tue"
body contains "Wed"
body contains "Thu"
body contains "Fri"
body contains "Sat"
body contains "Sun"

# Check monthly
GET http://{{hostnport}}/projet/activity/permonth.php
HTTP 200
[Asserts]
body contains "Input per month"
body contains "Input per week"
body contains "Input per day"
body contains "Total"
body contains "now"
body contains "Task"
body contains "Workload"
body contains "Declared real progress"
body contains "Everybody"
body contains "Time spent"
body contains "Week"

