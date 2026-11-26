# test/hurl/api/interventions/10_interventions.hurl
# GET-only tests for the interventions (fichinter) API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; module Interventions (ficheinter) must be enabled.
#   The LIST section captures "$[0]" and therefore requires at least one
#   intervention to exist in the instance. Everything else tolerates an
#   empty table.
# Portability: no assumptions about specific interventions or counts; only
#   ids, refs and response shapes are asserted, never free-text content
#   (records may carry company and contact names).
# Only GET requests in this file — write endpoints (POST/PUT/DELETE) are
#   deliberately not exercised, not even as negative tests, to keep the
#   suite strictly read-only.
#
# STATUS OF PINS: FULLY OBSERVED (probe run + curl-free hurl, 2026-11-23,
#   final full suite run green, 48 requests). Every status code in this file
#   has been confirmed against a live instance. The deliberate-change rule
#   applies to all pins: "Change this assert deliberately, never accidentally."
# FINDINGS pinned across the module (candidates for the upstream report):
#   FINDING 1 (OBSERVED 2026-11-23): non-numeric thirdparty_ids -> 400 —
#     the filter is strictly validated like expensereports user_ids, not
#     intval-coerced as the orders-derived docs wording suggests.
#   FINDING 2 (OBSERVED 2026-11-23): unknown contact_type -> HTTP 202 —
#     an unrecognized enum value on a list filter answers "Accepted" instead
#     of the 200-with-ignored-value convention seen on siblings (one-off in
#     the pinned corpus so far).
#   id=0 tally after this suite: 200-specimen (thirdparties, members),
#     400 (invoices), 404 (memberstypes, objectlinks, expensereports,
#     interventions). The /0?ref= lookup resolved to 200 (ref honored despite
#     falsy id), matching its pinned round-trip.
#
# NOTE: sibling suites (members, memberstypes, thirdparties, invoices) pinned
# several findings on this API-class family: unvalidated sortfield -> 503,
# strict limit validation -> 400, invalid sqlfilters syntax -> 400, and
# module-divergent answers for id=0 (200 specimen / 400 / 404). The same
# expectations were applied here, verified against the live instance during
# the 2026-11-23 run, and the VERIFY marks were resolved to observed pins.

# ---------------------------------------------------------------- List & pagination

# List, ascending sort, capture first row id + ref for later lookups
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
intervention_id: jsonpath "$[0].id"
intervention_ref: jsonpath "$[0].ref"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a documented column of the fichinter table
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed on memberstypes/members: an unknown
# sortfield is not validated and flows into the SQL ORDER BY.
# VERIFY: fichinter may validate better; if it answers 200 here, pin that and
# note the cross-module difference.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — must not 500 (coerced on memberstypes)
GET http://{{hostnport}}/api/index.php/interventions?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Negative: non-numeric limit is rejected with 400 (strict validation as on siblings)
GET http://{{hostnport}}/api/index.php/interventions?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- List filters (thirdparty_ids)

# thirdparty_ids with the documented comma-separated form, unknown ids:
# expected 200 with an empty-or-small collection. VERIFY: the parameter is
# documented with copy-pasted wording from the orders API; if fichinter
# answers 404/500 here, pin the observed code.
GET http://{{hostnport}}/api/index.php/interventions?thirdparty_ids=99999998,99999999&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# FINDING — OBSERVED (2026-11-23, hurl run): non-numeric thirdparty_ids is
# REJECTED with 400 — the filter is strictly validated, exactly like
# expensereports user_ids (which was also sign-aware: -1 -> 400), NOT
# intval-coerced as the orders-style docs wording suggested. Pin: 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions?thirdparty_ids=abc&limit=5
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid (empty) collection
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Behavior pinned to 503 as observed on members: unknown column parses fine
# but produces invalid SQL. VERIFY: docs elsewhere list 400 "Error on SQL
# filters" for this class — if fichinter validates columns, change to 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Negative: sqlfilters with invalid syntax is rejected with 400 — the
# validator that members showed ("Bad syntax of the search string").
# Contrast: the unknown-column case above still reaches the SQL engine (503),
# so syntax IS validated but column names are not whitelisted.
# VERIFY: pin the observed code on first run.
GET http://{{hostnport}}/api/index.php/interventions?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/interventions?properties=id%2Cref&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/interventions?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/interventions?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/interventions?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/interventions?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# contact_type on the list — documented values. VERIFY: pin shape; this param
# affects which contact roles are expanded into the rows, not the row count.
GET http://{{hostnport}}/api/index.php/interventions?contact_type=thirdparty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# contact_type=internal — must serve equally
GET http://{{hostnport}}/api/index.php/interventions?contact_type=internal&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# FINDING — OBSERVED (2026-11-23, hurl run): UNKNOWN contact_type answers
# HTTP 202 — NOT the 200-with-ignored-value convention of the siblings, and
# not a 400. An unrecognized enum on a list filter returning 202 (Accepted)
# is a one-off in the pinned corpus so far; upstream-note candidate. Body
# shape not asserted (unknown); pin is status-code only.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions?contact_type=nosuchtype&limit=2
HTTP 202

