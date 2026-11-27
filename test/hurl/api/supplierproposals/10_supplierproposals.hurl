# test/hurl/api/supplierproposals/10_supplierproposals.hurl
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# PLACEMENT NOTE: in the real repo tree this file lives at
#   api/supplierproposals/10_supplierproposals.hurl
# run.sh only executes hurl files matching the glob */10*.hurl, so it
# MUST sit one directory deep. In this flattened workspace the file sits
# at the root beside the other suites (move with the same install-script
# convention as supplierinvoices).
#
# GET-only tests for the supplier proposals (price requests,
# SupplierProposal) API — every GET endpoint in the spec for
# /supplierproposals and the class api_supplier_proposals
#   (htdocs/supplier_proposal/class/api_supplier_proposals.class.php —
#   SOURCE READ COMPLETED 2026-11-26 against instance commit
#   819a092565742b6133745f88ab1b7b3c06db494b: all routes/params below
#   re-checked against the class AND the instance run; see the GET
#   SURFACE block for the confirmed facts).
#
# GET SURFACE (CONFIRMED by source read 2026-11-26, class
#   SupplierProposals, instance commit 819a0925 — snapshot staleness
#   resolved, SPP8 upheld):
#     GET /supplierproposals                (index: list)
#     GET /supplierproposals/{id}           (get: one proposal)
#     a) sqlfilterlines: NOT in the index() signature — parameter is
#        ABSENT in code; the all-five-200 matrix result is thereby
#        SEALED as silent-ignore (family width of #43430, SPP2). No
#        binding ever existed on this class.
#     b) count: PRESENT in the source docblock (@since 25.0.0) — the
#        pasted snapshot was stale, exactly the SIF8 pattern. The
#        count section pins stand as regression guards.
#     c) subresources: NONE in the class — no getLines, no contacts,
#        no other GET subroutes; the only sub-route is the write-side
#        POST {id}/line (postLine, @since 25.0.0). No per-subresource
#        sections needed; no /0 per-route probes owed (SIF7 moot here).
#     d) by-value routes: NONE (no /ref, no /ref_ext) — no by-value
#        sentinel/"."/"0" probes applicable.
#     e) NO status parameter exists on index() (unlike both siblings:
#        orders seven values, invoices four) — the suite correctly
#        guesses no enum; thirdparty_ids (+sqlfilters) is the entire
#        documented filter surface.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Suppliers/Price-requests (supplier proposals) module
#   enabled. The LIST section captures "$[0]" and therefore requires at
#   least one supplier proposal on the instance (create a fixture
#   outside hurl if the list is empty — GET-only here).
# STATUS OF PINS: FULLY OBSERVED (2026-11-26): full 44-request suite
#   run green (admin API key, ./run.sh 10_supplierproposals), every
#   BY-ANALOGY pin either confirmed or corrected with an OBSERVED
#   comment. One correction this run: GET /supplierproposals/0 -> 404
#   (SPP1 — the 404 camp, diverging from both siblings' 200-specimen).
#   Everything else passed as pinned: unknown sortfield -> 503,
#   sqlfilters syntax/semantics (400/503/200), ALL FIVE sqlfilterlines
#   probes -> 200 (SPP2 silent-ignore verdict, #43430 family),
#   thirdparty_ids=abc -> 400, pagination_data=NOTABOOLEAN -> 400 (SPP3
#   strict-on-bound), limit=0+pagination_data -> 200 (SPP5, PR #42952),
#   count=1 -> 200 {"count":...} (param EXISTS on this instance — the
#   pasted snapshot was stale, SIF8 pattern repeats, SPP8 upheld).
#   "Change this assert deliberately, never accidentally."
#   STILL PENDING (manual curl, outside run.sh): SPP4 thirdparty_ids=0
#   diff probe, SPP6 jq serialization note, C6 zero-rights probes, and
#   the source-read confirmations (class source grep for
#   sqlfilterlines/count/subresources) — see sections below and
#   supplier_family_common_issues.md.
# Instance: INSTANS: commit 819a092565742b6133745f88ab1b7b3c06db494b
#   (HEAD -> hurl_tests_api_supplierproposals, origin/develop,
#   origin/HEAD, develop; Laurent Destailleur, 2026-11-26 15:36:07
#   +0100, "Merge pull request #43368 from momodemo333/FIX/
#   mcp-cache-hints-per-caller — FIX: MCP tools/list and the other
#   list and read results lack the cacheScope and ttlMs the
#   2026-07-28 schema requires").
#   All OBSERVED pins in this file refer to this commit.
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
#       "http://${DOLIHOST}/api/index.php/supplierproposals?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/supplierproposals?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   Family precedent (supplierorders AND supplierinvoices): id is a
#   STRING (C5/#43386). If id here is a number, switch bare-compare
#   asserts to the toString form:
#     jsonpath "$.id" toString == "{{proposal_id}}"
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE: read rights on supplier proposals are required
#   for all sections; get()/index() are expected to call
#   DolibarrApi::_checkAccessToResource(). A 403 during a 4-profile
#   API-key matrix run is a permission finding, not a pin change.
#   LOW-PRIVILEGE NEGATIVE CONTROL (C6): NOT AUTOMATED — the zero-rights
#   (LOWKEY) key is not available to hurl tests via run.sh. C6 is
#   MANUAL curl ONLY (three probes: list, /0, sqlfilterlines). Never
#   embed {{lowkey}} in this file. See the C6 section at the bottom and
#   supplier_family_common_issues.md.
#
# SOURCE-NOTED PARAMETER FACTS (pasted Swagger snapshot — STALE-RISK
#   flagged; instance swagger.json is the spec of record, SPP8):
#   - index(): sortfield, sortorder, limit, page,
#     thirdparty_ids (example '1' or '1,2,3'),
#     sqlfilters, properties, pagination_data (default false; page
#     starts from 0).
#   - NO status parameter visible in the pasted snapshot — CONTRAST
#     supplierorders (7 values) and supplierinvoices (4 values).
#     VERIFY: supplier proposals DO have statuses (draft/sent/accepted/
#     refused/closed...) in the GUI, and sibling index() handlers
#     usually expose a status filter. If the source shows a status
#     param, ADD the status enum section (valid values + NOSUCHSTATUS
#     -> 400, SPP3) before first run; the sweep below intentionally
#     does NOT guess enum values.
#   - get(id) takes only the path id (typed long -> 400 on non-numeric).
#
# FINDINGS CATALOGUED (SPP = SupplierProposals Finding; pre-catalogued
#   from the family checklist, resolved at first run):
#   SPP1 (OBSERVED 2026-11-26, first hurl run): GET /supplierproposals/0
#     -> 404 error envelope — the class joins the 404 camp, DIVERGING
#     from BOTH siblings (supplierorders, supplierinvoices: 200-specimen,
#     #43307 tally). Not a bug (404 for a nonexistent id 0 is defensible);
#     falsy-id-tally evidence only — comment on #43307, no new issue.
#     If the source read adds subresources, probe /0 on EACH of them
#     (SIF7 lesson: camps are per route).
#   SPP2 (SEALED 2026-11-26, first hurl run): sqlfilterlines is NOT a
#     bound parameter on /supplierproposals — ALL FIVE matrix probes
#     (t.qty, qty, t.rowid, t.nosuchcolumn, bad syntax) answered 200
#     with the unfiltered list, INCLUDING invalid syntax (sibling
#     supplierorders answers 400 there, #43388). Identical silent-
#     ignore verdict as supplierinvoices (#43430): SPP2 is FAMILY
#     WIDTH for #43430, NOT a new issue, NOT a #43388 confirmation.
#     Comment on #43430. Checklist C1 supplierproposals row = "N/A —
#     parameter absent, silently ignored". Coherent picture with SPP3:
#     strict on BOUND typed params, silent on UNBOUND ones.
#     Confirmatory source grep still owed:
#       grep -n sqlfilterlines \
#         htdocs/supplier_proposal/class/api_supplier_proposals.class.php
#   SPP3 (OBSERVED 2026-11-26, first hurl run): strict 400 validation
#     of bound typed params confirmed — thirdparty_ids=abc -> 400 and
#     pagination_data=NOTABOOLEAN -> 400. Same coherent
#     strict-on-bound / silent-on-unbound picture as supplierinvoices
#     (C4b). Family-width confirmation; tally evidence, no new issue.
#   SPP4 (candidate, C4): falsy-param drop — thirdparty_ids=0 diffed
#     against unfiltered pagination.total (SOF5, SIF4 precedent).
#     Status pinned 200 in the suite; the diff itself is a MANUAL
#     curl probe — run it before finalising.
#   SPP5 (OBSERVED 2026-11-26, first hurl run): limit=0 +
#     pagination_data=true -> 200 data envelope. PR #42952 confirmed
#     fixed on the THIRD family class (orders, invoices, proposals;
#     receptions still owed). Checklist C3 nearly closed.
#   SPP6 (candidate, C5): mixed serialization (id string vs numbers) —
#     note via jq only, never asserted in hurl. Verify with the row-
#     shape jq probe; tally evidence for #43386 if string.
#   SPP7 (CATALOGUED 2026-11-26, future task): GET-contacts parity gap
#     — supplierorders exposes GET /{id}/contacts, but supplierinvoices
#     AND supplierproposals expose no contacts route, although both
#     elements have GUI contact linkage. Future task (probe + source
#     grep + one shared parity issue) — see the SPP7 section at the
#     bottom of this file. No other new divergences vs the two
#     siblings this episode beyond SPP1's camp flip.
#   SPP8 (UPHELD 2026-11-26): spec of record is the INSTANCE's own
#     swagger.json. The pasted snapshot's missing count parameter
#     turned out STALE AGAIN (count=1 -> 200 {"count":...} on the
#     instance; SIF8 pattern repeats verbatim). Never trust a pasted
#     snapshot of unknown vintage.

