# test/hurl/api/shipments/10_shipments.hurl
#
# PLACEMENT NOTE: in the real repo tree this file MUST live at
#   api/shipments/10_shipments.hurl
# run.sh's auth-required batch only executes files matching the glob
# */10*.hurl, so it MUST sit one directory deep under api/. In this
# sandbox the api/shipments/ directory could not be created; move the
# file into place before running: mkdir -p api/shipments && mv
# 10_shipments.hurl api/shipments/. Directory is the ENDPOINT name
# "shipments", not the module name "expedition" — the README's
# "Undecided discrepancy" note applies; endpoint wins here, in line
# with every prior suite (orders/commande, receptions/reception).
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the shipments (Shipment / Expedition) API —
#   every GET endpoint of the class api_shipments
#   (source: htdocs/expedition/class/api_shipments.class.php, class
#   Shipments — SOURCE READ COMPLETED 2026-11-27: the full class
#   source was pasted and analysed AFTER the first green run (50/50)
#   and annotated back into this file; all GET routes/params are now
#   sealed against BOTH the live explorer paste AND the class source.
#   Episode order was the RCP1 lineage inverted-and-then-completed:
#   the instance spoke first (live explorer responses), the run
#   confirmed (50/50), the source sealed last). SIF8/SPP8/RCP8/SMF7
#   discipline now FULLY closed for this class.
#
# SPEC OF RECORD (SIF8/SPP8/RCP8/SMF7 lesson, now fivefold): the
#   instance's own swagger.json — re-pull at first run, ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/shipments"'
#
# GET SURFACE (explorer-paste-sealed 2026-11-27, live responses included;
#   a)-g) notes — re-check each against the class source when pasted):
#     GET /shipments              (index: list)
#     GET /shipments/{id}         (get)
#     GET /shipments/{id}/lines   (getLines)
#     a) sqlfilterlines: SEALED IN SOURCE — index() declares exactly
#        sortfield/sortorder/limit/page/thirdparty_ids/sqlfilters/
#        properties/pagination_data/count; NO sqlfilterlines parameter
#        (the SPP2/RCP2 pattern VERBATIM, source-read 2026-11-27).
#        Verdict CONFIRMED: silent-ignore on all five probes (#43430/
#        #43439 family) — the run already returned 200 on all five,
#        decisive probe (bad syntax -> 200) fell as sealed. Comment on
#        #43430 with the source+run evidence.
#     b) count: PRESENT in the docblock (@since 25.0.0 — SOURCE-
#        CONFIRMED) AND in the explorer paste (a family-first double
#        agreement, then triply sealed by the run). index() early-
#        returns _getCountOfRecords($sql) AFTER the sqlfilters
#        validation but BEFORE order()/plimit() — so validation-wins-
#        -over-count holds, and limit/page/pagination_data are all
#        IGNORED in count mode (the early return precedes both blocks).
#     c) status enum: NO status parameter on GET /shipments; documented
#        filter surface = thirdparty_ids + sqlfilters (exactly the
#        receptions/supplierproposals shape).
#     d) by-value routes: NONE (no /ref, no /ref_ext) — no
#        sentinel/"."/"0" by-value probes applicable.
#     e) contacts: NO contacts route in the explorer — joins the
#        C8 no-contacts camp (supplierinvoices + supplierproposals +
#        receptions); supplierorders remains the sole carrier.
#     f) element_element-join: NONE in index() — but a UNIQUE
#        search_sale sub-filter exists: an INTERNAL user WITHOUT
#        hasRight('societe','client','voir') gets an automatic
#        getSalesRepresentativeSqlFilter('t.fk_soc', user->id) added
#        to the WHERE clause (source lines visible). This is the
#        receptions-g)-analogue for the 4-profile matrix: a
#        sales-representative-scoped admin key sees a REDUCED list.
#        Not probed by this suite (admin key typically has
#        societe/client/voir); noted for cross-profile sessions.
#     g) external-user behaviour (SOURCE-SEALED): with an external
#        user, thirdparty_ids is IGNORED and replaced by the user's
#        own socid ('$socids = user->socid ?: $thirdparty_ids') —
#        identical to the receptions pattern (g). Admin-key pins
#        unaffected; the 4-profile API-key matrix owes a probe.
#     Write-side routes (context only, OUT OF SCOPE here): POST /
#     (create), PUT /{id} (update general fields), POST {id}/close,
#     POST {id}/settodraft, POST {id}/validate, POST {id}/lines,
#     PUT {id}/lines/{lineid}, DELETE {id}/lines/{lineid},
#     DELETE /{id}.
#
# LIVE OBSERVATIONS BEARING ON PINS (explorer paste, 2026-11-27,
#   instance commit 08183c4791d781294d300d2d2df4c226b1773917, HEAD ->
#   develop, merge of #43517, Laurent Destailleur 2026-11-27 14:44:38
#   +0100):
#     GET /shipments/0        -> 200 ALL-NULL SPECIMEN object
#                                ("id": null, ..., "specimen": 0,
#                                "billed": 0, "signed_status": 0)
#     GET /shipments/0/lines  -> 200 with []
#   These are the RCP1-fodeklasse observations BEFORE any hurl run:
#   shipments joins the 200-SPECIMEN camp on get() (supplierorders,
#   supplierinvoices, receptions — DIVERGING from supplierproposals'
#   404-envelope and stockmovements' 400-explicit-guard "No stock
#   movement with id 0 can exist") and the correct-empty-set
#   subresource camp on getLines(). The pins below carry these as
#   OBSERVED-with-caveat — the hurl run must reproduce them (a
#   mismatch between explorer and plain API routing would itself be an
#   SHF6 finding).
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Expedition (shipments) module enabled with
#   'expedition lire' rights for the key (verify permission gate(s)
#   against the source when pasted — DOUBLE-GATE CHECK owed: do NOT
#   assume the single-gate shape; stockmovements taught stock/lire AND
#   stock/mouvement/lire in one method).
# FIXTURE REQUIREMENT (receptions/stockmovements lesson): the LIST
#   section captures "$[0]" and therefore REQUIRES at least one shipment
#   on the instance. Gauge BEFORE the first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/shipments?limit=100&count=1"
#   An empty instance = "query didn't return any result" on the first
#   capture request = MISSING FIXTURE, not a pin failure. Fixture
#   recipe: create+validate a customer order in the GUI, then use the
#   "Create shipment/Expedition" button (module Expedition), or create
#   via the write-API OUTSIDE this hurl file.
# Pin-history convention (analogi -> kilde -> instans, with dates) is
#   recorded per-pin below. Instans-commit HISTORY of this episode:
#   819a0925 -> 2904a1b3 -> 8f38e8fe -> d762ef09 -> 08183c47
#   (pins OBSERVED here: explorer paste + green 50/50 run + source
#   read) -> 817f40c3261cbf0f26b0aea31dcbfe5aa8cc7c33 (merge of #43510
#   translation-key fix, 2026-11-27 15:09:10 +0100; re-run green 50/50)
#   -> **e0e62e83318dd19242adf969794874bd56ce5789** (current HEAD ->
#   develop, merge of #43147 "FIX: French amount in words writes the
#   currency as given (un Euros, dix EUR)", Laurent Destailleur
#   2026-11-27 18:59:38 +0100; THIRD green run on this commit: 50/50,
#   3210 ms). Three consecutive green runs across three commits — all
#   pins reproduced unchanged each time; every OBSERVED annotation
#   below carries all three commits (08183c47 = original observation,
#   817f40c3 + e0e62e83 = re-run confirmations). If the pins are
#   re-run on a later commit, re-annotate here first.
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
#       "http://${DOLIHOST}/api/index.php/shipments?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/shipments?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   Family precedent (supplierorders, supplierinvoices, supplier-
#   proposals, receptions, stockmovements — five-for-five): id is a
#   STRING (C5/#43386). If id here is a number, switch bare-compare
#   asserts to the toString filter form:
#     jsonpath "$.id" toString == "{{shipment_id}}"
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE (SOURCE-SEALED 2026-11-27): SINGLE gate.
#   All three GET methods (get, index, getLines) gate
#   hasRight('expedition', 'lire') FIRST, then fetch, then
#   DolibarrApi::_checkAccessToResource() — so a low-privilege key
#   sees 403 BEFORE the falsy-id/404 path and BEFORE any SQL build.
#   DOUBLE-GATE CHECK CLOSED: NO second right is consulted on any
#   GET route (unlike stockmovements' stock/lire + stock/mouvement/
#   lire pair). C6 expectation: 403 clean envelope on every probe.
#   LOW-PRIVILEGE NEGATIVE CONTROL (C6): NOT AUTOMATED — the zero-rights
#   (LOWKEY) key is not available to hurl tests via run.sh. C6 is
#   MANUAL curl ONLY. Never embed {{lowkey}} in this file. See the C6
#   section at the bottom.
#
# FINDINGS CATALOGUED (SHF = Shipments Finding; catalogue prefix
#   pre-allocated, resolution status per finding below):
#   SHF1 (OBSERVED-BY-EXPLORER 2026-11-27, commit 08183c47): id=0-camp,
#     PER ROUTE, three possible outcomes camp-wide:
#       GET /shipments/0       -> 200 all-null SPECIMEN (explorer live)
#       GET /shipments/0/lines -> 200 []                        (live)
#     Matches supplierorders/supplierinvoices/receptions; diverges from
#     supplierproposals (404-envelope) and stockmovements (400 explicit
#     guard, @since 23.0.0). Pin history: (1) BY-ANALOGY would have said
#     200-specimen (four-of-six majority); (2) no source read yet —
#     the source could argue 404 (receptions lesson); (3) the INSTANCE
#     spoke first here via the live explorer: 200-specimen + 200 [].
#   Pin history: (1) BY-ANALOGY would have said
#     200-specimen (four-of-six majority); (2) no source read yet —
#     the source could argue 404 (receptions lesson); (3) the INSTANCE
#     spoke first via the live explorer: 200-specimen + 200 [].
#     CONFIRMED OBSERVED three times (green run 50/50 on commits
#     08183c47, 817f40c3 and e0e62e83, all 2026-11-27):
#     api/shipments/10_shipments.hurl, commit 08183c47): both pins held.
#   SHF2 (BY-ANALOGY, five-for-five): strict 400 on BOUND typed params
#     (limit, page, pagination_data) vs SILENT passthrough on UNBOUND
#     (sqlfilterlines, unknown params). C4b streak expected to hold
#     outside the supplier family.
#   SHF3 (SOURCE-SEALED 2026-11-27 — the C4 DIVERGENCE): falsy-param
#     drop on thirdparty_ids=0. index() builds '$socids = user->socid
#         ?: $thirdparty_ids' then guards 'if ($socids) { AND t.fk_soc
#     IN (sanitize($socids)) }'. The string "0" IS truthy in PHP, so
#     the IN(0) clause IS built — the outcome is decided by the SQL:
#     fk_soc IN (0) matches no real thirdparty -> EMPTY list =
#     CORRECT enforcement, DIVERGING from the supplier-family drop
#     (SOF5/SIF4/SPP4 — those guard at if($param)-level and DROP).
#     Receptions (RCP4) was flagged as the possible first divergence;
#     shipments' source shows the SAME truthy-IN-build shape as
#     receptions — the manual diff probe below decides both. Diff
#     .pagination.total against the unfiltered baseline. Either
#     result is a C4 tally data point; source predicts ENFORCED
#     (empty), not dropped.
#   SHF4 (SOURCE-SEALED 2026-11-27): limit=0 + pagination_data=true
#     -> 200 data envelope (guard '($limit > 0 ? ceil(...) : 1)' IS
#     VISIBLE in index(); #42952 pattern, seventh confirmation, incl.
#     the green run). LIMIT IDIOM: this class uses the
#     'if ($limit) { plimit($limit + 1, $offset) }' variant — the
#     stockmovements if($limit)-SLÆGT, NOT the family plimit-always
#     shape: limit<=0 (incl. limit=-1) SKIPS plimit entirely -> the
#     UNBOUNDED list is served (min($num,$num)=num rows). The
#     limit=-1 pin (200 isCollection) below is correct EITHER WAY and
#     remains valid; the empty-vs-full distinction is data-dependent
#     and NOT asserted. Documented variant: if($limit) — DIVERGES
#     from receptions/supplier-family.
#   SHF5 (BY-ANALOGY, five-for-five; run-consistent): id-as-STRING
#     serialization (C5/#43386). jq probe still owed; the working
#     capture chain strongly suggests STRING. NUMBER = first-ever
#     divergence = SHF6 flip.
#   SHF6 (OBSERVED 2026-11-27, source read — DEAD CODE, issue-comment
#     candidates #43515/#43514): the class carries TWO large
#     commented-out method blocks: setinvoiced() (POST {id}/setinvoiced
#     — classify billed) and createShipmentFromOrder() (POST
#     /createfromorder/{orderid}) — both fully /* */-commented with
#     intact docblocks, the classic specimen/dead-code artefact
#     pattern. NO live divergence was found in any GET behaviour: all
#     pins held on the green run. Comment drafts belong in fase 2.
#     Also noted: validate()/close() share the family 403-empty-body
#     quirk documented in the docblock (POST-only, out of scope).
#   SHF7 (CLOSED 2026-11-27): spec-of-record lesson FULLY upheld —
#     fresh explorer paste treated as hypothesis (instance spoke
#     first), instance swagger re-pull owed at first run, class source
#     read COMPLETED and sealed a)-g) back into this header; paste,
#     instance and source agreed on every route and parameter. No
#     stale-snapshot incident this episode; the five-times-confirmed
#     discipline (SIF8/SPP8/RCP8/SMF7/SHF7) is intact.
#   SHF8 (RESERVED): upload endpoint is POST-only per the full explorer
#     (no GET upload route on /shipments) — nothing GET-related to pin;
#     reserved for whatever the source read surfaces (e.g. documents/
#     attachments subroutes not shown in the explorer).
#     RESOLVED 2026-11-27 (source read): the class has NO upload method
#     at all; the explorer write-side list IS the complete surface.
#     Nothing GET-related; case closed — no upload pin needed.