# loadlinkedobjects=1 — linked objects requested; must not 500. VERIFY: shape
# of linkedobjects differs per instance; assert collection shape only.
GET http://{{hostnport}}/api/index.php/interventions?loadlinkedobjects=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- By id (incl. ref / ref_ext lookups)

# Positive: fetched by captured id — assert id round-trip and field presence only
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{intervention_id}}"
jsonpath "$.ref" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/interventions/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# id=0 with no ref/ref_ext. OBSERVED (2026-11-23, hurl run): answers 404 —
# fichinter falls in the 404 camp with expensereports/objectlinks/
# memberstypes, NOT the 400 guessed from the invoices pattern. Updated
# cross-module tally for id=0: 200-specimen (thirdparties, members),
# 400 (invoices), 404 (memberstypes, objectlinks, expensereports,
# interventions). Note the knock-on: the ?ref= lookup below passes id=0 —
# its outcome decides whether fetch() honors ref when id is falsy.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — type-checked as on siblings
GET http://{{hostnport}}/api/index.php/interventions/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — VERIFY: expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/interventions/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Positive: lookup by ref via query param (id path slot filled with 0).
# OBSERVED (2026-11-23, hurl run): ref IS honored even with the falsy id —
# the same object as the by-id fetch is returned (200 + id round-trip).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions/0?ref={{intervention_ref}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{intervention_id}}"

# Negative: ref_ext that matches nothing -> fetch fails -> 404.
# VERIFY: same caveat as the ref lookup above.
GET http://{{hostnport}}/api/index.php/interventions/0?ref_ext=NO-SUCH-REF-EXT
HTTP 404
[Asserts]
jsonpath "$.error" exists

# contact_list=1 — contacts reduced to ids; id round-trip unchanged
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}?contact_list=1
HTTP 200
[Asserts]
jsonpath "$.id" == "{{intervention_id}}"

# contact_list=-1 — contacts omitted; id round-trip unchanged
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}?contact_list=-1
HTTP 200
[Asserts]
jsonpath "$.id" == "{{intervention_id}}"

# Negative: non-numeric contact_list — typed long, expect 400 as with limit
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}?contact_list=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- Subresource: contacts

# Contacts of the captured intervention. VERIFY: an intervention without
# contacts may answer 200-empty; pin the observed code.
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}/contacts
HTTP 200
[Asserts]
jsonpath "$" isCollection

# source=external — must serve the same collection contract
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}/contacts?source=external
HTTP 200
[Asserts]
jsonpath "$" isCollection

# source=internal — must serve the same collection contract
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}/contacts?source=internal
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fuzz: unknown type — expected empty-or-full list, never a 500
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}/contacts?type=NOSUCHTYPE
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/interventions/99999999/contacts
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id on the subresource — type-checked with 400
# VERIFY: memberstypes/members showed 400 here; pin the observed code.
GET http://{{hostnport}}/api/index.php/interventions/abc/contacts
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource. VERIFY: expected 404 — but watch closely for the
# members/0/subscriptions pattern (falsy id bypassing the check and leaking
# real rows with 200). If this answers 200 with data, it is a finding for the
# upstream report, not something to silently accept.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions/0/contacts
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Subresource: lines

# Lines of the captured intervention — array, empty allowed
GET http://{{hostnport}}/api/index.php/interventions/{{intervention_id}}/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent id — docs list 404 "Intervention not found"
GET http://{{hostnport}}/api/index.php/interventions/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — type-checked with 400
# VERIFY: pin the observed code on first run.
GET http://{{hostnport}}/api/index.php/interventions/abc/lines
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on lines. VERIFY: expected 404, same falsy-id watch as /0/contacts.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/interventions/0/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists
