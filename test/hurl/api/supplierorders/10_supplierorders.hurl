# test/hurl/api/supplierorders/10_supplierorders.hurl
# GET-only tests for the supplier orders (CommandeFournisseur) API — every
#   GET endpoint listed in the Swagger spec / class SupplierOrders
#   (htdocs/fourn/class/api_supplier_orders.class.php, doxygen
#   dolibarr_dev/22.0 class_supplier_orders.html — SOURCE READ 2026-11-25
#   BEFORE pins were set):
#     GET /supplierorders                  (index: list)
#     GET /supplierorders/{id}             (get: one order)
#     GET /supplierorders/{id}/contacts    (getContacts; source REQUIRED,
#                                          type optional)
#   NOTE: unlike customer orders (api_orders), SupplierOrders exposes NO
#     GET /supplierorders/{id}/lines route and NO by-value GET routes
#     (no /ref, no /ref_ext) — verified in the class member list (doxygen
#     22.0/dev). GET-only surface is exactly the three above.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Suppliers/Orders module enabled. The LIST section
#   captures "$[0]" and therefore requires at least one supplier order to
#   exist on the instance. The contacts section requires the captured
#   order to exist; contacts may legitimately be empty.
# Instance: commit 85f960e24142a34a7f8d961031ddee859a722839,
#   HEAD -> develop, origin/develop (merge a049f7b0069 + eeca37d1602,
#   Laurent Destailleur, 2026-11-25 12:22:43 +0100). Tests written on
#   branch hurl_test_api_get_supplierorders.
# STATUS OF PINS: FULLY OBSERVED (2026-11-25): full 50-request suite
#   run green on the instance above (admin API key), every BY-ANALOGY
#   pin either confirmed or corrected with an OBSERVED comment, and
#   camp questions settled by manual jq probes. "Change this assert
#   deliberately, never accidentally."
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples in issues/PRs: http://${DOLIHOST}/api/index.php/...
#     with DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment.
#
# PORTABILITY: no hardcoded ids, refs or counts — everything captured at
#   runtime. Field names pinned below must be verified against real
#   payloads BEFORE first commit:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/supplierorders?limit=1" | jq '.[0]'
#   Type-forsight: captures are strings; verify (.id|type) with jq before
#   trusting any bare-compare, use the toString filter where the JSON
#   field is a number. Serialization is known-inconsistent (#43386).
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE: read rights on supplier orders (fourn commande
#   lire-class rights) are required for all sections; get()/getContacts()
#   call DolibarrApi::_checkAccessToResource(). A 403 seen during the
#   4-profile API-key matrix run is a permission finding, not a reason to
#   change these pins.
#   LOW-PRIVILEGE NEGATIVE CONTROL (OBSERVED 2026-11-25, key with zero
#   granted rights): GET /supplierorders/0 -> 403 {"code":403} (NO
#   specimen — the SOF1 falsy bypass does NOT bypass the permission
#   gate) and GET ?sqlfilterlines=(t.qty...) -> 403 (NO raw SQL text
#   leaks to unauthorized keys — the #43388 error body stays behind the
#   permission gate). Permission checks run BEFORE both code paths.
#   SOF1 and #43388 are consistency/error-semantics findings, not
#   access-control findings.
#
# SOURCE-NOTED PARAMETER FACTS (doxygen, class SupplierOrders):
#   - index(): sortfield="t.rowid", sortorder='ASC', limit=100, page=0,
#     thirdparty_ids='' ({@pattern /^[0-9,]*$/i}), product_ids=''
#     ({@pattern /^[0-9,]*$/i}), status='' (draft|validated|approved|
#     running|received_start|received_end|cancelled|refused),
#     sqlfilters='', sqlfilterlines='', properties='', pagination_data=false
#   - getContacts(): $source is a REQUIRED parameter (internal|external|
#     all), $type optional (BILLING, SHIPPING, ...). Missing required
#     parameters under Restler conventionally yield 400.
#   - UPSTREAM FIX (PR #42952, merged Nov 2026, develop): limit=0 with
#     pagination_data=true used to raise DivisionByZeroError on
#     /supplierorders. The limit=0 pin below documents the FIXED
#     behaviour; keep it OBSERVED-BY-ANALOGY until first run.
#
# STATUS ANNOTATIONS: pins marked OBSERVED-BY-ANALOGY follow the
#   tasks/projects suites and MUST be re-verified at first run and
#   re-annotated with OBSERVED-date, or corrected, per the convention
#   "change this assert deliberately, never accidentally".
#
# FINDINGS CATALOGUED (all OBSERVED 2026-11-25, commit 85f960e2, hurl
#   runs + manual probes; issue files where noted):
#   SOF1: GET /supplierorders/0 -> 200 all-null SPECIMEN ("specimen": 0)
#     — 200-specimen camp (#43307 tally), tasks guessed wrong via
#     analogy; nonexistent non-falsy ids still 404 correctly. Same
#     shape family as projects FINDING 1; no separate issue (already
#     tallied by #43307/#43385 discussions).
#   SOF2: sqlfilterlines semantics + error handling — line columns work
#     BARE (qty), the t.-alias convention fails on line columns
#     (t.qty -> 503 "Unknown column 't.qty' in 'WHERE'"), unknown
#     columns -> 503 with raw SQL message instead of 400.
#     UPSTREAM ISSUE: Dolibarr/dolibarr#43388
#     ISSUE FILE (repo copy): issue_sqlfilterlines_supplierorders.md.
#   SOF3 (divergence, not bug): index() strictly validates documented
#     list params — thirdparty_ids/product_ids pattern violations and
#     unknown status keywords answer 400 (no comparable strictness in
#     tasks/projects list params). Desirable behaviour; noted as
#     cross-resource divergence only.
#   SOF4 (divergence, confirmed fixed): limit=0 + pagination_data=true
#     -> 200 data envelope (DivisionByZeroError fixed by PR #42952,
#     present on this instance).
#   SOF5 (OBSERVED 2026-11-25, diff probe): thirdparty_ids=0 DROPS the
#     filter entirely and returns ALL orders (same pagination.total as
#     the unfiltered list) — falsy-param family of projects category=0.
#     No separate issue; same-class evidence for the falsy-param tally.
#
# ---------------------------------------------------------------- GET /supplierorders (index)

# Positive: list, ascending sort on the documented default sortfield,
#   capture first row id for later lookups.
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
order_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on another documented column of the order table (ref)
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (tasks P2 / projects): unknown sortfield flows into
#   SQL ORDER BY -> 503. Re-pin against the live instance if it differs.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — coerced, no 500 (tasks/projects camp)
GET http://{{hostnport}}/api/index.php/supplierorders?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED-BY-ANALOGY (tasks/projects): negative limit -> 200 (coerced)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (tasks/projects P1): non-numeric limit -> 400
GET http://{{hostnport}}/api/index.php/supplierorders?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (tasks/projects P1): non-numeric page -> 400
GET http://{{hostnport}}/api/index.php/supplierorders?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — formerly DivisionByZeroError (PR
#   #42952, fixed on develop Nov 2026). OBSERVED 2026-11-25 (hurl run
#   on commit 85f960e2): HTTP 200 with the data envelope — the fix is
#   confirmed on this instance.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# thirdparty_ids — documented pattern /^[0-9,]*$/i; "0" matches nothing
#   but is syntactically valid (never hardcoded to real ids — portability)
#   SOF5 (OBSERVED 2026-11-25, diff probe of pagination.total against the
#   unfiltered list): the filter is DROPPED for the falsy value —
#   thirdparty_ids=0 returns ALL orders, identical total to no filter.
#   Same falsy-param family as projects category=0 (projects FINDING 2
#   parenthetical). Count NOT pinned (data-dependent); status pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?thirdparty_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# thirdparty_ids — junk violating the documented {@pattern /^[0-9,]*$/i}:
#   OBSERVED 2026-11-25 (first hurl run): HTTP 400 — the pattern IS
#   enforced by Restler, unlike the unpatterned list params of the
#   tasks/projects suites. DIVERGENCE from the tasks/projects patterns:
#   first list-parameter-level strict validation seen; not a bug.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?thirdparty_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# product_ids — same documented @pattern as thirdparty_ids, so pinned
#   OBSERVED-BY-ANALOGY to the same 400; verify at the next run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?product_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# status — documented keyword value; "draft" must be accepted even if no
#   drafts exist (empty collection is fine)
GET http://{{hostnport}}/api/index.php/supplierorders?status=draft
HTTP 200
[Asserts]
jsonpath "$" isCollection

# status — unknown keyword: OBSERVED 2026-11-25 (first hurl run): HTTP
#   400 — the documented status enum IS enforced, strictly validated
#   like the patterned ids above. DIVERGENCE from tasks/projects, which
#   had no comparable enum list filter; consistent strict-validation
#   family on this resource. Not a bug.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?status=NOSUCHSTATUS
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index filter: sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid collection
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (projects P3 / tasks): unknown sqlfilters column ->
#   503 (syntax validated, column names not). Same code family applies
#   here; verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (projects P3 / tasks): invalid sqlfilters syntax -> 400
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.dateo%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — second, dedicated line-filter parameter of this
#   resource's index(); swept with the same trio.
#   RESOLVED SEMANTICS (OBSERVED 2026-11-25, hurl runs + manual probes
#   on commit 85f960e2): the CONTRARY of the first reading. Line columns
#   work BARE (qty, no alias); the plain-sqlfilters "t."-alias
#   convention does NOT apply to sqlfilterlines — "t." inside
#   sqlfilterlines resolves against the ORDER-HEADER table:
#     sqlfilterlines=(t.qty>=0)  -> 503 "Unknown column 't.qty' in 'WHERE'"
#     sqlfilterlines=(qty>=0)    -> 200 (line filter WORKS)
#     sqlfilterlines=(t.rowid>=0)-> 200 (header columns reachable via t.)
#   So the parameter filters on a t.*-header + bare-line-columns combo
#   query. FINDINGS for the issue file (kept, narrowed):
#     (a) unknown column -> 503 with a RAW SQL message, not 400
#         (client input can never succeed — wrong error semantics);
#     (b) the t.-alias convention documented for sqlfilters FAILS on
#         exactly the line columns sqlfilterlines exists for — a
#         convention trap for API consumers.
#   ISSUE FILED: issue_sqlfilterlines_supplierorders.md (revised).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# sqlfilterlines with a BARE line column — the WORKING form: OBSERVED
#   200 (2026-11-25). Pins that the functional purpose (line filtering)
#   is served; row shape not asserted beyond collection (portability).
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilterlines=(qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines via t.-alias on a real HEADER column: OBSERVED 200
#   (2026-11-25) — pins the hybrid header/line semantics.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — unknown column: OBSERVED 2026-11-25: HTTP 503 with
#   "Unknown column 't.nosuchcolumn' in 'WHERE'" — same error-semantics
#   family as plain sqlfilters P3 (syntax validated, column names not,
#   raw SQL error surfaced as 503 instead of 400).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilterlines=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# sqlfilterlines — invalid syntax: OBSERVED 2026-11-25 (hurl run):
#   HTTP 400, same "Bad syntax"-family validation as plain sqlfilters.
GET http://{{hostnport}}/api/index.php/supplierorders?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/supplierorders?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/supplierorders?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/supplierorders?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/supplierorders?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/supplierorders?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /supplierorders/{id}

# Positive: fetched by captured id — assert id round-trip and field
#   presence only. NOTE: verify the id serialization type with jq first;
#   tasks-family precedent is string serialization (#43386). If the id
#   here is a NUMBER, switch to the toString filter form:
#     jsonpath "$.id" toString == "{{order_id}}"
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{order_id}}"
jsonpath "$.ref" exists

# id=0 — falsy-id family. OBSERVED 2026-11-25 (hurl run + manual
#   payload probe on commit 85f960e2): HTTP 200 with the all-null
#   SPECIMEN object ("id": null, "ref": null, "specimen": 0) —
#   SupplierOrders joins the 200-SPECIMEN camp (projects, thirdparties,
#   members, now supplierorders — see cross-module tally
#   Dolibarr/dolibarr#43307), CONTRARY to the tasks family (404).
#   Nonexistent non-falsy ids still answer 404 with the JSON envelope
#   ("Not Found: Supplier order not found", OBSERVED 2026-11-25), so it
#   is ONLY the falsy 0 that bypasses the not-found path. Header
#   finding SOF1; same-shape family as projects FINDING 1.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders/0
HTTP 200
[Asserts]
jsonpath "$.specimen" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/supplierorders/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter is typed int -> 400
GET http://{{hostnport}}/api/index.php/supplierorders/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/supplierorders/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /supplierorders/{id}/contacts
# getContacts($id, $source, $type='') — $source is REQUIRED per the class
#   signature (internal|external|all), $type optional. There is therefore
#   NO source-less legal form of this endpoint.

# Positive: source=all on the captured order — may be empty, must be a
#   collection
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=all
HTTP 200
[Asserts]
jsonpath "$" isCollection

# source=internal and source=external variants
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=internal
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=external
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Optional type filter — documented contact type keyword and a junk value,
#   pinned to 200 + collection pending first-run verification
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=external&type=BILLING
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=external&type=NOSUCHTYPE
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Fuzz: junk source value — unknown source should behave like a filter
#   returning an empty collection (pinned 200 pending first-run check;
#   a 400 would also be defensible — re-pin with an OBSERVED comment).
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts?source=NOSUCHSOURCE
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Missing required source parameter — Restler rejects calls lacking a
#   required parameter; conventional answer is 400. OBSERVED-BY-ANALOGY
#   (Restler convention, tasks-family style).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders/{{order_id}}/contacts
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: nonexistent order id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/supplierorders/99999999/contacts?source=all
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric order id on the subresource -> 400
GET http://{{hostnport}}/api/index.php/supplierorders/abc/contacts?source=all
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — falsy-id family. OBSERVED 2026-11-25
#   (hurl run + manual payload probe on commit 85f960e2): HTTP 200 with
#   an EMPTY ARRAY (jq: length 0) — the order constraint IS applied on
#   contacts, same correct empty-set behaviour as /projects/0/contacts.
#   NO over-exposure, NO projects-FINDING-5-class leak on this
#   subresource. Pinned as the empty-collection shape.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierorders/0/contacts?source=all
HTTP 200
[Asserts]
jsonpath "$" exists
jsonpath "$" count == 0

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# SupplierOrders exposes NO lines GET route and NO other read subresource
#   besides /contacts — so the tasks TF1-style cross-object leak probe
#   (line belonging to another order) is NOT applicable within the
#   GET-only surface of this class. The contacts endpoint returns contacts
#   LINKED to the asked order; a cross-probe would only be meaningful
#   with two orders with known distinct contacts. MANUAL PROBE PLAN (not
#   a hurl pin, no fixtures via GET-only):
#     1. Pick two supplier orders A and B on the instance with known
#        contacts (GUI or POST fixtures outside this file).
#     2. GET /supplierorders/{A}/contacts?source=all and
#        GET /supplierorders/{B}/contacts?source=all with
#        ${DOLIHOST}+${DOLAPIKEY}; diff the returned contact ids.
#     3. If B's list contains A-only contact ids, file a BUG issue
#        (cross-object contact leak) with the observed outputs only.
#   RESOLVED (2026-11-25, fixtures on the instance): user 29 linked to
#   order 7 ONLY via POST /supplierorders/7/contact/29/SALESREPFOLL/
#   internal (NOTE: type BILLING/internal was rejected with HTTP 304 —
#   write-side observation, outside this GET-only suite). Result:
#   order 7 -> contacts ["28","29"], order 11 (never had contacts) ->
#   []. Ownership IS enforced on the contacts subresource — NO
#   projects-FINDING-5-class leak. LOWKEY side-note (OBSERVED): the
#   zero-rights key still answers 403 on BOTH orders after the link —
#   becoming a linked user does NOT open module access.
