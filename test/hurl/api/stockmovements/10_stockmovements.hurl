# test/hurl/api/stockmovements/10_stockmovements.hurl
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# PLACEMENT NOTE: in the real repo tree this file lives at
#   api/stockmovements/10_stockmovements.hurl
# run.sh's auth-required batch only executes files matching the glob
# */10*.hurl, so it MUST sit one directory deep under api/.
#
# GET-only tests for the stock movements API — every GET endpoint of the
#   class StockMovements
#   (htdocs/product/stock/class/api_stockmovements.class.php — PATH NOW
#   CONFIRMED: the full class source was pasted and analysed
#   2026-11-27; it requires movementstock.class.php and wraps
#   movementStock).
#
# SOURCE READ: COMPLETED 2026-11-27. All routes/params/gates below are
#   sealed against the class source. Behavioural pins are re-annotated
#   with the FIRST RUN (2026-11-27, commit d762ef09): 34 of 35 requests
#   GREEN; the single failure was the SMF1 pin (BY-ANALOGY 200 on /0),
#   deliberately flipped to the OBSERVED 400 — see the SMF1 pin history.
#   "Change this assert deliberately, never accidentally."
#
# SPEC OF RECORD (SIF8/SPP8/RCP8 LESSON): the instance's own
#   swagger.json — never a pasted snapshot. This episode ran
#   swagger-first (fresh explorer paste 2026-11-27), then the source
#   read, then the instance run — all three agreed everywhere except
#   where the pin was analogy-only (SMF1), which is exactly the RCP1
#   lesson working as intended:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/stockmovements"'
#
# GET SURFACE (SEALED by source read 2026-11-27 + instance run):
#     GET /stockmovements          (index: list, @since 5.0.0;
#                                   count param @since 25.0.0)
#     GET /stockmovements/{id}     (get: one movement, @since 23.0.0)
#     a) NO subresources — source-confirmed: no getLines/getContacts/
#        anything; a stock movement IS a single ledger row. No
#        subresource sections in this suite, and no per-subresource
#        id=0 camps (SMF1 is one route only).
#     b) count: in the docblock WITH @since 25.0.0 AND in the swagger
#        paste — mechanics source-sealed: sqlfilters validated (400)
#        BEFORE the `if ($count) return _getCountOfRecords($sql);`
#        early return; the early return precedes order()/plimit() and
#        the pagination block, so limit/page/pagination_data are all
#        IGNORED in count mode. Confirmed OBSERVED (green run).
#     c) sqlfilterlines: NOT in code AND no line table to filter —
#        matrix SKIPPED with this source note (definitive now, not
#        conditional).
#     d) product_id / warehouse_id list filters: NOT in code either —
#        RULED OUT (they exist only as POST body params). The
#        DOCUMENTED way to scope the list is sqlfilters on
#        t.fk_product / t.fk_entrepot — both columns CONFIRMED live
#        (green probes: sortfield=t.fk_product, t.batch LIKE).
#     e) status enum: NO status parameter — source-confirmed.
#     f) by-value routes: NONE — source-confirmed.
#     g) PUT/DELETE: NOT ROUTED — but present as COMMENTED-OUT DEAD
#        CODE in the class (put() gated stock/creer, delete() gated
#        stock/supprimer; delete()'s success message is a copy-paste
#        artefact: 'Warehouse deleted'). WISH candidate DEFERRED to
#        phase 2 — see WISH_NOTE_stockmovements_PUT_DELETE.md
#        (workspace root): either finish/expose them deliberately
#        (with a documented reverse/correction convention) or delete
#        the dead code and document immutability.
#     h) DUAL PERMISSION GATES (new vs the supplier family): index()
#        requires hasRight('stock', 'lire'); get() requires the
#        NARROWER hasRight('stock', 'movement', 'lire'). The C6
#        manual probes below cover BOTH gates.
#     i) get() carries an EXPLICIT falsy guard BEFORE fetch:
#        `if ($id == 0) throw RestException(400, 'No stock movement
#        with id 0 can exist')` — the SMF1 story (see below).
#     j) index() SQL shape: FROM llx_stock_movement t INNER JOIN
#        entrepot e ON t.fk_entrepot = e.rowid LEFT JOIN
#        stock_movement_extrafields ef, WHERE e.entity IN
#        (getEntity('stock')) — warehouse-entity scoping is structural;
#        get() additionally runs _checkAccessToResource('stockmovement',
#        ..., 'stock_movement', '', '', 'rowid', 'fk_entrepot@entrepot').
#     Write-side routes (context only, OUT OF SCOPE): POST
#     /stockmovements — NOT exercised by this GET-only suite.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment (admin key); product/stock modules enabled with
#   stock+lire AND stock/movement/lire rights for the key (note (h)).
# FIXTURE RESOLVED (2026-11-27): the instance has >= 1 stock movement —
#   the capture chain and the count gauge were GREEN on the first run.
#   If the instance is ever emptied, the LIST section dies with
#   "query didn't return any result" on the FIRST request = MISSING
#   FIXTURE, not a pin failure. Fixture recipes: GUI product card ->
#   stock tab -> "Correct stock"/transfer; or any
#   consumption/reception/shipment writing a movement; or POST
#   /stockmovements OUTSIDE hurl. Gauge:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/stockmovements?limit=100&count=1"
#     -> {"count": N}   (N >= 1 required)
# Instance: INSTANS: commit d762ef0986b573279bf8e680c69f9248558bf6a9
#   (HEAD -> develop, origin/develop, origin/HEAD; merge of #43483
#   "FIX Accountancy - Unletter the whole group in deleteLettering()",
#   Alexandre Spangaro, 2026-11-27 08:54:38 +0100).
#   Episode narrative: drafted 2026-11-27 BEFORE the source read (all
#   pins BY-ANALOGY, instance commit PENDING); source pasted + manual
#   curl /0 -> 400 the same morning; pin flipped deliberately; first
#   hurl run 2026-11-27: 34/35 green, sole failure the pre-flip copy
#   of this file. All OBSERVED pins refer to d762ef09 unless a comment
#   cites another commit. Re-annotate here if the instance moves.
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples: http://${DOLIHOST}/api/index.php/... with
#     DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#
# PORTABILITY: no hardcoded ids, refs or counts — everything captured at
#   runtime. VERIFY THE ROW SHAPE FIRST with jq before trusting captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/stockmovements?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/stockmovements?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   Family precedent (supplierorders, supplierinvoices, supplier-
#   proposals, receptions): id is a STRING (C5/#43386, four-for-four).
#   The bare-compare round-trip assert below was GREEN on the first
#   run, which is CONSISTENT WITH BOTH string and number (hurl compares
#   the captured token literally) — the jq probe (SMF5) settles the
#   C5 tally. If number, switch to the toString filter form:
#     jsonpath "$.id" toString == "{{stockmovement_id}}"
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE (SOURCE-SEALED, note (h)): index() gates
#   hasRight('stock', 'lire') FIRST; get() gates hasRight('stock',
#   'movement', 'lire') FIRST, then the id==0 400 guard, then fetch
#   -> 404, then _checkAccessToResource -> 403. A low-privilege key
#   sees 403 BEFORE any falsy-id/404/SQL path on BOTH routes.
#   LOW-PRIVILEGE NEGATIVE CONTROL (C6): NOT AUTOMATED — the zero-rights
#   (LOWKEY) key is not available to hurl tests via run.sh. C6 is
#   MANUAL curl ONLY. Never embed {{lowkey}} in this file. See the C6
#   section at the bottom.
#
# FINDINGS CATALOGUED (SMF = StockMovements Finding):
#   SMF1 (OBSERVED 2026-11-27, manual curl + hurl run, commit
#     d762ef09): GET /stockmovements/0 -> **400 "No stock movement
#     with id 0 can exist"** — a THIRD CAMP, new to the whole saga:
#     neither 200-specimen (supplierorders/supplierinvoices/receptions)
#     nor 404 (supplierproposals/tasks), but an EXPLICIT 400 guard
#     written BEFORE fetch() (source-verified, note (i)). PIN HISTORY
#     (RCP1 lesson again, opposite direction from receptions): (1)
#     BY-ANALOGY 200-specimen (family majority) — WRONG; (2) the
#     source read revealed the guard; (3) manual curl + the instance
#     confirmed 400; pin flipped DELIBERATELY. The #43307 "flip
#     falsy-id pins" activity did NOT reach this class as a 404 —
#     it (or its author) chose 400 here. NOTE: get() is @since 23.0.0
#     (a young route, unlike the @since 5.0.0 index()) — the modern
#     guard plausibly reflects newer coding conventions (INFERENCE,
#     not observed). Tally comment candidate for #43307 in PHASE 2:
#     a third camp exists and this class has the cleanest answer of
#     all. No new issue: the guard is CORRECT behaviour.
#   SMF2 (OBSERVED 2026-11-27, first run, all GREEN): STRICT 400 on
#     BOUND typed params — limit=abc -> 400, page=abc -> 400,
#     pagination_data=NOTABOOLEAN -> 400, count-mode bad sqlfilters ->
#     400; SILENT 503 on unknown sortfield/sqlfilters column (query
#     error path), 400 on invalid sqlfilters syntax. Family pattern
#     now extends BEYOND the supplier family (C4b was four-for-four
#     in-family; this is the first non-family confirmation — see
#     FAMILY STATUS at the bottom).
#   SMF3 (PARTLY SEALED): falsy-param drop — the undocumented-filter
#     hypothesis is RULED OUT by source (note (d): no product_id/
#     warehouse_id query params exist). sqlfilters=0 -> 200 GREEN
#     (PHP "0" is falsy to the `if ($sqlfilters)` guard — filter
#     dropped, unfiltered list; consistent with family). The
#     pagination.total diff probe stays MANUAL (bottom section) as a
#     cheap regression guard.
#   SMF4 (OBSERVED 2026-11-27, first run, GREEN): limit=0 +
#     pagination_data=true -> 200 envelope; the guard
#     '($limit > 0 ? (int) ceil(...) : 1)' is VISIBLE in the source.
#     This is the FIRST class outside the supplier family carrying the
#     #42952 fix pattern — the pattern is engine-wide, not
#     family-local. Aggregate comment candidate on #42952 in PHASE 2.
#   SMF5 (PENDING, MANUAL): id serialization (string vs number,
#     C5/#43386). Bare-compare round-trip was green (consistent with
#     both); the jq probe at the bottom settles the tally.
#   SMF6 (RESERVED): any NEW divergence. Raised so far (candidates
#     for PHASE 2, both WISH-flavoured, no bugs found):
#       - dead PUT/DELETE code (note (g)) — finish/expose or remove;
#       - specimen-object idea (user-raised 2026-11-27): the family's
#         accidental 200-all-null answers on /0 LOOKED like a
#         specimen preview but were accidents; a DELIBERATE specimen
#         feature (e.g. GET /stockmovements/specimen or richer
#         swagger model examples) would serve the "see the object
#         shape before real data exists" need cleanly. Must NOT be
#         tied to id=0 semantics — this class shows why (explicit
#         400).
#   SMF7 (UPHELD): spec-of-record — swagger paste, source and instance
#     agreed everywhere except the analogy-only pin; observation beat
#     analogy exactly as the RCP1 lesson predicts.

# ---------------------------------------------------------------- GET /stockmovements (index)

# Positive: list, ascending sort on the conventional default sortfield
#   (source default: t.rowid / ASC), capture first row id for the
#   get() lookup. OBSERVED 2026-11-27 (green run, commit d762ef09).
#   REQUIRES the fixture above (>=1 stock movement).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
stockmovement_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — OBSERVED 2026-11-27 (green, no 500).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on the movement table's product column — OBSERVED 2026-11-27
#   (green): t.fk_product is a live column (note (d) confirmed).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.fk_product&sortorder=ASC&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Unknown sortfield -> 503 — OBSERVED 2026-11-27 (green; the query
#   error path throws RestException(503, 'Error when retrieve stock
#   movement list : '.lasterror)). SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works. OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected. OBSERVED 2026-11-27
#   (green; the source guards $page < 0 -> 0 only inside if ($limit)).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — with limit set, source coerces ($page < 0 ->
#   $page = 0), no 500. OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Negative limit -> 200 — OBSERVED 2026-11-27 (green). SOURCE NOTE
#   (differs from the siblings!): the pagination block is wrapped in
#   `if ($limit)` — a NEGATIVE limit is FALSY in PHP, so plimit() is
#   NEVER reached and the query runs WITHOUT a LIMIT clause: expect
#   the FULL list, not the empty LIMIT-0 collection the family
#   returned. The isCollection assert holds either way; count NOT
#   pinned because the full-list size is data-dependent.
#   (Family tally nuance for the phase-2 aggregate comment: the
#   C4/C3 family behaviours are decided by each class's own
#   if($limit)/plimit idiom — same status, different mechanism.)
GET http://{{hostnport}}/api/index.php/stockmovements?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Non-numeric limit (typed long) -> 400 — OBSERVED 2026-11-27 (green).
#   SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Non-numeric page (typed long) -> 400 — OBSERVED 2026-11-27 (green).
#   SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — OBSERVED 2026-11-27 (green). SMF4:
#   the #42952 guard '($limit > 0 ? ceil : 1)' is in THIS class too —
#   first confirmation OUTSIDE the supplier family. NOTE: with
#   limit=0 the `if ($limit)` skips plimit(), so the query is again
#   UNLIMITED; the envelope wraps whatever the full list is.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/stockmovements?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# sqlfilters — always-true filter on rowid. OBSERVED 2026-11-27.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection.
#   OBSERVED 2026-11-27.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored (source guard: if
#   ($sqlfilters)), list still served. OBSERVED 2026-11-27.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters=0 — SMF3: PHP "0" is falsy to the truthiness guard, so
#   the filter is DROPPED and the unfiltered list is served.
#   OBSERVED 2026-11-27 (green). Status pinned; the manual total-diff
#   probe below is the regression guard.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=0&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — LIKE that matches nothing: still a valid collection.
#   OBSERVED 2026-11-27 (green): t.batch is a live column.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.batch%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unknown sqlfilters column -> 503 — OBSERVED 2026-11-27 (green;
#   RestException(503, 'Error when retrieve stock movement list')).
#   SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Invalid sqlfilters syntax -> 400 — OBSERVED 2026-11-27 (green;
#   forgeSQLFromUniversalSearchCriteria errormessage ->
#   RestException(400, 'Error when validating parameter sqlfilters')).
#   SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.date_creation%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500.
#   OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.batch%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PRODUCT-SCOPED filter probe (note (d)): the documented way to scope
#   movements to one product is sqlfilters on t.fk_product. 12345 is
#   a syntactic digit example, data-independent; shape only.
#   OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sqlfilters=(t.fk_product%3A%3D%3A12345)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — SKIPPED (source-SEALED, note (c))
# NO sqlfilterlines parameter in code AND no header/line pair in this
#   class (a stock movement IS the line). The SOF2/#43388/#43430
#   five-probe matrix is skipped definitively. Nothing to reinstate.

