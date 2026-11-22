# test/hurl/api/expensereports/10_expensereports.hurl
# GET-only tests for the expensereports API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner environment.
# STATUS OF PINS: FULLY OBSERVED (probe runs + curl, 2026-11-22; final full
#   suite run green, 43 requests). Every status code in this file has been
#   confirmed against a live instance. The deliberate-change rule applies to
#   all pins: "Change this assert deliberately, never accidentally."
# Portability: no assumptions about instance data beyond AT LEAST ONE expense
#   report existing (the by-id/lines sections capture a real id from the list).
#   The payments-positive path is NOT captured (an instance may legitimately
#   have zero payments) — payments are pinned only on negative/zero ids.
# PII caveat: expense reports carry personal data (owner names, amounts,
#   receipt refs). Asserts are therefore restricted to status codes, error
#   shape, envelope presence and id round-trips — never amounts, names or refs.
# Only GET requests in this file — the state-machine endpoints (approve, deny,
#   cancel, validate, settodraft, setpaid, payments POST/PUT, lines POST/PUT/
#   DELETE) are deliberately not exercised, not even as negative tests, to keep
#   the suite strictly read-only.
# Documented GET surface (per API explorer):
#   GET /expensereports             list    — sortfield, sortorder, limit, page,
#                                             user_ids ('1' or '1,2,3'),
#                                             sqlfilters, properties,
#                                             pagination_data (boolean)
#   GET /expensereports/{id}        by id   — path long
#   GET /expensereports/{id}/lines  lines   — docs list 403 and 404 rows
#   GET /expensereports/payments    list    — sortfield, sortorder, limit, page
#   GET /expensereports/payments/{pid}      — path long
#
# FINDINGS pinned across the module (candidates for the upstream report):
# FINDING 1 (OBSERVED 2026-11-22, curl): pagination_data=true IS honored — but
#   the envelope keys are "data" + "pagination"
#   ({total, page, page_count, limit}) — NOT a "$.pagination_data" node.
#   Without the param the list stays a bare array (capture of $[0].id on the
#   baseline succeeded). Two different body shapes on one route, driven by
#   one boolean. Empty lists WITHOUT the param are bare empty arrays (not
#   PHP empty-objects); the object shape appears only with the param set.
# FINDING 2 (OBSERVED 2026-11-22): user_ids validation is SIGN-AWARE:
#   -1 -> 400, while nonexistent positive ids (99999999) -> 200 with an empty
#   envelope. Contrast within the same route. Siblings' typecasts are
#   format-only (objectlinks passed negative longs to the gate). Positive ids
#   are never existence-checked before the query — only the sign and format.
# FINDING 3 (OBSERVED 2026-11-22): sqlfilters VALIDATION IS SPLIT — the
#   contacts-style SYNTAX validator IS shared (syntactically invalid filter
#   -> 400), but COLUMN NAMES are NOT validated: an unknown column reaches
#   the SQL engine -> 503 (like members/memberstypes), never a 400. Same
#   split observed for sortfield: unknown sortfield -> 503, unvalidated.
# FINDING 4 (OBSERVED 2026-11-22, curl): id=0 is a 404 on ALL THREE by-id
#   surfaces (by id, lines, payments) — NOT a specimen. Sibling-module tally
#   for id=0 is now: 200-specimen (thirdparties, members), 400 (invoices),
#   404 (memberstypes, objectlinks, expensereports). No members-style
#   /0/subscriptions leak on this module.
# OBSERVED behaviors worth keeping in mind (2026-11-22): asc/ASC/DESCx
#   sortorder all 200 (garbage sortorder ignored), limit=abc -> 400 (strict),
#   page=abc -> 400 (strict), properties projection honored, unknown
#   properties ignored (200), route collision /payments resolved statically
#   (list wins over {id}), payments share the strict limit validation.

# ------------------------------------------------------- Route collision note (payments vs {id})
# /expensereports/payments competes with /expensereports/{id} — 'payments'
# could be captured as a non-numeric {id} and answered 400 by the id route.
# The suite pins BOTH sides of the collision so any drift shows up as a pair
# of failures, not as silent rerouting.

# ------------------------------------------------------- Section A: list — baseline & pagination

# OBSERVED — pinned (2026-11-22): bare list answers 200 with an array body
# (capture of $[0].id succeeded). Capture a real id for later sections.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports?limit=1
HTTP 200
[Captures]
expensereport_id: jsonpath "$[0].id"

