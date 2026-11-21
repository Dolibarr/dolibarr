# test/hurl/api/memberstypes/10_memberstypes.hurl
# GET-only tests for the memberstypes API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner environment.
#   The LIST sections capture "$[0]" and therefore require at least one member
#   type to exist in the instance. Everything else tolerates an empty table.
# Portability: no assumptions about specific types, labels or counts; no personal
#   data is captured or asserted (member types carry only id/label/flags).
# Only GET requests in this file — write endpoints (POST/PUT/DELETE) are deliberately
# not exercised, not even as negative tests, to keep the suite strictly read-only.
#
# NOTE: the Swagger docs expose TWO routes for the same resource:
#   /memberstypes  — older group, documented to answer "500 RestException" on errors
#   /members/types — newer group, documented to answer 403/404/503
# Both are tested side by side; differences between the two are flagged, not hidden.

# ---------------------------------------------------------------- List & pagination (/members/types)

# List, ascending sort, capture first row id for later lookups (id only — no PII)
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
membertype_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on the documented alternative field name (docs example uses t.libelle)
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.libelle&sortorder=ASC&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed: an unknown sortfield is not validated and
# flows straight into the SQL ORDER BY, which answers 503 — not 400, not an
# ignored-parameter fallback. Same lack of whitelisting as sqlfilters below.
# This is a consistency finding worth raising upstream; pinning keeps it visible.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 2 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — must not 500 (VERIFY: 400 or coerced to page 0 both acceptable)
GET http://{{hostnport}}/api/index.php/members/types?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Negative: non-numeric limit is rejected with 400 (strict validation — good,
# in contrast to sortfield/sqlfilters which are not validated)
GET http://{{hostnport}}/api/index.php/members/types?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- sqlfilters (/members/types)

# sqlfilters — documented equality syntax; works regardless of how many rows match
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.subscription%3A%3D%3A%271%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — documented LIKE pattern from the Swagger example
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.libelle%3Alike%3A%27%25%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — IS NULL operator (parses even if no column value is NULL)
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.subscription%3Ais%3ANULL)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — greater-than on rowid, always true for existing rows
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed: a column that does not exist parses fine but
# produces invalid SQL, which answers 503 — not 400, not 404. This is a consistency
# finding worth raising upstream; pinning keeps it visible.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Behavior pinned to 503 as observed: an operator that parses but yields invalid SQL
# (::: instead of :=) also answers 503 rather than 400. See note above.
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.subscription%3A%3A%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Behavior pinned to 503 as observed: even a syntactically INVALID sqlfilters
# (space inside the clause breaks the field:operator:value grammar) answers 503
# here — not 400 with "Bad syntax of the search string" as seen on thirdparties
# and as the docs imply. On this route no sqlfilters validation is reached at all:
# bad syntax, unknown column and bad operators all hit the SQL engine (503).
# Consistency finding for upstream; pinning keeps it visible.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/members/types?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Field selection & envelope (/members/types)

# properties — field reduction returns the requested property (works on any instance
# because it asserts on shape, not on which concrete types exist)
GET http://{{hostnport}}/api/index.php/members/types?properties=id%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500 (VERIFY: expected 200 with the key omitted;
# if your version 400s or 500s here, pin the observed behavior and flag it)
GET http://{{hostnport}}/api/index.php/members/types?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/members/types?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope (distinct from the true-case above)
GET http://{{hostnport}}/api/index.php/members/types?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/members/types?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- By id (/members/types)

# Positive: fetched by captured id (id is neutral data — safe to capture and echo)
GET http://{{hostnport}}/api/index.php/members/types/{{membertype_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{membertype_id}}"
jsonpath "$.label" exists

# Negative: nonexistent id -> 404 per docs ("No Member Type found")
GET http://{{hostnport}}/api/index.php/members/types/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: id=0 — VERIFY: thirdparties returns a specimen (200), invoices 400.
# Docs for this route only list 404; adjust to the observed code and note the
# inconsistency — cross-module id=0 behavior differs and deserves an upstream ticket.
GET http://{{hostnport}}/api/index.php/members/types/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — VERIFY: docs list nothing; thirdparties answers 400 here.
GET http://{{hostnport}}/api/index.php/members/types/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — VERIFY: expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/members/types/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Same surface via the legacy /memberstypes route

# List — must behave identically to /members/types
GET http://{{hostnport}}/api/index.php/memberstypes?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Desc sort + small limit
GET http://{{hostnport}}/api/index.php/memberstypes?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Documented sqlfilters example verbatim from the Swagger page (AND of two filters,
# space-separated clauses — URL-encoded)
GET http://{{hostnport}}/api/index.php/memberstypes?sqlfilters=(t.libelle%3Alike%3A%27%25%25%27)%20and%20(t.subscription%3A%3D%3A%271%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# properties projection
GET http://{{hostnport}}/api/index.php/memberstypes?properties=id%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Behavior pinned as observed: the legacy /memberstypes route IGNORES
# pagination_data=true — it serves a plain array, no {data, pagination} envelope
# (while /members/types honors the parameter; see the section above).
# Silent ignore instead of error; another route gap for the upstream report.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/memberstypes?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent id — VERIFY: Swagger documents "500 RestException" for this
# route group while /members/types documents 404. Pin whichever code you observe —
# a mismatch between the two routes is itself worth an upstream report.
GET http://{{hostnport}}/api/index.php/memberstypes/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: id=0 — same VERIFY note as above
GET http://{{hostnport}}/api/index.php/memberstypes/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — same VERIFY note
GET http://{{hostnport}}/api/index.php/memberstypes/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists