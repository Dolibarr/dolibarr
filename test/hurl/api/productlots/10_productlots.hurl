# test/hurl/api/productlots/10_productlots.hurl
#
# PLACEMENT NOTE: this file lives at api/productlots/10_productlots.hurl
# (run.sh's auth-required batch only executes files matching the glob
# */10*.hurl). Directory is the ENDPOINT name "productlots", not the
# module name "productbatch" — the README's "Undecided discrepancy"
# note applies; endpoint wins, in line with every prior suite.
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the product lots (productbatch) API — every GET
#   endpoint of the class Productlots
#   (source: htdocs/product/stock/class/api_productlots.class.php,
#   SOURCE READ COMPLETED 2026-11-27: full class source pasted and
#   analysed; all GET routes/params sealed against BOTH the live
#   explorer paste AND the class source. Episode order completed the
#   RCP1 lineage: instance spoke first (swagger paste + live probes),
#   probes confirmed (the 503-on-t.rowid incident), source sealed last.)
#
# CONFIDENTIALITY NOTE: the instance's product identities and product
#   data are CONFIDENTIAL (contributor instruction 2026-11-27). This
#   suite references NO product data whatsoever — only the integer
#   fk_product captured at runtime and the batch label TEST-LOT-API-0001
#   created by the maintainer as a fixture. Do not add product refs,
#   labels, prices or extrafield values from this instance to this file,
#   to commit messages, to PR text or to issue files.
#
# SPEC OF RECORD (SIF8/SPP8/RCP8/SMF7/SHF7 discipline, now SIX-fold):
#   the instance's own swagger.json — re-pull at first run, ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/productlots"'
#
# GET SURFACE (SOURCE-SEALED 2026-11-27; explorer paste agreed on every
#   route and parameter — a)-g) notes closed):
#     GET /productlots           (index)
#     GET /productlots/{id}      (get)
#     a) sqlfilterlines: ABSENT from index()'s signature (source-
#        sealed). Silently ignored (#43430/#43439 family). Decisive
#        probe OBSERVED 2026-11-27: bad syntax -> 200. Regression
#        guards kept below.
#     b) count: PRESENT and SOURCE-SEALED: @since 25.0.0 docblock +
#        explorer + run. index() returns _getCountOfRecords($sql)
#        AFTER the sqlfilters validation but BEFORE order()/plimit() —
#        validation-wins-over-count holds; limit/page/pagination_data
#        are all IGNORED in count mode.
#     c) status enum: NO status parameter on either GET route.
#     d) by-value routes: NONE (no /ref, no /lot_number).
#     e) FILTER SURFACE: NO thirdparty_ids (lots have no socid —
#        confirmed in source: index() declares exactly sortfield/
#        sortorder/limit/page/user_ids/sqlfilters/properties/
#        pagination_data/count). user_ids is DECLARED and PATTERN-BOUND
#        ({@pattern /^[0-9,]*$/i} — non-conforming values -> 400) but
#        NEVER READ in the method body: a DEAD-BOUND parameter. Its
#        docblock ("User ids filter field (owners of event)") and the
#        error strings ("Insufficient rights to read an event",
#        "Error creating event") are agenda/event copy-paste artefacts.
#     f) NO contacts route, NO subresources — the capture chain is
#        list -> id -> get() only.
#     g) SORTORDER (SOURCE-SEALED): '(strtoupper($sortorder) ===
#        "DESC") ? "DESC" : "ASC"' — the UPPERCASE-COERCION idiom,
#        DIVERGENT from the family's exact-match handling: lowercase
#        "desc" and junk values are silently coerced (no 503).
#     ALIAS DIVERGENCE (SOURCE-SEALED + OBSERVED 2026-11-27): the
#        table alias is **pl** ("FROM product_lot AS pl" + LEFT JOIN
#        product_lot_extrafields AS ple), NOT the family's "t". The
#        docblock's own example uses pl ("(pl.label:like:'%dol%')").
#        OBSERVED live: sortfield=t.rowid -> 503 "Unknown column
#        't.rowid' in 'ORDER BY'"; sqlfilters=(t.rowid:>:...)
#        -> 503 "Unknown column 't.rowid' in 'WHERE'"; pl.rowid -> 200.
#        ALL pl.* below is therefore normative; the t.* probes stay as
#        REGRESSION GUARDS pinning the 503 (a future 200 would mean the
#        alias changed).
#     ENTITY SCOPE (SOURCE-SEALED): index() hard-restricts to
#        "WHERE pl.entity IN (getEntity('productlot'))" (multi-entity
#        managed) — no parameter exposes this; noted for cross-entity
#        sessions only.
#     LIMIT IDIOM (SOURCE-SEALED): 'if ($limit) { plimit($limit + 1,
#        $offset) }' — the stockmovements/shipments KIN, NOT the
#        plimit-always family shape: limit<=0 (incl. -1) skips plimit
#        -> the UNBOUNDED list is served. Pagination-data guard is
#        '($limit > 0) ? min(...) : num' with safeLimit =
#        max(1, count($obj_ret)) — limit=0 + pagination_data=true
#        therefore serves the FULL list wrapped, 200 (#42952 pattern,
#        eighth confirmation).
#     PERMISSION GATES (SOURCE-SEALED; behavioural outcome OBSERVED
#        2026-11-27): get() gates hasRight('product', 'lire'); index()
#        gates hasRight('produit', 'lire') — two module-key spellings
#        in one class. RESOLVED BEHAVIOURALLY NEUTRAL: both spellings
#        resolve to the SAME permission entry (a products-lire-only
#        key passed BOTH routes live). Comment-worthy cosmetics, NOT
#        an access bug. Double-gate check CLOSED: each GET method
#        consults exactly ONE right (+ _checkAccessToResource
#        ('produit', ..., 'product_lot') AFTER a successful fetch on
#        get()). TYPE-SCOPING consequence: see PLOT6(iii) — the gates
#        consult the PRODUCTS permission block only; the SERVICES
#        block is never checked, regardless of the lot's product type.
#     Write-side routes (context only, OUT OF SCOPE here): POST /
#     (create), PUT /{id} (update), DELETE /{id}.
#
# LIVE OBSERVATIONS BEARING ON PINS (probes, 2026-11-27):
#     GET /productlots/0   -> 200 ALL-NULL SPECIMEN object
#                             ("id": 0, "batch": "ABCD123456",
#                             "specimen": 1) — source: get() branches
#                             EXPLICITLY on ($id === 0) to
#                             initAsSpecimen(): a family-FIRST
#                             explicit-specimen idiom (siblings reach
#                             the specimen camp implicitly via falsy
#                             fetch).
#     GET /productlots/1 (nonexistent at paste time) -> 404 envelope
#                             "Not Found: Product lots not found".
#     sortfield/pl probes as listed above; sqlfilterlines bad syntax
#                             -> 200; empty list -> 200 [].
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; produit/lire (and product/lire for get()) rights on
#   the key; the productbatch module enabled and at least one row in
#   product_lot. Fixture USED IN THIS EPISODE (2026-11-27): lot created
#   via write-API OUTSIDE this hurl file:
#     curl -s -X POST -H "Content-Type: application/json" \
#       -H "DOLAPIKEY: ${ADMINKEY}" -d @fixture_productlot.json \
#       "http://${DOLIHOST}/api/index.php/productlots"
#   with fixture_productlot.json beside this file: {"fk_product": <a
#   real product id>, "batch": "TEST-LOT-API-0001"} (contents
#   CONFIDENTIAL per the note above — never print the product id's
#   neighbours or the product's data). A second identical POST returns
#   500 "Error creating event" with the RAW DB duplicate-key text
#   leaked ("Duplicate entry ... for key 'uk_product_lot'") — a
#   phase-2 issue-comment candidate (error-message leak), recorded
#   OBSERVED 2026-11-27.
#   Gauge BEFORE a run on a fresh instance:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots?limit=100&count=1"
#   Empty list ({"count":0}) = MISSING FIXTURE, not a pin failure.
# Instans-commit: a6eb6f216918a931ba10474ee23e398c624729c8 (merge of
#   #43566, 2026-11-27 21:36:59 +0100) — the FIRST GREEN RUN ran
#   44/44 in 1554 ms on this commit. Instance then MOVED:
#   -> **3d7bc9abc3d37b4a0b29d82c4cf76fa4d82c1a38** (HEAD -> develop,
#   merge of #43565 "FIX AI assistant: Dolibarr no longer frozen while
#   the AI answers", Laurent Destailleur 2026-11-27 21:47:58 +0100).
#   Re-run on 3d7bc9ab COMPLETED 2026-11-27: SECOND GREEN RUN, 44/44
#   in 1382 ms — all pins reproduced UNCHANGED (including the two
#   t.-alias 503-regression guards, the specimen pin on /0, and the
#   count-mode shapes). Episode status: two green runs across two
#   commits (a6eb6f21 + 3d7bc9ab), pins OBSERVED and re-confirmed on
#   both; no API-side change attributable to the merged PR, as
#   expected. Convention:
#   ... -> 08183c47 -> 817f40c3 -> e0e62e83 -> a6eb6f21 -> 3d7bc9ab.
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
#       "http://${DOLIHOST}/api/index.php/productlots?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   C5/#43386 + #43568 tally: five endpoints serialize list-id as
#   STRING, shipments as NUMBER (first divergence). ANTAG INGEN DEFAULT:
#   the jq probe comes FIRST; all asserts below are TYPE-AGNOSTIC
#   (existence/shape/count/isCollection; bare compares only on
#   runtime-captured values used round-trip). SIDE OBSERVATION
#   2026-11-27: POST returns the new id as a bare NUMBER (source
#   @return int); that is the write-side return, NOT proof about list
#   serialization — the GET jq probe still decides PLOT5.
#   No asserts on free text — only status codes, ids and shapes.
#
# FINDINGS CATALOGUED (PLOT = ProductLots Finding; catalogue prefix
#   pre-allocated; OBSERVED items carry their date — instance commit
#   hash appended at the first green run):
#   PLOT1 (OBSERVED-BY-EXPLORER+SOURCE+PROBE 2026-11-27): id=0-camp on
#     get() -> 200 ALL-NULL SPECIMEN, via an EXPLICIT ($id === 0)
#     branch to initAsSpecimen() — family-first explicit-specimen
#     idiom. Pin history: (1) 2026-11-27 BY-ANALOGY (four-of-seven
#     majority); (2) 2026-11-27 source read CONFIRMED (explicit
#     branch, stronger than the siblings' implicit path); (3)
#     2026-11-27 instance probe CONFIRMED (200, specimen:1,
#     batch:"ABCD123456"). No subresources exist on /productlots, so
#     there is no second per-route camp. Tally evidence for #43307.
#   PLOT2 (BY-ANALOGY, streak six-for-six): strict 400 on BOUND typed
#     params (limit, page, pagination_data — and user_ids via
#     {@pattern}) vs SILENT passthrough on UNBOUND (unknown params,
#     sqlfilterlines). Source read supports the bound half (typed
#     signatures); the run decides the rest.
#   PLOT3 (SOURCE-SEALED + OBSERVED-shape 2026-11-27 — REVISED):
#     user_ids is DEAD-BOUND: pattern-validated (non-conforming -> 400)
#     but never read in the body. Prediction for the diff probe: EMPTY
#     diff for user_ids=0 AND for user_ids=1,2,3 alike — the drop is
#     BY-DEATH, not by falsy-guard (the C4/#43307 falsy-drop tally
#     gains a THIRD mechanism: declared-bound-dead). Pin history:
#     (1) BY-ANALOGY falsy-drop; (2) source read revealed the
#     dead-param variant; (3) diff probe decides between "dead" and
#     any residual enforcement — a non-empty diff would REFUTE the
#     source reading and be a PLOT6 candidate.
#   PLOT4 (SOURCE-SEALED 2026-11-27): limit idiom = if($limit) KIN —
#     limit<=0 skips plimit (UNBOUNDED list); limit=0 + pagination_
#     data=true -> 200 wrapped full list (safeLimit fallback
#     max(1, count)); #42952 pattern, eighth confirmation.
#   PLOT5 (OPEN): id serialization on the LIST — jq probe FIRST
#     (manual section). STRING = majority restored; NUMBER = second
#     divergence after shipments (#43568). POST already observed
#     returning a bare NUMBER (different surface, no tally impact).
#   PLOT6 (OBSERVED 2026-11-27): NEW DIVERGENCES, two live ones:
#     (i) ALIAS: pl not t (503s on t.* captured live; sealed in
#         source "FROM product_lot AS pl");
#     (ii) GATE-LITERAL INCONSISTENCY (SOURCE-SEALED, behaviourally
#         RESOLVED 2026-11-27): get() uses hasRight('product', 'lire'),
#         index()/post()/put()/delete() use 'produit' — two spellings,
#         one class. OBSERVED: products-lire-only key passed BOTH GET
#         routes, so the literals resolve to the same permission entry.
#         Cosmetic; comment-worthy only.
#     (iii) NO PRODUCT-TYPE SCOPING (OBSERVED 2026-11-27, commit
#         a6eb6f21): the gates consult the PRODUCTS permission block
#         only; the SERVICES block is never checked, and no code path
#         inspects the lot's product type. Live evidence on a lot
#         owned by a SERVICE-type product: products-lire-only key ->
#         200 on BOTH GET routes (full lot detail readable by a
#         products reader); services-lire-only key -> 403 on BOTH
#         routes (a services reader cannot read its own services'
#         lots). Both facets are permission-model findings, not bugs
#         in the suite. Also OBSERVED: POST /productlots accepts a
#         service-type fk_product with NO batch-tracking validation
#         (status_batch=0 on the product) — the lot surfaces existed
#         only via API, which leads straight to the (iii) evidence.
#   PLOT7 (CLOSED 2026-11-27): spec-of-record lesson upheld — fresh
#     explorer paste as hypothesis, probes next (the 503-on-t.rowid
#     incident was the instance overruling the family analogy), source
#     sealed last. Paste, probes and source agreed on every route and
#     parameter once the alias was understood.
#   PLOT8 (OBSERVED-BY-SOURCE 2026-11-27): COPY-PASTE ARTEFACTS: the
#     agenda/event strings ("read an event", "creating event",
#     "owners of event" docblock) in a productbatch class, and a fully
#     commented-out expensereport-lines block inside post() (the
#     #43515/#43514/#43569 dead-code pattern). No GET-side effect; all
#     evidence is phase-2 issue-comment material.