# OBSERVED — pinned: canonical ASC sort answers 200.
GET http://{{hostnport}}/api/index.php/expensereports?sortfield=t.ref&sortorder=asc&limit=1
HTTP 200

# OBSERVED — pinned: uppercase sortorder accepted like lowercase.
GET http://{{hostnport}}/api/index.php/expensereports?sortfield=t.ref&sortorder=ASC&limit=1
HTTP 200

# OBSERVED — pinned: garbage sortorder DESCx is IGNORED (200) — sortorder is
# not strictly validated; only the presence of a sortfield drives sorting.
GET http://{{hostnport}}/api/index.php/expensereports?sortfield=t.ref&sortorder=DESCx&limit=1
HTTP 200

# FINDING 3 — OBSERVED — pinned: UNKNOWN SORTFIELD answers 503 — the sortfield
# string reaches the SQL engine unvalidated, like members/memberstypes (NOT
# the contacts-style 400). Sortfield is NOT validated on this route;
# upstream-report candidate alongside the members 503s. Must not 500-crash.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports?sortfield=t.nosuchfield&limit=1
HTTP 503

# OBSERVED — pinned: far-out page answers 200 with an (empty) array, never 500.
GET http://{{hostnport}}/api/index.php/expensereports?page=999&limit=5
HTTP 200

# OBSERVED — pinned: strict limit validation — non-numeric long rejected 400
# (same as members/memberstypes/invoices).
GET http://{{hostnport}}/api/index.php/expensereports?limit=abc
HTTP 400

# OBSERVED — pinned: strict page validation — non-numeric page 400.
GET http://{{hostnport}}/api/index.php/expensereports?page=abc&limit=1
HTTP 400

# OBSERVED — pinned (curl 2026-11-22): pagination_data=true IS honored. The
# envelope keys are "data" and "pagination" (total, page, page_count, limit) —
# NOT "$.pagination_data" (see FINDING 1). Without the param the body is
# a bare array; with it, an object. Asserts pinned to the observed shape.
# NOTE: assumes at least one expense report exists (see Portability note);
# on an empty instance $.data count == 1 will fail legitimately.
GET http://{{hostnport}}/api/index.php/expensereports?pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination" exists
jsonpath "$.pagination.limit" == 1
jsonpath "$.pagination.total" >= 1
jsonpath "$.data" count == 1

# OBSERVED — pinned: the fully combined request — every documented list
# parameter at once — answers 200 with the data/pagination envelope.
# Regression anchor for the whole signature. Uses nonexistent user ids
# (99999999) rather than a possibly-nonexistent owner like user 1: on an
# instance where nothing is owned by user 1, a loose count assert would be
# trivially true and the request would lose its discriminative power —
# 99999999 guarantees the deterministic 200-empty pin.
GET http://{{hostnport}}/api/index.php/expensereports?sortfield=t.ref&sortorder=asc&limit=2&page=0&user_ids=99999999&sqlfilters=(t.ref%3Alike%3A%27%25%27)&properties=id,ref&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.pagination" exists
jsonpath "$.data" count == 0

# ------------------------------------------------------- Section B: list — user_ids (module-specific filter)

# OBSERVED — pinned (curl 2026-11-22): a filter on a nonexistent user id
# answers 200 with the envelope — total 0, data []. An empty result is the
# correct answer, not an error. Data-independent: user 99999999 owns nothing
# on any instance.
GET http://{{hostnport}}/api/index.php/expensereports?user_ids=99999999&pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination.total" == 0
jsonpath "$.data" count == 0

# OBSERVED — pinned: multi-id syntax from the docs ('1,2,3') — using a
# nonexistent pair; 200 with an empty envelope (same shape as the single-id
# pin).
GET http://{{hostnport}}/api/index.php/expensereports?user_ids=99999999,88888888&pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination.total" == 0
jsonpath "$.data" count == 0

# OBSERVED — pinned: non-numeric user_ids is REJECTED with 400 — consistent
# with the sign-aware validation of FINDING 2 (the filter is validated, not
# silently dropped).
GET http://{{hostnport}}/api/index.php/expensereports?user_ids=notanumber&limit=1
HTTP 400