# ---------------------------------------------------------------- GET /shipments (index)

# Positive: list, ascending sort on the conventional default sortfield
#   (family default: t.rowid / ASC), capture first row id for later
#   get()/lines lookups. REQUIRES the fixture above (>=1 shipment).
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
shipment_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on another conventional column of the shipment table (ref)
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (supplierorders SOF / supplierinvoices SIF /
#   supplierproposals SPP / receptions RCP / stockmovements SMF —
#   six-for-six), the sortfield flows into order() and the query error
#   path throws RestException(503): unknown sortfield -> 503. Verify at
#   first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — source coerule ($page < 0 -> $page = 0), no 500
GET http://{{hostnport}}/api/index.php/shipments?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED (family): negative limit -> 200. SHF4 SOURCE-SEALED: this
#   class uses the if ($limit) { plimit($limit + 1, $offset) } idiom
#   — the stockmovements SLÆGT, NOT the family plimit-always shape:
#   limit<=0 (incl. limit=-1) SKIPS plimit entirely -> the UNBOUNDED
#   list is served (min($num,$num)=num rows). The 200 pin below holds
#   EITHER WAY (fixture is small); the empty-vs-full distinction is
#   data-dependent and NOT asserted. THE FAMILIES HAVE NOW BOTH BEEN
#   SEEN with distinct idioms — documented in SHF4; re-pin only if the
#   source idiom ever changes.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family, five-for-five): non-numeric limit (typed
#   int) -> 400. SHF2 bound-param streak probe.
GET http://{{hostnport}}/api/index.php/shipments?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (family, five-for-five): non-numeric page (typed
#   int) -> 400. SHF2 streak probe.
GET http://{{hostnport}}/api/index.php/shipments?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — the #42952 pattern (guard
#   ($limit > 0 ? ceil(...) : 1)), confirmed BOTH in the supplier family
#   AND in stockmovements; a 500 here would be a NEW independent finding
#   (SHF6). Sixth confirmation probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# thirdparty_ids — explorer-documented ("example '1' or '1,2,3'");
#   "0" is syntactically consistent (never hardcoded to real ids).
#   SHF3 candidate (falsy-param family, SOF5/SIF4/SPP4/RCP4) —
#   SOURCE-SEALED 2026-11-27: '$socids = user->socid ?: $thirdparty_
#   ids' + 'if ($socids) { AND t.fk_soc IN (sanitize($socids)) }'.
#   The string "0" IS truthy in PHP, so the IN(0) clause IS built —
#   outcome decided by the SQL: fk_soc IN (0) matches nothing ->
#   source predicts EMPTY (correct enforcement), DIVERGING from the
#   supplier-family drop. The manual diff probe below decides. Count
#   NOT pinned (data-dependent); status pinned (and green).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?thirdparty_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unfiltered baseline with pagination envelope for the SHF3 diff probe
#   (manual: compare .pagination.total of the two requests)
GET http://{{hostnport}}/api/index.php/shipments?properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# thirdparty_ids — junk value. If Restler-enforced {@pattern
#   /^[0-9,]*$/i} binds (family precedent, five-for-five 400):
#   expect 400. SHF2 streak probe; a silently-ignored 200 would BREAK
#   the pattern — pin whichever is OBSERVED.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?thirdparty_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# thirdparty_ids — comma list, valid shape (multi-example from the
#   explorer doc). Single-digit, never hardcoded; only status pinned.
GET http://{{hostnport}}/api/index.php/shipments?thirdparty_ids=1,2,3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored (if ($sqlfilters) guard),
#   list still served
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid collection
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family, six-for-six), unknown column -> query
#   error -> RestException(503): unknown sqlfilters column -> 503.
#   Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (family, six-for-six), forgeSQLFromUniversal-
#   SearchCriteria errormessage -> RestException(400): invalid sqlfilters
#   syntax -> 400. Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.date_creation%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/shipments?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — SHF-a (SEALED IN SOURCE: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-read 2026-11-27
#   — the SPP2/RCP2 pattern VERBATIM). Restler passes undeclared query
#   parameters through unbound and unvalidated, and the green run
#   already returned 200 on all five probes INCLUDING bad syntax —
#   the decisive probe fell as sealed: silently ignored = #43430/
#   #43439 family, COMMENT-worthy on #43430 (source+run evidence).
#   The matrix stays as REGRESSION GUARDS: if any probe stops being
#   200, either a binding appeared (re-decide against #43388) or
#   validation tightened.
# Line column: shipments HAS a line table (expeditiondet; write side
#   postLine/putLine/deleteLine; batch rows in ExpeditionLineBatch) —
#   but index() has no line-table JOIN, so even a hypothetically bound
#   t.qty-style filter would need the alias the class doesn't declare.