# ---------------------------------------------------------------- GET /productlots (index)

# Positive: list, ascending sort on the SOURCE-SEALED alias pl,
#   capture first row id for the get() round-trip. REQUIRES the
#   fixture (>=1 lot).
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
lot_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# PLOT-g (SOURCE-SEALED uppercase-coercion idiom): lowercase sortorder
#   is COERCED, not rejected.
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# PLOT-g continued: unknown sortorder value — silently coerced to ASC
#   (no 503). A 503 here would REFUTE the source-sealed idiom.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on the class' own DEFAULT sortfield (index() signature default:
#   "pl.batch") and on a lot-specific column
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.batch&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (family, six-for-six) + LIVE-CONFIRMED SHAPE
#   (2026-11-27: the 503 envelope "Error when retrieving product lot
#   list: Unknown column '...' in 'ORDER BY'"): unknown sortfield ->
#   query error -> RestException(503).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# PLOT6(i) REGRESSION GUARD — the ALIAS DIVERGENCE pinned as OBSERVED
#   2026-11-27 (live 503 "Unknown column 't.rowid' in 'ORDER BY'"):
#   the family alias t. is ILLEGAL here. A future 200 would mean the
#   alias changed — re-pin and comment the tally.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — source clamps via max(0, (int) $page), no 500
GET http://{{hostnport}}/api/index.php/productlots?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# PLOT4 (SOURCE-SEALED): negative limit -> 200; the if($limit) idiom
#   SKIPS plimit entirely -> the UNBOUNDED list is served. The 200 pin
#   holds either way (fixture is small); empty-vs-full is
#   data-dependent, NOT asserted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PLOT2: non-numeric limit (typed int) -> 400. Bound-param streak probe.
GET http://{{hostnport}}/api/index.php/productlots?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# PLOT2: non-numeric page (typed int) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/productlots?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# PLOT4 (SOURCE-SEALED): limit=0 + pagination_data — the #42952
#   pattern (safeLimit fallback max(1, count($obj_ret))), eighth
#   confirmation probe. A 500 here would be a NEW finding.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination" exists