# ---------------------------------------------------------------- PARAM count (@since 25.0.0 — docblock AND swagger AGREE; SMF7 symmetric)
# Source-sealed mechanics: index() validates sqlfilters (400) BEFORE
#   the `if ($count) return _getCountOfRecords($sql);` early return;
#   the early return precedes order()/plimit() and the pagination
#   block — limit/page/pagination_data are all IGNORED in count mode.
#   All five probes OBSERVED 2026-11-27 (green).

# Bare count — the whole-list total (fixture gauge: count >= 1)
GET http://{{hostnport}}/api/index.php/stockmovements?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 1

# count honours a filter (always-true sqlfilters — same count as
#   bare, shape pinned; data-dependent)
GET http://{{hostnport}}/api/index.php/stockmovements?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" >= 1

# Validation wins over count: invalid sqlfilters syntax must still
#   be rejected in count mode. OBSERVED 2026-11-27 (green). SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?count=1&sqlfilters=(bad%20syntax)
HTTP 400
[Asserts]
jsonpath "$.error" exists

# limit/page ignored in count mode — same count as bare count.
#   OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?count=1&limit=2&page=3
HTTP 200
[Asserts]
jsonpath "$.count" >= 1

# pagination_data does not wrap the count-mode response.
#   OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 1

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; shape only. OBSERVED 2026-11-27
#   (green; id and qty are live property names).
GET http://{{hostnport}}/api/index.php/stockmovements?properties=id%2Cqty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500. OBSERVED 2026-11-27
#   (green; silent drop).
GET http://{{hostnport}}/api/index.php/stockmovements?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata.
#   OBSERVED 2026-11-27 (green; source keys: total, page,
#   page_count, limit).
GET http://{{hostnport}}/api/index.php/stockmovements?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope. OBSERVED
#   2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data junk value — typed boolean -> 400. OBSERVED
#   2026-11-27 (green). SMF2.
GET http://{{hostnport}}/api/index.php/stockmovements?limit=5&pagination_data=NOTABOOLEAN
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Combined: sort + filter + projection + pagination envelope in one
#   request. OBSERVED 2026-11-27 (green).
GET http://{{hostnport}}/api/index.php/stockmovements?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cqty&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /stockmovements/{id}
# Source-sealed shape: gate hasRight('stock','movement','lire')
#   FIRST (note (h)), then the id==0 400 guard, then fetch($id) ->
#   404 'stock movement not found' on falsy, then
#   _checkAccessToResource(..., 'fk_entrepot@entrepot') -> 403.