# sqlfilterlines with t.-aliased LINE column — binding here would 503
#   (#43388 trap); silently ignored gives 200.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines with a BARE line column — the WORKING filter if bound;
#   unfiltered 200 if ignored.
GET http://{{hostnport}}/api/index.php/shipments?sqlfilterlines=(qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines via t.-alias on a real HEADER column
GET http://{{hostnport}}/api/index.php/shipments?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — unknown column: 503 if bound, 200 if ignored
GET http://{{hostnport}}/api/index.php/shipments?sqlfilterlines=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — invalid syntax: the DECISIVE probe. 400 = bound
#   (#43388 family, contradicts the paste-sealed absence); 200 =
#   silently ignored (#43430/#43439 family).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- PARAM count (explorer-documented; @since tag owed to source)
# Explorer documentation: "Set to 1 to return only the number of records
#   matching the filters, as an array with the key 'count'". Mechanics
#   hypothesised by family analogy (SIF8/SPP8/RCP8): sqlfilters
#   validation (400) BEFORE the count early-return, and the early return
#   precedes order()/plimit() and the pagination block — so limit/page/
#   pagination_data are IGNORED in count mode, and thirdparty_ids
#   pattern validation (if bound) still fires first. Pins BY-ANALOGY;
#   re-annotate OBSERVED at first run.

