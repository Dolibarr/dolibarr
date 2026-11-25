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
xpath "count(//select[@name='search_private']/option[@value='0' or @value='1'])" == 2
xpath "count(//select[@name='search_joinfiles']/option[@value='0' or @value='1'])" == 2
xpath "count(//select[@name='search_defaultfortype']/option[@value='0' or @value='1'])" == 2
xpath "count(//input[@name='search_position'])" == 1
xpath "count(//tr[@id='Title line with search boxes']/td[not(contains(@class, 'liste_titre'))])" == 0
xpath "count(//tr[@id='Title line with search boxes']/*) = count(//tr[@id='Title of lines']/*)" == true
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
