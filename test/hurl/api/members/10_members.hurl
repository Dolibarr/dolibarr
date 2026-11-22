# test/hurl/api/members/10_members.hurl
# GET-only tests for the members API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner environment.
#   The LIST section captures "$[0]" and therefore requires at least one member
#   to exist in the instance. Everything else tolerates an empty table.
# Portability: no assumptions about specific members or counts; no personal data
#   is captured or asserted — only ids and response shapes. Email lookups use
#   syntactically valid, guaranteed-nonexistent addresses.
# Only GET requests in this file — write endpoints (POST/PUT/DELETE) are deliberately
# not exercised, not even as negative tests, to keep the suite strictly read-only.
#
# NOTE: member objects carry PII (name, email, address). Asserts are therefore
# restricted to existence/shape checks and id round-trips, never content.
# NOTE: sibling suite memberstypes/10_memberstypes.hurl pinned several findings
# on this API class (unvalidated sortfield/sqlfilters -> 503, strict limit -> 400,
# legacy route ignoring pagination_data). Same expectations are applied here;
# VERIFY marks where the members class may genuinely differ.

# ---------------------------------------------------------------- List & pagination

# List, ascending sort, capture first row id for later lookups (id only — no PII)
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
member_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a documented column of the members table
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed on memberstypes: an unknown sortfield is not
# validated and flows into the SQL ORDER BY. VERIFY: members may validate better;
# if it answers 200 here, pin that and note the cross-module difference.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 2 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — must not 500 (coerced on memberstypes)
GET http://{{hostnport}}/api/index.php/members?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Negative: non-numeric limit is rejected with 400 (strict validation as on memberstypes)
GET http://{{hostnport}}/api/index.php/members?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- List filters

# typeid filter — unknown type id. VERIFY: expected 200 with an empty-or-small
# collection; docs list 404 "No Member found" for this endpoint, which an empty
# instance may also trigger on any list call. Pin whichever code you observe.
GET http://{{hostnport}}/api/index.php/members?typeid=999999&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned as observed: an unknown category filter yields 200 with a
# (possibly empty) collection — whereas thirdparties answers 404 for the same
# case. Cross-module inconsistency; candidate for the upstream report.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members?category=999999&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/members?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/members?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/members?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed on memberstypes: unknown column parses fine
# but produces invalid SQL. VERIFY: docs list 400 "Error on SQL filters" for this
# endpoint — if members actually validates, change to 400 and note the difference.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Negative: sqlfilters with invalid syntax is rejected with 400 on this route —
# the validator that memberstypes lacks ("Bad syntax of the search string").
# Contrast: the unknown-column case above still hits the SQL engine (503),
# so syntax IS validated here but column names are not whitelisted.
GET http://{{hostnport}}/api/index.php/members?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/members?properties=id%2Clastname&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/members?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/members?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/members?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/members?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Clastname&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- By id

# Positive: fetched by captured id — assert id round-trip and field presence only,
# never content (member records carry personal data)
GET http://{{hostnport}}/api/index.php/members/{{member_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{member_id}}"
jsonpath "$.lastname" exists

# Negative: nonexistent id -> 404 per docs ("Member not found")
GET http://{{hostnport}}/api/index.php/members/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Behavior pinned as observed: GET /members/0 returns 200 with a specimen object
# (specimen=1, id=0, dummy data only) — same as thirdparties, NOT 404 as the docs
# list and unlike memberstypes. Cross-module id=0 behavior now spans three answers:
# 200-specimen (thirdparties, members), 400 (invoices), 404 (memberstypes).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/0
HTTP 200
[Asserts]
jsonpath "$.specimen" == 1
jsonpath "$.id" == 0

# Negative: non-numeric id — VERIFY: memberstypes answered 400 here
GET http://{{hostnport}}/api/index.php/members/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — VERIFY: expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/members/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Subresources (on the captured member)

