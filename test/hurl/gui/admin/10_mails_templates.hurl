GET http://{{hostnport}}/admin/mails_templates.php
HTTP 200
[Captures]
language_select_count: xpath "count(//select[@name='search_lang'])"
[Asserts]
body not contains "Enter login details"
body contains "<!-- Show logo on menu -->"
body contains "Emails setup"
body contains "Email templates"
body contains "Attach file"
body contains "Code"
body contains "Default"
body contains "Module/Application"
body contains "Owner"
body contains "Position"
body contains "Status"
body contains "Subject"
body contains "Type of template"
body contains "NewEMailTemplate"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
body not contains "notice"
body not contains "warning"

# Creation and list language controls must follow the same MAIN_MULTILANGS setting.
GET http://{{hostnport}}/admin/mails_templates.php?action=create
HTTP 200
[Asserts]
xpath "count(//form[@id='create_c_email_template']//select[@name='langcode'])" == {{language_select_count}}
xpath "count(//form[@id='create_c_email_template']//select[@name='langcode']/option[@value='fr_FR'])" == {{language_select_count}}
xpath "count(//form[@id='create_c_email_template']//select[@name='langcode']/option[@value='en_US'])" == {{language_select_count}}
xpath "count(//form[@id='create_c_email_template']//input[@name='token'])" == 1
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
body not contains "notice"
body not contains "warning"
xpath "count(//form[@id='create_c_email_template']//input[@name='langcode'])" == 0