# OBSERVED — pinned: EMPTY user_ids is treated as absent, not as "own nobody"
# — the baseline listing is served, 200. (Members convention for empty
# filters.)
GET http://{{hostnport}}/api/index.php/expensereports?user_ids=&limit=1
HTTP 200

# FINDING 2 — OBSERVED — pinned (2026-11-22, probe run):
# NEGATIVE user_ids are REJECTED with 400 — the user_ids validation is
# sign-aware, unlike the siblings' format-only typecasts (objectlinks passed
# negative longs to the gate). Contrast within this very route: 99999999 ->
# 200-empty, -1 -> 400. Positive ids are never existence-checked before the
# query — only sign and format. Upstream-note candidate.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports?user_ids=-1&pagination_data=true&limit=1
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ------------------------------------------------------- Section C: list — sqlfilters

# OBSERVED — pinned: documented-syntax filter (always-true LIKE) answers 200.
GET http://{{hostnport}}/api/index.php/expensereports?sqlfilters=(t.ref%3Alike%3A%27%25%27)&limit=1
HTTP 200

# OBSERVED — pinned: always-FALSE filter answers 200 with an empty
# collection — the list convention (200-empty), NOT the by-id 404 convention.
# No pagination_data param, so the body is a bare empty array. Hurl semantics
# note (probe run 2026-11-22): a wildcard with no matches yields an EMPTY
# LIST, which "exists" — so emptiness is pinned with count == 0, never with
# "not exists". Data-independent.
GET http://{{hostnport}}/api/index.php/expensereports?sqlfilters=(t.rowid%3A%3D%3A%27-1%27)&limit=1
HTTP 200
[Asserts]
jsonpath "$[*]" count == 0

# FINDING 3 — OBSERVED — pinned: UNKNOWN sqlfilters COLUMN answers 503 — the
# column name reaches the SQL engine unvalidated, like members/memberstypes
# (NOT the contacts-style 400). Column names are NOT validated on this route;
# upstream-report candidate. Must never be a raw SQL error dump or a 500.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=1
HTTP 503

# OBSERVED — pinned: SYNTACTICALLY INVALID sqlfilters (space inside the
# operator) is REJECTED with 400 — the contacts-style SYNTAX validator IS
# shared by this route. Combined with the 503 pin above, sqlfilters
# validation is split: syntax checked, column names not (FINDING 3).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=1
HTTP 400

# OBSERVED — pinned: multiple criteria joined per docs syntax ("... and
# (...)") — 200.
GET http://{{hostnport}}/api/index.php/expensereports?sqlfilters=(t.ref%3Alike%3A%27%25%27)%20and%20(t.rowid%3A%3E%3A%270%27)&limit=1
HTTP 200

# ------------------------------------------------------- Section D: list — properties projection

# OBSERVED — pinned: properties projection answers 200 (honored when
# non-empty).
GET http://{{hostnport}}/api/index.php/expensereports?properties=id,ref&limit=1
HTTP 200

# OBSERVED — pinned: unknown property name is IGNORED (200) — property names
# are not validated, unlike limit/page (strict) — another asymmetry in the
# validation story.
GET http://{{hostnport}}/api/index.php/expensereports?properties=nosuchproperty&limit=1
HTTP 200

# ------------------------------------------------------- Section E: by id

# OBSERVED — pinned: positive round-trip on a captured id (from Section A)
# answers 200 and echoes the id back. The ONLY content assertion in the suite
# (the id itself — no PII).
GET http://{{hostnport}}/api/index.php/expensereports/{{expensereport_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == {{expensereport_id}}

# OBSERVED — pinned: nonexistent id -> 404 per the sibling/API-explorer
# convention.
GET http://{{hostnport}}/api/index.php/expensereports/99999999
HTTP 404

# FINDING 4 — OBSERVED — pinned (2026-11-22, curl): id 0 -> 404
# {"error":{"code":404,"message":"Not Found: Expense report not found"}} —
# NOT a specimen. Sibling-module tally for id=0 is now: 200-specimen
# (thirdparties, members), 400 (invoices), 404 (memberstypes, objectlinks,
# expensereports).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/expensereports/0
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404

# OBSERVED — pinned: negative id -> 404, like nonexistent ids (sibling
# convention).
GET http://{{hostnport}}/api/index.php/expensereports/-1
HTTP 404

# OBSERVED — pinned: non-numeric id -> 400 (members/memberstypes/objectlinks
# convention).
GET http://{{hostnport}}/api/index.php/expensereports/notanumber
HTTP 400