# Positive: fetched by captured id — assert id round-trip only.
#   Round-trip GREEN 2026-11-27 (bare compare). SMF5: the jq probe
#   below still owes the string-vs-number verdict for the C5 tally;
#   if NUMBER, flip to:
#     jsonpath "$.id" toString == "{{stockmovement_id}}"
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/stockmovements/{{stockmovement_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{stockmovement_id}}"

# id=0 — SMF1. OBSERVED 2026-11-27 (manual curl AND the failed first
#   hurl run, commit d762ef09): HTTP 400
#   {"error":{"code":400,"message":"Bad Request: No stock movement
#   with id 0 can exist"}} — a THIRD CAMP, via an EXPLICIT guard
#   written before fetch() (source note (i)). PIN HISTORY: BY-ANALOGY
#   200-specimen (family majority) -> source read revealed the guard
#   -> instance confirmed 400 -> pin flipped DELIBERATELY. Neither
#   family camp applies; this class has the CLEANEST answer in the
#   whole saga (an honest 400 instead of an accidental 200-null
#   object). Tally evidence for #43307 (route: get()); PHASE 2
#   aggregate comment candidate. Correct behaviour — no issue.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/stockmovements/0
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: nonexistent id -> 404 — OBSERVED 2026-11-27 (green run 2,
#   commit d762ef09). Source-consistent (fetch falsy ->
#   RestException(404, 'stock movement not found')); family
#   four-for-four before it — now five-for-five OBSERVED.
GET http://{{hostnport}}/api/index.php/stockmovements/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter typed long -> 400.
#   OBSERVED-BY-ANALOGY (C4b) + source-consistent (docblock lists
#   400); NOT YET RUN (see note above). Verify at next run.
GET http://{{hostnport}}/api/index.php/stockmovements/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id -> 404 like nonexistent ids (fetch(-1) falsy).
#   OBSERVED 2026-11-27 (green run 2, commit d762ef09).
GET http://{{hostnport}}/api/index.php/stockmovements/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- SMF3 — falsy-filter diff probe (MANUAL, outside run.sh)
# sqlfilters=0 is pinned 200 above (guard drops the falsy string).
#   Cheap regression guard — identical totals = dropped, as sealed:
#   MANUAL (run with ${DOLIHOST} + ${ADMINKEY}, never via run.sh):
#     diff <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/stockmovements?sqlfilters=0&properties=id&pagination_data=true" \
#       | jq -c '.pagination.total') \
#          <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/stockmovements?properties=id&pagination_data=true" \
#       | jq -c '.pagination.total')

# ---------------------------------------------------------------- SMF5 — serialization jq probe (MANUAL)
# C5/#43386 tally: four-for-four id-as-STRING in the family. One
#   probe settles the fifth data point:
#   curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/stockmovements?limit=1" \
#     | jq '.[0].id, (.[0].id|type)'
# STRING -> the bare-compare assert above is correct as written;
#   NUMBER -> flip to the toString form and note the flip in the C5
#   tally (comment on #43386, never a duplicate). NOTE: the bare
#   compare was GREEN, so BOTH outcomes remain possible — the probe
#   is genuinely owed.

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to
#   hurl tests via run.sh. C6 coverage for this class is MANUAL, via
#   curl. SOURCE-SEALED ORDER: rights gate FIRST on BOTH routes —
#   index(): hasRight('stock', 'lire'); get(): hasRight('stock',
#   'movement', 'lire') — so every probe answers 403 BEFORE the
#   id==0 guard, the fetch/404 and the SQL build. Cover BOTH gates:
#     LOWKEY=<zero-rights-key> DOLIHOST=localhost:8080 \
#       bash -c 'for p in "?limit=1" "/0" "/99999999" \
#         "?count=1" "?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=1"; do \
#         printf "probe %s -> " "${p}"; \
#         curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#           "http://${DOLIHOST}/api/index.php/stockmovements${p}"; done'
#   EXPECTED: 403 clean JSON envelope
#   {"error":{"code":403,"message":"Forbidden"}} on all five — no
#   400-on-/0 leak (the guard sits BEHIND the rights gate), no
#   404-on-nonexistent leak, no raw SQL text. EXTRA DATA POINT vs
#   the family: whether a key with stock/lire but WITHOUT
#   stock/movement/lire exists in the matrix separates the TWO
#   gates (index 200 vs get 403) — probe if such a key is available.
#   RESULT-FIELD:
#   C6 stockmovements result: PENDING (fill in at the manual run).

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Source-sealed scoping (note (j)): index() scopes structurally via
#   INNER JOIN entrepot + WHERE e.entity IN (getEntity('stock')) —
#   ENTITY scoping, not per-thirdparty scoping (movements have no
#   socid to leak). get() additionally enforces
#   _checkAccessToResource(..., 'fk_entrepot@entrepot') — warehouse
#   scoping on the single-object route. MANUAL PROBE PLAN (fixtures
#   outside hurl), for the phase-2 cross-object tally:
#     1. Ensure products A and B each have at least one movement in
#        DIFFERENT warehouses (GUI stock tab or write-API fixtures
#        OUTSIDE this file).
#     2. GET /stockmovements?sqlfilters=(t.fk_product:=:<A-id>) —
#        the filtered result must contain ONLY A's movement ids.
#     3. GET /stockmovements?sqlfilters=(t.fk_entrepot:=:<W-id>)
#        mirrored for the warehouse dimension.
#     4. Any B-owned row in A's filtered result, or a row from a
#        warehouse outside the current entity, = scoping leak = BUG
#        issue with observed outputs only (phase 2).

# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Stockmovements is the FIRST class OUTSIDE the supplier family
#   (supplierorders #43388/#43423, supplierinvoices #43430/SIF8,
#   supplierproposals #43439/SPP7/SPP8, receptions RCP1-RCP8) touched
#   by this methodology — and the first to CONFIRM the engine-level
#   patterns beyond the family: strict typed-param 400s (SMF2), the
#   #42952 limit=0 guard (SMF4), sqlfilters 400/503 error split, all
#   OBSERVED. The one place it DIVERGES is the falsy-id camp — and it
#   diverges toward the CORRECT answer (explicit 400, SMF1). Phase 2
#   aggregate comments (never duplicates) go on #43307 (SMF1 third
#   camp + #43386 C5 + #42952 non-family confirmation). WISH
#   candidates are parked in WISH_NOTE_stockmovements_PUT_DELETE.md:
#   dead PUT/DELETE code and the specimen-object idea.