# Categories of the member. VERIFY: docs list 404 "Category not found" — an
# uncategoryzed member may yield an empty array (200) or a 404; pin the observed code.
GET http://{{hostnport}}/api/index.php/members/{{member_id}}/categories
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Categories with list params
GET http://{{hostnport}}/api/index.php/members/{{member_id}}/categories?sortfield=s.rowid&sortorder=DESC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# array — the invalid id resolves to nothing here, unlike /members/0/subscriptions
# which leaks real rows. The asymmetry between the two subresources strengthens
# the data-exposure finding on subscriptions.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/0/categories
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Negative: non-numeric id on a subresource is type-checked with 400
GET http://{{hostnport}}/api/index.php/members/abc/subscriptions
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400
jsonpath "$.error.message" contains "invalid value specified for `id`"

# Symmetry check pinned as observed: invalid NEGATIVE id on subscriptions is
# rejected with 404 — the leak is 0-SPECIFIC, consistent with a falsy-id code
# path in the handler (0 is falsy, -1 is truthy in PHP).
# Diagnostic for the upstream issue.
GET http://{{hostnport}}/api/index.php/members/-1/subscriptions
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404
jsonpath "$.error.message" contains "member not found"

# Subscriptions of the member. VERIFY: a member without subscriptions may answer
# 200-empty or 404 per docs ("Member not found" covers only a bad member id);
# pin the observed code.
GET http://{{hostnport}}/api/index.php/members/{{member_id}}/subscriptions
HTTP 200
[Asserts]
jsonpath "$" isCollection

# FINDING — pinned: GET /members/0/subscriptions answers 200 and returns REAL
# subscription rows for an existing member (not a specimen, not an error).
# An invalid member id must never resolve to another member's data — this is a
# correctness bug and a data-exposure candidate for an upstream issue.
# Pinned with a status-code + shape assert only; no content asserts (content is
# instance-dependent and carries real personal data).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/0/subscriptions
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Lookups via linked thirdparty

# Positive: member(s) linked to a thirdparty — skipped for portability: requires
# an instance where member[0] is actually linked to a thirdparty id. Capture-safe
# alternative: lookup with a nonexistent thirdparty id -> 404 per docs.
GET http://{{hostnport}}/api/index.php/members/thirdparty/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Email lookup — negative only: syntactically valid, guaranteed-nonexistent
# address; no real personal data in transit or in logs
GET http://{{hostnport}}/api/index.php/members/thirdparty/email/nobody-nonexistent@example.invalid
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Barcode lookup — negative only
GET http://{{hostnport}}/api/index.php/members/thirdparty/barcode/NO-SUCH-BARCODE
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Account lookup — unknown site/key. VERIFY: docs say 404 ("does not belongs to
# an existing thirdparty") but list 401 for a permissions case; pin as observed.
GET http://{{hostnport}}/api/index.php/members/thirdparty/accounts/nosuchsite/nosuchkey
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Stats

# amountbymonth — required year; assert shape only (array of pairs)
GET http://{{hostnport}}/api/index.php/members/stats/amountbymonth?year=2026
HTTP 200
[Asserts]
jsonpath "$" isCollection

# amountbymonth — format variants 0/1/2 must all serve
GET http://{{hostnport}}/api/index.php/members/stats/amountbymonth?year=2026&format=1
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/members/stats/amountbymonth?year=2026&format=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# amountbymonth — missing required year. VERIFY: expected 400; Restler may
# answer differently for unmet required query params — pin the observed code.
GET http://{{hostnport}}/api/index.php/members/stats/amountbymonth
HTTP 400
[Asserts]
jsonpath "$.error" exists

# nbbymonth — required year
GET http://{{hostnport}}/api/index.php/members/stats/nbbymonth?year=2026
HTTP 200
[Asserts]
jsonpath "$" isCollection

# nbbymonth — missing required year — VERIFY as amountbymonth above
GET http://{{hostnport}}/api/index.php/members/stats/nbbymonth
HTTP 400
[Asserts]
jsonpath "$.error" exists

# nbbyyear — no parameters documented
GET http://{{hostnport}}/api/index.php/members/stats/nbbyyear
HTTP 200
[Asserts]
jsonpath "$" isCollection

# lastmodifiedmembers — required max; count bounded by it
GET http://{{hostnport}}/api/index.php/members/stats/lastmodifiedmembers?max=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# lastmodifiedmembers — missing required max — VERIFY as above
GET http://{{hostnport}}/api/index.php/members/stats/lastmodifiedmembers
HTTP 400
[Asserts]
jsonpath "$.error" exists
