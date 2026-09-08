GET http://{{hostnport}}/admin/mails_templates.php
HTTP 200
[Asserts]
body not contains "Enter login details"
body contains "<!-- Show logo on menu -->"
body contains "Emails setup"
body contains "Email templates"
body contains "Attach file"
body contains "Code"
body contains "Default"
body contains "Language"
body contains "Module/Application"
body contains "Owner"
body contains "Position"
body contains "Status"
body contains "Subject"
body contains "Type of template"
body contains "NewEMailTemplate"
