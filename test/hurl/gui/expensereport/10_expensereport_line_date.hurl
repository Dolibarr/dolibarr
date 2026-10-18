# Regression for #38629: default dates on the expense report add-line form.
# Run with an authenticated GUI cookie on a disposable instance with Expense Reports enabled.
# Requires an active expense type, no required project/attachment, and out-of-period blocking off.
# Use a test account without an existing report overlapping 2024-02-28 through 2024-03-02.
# Repeat with EXPENSEREPORT_LINES_SORTED_BY_ROWID off and on. No settings are changed here.

# Create a report spanning leap day and a month boundary.

GET http://{{hostnport}}/expensereport/card.php?action=create
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"
authorid: xpath "string(//select[@name='fk_user_author']/option[@selected]/@value)"
[Asserts]
xpath "//select[@name='fk_user_author']/option[@selected]" exists
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: add
fk_user_author: {{authorid}}
date_debutday: 28
date_debutmonth: 2
date_debutyear: 2024
date_finday: 2
date_finmonth: 3
date_finyear: 2024
note_private: Hurl38629 date defaults
HTTP 302
[Captures]
reportid: header "Location" regex "[?]id=([0-9]+)"
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"
feetype: xpath "string(//select[@name='fk_c_type_fees']/option[number(@value) > 0][1]/@value)"
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?28$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# The newest ID must win even when its expense date sorts before an older line.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: addline
id: {{reportid}}
dateday: 2
datemonth: 3
dateyear: 2024
fk_c_type_fees: {{feetype}}
vatrate: 0
value_unit: 10
qty: 1
comments: hurl38629-late
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"
lateid: xpath "string(//tr[@data-id][contains(., 'hurl38629-late')]/@data-id)"
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?3$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: addline
id: {{reportid}}
dateday: 29
datemonth: 2
dateyear: 2024
fk_c_type_fees: {{feetype}}
vatrate: 0
value_unit: 10
qty: 1
comments: hurl38629-recent
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"
recentid: xpath "string(//tr[@data-id][contains(., 'hurl38629-recent')]/@data-id)"
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?29$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Failed submissions retain the supplied date or an explicitly blank date.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: addline
id: {{reportid}}
dateday: 1
datemonth: 3
dateyear: 2024
fk_c_type_fees: {{feetype}}
vatrate: 0
value_unit: 0
qty: 1
comments: hurl38629-invalid-price
HTTP 200
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?1$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?3$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?29$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: addline
id: {{reportid}}
dateday:
datemonth:
dateyear:
fk_c_type_fees: {{feetype}}
vatrate: 0
value_unit: 10
qty: 1
comments: hurl38629-blank-date
HTTP 200
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" == ""
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" == ""
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" == ""

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?29$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# An out-of-period newest line falls back to report start, not an older line.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: addline
id: {{reportid}}
dateday: 3
datemonth: 3
dateyear: 2024
fk_c_type_fees: {{feetype}}
vatrate: 0
value_unit: 10
qty: 1
comments: hurl38629-outside
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"
outsideid: xpath "string(//tr[@data-id][contains(., 'hurl38629-outside')]/@data-id)"
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?28$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Editing an existing line still shows that line's own date.

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}&action=editline&rowid={{lateid}}&token={{token}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?3$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?28$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Delete the newest remaining line and check the recomputed default.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: confirm_delete_line
id: {{reportid}}
rowid: {{outsideid}}
confirm: yes
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?29$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Delete the newest remaining line and check the recomputed default.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: confirm_delete_line
id: {{reportid}}
rowid: {{recentid}}
confirm: yes
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?3$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Delete the newest remaining line and check the recomputed default.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: confirm_delete_line
id: {{reportid}}
rowid: {{lateid}}
confirm: yes
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"

GET http://{{hostnport}}/expensereport/card.php?id={{reportid}}
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
xpath "string(//input[@name='dateday']/@value | //select[@name='dateday']/option[@selected]/@value)" matches "^0?28$"
xpath "string(//input[@name='datemonth']/@value | //select[@name='datemonth']/option[@selected]/@value)" matches "^0?2$"
xpath "string(//input[@name='dateyear']/@value | //select[@name='dateyear']/option[@selected]/@value)" matches "^0?2024$"

# Remove the report created by this scenario.

POST http://{{hostnport}}/expensereport/card.php
[FormParams]
token: {{token}}
action: confirm_delete
id: {{reportid}}
confirm: yes
HTTP 302
[Asserts]
body not contains "Error:"
body not contains "Warning:"
body not contains "notice:"
body not contains "warning:"
body not contains "Notice:"
body not matches "(?i)(warning|notice|fatal error)(</b>)?:"
body not contains "xdebug-error"