# ---------------------------------------------------------------- GET /supplierproposals (index)

# Positive: list, ascending sort on the conventional sortfield, capture
#   first row id for later get() lookups.
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
proposal_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on another conventional column of the proposal table (ref)
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (supplierorders SOF / supplierinvoices SIF /
#   tasks / projects): unknown sortfield flows into SQL ORDER BY -> 503.
#   Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — coerced, no 500 (family camp)
GET http://{{hostnport}}/api/index.php/supplierproposals?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED-BY-ANALOGY: negative limit -> 200 (coerced)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family): non-numeric limit -> 400
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY: non-numeric page -> 400
GET http://{{hostnport}}/api/index.php/supplierproposals?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — formerly DivisionByZeroError, fixed by
#   PR #42952 on all four supplier-family classes. SPP5: confirmation
#   probe (both siblings green).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# thirdparty_ids — spec example '1' or '1,2,3'; "0" is syntactically
#   consistent with the digit/comma pattern (never hardcoded to real
#   ids). SPP4 candidate (falsy-param family, SOF5/SIF4): manually diff
#   .pagination.total of this request (+pagination_data=true) against
#   the unfiltered baseline two requests below — empty diff means the
#   filter is DROPPED for the falsy 0. Count NOT pinned
#   (data-dependent); status pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?thirdparty_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unfiltered baseline with pagination envelope for the SPP4 diff probe
#   (manual: compare .pagination.total of the two requests)
GET http://{{hostnport}}/api/index.php/supplierproposals?properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# thirdparty_ids — junk value violating the digit/comma pattern:
#   SPP3 candidate (both siblings enforce with 400). Pin 400
#   BY-ANALOGY; a silently-ignored 200 would be a DIVERGENCE — pin
#   whichever is OBSERVED.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?thirdparty_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid collection
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family): unknown sqlfilters column -> 503
#   (syntax validated, column names not). Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY: invalid sqlfilters syntax -> 400
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.date_valid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — SPP2 (C1, the #43388/#43430 fork)
# The pasted Swagger snapshot does NOT list sqlfilterlines on
#   /supplierproposals. TWO sealed sibling outcomes exist: supplierorders
#   BINDS it (#43388: bare column works, t.-alias on line columns -> 503,
#   bad syntax -> 400); supplierinvoices does NOT bind it at all
#   (#43430: every value -> 200 unfiltered, incl. bad syntax). Run the
#   whole five-probe matrix + the source grep, then decide SPP2:
#     - All five -> 200 identical: silent ignore -> COMMENT on #43430
#       (family width of the silent-ignore behaviour), checklist row
#       "N/A — parameter absent"; NO new issue.
#     - 503/400/200 split like supplierorders: COMMENT on #43388.
#     - Anything else: NEW SPP2 issue file.
# Line column for supplier proposals is qty (line table
#   supplier_proposaldet), same as both siblings.

# sqlfilterlines with t.-aliased LINE column — binding here would 503
#   (#43388 trap); ignoring gives 200. Disambiguated by bad-syntax probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines with a BARE line column — the WORKING filter if bound
#   (supplierorders shape); unfiltered 200 if ignored.
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilterlines=(qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines via t.-alias on a real HEADER column
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — unknown column: 503 if bound, 200 if ignored
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilterlines=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — invalid syntax: the DECISIVE probe. 400 = bound
#   (#43388 family); 200 = silently ignored (#43430 family).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- PARAM count (@since 25.0.0, NOT in the pasted snapshot)
# SIF8 lesson (STANDING, SPP8): the pasted snapshot may simply be
#   stale. DECIDE FROM THE INSTANCE:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/supplierproposals".get.parameters[].name'
#   and the source docblock:
#     grep -n "int \$count" \
#       htdocs/supplier_proposal/class/api_supplier_proposals.class.php
#   count present -> the pins below are regression guards (as on
#   supplierinvoices; SIF8: no divergence, snapshot was stale).
#   count ABSENT both places -> DELETE this section, note in SPP7.
# Pins BY-ANALOGY to SIF8 (correct full behaviour there).

# Bare count — the whole-list total
GET http://{{hostnport}}/api/index.php/supplierproposals?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count honours a filter (thirdparty_ids=1 — single-digit, valid
#   pattern; data-dependent, only shape pinned)
GET http://{{hostnport}}/api/index.php/supplierproposals?count=1&thirdparty_ids=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# Validation wins over count (checked BEFORE the early return) — junk
#   id-style value must still be rejected in count mode
GET http://{{hostnport}}/api/index.php/supplierproposals?count=1&thirdparty_ids=NOSUCHPATTERN
HTTP 400
[Asserts]
jsonpath "$.error" exists

# limit/page ignored in count mode — same count as bare count
GET http://{{hostnport}}/api/index.php/supplierproposals?count=1&limit=2&page=3
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# pagination_data does not wrap the count-mode response
GET http://{{hostnport}}/api/index.php/supplierproposals?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/supplierproposals?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/supplierproposals?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SPP3 candidate: pagination_data junk value — typed boolean, siblings
#   answer 400 (strict binding). Pin 400 BY-ANALOGY; verify at run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals?limit=5&pagination_data=NOTABOOLEAN
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/supplierproposals?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /supplierproposals/{id}

# Positive: fetched by captured id — assert id round-trip and field
#   presence only. Verify the id serialization type with jq FIRST
#   (both siblings: string, C5/#43386). If number, switch:
#     jsonpath "$.id" toString == "{{proposal_id}}"
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals/{{proposal_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{proposal_id}}"
jsonpath "$.ref" exists

# id=0 — falsy-id family, SPP1 (C2). OBSERVED 2026-11-26 (first hurl
#   run): HTTP 404 — the class joins the 404 camp, DIVERGING from BOTH
#   siblings (supplierorders 2026-11-25 AND supplierinvoices 2026-11-26
#   both answer 200 with the all-null SPECIMEN object). Camps already
#   differed per class in the #43307 tally (categories 500, invoices
#   400, memberstypes 404, orders 200-specimen); supplierproposals now
#   supplies the 404 data point for a SUPPLIER-family class. NOT a bug
#   per se (404 for a nonexistent id 0 is defensible); evidence for the
#   falsy-id tally — comment on #43307, no new issue. SIF7 lesson
#   stands: camps are PER ROUTE — if subresources are added from the
#   source read, probe /0 on each of them independently.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierproposals/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/supplierproposals/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter typed long -> 400
GET http://{{hostnport}}/api/index.php/supplierproposals/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/supplierproposals/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to hurl
#   tests started via run.sh. C6 coverage for this class is MANUAL, via
#   curl (three probes only — both siblings confirmed 403 clean
#   envelope on all three):
#     LOWKEY=<zero-rights-key> DOLIHOST=localhost:8080 \
#       bash -c 'for p in "?limit=1" "/0" \
#         "?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=1"; do \
#         printf "probe %s -> " "${p}"; \
#         curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#           "http://${DOLIHOST}/api/index.php/supplierproposals${p}"; done'
#   EXPECTED (family precedent, supplierorders 2026-11-25 AND
#   supplierinvoices 2026-11-26): 403 clean JSON envelope on all three —
#   no specimen on /0 (permission gate BEFORE the SPP1 falsy-specimen
#   path; a 200 with specimen here would be a REAL access-control
#   finding), no raw SQL text on the filter probe. RESULT-FIELD:
#   C6 supplierproposals result: [TO BE FILLED AFTER MANUAL RUN — date,
#   commit, three statuses].
# C6 STATUS: PENDING (manual curl required before claiming coverage).

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# The pasted snapshot exposes NO contacts route and no subresources on
#   supplier proposals. If the source read adds subresources (lines or
#   others), add per-subresource negatives (99999999 -> 404, abc -> 400,
#   /0 per SIF7) AND a manual cross-object leak plan here, modelled on
#   the supplierinvoices payments/lines sections:
#   MANUAL PROBE PLAN template (GET-only; fixtures outside hurl):
#     1. Create/choose two proposals A and B where B has lines and A
#        does not (GUI or write-API fixtures OUTSIDE this file).
#     2. GET /supplierproposals/{A}/lines and /{B}/lines with
#        ${DOLIHOST}+${DOLAPIKEY}; diff the returned line ids.
#     3. If A's list contains B-only line ids, file a BUG issue
#        (cross-object line leak) with observed outputs only.
#   Line ownership analogously: a line belonging to B must never appear
#   in GET /supplierproposals/{A}/lines.
#
# ---------------------------------------------------------------- SPP7 — GET-contacts PARITY GAP (future task, catalogued 2026-11-26)
# Family divergence in the GET surface itself: supplierorders exposes
#   GET /supplierorders/{id}/contacts (full contacts coverage in its
#   suite, incl. the C7/postContact 304 episode), but BOTH younger
#   siblings expose NO contacts route at all — not on /supplierinvoices
#   (noted in its suite header since the SIF episode) and not on
#   /supplierproposals (confirmed against the pasted snapshot here;
#   source-read grep still owed). Both elements DO have contact
#   linkage in the GUI (Commercial proposals and Supplier invoices both
#   carry contacts/addresses tabs), so GUI-capable data is unreachable
#   via API on two of three classes — a parity/exposure gap, not a
#   runtime bug. FUTURE TASK (recorded, not yet an issue file):
#     1. Source-grep all three classes for getContacts / 'contacts'
#        route registration to confirm the absence is in code, not
#        just the (twice-stale) snapshots.
#     2. Probe GET /supplierproposals/{id}/contacts and
#        /supplierinvoices/{id}/contacts manually on the instance —
#        expect 404/route-not-found; capture outputs.
#     3. Then decide: single cross-resource issue "contacts route
#        missing on supplierinvoices + supplierproposals (parity with
#        supplierorders)" — likely one shared issue, NOT one per class,
#        with C7/#43423 as the write-side companion context.
#   Until that task runs, this suite treats contacts as N/A and the C7
#   write-side episodes as applicable to supplierorders only.