# ---------------------------------------------------------------- Index filters: documented params

# PLOT3: user_ids=0 — DECLARED and PATTERN-BOUND but DEAD in the body
#   (source-sealed: the parameter is never read). The falsy-drop
#   hypothesis is REVISED to drop-by-death: the unfiltered list is
#   served for 0, for valid ids and for everything else. Status only
#   pinned; the MANUAL diff probe below separates drop vs enforcement.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?user_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PLOT2/PLOT3: user_ids — junk value violates the {@pattern
#   /^[0-9,]*$/i} binding -> strict 400 EVEN THOUGH the parameter is
#   dead in the body (bound-and-dead). A silently-ignored 200 would
#   REFUTE the source-sealed pattern annotation — pin whichever is
#   OBSERVED.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?user_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# user_ids — comma list, valid shape per the pattern (docblock example
#   "1,2,3"). Single-digit, never hardcoded; only status pinned. Dead
#   parameter: expected to behave EXACTLY like the unfiltered list.
GET http://{{hostnport}}/api/index.php/productlots?user_ids=1,2,3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — always-true filter on the source-sealed alias pl
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored (if ($sqlfilters) guard),
#   list still served
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE on the lot-specific column batch (the docblock's
#   own example column namespace: pl.label dates from the agenda
#   copy-paste; pl.batch is the real lot column), matching nothing:
#   still a valid collection
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.batch%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family, six-for-six) + LIVE SHAPE CONFIRMED:
#   unknown (but legal-alias) column -> query error -> 503 ("Unknown
#   column ... in 'WHERE'").
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# PLOT6(i) REGRESSION GUARD — sqlfilters with the FAMILY alias t.:
#   OBSERVED 2026-11-27 (live 503 "Unknown column 't.rowid' in
#   'WHERE'"); source-sealed (alias is pl). Future 200 = alias changed.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# SOURCE-SEALED (forgeSQLFromUniversalSearchCriteria errormessage ->
#   RestException(400)): invalid sqlfilters syntax -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.date_creation%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/productlots?sqlfilters=(pl.batch%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — PLOT-a (SOURCE-SEALED: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-sealed
#   2026-11-27; a lot IS itself the "line" entity). OBSERVED
#   2026-11-27: bad syntax -> 200, silently ignored (#43430/#43439
#   family). Kept as a two-probe REGRESSION GUARD: if bad syntax stops
#   being 200, a binding appeared — re-decide against #43388.