# OBSERVED — pinned: extremely large id is handled as a long, not truncated —
# 404, not 500.
GET http://{{hostnport}}/api/index.php/expensereports/99999999999999
HTTP 404

# ------------------------------------------------------- Section F: lines subresource

# Docs rows for /expensereports/{id}/lines: 200 success, 403 Forbidden, 404
# Not Found. The 403 row needs a genuinely underprivileged key to trigger —
# not forceable in a read-only suite; pinned here only for the reachable ids.

# OBSERVED — pinned: lines on a captured real id answers 200 with an array.
GET http://{{hostnport}}/api/index.php/expensereports/{{expensereport_id}}/lines
HTTP 200

# OBSERVED — pinned: lines on a nonexistent id -> 404 (docs row).
GET http://{{hostnport}}/api/index.php/expensereports/99999999/lines
HTTP 404

# FINDING 4 — OBSERVED — pinned (2026-11-22, curl): lines on id 0 -> 404 —
# consistent with /expensereports/0 being a 404, no specimen. NO members-style
# /0/subscriptions leak on this module: by-id and subresource agree on 404.
GET http://{{hostnport}}/api/index.php/expensereports/0/lines
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404

# OBSERVED — pinned: non-numeric id in the path -> 400 before any subresource
# logic.
GET http://{{hostnport}}/api/index.php/expensereports/notanumber/lines
HTTP 400

# OBSERVED — pinned: negative id -> 404.
GET http://{{hostnport}}/api/index.php/expensereports/-1/lines
HTTP 404

# ------------------------------------------------------- Section G: payments list (route-collision side)

# OBSERVED — pinned: /expensereports/payments is routed to the payments LIST,
# not parsed as {id}='payments' (which would 400 as a non-numeric long). The
# router resolves the static segment over the dynamic {id} — the collision
# pin from the header holds. If this ever fails, the router lost the
# static-over-dynamic precedence — regression.
GET http://{{hostnport}}/api/index.php/expensereports/payments
HTTP 200

# OBSERVED — pinned: payments list honors the strict pagination signature —
# non-numeric limit rejected 400 (same as the main list).
GET http://{{hostnport}}/api/index.php/expensereports/payments?limit=abc
HTTP 400

# OBSERVED — pinned: payments list with canonical sort answers 200.
GET http://{{hostnport}}/api/index.php/expensereports/payments?sortfield=t.rowid&sortorder=asc&limit=1
HTTP 200

# OBSERVED — pinned: unknown sortfield on payments answers 503 — same
# unvalidated-SQL-reach behavior as the main list (consistent FINDING 3
# behavior on this route family). Must not crash.
GET http://{{hostnport}}/api/index.php/expensereports/payments?sortfield=t.nosuchfield&limit=1
HTTP 503

# OBSERVED — pinned: far-out page on payments answers 200 (empty), never 500.
GET http://{{hostnport}}/api/index.php/expensereports/payments?page=999&limit=2
HTTP 200

# OBSERVED — pinned: combined payments-list request answers 200.
GET http://{{hostnport}}/api/index.php/expensereports/payments?sortfield=t.rowid&sortorder=asc&limit=2&page=0
HTTP 200

# ------------------------------------------------------- Section H: payments by pid

# NOTE: the positive payments path is deliberately NOT captured — an instance
# may have zero payment rows, and a capture-miss would abort the suite. Only
# negative/zero ids are pinned. Positive-pid coverage: add manually on an
# instance with payments, mirroring Section E.

# OBSERVED — pinned: nonexistent pid -> 404 (sibling by-id convention).
GET http://{{hostnport}}/api/index.php/expensereports/payments/99999999
HTTP 404

# FINDING 4 — OBSERVED — pinned (2026-11-22, curl): pid 0 -> 404 — no
# specimen on the payments side either; consistent with the by-id 404 and the
# pure-association nature of payment rows.
GET http://{{hostnport}}/api/index.php/expensereports/payments/0
HTTP 404
[Asserts]
jsonpath "$.error.code" == 404

# OBSERVED — pinned: negative pid -> 404.
GET http://{{hostnport}}/api/index.php/expensereports/payments/-1
HTTP 404

# OBSERVED — pinned: non-numeric pid -> 400.
GET http://{{hostnport}}/api/index.php/expensereports/payments/notanumber
HTTP 400