# Bare count — the whole-list total
GET http://{{hostnport}}/api/index.php/shipments?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count honours a filter (thirdparty_ids=1 — valid shape; data-
#   dependent, only shape pinned)
GET http://{{hostnport}}/api/index.php/shipments?count=1&thirdparty_ids=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count + INVALID sqlfilters -> 400 BEFORE the count early-return
#   (validation-wins-over-count)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?count=1&sqlfilters=(bad%20syntax)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# count ignores limit/page (both set to absurd values; response must
#   still be a count envelope, NOT an empty list or an error)
GET http://{{hostnport}}/api/index.php/shipments?count=1&limit=1&page=999
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count ignores pagination_data (must remain a count envelope, not a
#   {data,pagination} wrapper)
GET http://{{hostnport}}/api/index.php/shipments?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- properties

# properties restriction — shape-only pin: response is a collection of
#   rows (fields restricted; no value assertions on names)
GET http://{{hostnport}}/api/index.php/shipments?properties=id&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# properties ignored if empty (docblock wording) — still a valid list
GET http://{{hostnport}}/api/index.php/shipments?properties=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# ---------------------------------------------------------------- pagination_data

# pagination_data=false — plain list envelope
GET http://{{hostnport}}/api/index.php/shipments?pagination_data=false&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# pagination_data=true — wrapped {data,pagination} envelope
GET http://{{hostnport}}/api/index.php/shipments?pagination_data=true&limit=2
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination" exists

# pagination_data=junk — typed bool param; family rule (five-for-five):
#   strict 400. SHF2 streak probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments?pagination_data=NOTABOOLEAN&limit=2
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- KOMBINERET ALT-I-ÉT (combined smoke)

# Every documented filter parameter at once, all valid
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=ASC&limit=10&page=0&thirdparty_ids=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# Combined with count — count envelope wins (early-return semantics)
GET http://{{hostnport}}/api/index.php/shipments?sortfield=t.rowid&sortorder=ASC&limit=10&page=0&thirdparty_ids=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- GET /shipments/{id}

# Positive: the runtime-captured shipment — round-trip on the captured id
GET http://{{hostnport}}/api/index.php/shipments/{{shipment_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# SHF1 — id=0. OBSERVED-BY-EXPLORER 2026-11-27 (commit 08183c47, live
#   response pasted in this episode): 200 with the ALL-NULL SPECIMEN
#   object ("id": null, ..., "specimen": 0, "billed": 0,
#   "signed_status": 0) — shipments joins the 200-SPECIMEN camp with
#   supplierorders, supplierinvoices and receptions, DIVERGING from
#   supplierproposals (404-envelope) and stockmovements (400 explicit
#   guard "No stock movement with id 0 can exist", @since 23.0.0).
#   PIN HISTORY (analogi -> kilde -> instans): (1) 2026-11-27
#   BY-ANALOGY 200-specimen (four-of-six sibling majority); (2) source
#   read NOT yet performed — a get() RestException(404) on falsy fetch
#   is possible (the receptions class carried it yet never fired);
#   (3) 2026-11-27 the INSTANCE spoke first via the live explorer:
#   200-specimen. Camps are per-route and only pinned OBSERVED —
#   Pin history: (1) 2026-11-27
#   BY-ANALOGY 200-specimen (four-of-six sibling majority); (2) source
#   read NOT yet performed — a get() RestException(404) on falsy fetch
#   is possible (the receptions class carried it yet never fired);
#   (3) 2026-11-27 the INSTANCE spoke first via the live explorer:
#   200-specimen. CONFIRMED OBSERVED three times (green run 50/50 on
#   commits 08183c47, 817f40c3 and e0e62e83, all 2026-11-27).
#   Camps are per-route and pinned OBSERVED.
#   Tally evidence for #43307 (route: get()); no new issue.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/shipments/0
HTTP 200
[Asserts]
jsonpath "$.specimen" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/shipments/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter typed long -> 400
GET http://{{hostnport}}/api/index.php/shipments/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/shipments/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /shipments/{id}/lines
# Hypothesis (paste + family): gates the shipment right first, fetch ->
#   404 on falsy-or-missing, lines MAY legitimately be empty (shape
#   pinned only).

# Positive: lines of the captured shipment — may be empty, must be a
#   collection
GET http://{{hostnport}}/api/index.php/shipments/{{shipment_id}}/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent shipment id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/shipments/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric shipment id on the subresource -> 400
GET http://{{hostnport}}/api/index.php/shipments/abc/lines
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — SHF1 second camp (camps are PER ROUTE).
#   OBSERVED-BY-EXPLORER 2026-11-27 (commit 08183c47, live response):
#   HTTP 200 with an EMPTY ARRAY [] — the correct-empty-set subresource
#   camp, mirroring receptions (/0 -> 200-specimen, /0/lines -> 200 []),
#   supplierinvoices and supplierorders (/0/contacts -> 200 []). Same
#   defence as RCP1: a fetch(0) specimen has no lines, so [] is the
#   truthful answer; 404 is reserved for NONEXISTENT non-falsy ids.
#   Pin history mirrors SHF1 main camp; CONFIRMED OBSERVED three
#   times (green runs 50/50 on 08183c47, 817f40c3 and e0e62e83, all
#   2026-11-27).
#   (first green hurl run, 50/50, commit 08183c47).
GET http://{{hostnport}}/api/index.php/shipments/0/lines
HTTP 200
[Asserts]
jsonpath "$" exists
jsonpath "$" count == 0

# ---------------------------------------------------------------- SHF3 — falsy-param diff probe (MANUAL, outside run.sh)
# Family tally C4 five-for-five so far. Manual diff:
#   diff <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/shipments?thirdparty_ids=0&properties=id&pagination_data=true" \
#     | jq -c '.pagination.total') \
#        <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/shipments?properties=id&pagination_data=true" \
#     | jq -c '.pagination.total')
# Empty diff = filter DROPPED for falsy 0 (family pattern holds);
#   different totals = correct SQL-level enforcement. Either result is
#   a C4 data point. RESULT-FIELD: SHF3 shipments result: OBSERVED
#   2026-11-27 (manual diff probe on commit 817f40c3): EMPTY DIFF —
#   thirdparty_ids=0 returns the SAME pagination.total as the
#   unfiltered baseline -> the filter is DROPPED for the falsy "0";
#   the C4 family pattern HOLDS (now six-for-six with shipments).
#   SOURCE READING CORRECTED in the same motion: my earlier
#   PHP-truthiness note ('"0" is truthy so IN(0) is built') was WRONG
#   — the string "0" IS the famous falsy-string case in PHP, so
#   `if ($socids)` correctly rejects it and the IN-clause is never
#   built; the source thus PREDICTS the drop once read properly.
#   Pin history: (1) BY-ANALOGY drop; (2) source mis-read said
#   enforcement; (3) OBSERVED drop + corrected source reading —
#   observation beats my analysis, and the corrected analysis agrees
#   with the observation. Tally evidence for C4; no new issue.

# ---------------------------------------------------------------- SHF5 — serialization jq probe (MANUAL)
# C5/#43386 tally: five-for-five id-as-STRING so far. One probe:
#   curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/shipments?limit=1" \
#     | jq '.[0].id, (.[0].id|type)'
# STRING -> bare-compare asserts above are correct as written; NUMBER
#   -> flip the round-trip assert to the toString form and log the
#   FIRST-EVER divergence as SHF6 (new finding, fase 2).
# RESULT-FIELD: SHF5 shipments result: OBSERVED 2026-11-27 (manual
#   probe on commit 817f40c3): jq '.[0].id, (.[0].id|type)' returned
#   2 / "number" — **id is a NUMBER**, the FIRST-EVER divergence from
#   the C5/#43386 string convention (five-for-five STRING across
#   supplierorders, supplierinvoices, supplierproposals, receptions,
#   stockmovements). Logged as SHF6-issue candidate (fase 2: BUG/WISH
#   Markdown with ${DOLIHOST} repro + cross-ref #43386). The bare
#   compares in this suite remain valid as written (no id-type
#   compare is asserted; captures are type-agnostic), but any future
#   suite that compares ids against this endpoint must expect a
#   number, not a string.

# ---------------------------------------------------------------- SHF4 — limit idiom note (MANUAL, source-dependent)
# Before trusting the limit=-1 pin on later commits, read the class'
#   limit idiom: '($limit > 0 ? ceil(...) : 1)' + plimit($limit+1) (the
#   family shape -> LIMIT 0 -> empty list) vs the stockmovements
#   if($limit) shape (unbounded list). Record which variant this class
#   uses here: PENDING source read.

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to hurl
#   tests via run.sh. C6 coverage for this class is MANUAL, via curl.
#   Gate hypothesis: hasRight('expedition', ...) is checked FIRST in
#   all three GET methods (single gate by analogy — DOUBLE-GATE CHECK
#   owed at source read; do not assume), so every probe answers 403
#   BEFORE the fetch/404 and SQL paths. Shell-agnostic probe (POSIX
#   loop, export on its own line, no <placeholders>, works in zsh):
#     export LOWKEY=<zero-rights-key>
#     export DOLIHOST=localhost:8080
#     for p in "?limit=1" "/0" "/0/lines" \
#       "?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=1"; do \
#       printf "probe %s -> " "${p}"; \
#       curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#         "http://${DOLIHOST}/api/index.php/shipments${p}"; done
#   EXPECTED (family precedent, six-for-six identical): 403 clean JSON
#   envelope {"error":{"code":403,"message":"Forbidden"}} on all four —
#   no 404-on-/0 leak, no raw SQL text.
#   RESULT-FIELD: C6 shipments result: OBSERVED 2026-11-27 (manual
#   probe on commit 817f40c3): all four probes -> HTTP 403 with the
#   clean envelope {"error":{"code":403,"message":"Forbidden"}} —
#   "?limit=1", "/0", "/0/lines" AND "?sqlfilterlines=(t.qty...)":
#   NO 404-on-/0 leak, no raw SQL text, gate fires first exactly as
#   source-sealed (single gate expedition/lire). Seventh consecutive
#   clean 403 matrix (C6 streak continues).
#   run, both if single-gated and if double-gated — record WHICH).

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Shipments have PROFESSIONAL ownership: a shipment belongs to ONE
#   thirdparty (socid) and ONE source object (customer order / origin).
#   Two manual GET-only probe plans (fixtures OUTSIDE hurl):
#   1. LINE LEAK PAIRED (write side has /{id}/lines):
#     1. Create/choose two shipments A and B where B has lines and A
#        does not (or note their differing line sets) — GUI fixtures,
#        or write-API OUTSIDE this file.
#      b) GET /shipments/{A}/lines and /{B}/lines with ${DOLIHOST}+
#         ${DOLAPIKEY}; diff the returned line ids.
#      c) If A's list contains B-only line ids, file a BUG issue
#         (cross-object line leak) with observed outputs only.
#      A line belonging to B must never appear in GET
#      /shipments/{A}/lines.
#   2. THIRDPARTY SCOPING on the list:
#      GET /shipments?thirdparty_ids=<socid_of_B> must never return
#      rows whose socid differs; spot-check .[0].socid against the
#      filter. Cross-check thirdparty_ids=0 diff (SHF3) in the same
#      session. RESULT-FIELDS: OBSERVED 2026-11-27 (manual probes on
#      commit 817f40c3, admin key):
#      1. LINE LEAK PAIRED (A = shipment 4, zero lines; B = shipment
#         8, two lines; distinct socids 135 vs 342):
#         comm -12 of GET /shipments/4/lines and /shipments/8/lines
#         line ids -> EMPTY. NO cross-object line leak. CLEAN.
#      2. THIRDPARTY SCOPING: GET
#         /shipments?thirdparty_ids=342&properties=id,socid returned
#         ONLY ids 7 and 8, both socid "342" — no row of any other
#         socid came through the filter. CLEAN. (Methodological note:
#         the jq guard 'select(.socid != 342)' compares string vs
#         number and is therefore always true — so the probe output
#         listed EVERYTHING the filtered call returned, which proves
#         the absence of foreign rows; socid serializes as STRING
#         while id serializes as NUMBER — another facet of the SHF5/
#         SHF6 mixed-serialization finding for the fase-2 issue
#         draft.)

# ---------------------------------------------------------------- SHF6 / SHF7 / SHF8 — episode-status notes
# SHF7: the explorer paste was FRESH and bore live responses (the RCP1
#   lineage: instance speaks first); instance swagger re-pull at first
#   run + class source read (htdocs/expedition/class/
#   api_shipments.class.php, EXPECTED path — verify with find when in
#   the repo) close the spec-of-record loop.
# SHF6 watchlist: dead PUT/DELETE code artefacts (#43515/#43514
#   candidates — the explorer shows PUT /{id} and DELETE /{id};
#   whether the code behind them is live is a fase-2/source question),
#   specimen artefacts leaking into get() responses, and any
#   customer-order element_element join analogue (none advertised).
# SHF8: no GET upload route exists on /shipments (POST-only upload in
#   the write-side family) — nothing to pin; reserved.

# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Shipments is the FIRST member OUTSIDE the supplier family to confirm
#   the family patterns via live explorer evidence (the stockmovements
#   episode already confirmed them once outside; shipments is the
#   expedition-family carrier and the RCP1 fodeklasse: the instance's
#   live responses arrived before any analogue argument). All
#   family-wide confirmations become COMMENTS on existing issues
#   (#43307, #43385, #43386, #43388, #43423, #43430, #43439, #42952;
#   #43515/#43514 if dead-code artefacts surface) — never duplicates.
#   Only genuinely new failure modes (SHF6 candidates) get BUG/WISH
#   Markdown files — and those are written FIRST in fase 2, when
#   explicitly requested.
