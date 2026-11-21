# test/hurl/api/thirdparties/10_thirdparties.hurl
# GET-only tests for the thirdparties API.
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

# mode=1 — only customers (requires instance data; locked to 200)
GET http://{{hostnport}}/api/index.php/thirdparties?mode=1&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fuzz: mode=abc — not a long; strictly rejected with 400
GET http://{{hostnport}}/api/index.php/thirdparties?mode=abc&limit=5
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Fuzz: mode=-1 — documented range is 0-4; unknown modes fall through to "all"
GET http://{{hostnport}}/api/index.php/thirdparties?mode=-1&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fuzz: mode=999 — same fall-through expectation
GET http://{{hostnport}}/api/index.php/thirdparties?mode=999&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fuzz: empty mode — coerces to 0
GET http://{{hostnport}}/api/index.php/thirdparties?mode=&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: category filter with a nonexisting category id -> 404
GET http://{{hostnport}}/api/index.php/thirdparties?category=999999&limit=5
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404

# properties — field reduction returns only requested properties
GET http://{{hostnport}}/api/index.php/thirdparties?properties=id%2Cname&limit=2
HTTP 200
[Asserts]
jsonpath "$[0].id" exists
jsonpath "$[0].name" exists

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/thirdparties?limit=100&page=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

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
# Negative: numeric barcode that does not exist -> 404
GET http://{{hostnport}}/api/index.php/thirdparties/barcode/1234567890
HTTP 404

# Negative: alphabetic barcode that does not exist -> 404
GET http://{{hostnport}}/api/index.php/thirdparties/barcode/NO-SUCH-BARCODE
HTTP 404

# ---------------------------------------------------------------- Site accounts (negative only — creating data requires POST)

# Unknown site key for the captured third party -> 404 per API docs
# VERIFY: adjust if your instance answers differently
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/accounts?site=nosuchsite
HTTP 404

# Global account lookup with unknown site/key -> 404 per API docs
# VERIFY: adjust if your instance answers differently
GET http://{{hostnport}}/api/index.php/thirdparties/accounts/nosuchsite/nosuchkey
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

# Representatives with mode=1 — array of ids instead of objects
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/representatives?mode=1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Customer categories
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/categories
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Customer categories with sort + pagination params
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/categories?sortfield=s.rowid&sortorder=DESC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Supplier categories
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/supplier_categories
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fixed amount discounts with mode=customer
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/fixedamountdiscounts?mode=customer
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

# Outstanding invoices with mode=supplier
GET http://{{hostnport}}/api/index.php/thirdparties/{{thirdparty_id}}/outstandinginvoices?mode=supplier
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
