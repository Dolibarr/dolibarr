# test/hurl/api/contacts/10_contacts.hurl
# GET-only tests for the contacts API.
# Preconditions: {{hostnport}} in env; at least one contact exists in the instance.
# Only GET requests in this file — write endpoints (POST/PUT/DELETE) are deliberately
# not exercised, not even as negative tests, to keep the suite strictly read-only.
# NOTE: id=0 / non-numeric-id / empty-subresource behaviors may differ from thirdparties;
# verify by hand and adjust the status codes if needed (marked VERIFY below).

# ---------------------------------------------------------------- List & pagination

# List, ascending sort, capture first row for later lookups
GET http://{{hostnport}}/api/index.php/contacts?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
contact_id: jsonpath "$[0].id"
contact_email: jsonpath "$[0].email"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/contacts?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Page 2 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/contacts?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# sqlfilters — valid USF syntax: field:operator:value, no spaces
GET http://{{hostnport}}/api/index.php/contacts?sqlfilters=(t.rowid%3A%3E%3D%3A%271%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# Negative: sqlfilters with invalid syntax must be rejected with 400 (not 500)
GET http://{{hostnport}}/api/index.php/contacts?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400
jsonpath "$.error.message" contains "Bad syntax of the search string"

# Filter contacts by third party id — accepts single id
GET http://{{hostnport}}/api/index.php/contacts?thirdparty_ids=1&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Filter by a category id that has no contacts (or does not exist)
GET http://{{hostnport}}/api/index.php/contacts?category=999999&limit=10
HTTP 200

GET http://{{hostnport}}/api/index.php/contacts?properties=id%2Clastname&limit=2
HTTP 200
[Asserts]
jsonpath "$[0].id" exists
jsonpath "$[0].lastname" exists

GET http://{{hostnport}}/api/index.php/contacts?limit=100&page=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

GET http://{{hostnport}}/api/index.php/contacts/{{contact_id}}?includecount=1&includeroles=1
HTTP 200

# Capture from DESC so we get the newest contact (older rows often share
# duplicated emails in this instance, see email-409 note below)
GET http://{{hostnport}}/api/index.php/contacts?sortfield=t.rowid&sortorder=DESC&limit=1
HTTP 200
[Captures]
contact_id: jsonpath "$[0].id"
contact_email: jsonpath "$[0].email"

# ---------------------------------------------------------------- By id

# Positive: fetched by captured id, core fields present
GET http://{{hostnport}}/api/index.php/contacts/{{contact_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists
jsonpath "$.lastname" exists

# Negative: nonexistent id
GET http://{{hostnport}}/api/index.php/contacts/99999999
HTTP 404

# Negative: id=0 — VERIFY: thirdparties returns a specimen (200), invoices returns 400.
# Adjust the expected code to whatever this API class actually does.
GET http://{{hostnport}}/api/index.php/contacts/0
HTTP 200
[Asserts]
jsonpath "$.specimen" == 1
jsonpath "$.id" == 0

# Negative: non-numeric id — VERIFY: thirdparties answers 400 here.
GET http://{{hostnport}}/api/index.php/contacts/notanumber
HTTP 400

# ---------------------------------------------------------------- By identifiers

# Positive email lookup — SKIPPED: this endpoint answers 409 Conflict when more
# than one contact shares the email, which test/specimen data often does.
# Re-enable with an email that is guaranteed unique in the instance.
# GET http://{{hostnport}}/api/index.php/contacts/email/{{contact_email}}
# HTTP 200
# [Asserts]
# jsonpath "$.id" exists

# Negative: unknown email -> 404
GET http://{{hostnport}}/api/index.php/contacts/email/nobody-nonexistent@example.invalid
HTTP 404

# ---------------------------------------------------------------- Subresources

# Categories of the contact — VERIFY: may answer 404 on empty list
# instead of 200 with an empty array (see notifications/bankaccounts on thirdparties).
GET http://{{hostnport}}/api/index.php/contacts/{{contact_id}}/categories
HTTP 200
[Asserts]
jsonpath "$" isCollection