# sqlfilterlines — invalid syntax: the DECISIVE probe (OBSERVED 200).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — well-formed pl. filter on a real column: 200 either
#   way (ignored or hypothetically applied)
GET http://{{hostnport}}/api/index.php/productlots?sqlfilterlines=(pl.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- PLOT-b: count mode (SOURCE-SEALED @since 25.0.0)
# Mechanics source-sealed: _getCountOfRecords($sql) is returned AFTER
#   the sqlfilters validation but BEFORE order()/plimit() and the
#   pagination block — validation-wins-over-count; limit/page/
#   pagination_data are IGNORED in count mode.

# Bare count — the whole-list total
GET http://{{hostnport}}/api/index.php/productlots?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count honours a filter (sqlfilters always-true — valid shape;
#   data-dependent, only shape pinned)
GET http://{{hostnport}}/api/index.php/productlots?count=1&sqlfilters=(pl.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count + INVALID sqlfilters -> 400 BEFORE the count early-return
#   (validation-wins-over-count, source-sealed order)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?count=1&sqlfilters=(bad%20syntax)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# count ignores limit/page (both absurd; response must still be a count
#   envelope, NOT an empty list or an error)
GET http://{{hostnport}}/api/index.php/productlots?count=1&limit=1&page=999
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count ignores pagination_data (must remain a count envelope, not a
#   {data,pagination} wrapper)
GET http://{{hostnport}}/api/index.php/productlots?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- properties

# properties restriction — shape-only pin: response is a collection of
#   rows (fields restricted via _filterObjectProperties; no value
#   assertions on names)
GET http://{{hostnport}}/api/index.php/productlots?properties=id&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# properties ignored if empty (docblock wording) — still a valid list
GET http://{{hostnport}}/api/index.php/productlots?properties=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# ---------------------------------------------------------------- pagination_data

# pagination_data=false — plain list envelope
GET http://{{hostnport}}/api/index.php/productlots?pagination_data=false&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# pagination_data=true — wrapped {data,pagination} envelope
GET http://{{hostnport}}/api/index.php/productlots?pagination_data=true&limit=2
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination" exists

# PLOT2: pagination_data=junk — typed bool param; family rule
#   (six-for-six): strict 400. Streak probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots?pagination_data=NOTABOOLEAN&limit=2
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Combined smoke (all documented filters at once)

# Every documented filter parameter at once, all valid
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=ASC&limit=10&page=0&user_ids=1&sqlfilters=(pl.rowid%3A%3E%3A%270%27)&properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# Combined with count — count envelope wins (early-return semantics)
GET http://{{hostnport}}/api/index.php/productlots?sortfield=pl.rowid&sortorder=ASC&limit=10&page=0&user_ids=1&sqlfilters=(pl.rowid%3A%3E%3A%270%27)&count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- GET /productlots/{id}

# Positive: the runtime-captured lot — round-trip on the captured id
#   (type-agnostic: existence only, no id-type compare)
GET http://{{hostnport}}/api/index.php/productlots/{{lot_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# PLOT1 — id=0. OBSERVED 2026-11-27 (live probe + source): 200 with the
#   ALL-NULL SPECIMEN object ("id": 0, "specimen": 1, batch artefact
#   "ABCD123456"). SOURCE-SEALED idiom: get() branches EXPLICITLY on
#   ($id === 0) to initAsSpecimen() — a family-FIRST explicit-specimen
#   path (siblings reach the specimen camp implicitly). Camps are per
#   route; with no subresources on /productlots this is the only camp.
#   Pin history: (1) BY-ANALOGY; (2) source CONFIRMED; (3) instance
#   CONFIRMED. Tally evidence for #43307.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/productlots/0
HTTP 200
[Asserts]
jsonpath "$.id" exists
jsonpath "$.specimen" exists

# Negative: nonexistent id — 404 envelope OBSERVED-BY-EXPLORER
#   2026-11-27 (on a then-nonexistent id): "Not Found: Product lots not
#   found" (source: fetch() falsy -> RestException(404))
GET http://{{hostnport}}/api/index.php/productlots/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter typed int -> 400
GET http://{{hostnport}}/api/index.php/productlots/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — fetch(-1) falsy -> 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/productlots/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- PLOT3 — dead-bound user_ids diff probe (MANUAL, outside run.sh)
# SOURCE-SEALED prediction: EMPTY diff in ALL THREE comparisons (the
#   parameter is never read; drop-by-death, a THIRD mechanism beside
#   the falsy-guard drop and SQL-level enforcement in the C4 tally):
#   export DOLIHOST=localhost:8080
#   for u in 0 "1,2,3" 99999999; do \
#     diff <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots?user_ids=${u}&properties=id&pagination_data=true" \
#       | jq -c '.pagination.total') \
#        <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots?properties=id&pagination_data=true" \
#       | jq -c '.pagination.total'); done
# Empty diffs x3 = dead-bound confirmed end-to-end. A non-empty diff
#     rsultat belongs to PLOT3, below. RESULT-FIELD: PLOT3 productlots
#   result: OBSERVED 2026-11-27 (manual diff probe x3 on commit
#   a6eb6f21): user_ids=0, user_ids=1,2,3 AND user_ids=99999999 all
#   returned EMPTY diffs vs the unfiltered baseline (identical
#   .pagination.total each time) — DEAD-BOUND confirmed end-to-end:
#   drop-by-death, the THIRD mechanism in the C4 tally (declared,
#   pattern-bound, never read). Source reading upheld; no enforcement
#   anywhere. Tally evidence for C4/#43307; comment-worthy, no new
#   issue (the {@pattern} binding still 400s on junk — see PLOT2).

# ---------------------------------------------------------------- PLOT5 — serialization jq probe (MANUAL)
# C5/#43386 + #43568 tally: five STRING, one NUMBER (shipments). One
#   probe decides:
#   curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/productlots?limit=1" \
#     | jq '.[0].id, (.[0].id|type)'
# STRING -> majority restored; NUMBER -> SECOND divergence (issue-
#   comment candidate on #43568, phase 2). All asserts in this suite
#   are written TYPE-AGNOSTIC; the tostring-filter form is the noted
#   alternative if a future revision adds bare id compares. NOTE
#   2026-11-27: POST already observed returning the new id as a bare
#   NUMBER ("1") — write-side @return int, NOT proof about the list.
# RESULT-FIELD: PLOT5 productlots result: OBSERVED 2026-11-27 (manual
#   jq probe on commit a6eb6f21): jq '.[0].id, (.[0].id|type)' returned
#   "1" / "string" — **id is a STRING**. The C5/#43386 majority is
#   RESTORED: six STRING vs one NUMBER (shipments #43568 stays the
#   sole divergence). Issue-comment candidate on #43386 (phase 2).

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to hurl
#   tests via run.sh. C6 coverage for this class is MANUAL, via curl.
#   Gate situation (SOURCE-SEALED, PLOT6(ii)): get() checks
#   hasRight('product', 'lire'); index() checks hasRight('produit',
#   'lire') — INCONSISTENT literals; each GET method consults exactly
#   ONE right (+ _checkAccessToResource on get() after a successful
#   fetch). A zero-rights key therefore expects 403 on every probe.
#   Shell-agnostic probe (POSIX loop, export on its own line, no
#   <placeholders>, works in zsh):
#     export LOWKEY=<zero-rights-key>
#     export DOLIHOST=localhost:8080
#     for p in "?limit=1" "/0" \
#       "?sqlfilterlines=(pl.rowid%3A%3E%3A%270%27)&limit=1"; do \
#       printf "probe %s -> " "${p}"; \
#       curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#         "http://${DOLIHOST}/api/index.php/productlots${p}"; done
#   EXPECTED (family precedent, seven-for-seven identical): 403 clean
#   JSON envelope {"error":{"code":403,...}} on all three — no
#   404-on-/0 leak, no raw SQL text.
#   C6 result: OBSERVED 2026-11-27 (manual probes on commit a6eb6f21):
#   all three probes -> HTTP 403, clean envelopes, NO 404-on-/0 leak,
#   no raw SQL text — and the messages themselves carry the two
#   gate-literals: index() says "Insufficient rights to view your
#   products lots", get() says "Insufficient rights to read an event"
#   (the agenda copy-paste string, live in production). Eighth
#   consecutive clean 403 matrix.
#   PRODUIT-ONLY KEY (PKEY/LOWKEY reuse — the maintainer granted the
#   LOWKEY user "Read products" only and re-ran the pair):
#     "?limit=1"  -> HTTP 200 (the fixture row, specimen:0, ref "1")
#     "/0"        -> HTTP 200 ALL-NULL SPECIMEN (specimen:1)
#   BOTH PASSED — the gate-literal inconsistency is BEHAVIOURALLY
#   NEUTRAL on this instance: hasRight('product','lire') and
#   hasRight('produit','lire') resolve to the same permission entry.
#   PLOT6(ii) revised: literal inconsistency, alias-resolved, cosmetic.
#   THEN the permissions were FLIPPED on the SAME key to "Read
#   services" only, and GET /productlots/1 (the REAL lot, owned by a
#   SERVICE-type product) -> HTTP 403 ("Insufficient rights to read an
#   event"). THE SERVICES BLOCK IS NEVER CONSULTED — even for lots of
#   services. Combined with the products-only 200s: the products
#   reader can read a service's lots, the services reader cannot read
#   its own services' lots. PLOT6(iii) evidence COMPLETE (both
#   facets live, one commit). Note: the 403 message itself is the
#   get() gate's agenda copy-paste string.
#   PRODUIT-ONLY KEY (the PLOT6(ii)-specific probe): a key with
#   produit/lire but NOT product/lire is EXPECTED to pass index() and
#   FAIL get() with 403 — one command pair:
#     export PKEY=<produit-lire-only-key>
#     curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${PKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots?limit=1"
#     curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${PKEY}" \
#       "http://${DOLIHOST}/api/index.php/productlots/0"
#   First -> 200 ([] or rows); second -> 403. That asymmetry IS the
#   inconsistency made visible.
#   RESOLVED 2026-11-27 (PKEY replaced by LOWKEY reuse, commit
#   a6eb6f21): see the PKEY/LOWKEY block in the C6 section above for
#   the full observed outcome (products-only 200/200; services-only
#   403 on the real lot) — the asymmetry predicted here never
#   materialised; the REAL asymmetry found is PLOT6(iii).

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Product lots have PROFESSIONAL ownership: a lot belongs to ONE
#   product (fk_product); the write side enforces it via the unique
#   key uk_product_lot (OBSERVED 2026-11-27: a duplicate
#   (fk_product, batch) POST 500s with the RAW duplicate-key text
#   leaked — phase-2 issue-comment candidate). There is NO socid on
#   lots, so the thirdparty-scoping probe of the shipments suite has
#   NO analogue; the ownership probe is cross-PRODUCT:
#   1. PRODUCT SCOPING on the list: with lots of at least two
#      different products on the instance, filtering by one product's
#      id must never return rows of another:
#        export DOLIHOST=localhost:8080
#        export PID=<a product id that owns the fixture lot>
#        curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#          "http://${DOLIHOST}/api/index.php/productlots?sqlfilters=(pl.fk_product%3A%3D%3A%27${PID}%27)&properties=id,fk_product&limit=100" \
#          | jq '[.[] | select((.fk_product|tostring) != (PID|tostring))] | length'
#      Expected 0. Nonzero = cross-product leak -> BUG issue (phase 2,
#      observed outputs only; NEVER print the confidential product
#      data itself — ids and counts suffice as evidence).
#      CAUTION: verify HOW fk_product serializes (string vs number)
#      before trusting jq comparisons — the shipments episode's
#      select(.socid != 342) string-vs-number lesson applies; hence
#      tostring on BOTH sides above.
#   2. LOT-DETAIL scoping: GET /productlots/{id} of a lot exposes only
#      that lot's own fields — the object is single-product by
#      construction; spot-check that .fk_product equals the fixture's
#      product id and nothing else leaks.
#   RESULT-FIELDS: OBSERVED 2026-11-27 (manual probe on commit
#   a6eb6f21, admin key, PID granted by the maintainer, value NOT
#   printed per the confidentiality note): product-scoping jq guard
#   returned 0 foreign rows — NO cross-product leak. CLEAN. (The
#   fixture row serialized fk_product as a STRING, not a number — the
#   string-vs-number jq lesson applied via tostring on both sides
#   worked as designed.)

# ---------------------------------------------------------------- PLOT6/PLOT7/PLOT8 — episode-status notes
# PLOT7: CLOSED — the explorer paste was fresh, the probe round
#   arbitrated the family analogy (503 on t.* before any source
#   assumption was committed), and the source read sealed the alias
#   and every parameter. No stale-snapshot incident.
# PLOT6 watchlist (OPEN items beyond the three OBSERVED divergences):
#   the duplicate-key 500 error-message leak (raw DB text through the
#   API envelope, OBSERVED during fixture creation), the specimen's
#   plausible-looking artefact fields (batch "ABCD123456", dated tms/
#   eatby/sellby timestamps) leaking into /0 responses. The three
#   OBSERVED divergences: (i) alias pl, (ii) gate-literal cosmetics,
#   (iii) type-blind products-block-only gating — the latter is the
#   strongest phase-2 candidate (permission-model discussion). New
#   BUG/WISH files only in phase 2, on request.
# PLOT8: COPY-PASTE ARTEFACTS sealed in source: agenda/event error
#   strings ("Insufficient rights to read an event", "Error creating
#   event", "owners of event" docblock) and a fully commented-out
#   expensereport-lines block inside post(). Write side is POST /,
#   PUT /{id}, DELETE /{id}; no GET upload route exists. Issue-comment
#   material for #43515/#43514/#43569 kin — phase 2.

# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Productlots is the second member outside the supplier family with a
#   reduced GET surface (list + get only — no subresources, no
#   by-value routes, no contacts), and the FIRST with a divergent
#   table alias (pl) and a first-class explicit-specimen falsy-id
#   branch. The falsy-id tally (#43307), the serialization tally
#   (#43386 + #43568), the sqlfilterlines breadth note (#43430/
#   #43439), the post-merge footprint (#42952) and the dead-code
#   precedents (#43515/#43514/#43569) all gain EVIDENCE as COMMENTS on
#   the existing issues — never duplicates. Only genuinely new failure
#   modes (PLOT6 candidates) get BUG/WISH Markdown files — and those
#   are written FIRST in phase 2, when explicitly requested.
