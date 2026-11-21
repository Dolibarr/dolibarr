# test/hurl/api/thirdparties/10_thirdparties.hurl
# GET-only tests for the thirdparties API.
# Preconditions: {{hostnport}} and {{apiKey}} in env; at least one third party exists in the instance.
# Only GET requests in this file — write endpoints (POST/PUT/DELETE) are deliberately
# not exercised, not even as negative tests, to keep the suite strictly read-only.

# ---------------------------------------------------------------- List & pagination

# List, ascending sort, capture first row for later lookups
GET http://{{hostnport}}/api/index.php/thirdparties?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
thirdparty_id: jsonpath "$[0].id"
thirdparty_name: jsonpath "$[0].name"
thirdparty_email: jsonpath "$[0].email"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists
jsonpath "$[0].name" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/thirdparties?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Page 2 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/thirdparties?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# sqlfilters — date filter is parsed without error
GET http://{{hostnport}}/api/index.php/thirdparties?sqlfilters=(t.datec%3A%3E%3D%3A%272000-01-01%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# Negative: sqlfilters with invalid syntax must be rejected with 400 (not 500)
GET http://{{hostnport}}/api/index.php/thirdparties?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400
jsonpath "$.error.message" contains "Bad syntax of the search string"

# ---------------------------------------------------------------- By id

# Positive: fetched by captured id, core fields present
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}
HTTP 200
[Captures]
thirdparty_barcode: jsonpath "$.barcode"
[Asserts]
jsonpath "$.id" exists
jsonpath "$.name" exists
jsonpath "$.status" exists

# Negative-ish: id=0 returns a specimen object (Dolibarr behavior for thirdparties,
# inconsistent with /invoices/0 which returns 400) — document it, and flag upstream
GET http://{{hostnport}}/api/index.php/thirdparties/0
HTTP 200
[Asserts]
jsonpath "$.specimen" == 1
jsonpath "$.id" == 0

# Negative: nonexistent id
GET http://{{hostnport}}/api/index.php/thirdparties/99999999
HTTP 404

# Negative: non-numeric id
GET http://{{hostnport}}/api/index.php/thirdparties/notanumber
HTTP 400

# ---------------------------------------------------------------- By identifiers

# Lookup by email (captured from list)
GET http://{{hostnport}}/api/index.php/thirdparties/email/{{thirdparty_email}}
HTTP 200
[Asserts]
jsonpath "$.id" exists
jsonpath "$.name" exists

# Negative: unknown email
GET http://{{hostnport}}/api/index.php/thirdparties/email/nobody-nonexistent@example.invalid
HTTP 404

# Lookup by barcode — only meaningful when the instance has barcodes enabled.
# Negative: unknown barcode -> 404 (works regardless of whether barcodes are in use)
GET http://{{hostnport}}/api/index.php/thirdparties/barcode/1234567890
HTTP 404

# Negative: unknown barcode -> 404 (works regardless of whether barcodes are in use)
GET http://{{hostnport}}/api/index.php/thirdparties/barcode/NO-SUCH-BARCODE
HTTP 404

# ---------------------------------------------------------------- Subresources (all expect 200, most often an empty array)

# Empty list is served as 404 by Dolibarr (documented behavior — differs from
# other subresources that return an empty array)
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/bankaccounts
HTTP 404

# Representatives
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/representatives
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Customer categories
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/categories
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Supplier categories
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/supplier_categories
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fixed amount discounts
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/fixedamountdiscounts
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Notifications: Dolibarr answers 404 (not an empty array) when the third party
# has no notifications — documented behavior, locked here to catch drift
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/notifications
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404
jsonpath "$.error.message" contains "Notification not found"

# Negative: id=0 is rejected by this endpoint (unlike GET /thirdparties/0 which
# returns a specimen — see inconsistency note above)
GET http://{{hostnport}}/api/index.php/thirdparties/0/notifications
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400
jsonpath "$.error.message" contains "Thirdparty ID is mandatory"

# Outstanding invoices
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/outstandinginvoices
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Outstanding orders
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/outstandingorders
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Outstanding proposals
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/outstandingproposals
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Invoices qualified for a credit note
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/getinvoicesqualifiedforcreditnote
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Invoices qualified for a replacement invoice
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/getinvoicesqualifiedforreplacement
HTTP 200
[Asserts]
jsonpath "$" isCollection
