# test/hurl/api/receptions/10_receptions.hurl
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# PLACEMENT NOTE: in the real repo tree this file lives at
#   api/receptions/10_receptions.hurl
# run.sh's auth-required batch only executes files matching the glob
# */10*.hurl, so it MUST sit one directory deep under api/.
#
# GET-only tests for the receptions (product receptions, Reception) API —
#   every GET endpoint of the class api_receptions
#   (htdocs/reception/class/api_receptions.class.php —
#   SOURCE READ COMPLETED 2026-11-26: the full class source was pasted
#   and analysed against instance commit 819a092565742b6133745f88ab1b
#   7b3c06db494b; all GET routes/params below are confirmed against the
#   class. Behavioural pins are OBSERVED-BY-ANALOGY/SOURCE-NOTED until
#   the first green run re-annotates them — "change this assert
#   deliberately, never accidentally").
#
# SPEC OF RECORD (SIF8/SPP8 LESSON, standing as RCP8): the instance's
#   own swagger.json — NEVER a pasted snapshot of unknown vintage. The
#   source read is DONE; the instance swagger probe is still owed at
#   first run (cheap, and it seals RCP8 in the OTHER direction this
#   time — see below):
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/receptions"'
#
# GET SURFACE (CONFIRMED by source read 2026-11-26, class Receptions):
#     GET /receptions              (index: list)
#     GET /receptions/{id}         (get: one reception)
#     GET /receptions/{id}/lines   (getLines: ReceptionLineBatch rows;
#                                   403/404 documented in the docblock)
#     a) sqlfilterlines: NOT in the index() signature — the parameter
#        is ABSENT in code (exactly the SPP2 pattern, source-sealed).
#        Verdict: silent-ignore expected = family width of #43430
#        (RCP2); the five-probe matrix below stays as REGRESSION
#        GUARDS, decisive probe = bad syntax (expect 200, not 400).
#     b) count: PRESENT in the docblock (@since 25.0.0) AND in the
#        pasted snapshot — the FIRST family episode where the snapshot
#        and the source agree on count. index() early-returns
#        _getCountOfRecords($sql) after the sqlfilters validation, so
#        validation-wins-over-count holds; limit/page/pagination_data
#        are all ignored in count mode (the early return precedes
#        order()/plimit() and the pagination block). Count section
#        stands as regression guards.
#     c) status enum: NO status parameter exists (unlike supplierorders
#        7 values / supplierinvoices 4 values; same as supplierproposals)
#        — no status section; thirdparty_ids + sqlfilters is the entire
#        documented filter surface.
#     d) by-value routes: NONE (no /ref, no /ref_ext) — no
#        sentinel/"."/"0" by-value probes applicable.
#     e) contacts: NO contacts method in the class — receptions JOINS
#        the C8 no-contacts camp with supplierinvoices + supplierproposals
#        (supplierorders remains the only family member with the route).
#        Source-confirmed 2026-11-26; instance probe still owed for the
#        C8 issue text.
#     f) RECEPTIONS-SPECIAL sqlfilters case: index() pattern-matches
#        "el.fk_source:=:<digits>" in sqlfilters and adds a JOIN on
#        element_element (sourcetype='order_supplier',
#        targettype='reception') — the DOCUMENTED way to filter
#        receptions by their supplier order id. Probed below (unique
#        to this class; no sibling has it).
#     g) External-user behaviour (source): with an external user,
#        thirdparty_ids is IGNORED and replaced by the user's own
#        socid — noted for the 4-profile API-key matrix; the admin-key
#        suite pins are unaffected.
#     Write-side routes (context only, OUT OF SCOPE here): POST /,
#     POST {id}/lines, PUT {id}/lines/{lineid}, DELETE {id}/lines/
#     {lineid}, PUT /, DELETE /, POST {id}/validate, POST {id}/close,
#     POST {id}/settodraft — all carry reception-specific 405 guards
#   (draft-only line editing) and are NOT exercised by this suite.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Reception module enabled with 'reception lire'
#   rights for the key. The LIST section captures "$[0]" and therefore
#   requires AT LEAST ONE reception on the instance.
# FIXTURE RESOLVED (2026-11-27): the instance had ZERO receptions at the
#   first run attempts (hurl died on the reception_id capture with
#   "query didn't return any result" — a MISSING FIXTURE, not a pin
#   failure). A reception has now been created via the GUI; observed
#   on commit 819a0925:
#     GET /api/index.php/receptions?sortfield=t.rowid&sortorder=ASC&limit=100&count=1
#     -> {"count": 1}
#   Suite is runnable as-is. If the instance is ever empty again, the
#   quickest fixture is the Reception button on an approved supplier
#   order card (GUI), or POST {"socid": <existing socid>} when
#   RECEPTION_STANDALONE is enabled.
# Instance: INSTANS: commit 8f38e8fe587d2f62f0368a3c39471cc64308b45d
#   (HEAD -> develop, origin/develop, origin/HEAD; merge of #43478
#   "FIX Shipment creation: class attribute of the warehouse and
#   batch sub-lines", Laurent Destailleur, 2026-11-27 01:07:35 +0100).
#   Commit history of this episode: source read + drafting against
#   819a0925 (the supplierproposals instance, 2026-11-26); manual
#   id=0 / id=0-lines / count observations 2026-11-27; first GREEN
#   run 49/49 on 2026-11-27. The instance moved through
#   2904a1b3dd9605968fec0f929944952845fa9e90 (merge of #43458) during
#   the episode and to 8f38e8fe (this commit) at its close.
#   All OBSERVED pins in this file refer to 8f38e8fe unless a comment
#   explicitly cites an earlier commit. If the pins are re-run on a
#   later commit, re-annotate here first.
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
#       "http://${DOLIHOST}/api/index.php/receptions?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/receptions?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   Family precedent (supplierorders, supplierinvoices, supplier-
#   proposals): id is a STRING (C5/#43386). If id here is a number,
#   switch bare-compare asserts to the toString filter form:
#     jsonpath "$.id" toString == "{{reception_id}}"
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE (source-confirmed): ALL THREE GET methods gate
#   hasRight('reception', 'lire') FIRST, then fetch, then
#   DolibarrApi::_checkAccessToResource() — so a low-privilege key sees
#   403 BEFORE the falsy-id/404 path and BEFORE any SQL build. C6
#   expectation: 403 clean envelope on every probe.
#   LOW-PRIVILEGE NEGATIVE CONTROL (C6): NOT AUTOMATED — the zero-rights
#   (LOWKEY) key is not available to hurl tests via run.sh. C6 is
#   MANUAL curl ONLY. Never embed {{lowkey}} in this file. See the C6
#   section at the bottom and supplier_family_common_issues.md.
#
# FINDINGS CATALOGUED (RCP = Receptions Finding; catalogued from the
#   family checklist, source-annotated 2026-11-26, behaviour resolved
#   at first run):
#   RCP1 (OBSERVED 2026-11-27, commit 819a0925, manual curl BEFORE the
#     first green hurl run): GET /receptions/0 -> **200 with the
#     all-null SPECIMEN object** ("id": null, ..., "specimen": 0) — the
#     200-SPECIMEN camp, matching supplierorders/supplierinvoices and
#     DIVERGING from supplierproposals (404). CORRECTED TWICE, both
#     times deliberately: (1) initial pin 200-specimen BY-ANALOGY
#     (two-of-three majority); (2) re-pinned 404 after the source read
#     (get()/getLines() throw RestException(404) on a falsy
#     fetch($id)); (3) the INSTANCE overturned the source reading —
#     fetch(0) evidently returns a truthy specimen state on this
#     instance, so the 404 throw is not reached. OBSERVATION BEATS
#     SOURCE ANALYSIS; the pin is now the originally-analogized
#     200-specimen, OBSERVED. SECOND CAMP OBSERVED 2026-11-27 (first
#     green run): GET /receptions/0/lines -> **200 with []** — the
#     correct-empty-set subresource camp, matching the supplier-
#     invoices SIF7 shape (parent /0 -> 200-specimen, /0/lines -> 200
#     []) and the supplierorders /0/contacts -> 200 [] precedent.
#     Full per-route map, all OBSERVED on commit 819a0925:
#       GET /receptions/0        -> 200 all-null SPECIMEN
#       GET /receptions/0/lines  -> 200 []
#       GET /receptions/10/lines -> 404 "Not Found: Reception not
#                                    found" (nonexistent id, both routes)
#     Reception camps mirror supplierinvoices exactly — tally
#     evidence for #43307 (route-qualified), no new issue.
#   RCP2 (SEALED IN CODE 2026-11-26): sqlfilterlines — index()
#     declares exactly sortfield/sortorder/limit/page/thirdparty_ids/
#     sqlfilters/properties/pagination_data/count; NO sqlfilterlines
#     parameter (the SPP2 pattern verbatim). Predicted verdict:
#     silent-ignore on all five probes = family width of #43430 —
#     COMMENT on #43430 when the run confirms; NO new issue; the
#     matrix stays as regression guards.
#   RCP3 (SOURCE-NOTED): strict 400 on BOUND typed params —
#     thirdparty_ids carries {@pattern /^[0-9,]*$/i} in the docblock
#     (Restler-enforced), pagination_data is a typed bool.
#     thirdparty_ids=abc -> 400 and pagination_data=NOTABOOLEAN -> 400
#     predicted (C4b four-for-four). A 200 would BREAK the pattern.
#   RCP4 (BY-ANALOGY): falsy-param drop — thirdparty_ids=0 returns
#     ALL receptions (C4, three-for-three so far). CAUTION: source
#     build is `if ($socids) { ... IN (...) }` — a "0" string IS
#     truthy in PHP, so the IN-clause is built with 0; the predicted
#     drop comes from the SQL returning nothing matching... actually
#     VERIFY BY DIFF PROBE — receptions may DIVERGE here (the C4
#     family result is decided by the SQL, not the PHP). Manual diff
#     probe in the RCP4 section; either result is a tally data point.
#   RCP5 (OBSERVED 2026-11-27, commit 819a0925, manual curl before the
#     first green hurl run): limit=0 + pagination_data=true -> 200
#     data envelope is SOURCE-CONSISTENT (the guard
#     'page_count' => ($limit > 0 ? ceil(...) : 1) is visible in the
#     class) and behaviour-pending one green run confirmation —
#     receptions is the LAST family class touched by PR #42952; a
#     green pin CLOSES checklist C3 entirely.
#     NOTE: the fixture run also confirmed count works:
#     ?limit=100&count=1 -> {"count": 1} (OBSERVED 2026-11-27).
#   RCP6 (BY-ANALOGY): mixed serialization — id a STRING (C5,
#     #43386). One jq probe; if number, flip the round-trip asserts
#     to toString and note in the C5 tally.
#   RCP7 (RESERVED): any NEW divergence found at first run — write
#     the OBSERVED comment here and, if genuinely new, a BUG/WISH
#     Markdown file with a ${DOLIHOST} repro and cross-references.
#     Candidate to watch (receptions-specific): the el.fk_source
#     sqlfilters join and the external-user thirdparty_ids override
#     (g) — neither exists on any sibling.
#   RCP8 (UPHELD SO FAR): spec-of-record discipline — this episode is
#     the FIRST where the pasted snapshot and the source AGREE on
#     count (both directions of staleness have now been seen:
#     SIF8/SPP8 snapshot-stale-source-true; verify the instance
#     swagger agrees too, then the lesson stands symmetric).

# ---------------------------------------------------------------- GET /receptions (index)

# Positive: list, ascending sort on the conventional default sortfield
#   (source default: t.rowid / ASC), capture first row id for later
#   get()/lines lookups. REQUIRES the fixture above (>=1 reception).
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
reception_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on another conventional column of the reception table (ref)
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (supplierorders SOF / supplierinvoices SIF /
#   supplierproposals SPP / tasks / projects), SOURCE-CONSISTENT (the
#   sortfield flows into $this->db->order() and the query error path
#   throws RestException(503)): unknown sortfield -> 503. Verify at
#   first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — source coerces ($page < 0 -> $page = 0), no 500
GET http://{{hostnport}}/api/index.php/receptions?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED-BY-ANALOGY (family): negative limit -> 200. SOURCE-CONSISTENT
#   with a twist: plimit($limit + 1 = 0, 0) builds LIMIT 0, so expect a
#   200 with an EMPTY collection (siblings passed isCollection on the
#   same shape). Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family): non-numeric limit (typed int) -> 400
GET http://{{hostnport}}/api/index.php/receptions?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (family): non-numeric page (typed int) -> 400
GET http://{{hostnport}}/api/index.php/receptions?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — formerly DivisionByZeroError, fixed by
#   PR #42952 on all four supplier-family classes; the guard
#   ($limit > 0 ? ceil : 1) is VISIBLE in the pasted source. RCP5: LAST
#   family confirmation probe — green here CLOSES checklist item C3
#   entirely (orders, invoices, proposals already confirmed).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# thirdparty_ids — docblock pattern {@pattern /^[0-9,]*$/i}; "0" is
#   syntactically consistent (never hardcoded to real ids). RCP4
#   candidate (falsy-param family, SOF5/SIF4/SPP4): manually diff
#   .pagination.total of this request (+pagination_data=true) against
#   the unfiltered baseline two requests below — empty diff means the
#   filter is DROPPED for the falsy 0. SOURCE CAVEAT (RCP4 note): the
#   PHP guard is truthiness-based (if ($socids)), and "0" is TRUTHY in
#   PHP, so the IN(0) clause IS built — the outcome depends on SQL,
#   and receptions may DIVERGE from the family drop. Either result is a
#   C4 tally data point. Count NOT pinned (data-dependent); status
#   pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?thirdparty_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unfiltered baseline with pagination envelope for the RCP4 diff probe
#   (manual: compare .pagination.total of the two requests)
GET http://{{hostnport}}/api/index.php/receptions?properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# thirdparty_ids — junk value violating the documented digit/comma
#   pattern: RCP3 candidate (Restler-enforced {@pattern}, three-for-
#   three with 400 so far). Pin 400; a silently-ignored 200 would
#   BREAK the family pattern — pin whichever is OBSERVED.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?thirdparty_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored (source: if ($sqlfilters)),
#   list still served
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid collection
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — RECEPTIONS-SPECIAL documented case (RCP7 watch): the
#   source pattern-matches "el.fk_source:=:<digits>" and adds the
#   element_element JOIN (filter receptions by their SUPPLIER ORDER
#   id — the docblock advertises exactly this). 123 is a syntactic
#   digit example, data-independent; expect 200 collection (rows only
#   if a reception is linked to supplier order 123). If this 500s, the
#   documented JOIN is broken — RCP7 issue candidate.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(el.fk_source%3A%3D%3A123)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (family), SOURCE-CONSISTENT (unknown column ->
#   query error -> RestException(503, 'Error when retrieving reception
#   list')): unknown sqlfilters column -> 503. Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (family), SOURCE-CONSISTENT (forgeSQLFromUni-
#   versalSearchCriteria errormessage -> RestException(400)): invalid
#   sqlfilters syntax -> 400. Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.date_creation%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/receptions?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — RCP2 (SEALED IN CODE: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-read
#   2026-11-26 — the SPP2 pattern verbatim). Restler passes undeclared
#   query parameters through unbound and unvalidated, so the predicted
#   outcome is the #43430/#43439 silent-ignore family: ALL FIVE probes
#   -> 200 with the UNFILTERED list, including bad syntax (which
#   supplierorders answers 400 — #43388). The matrix stays as
#   REGRESSION GUARDS: if any probe stops being 200, either a binding
#   appeared (re-decide against #43388) or validation tightened —
#   comment on #43430 with the run evidence either way.
# Line column for receptions is qty (ReceptionLineBatch->qty).

# sqlfilterlines with t.-aliased LINE column — binding here would 503
#   (#43388 trap); silently ignored gives 200.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines with a BARE line column — the WORKING filter if bound
#   (supplierorders shape); unfiltered 200 if ignored.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilterlines=(qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines via t.-alias on a real HEADER column
GET http://{{hostnport}}/api/index.php/receptions?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — unknown column: 503 if bound, 200 if ignored
GET http://{{hostnport}}/api/index.php/receptions?sqlfilterlines=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — invalid syntax: the DECISIVE probe. 400 = bound
#   (#43388 family, would CONTRADICT the source read); 200 =
#   silently ignored (#43430/#43439 family, as sealed in code).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- PARAM count (@since 25.0.0 — docblock AND snapshot AGREE, a family first; RCP8)
# Source-confirmed mechanics: index() validates sqlfilters (400) BEFORE
#   the `if ($count) return _getCountOfRecords($sql);` early return,
#   and the early return precedes order()/plimit() and the pagination
#   block — so limit/page/pagination_data are all IGNORED in count
#   mode, and pattern validation (thirdparty_ids) still fires first
#   (Restler, before the method body). Pins BY-ANALOGY to SIF8/SPP8.

# Bare count — the whole-list total
GET http://{{hostnport}}/api/index.php/receptions?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count honours a filter (thirdparty_ids=1 — single-digit, valid
#   pattern; data-dependent, only shape pinned)
GET http://{{hostnport}}/api/index.php/receptions?count=1&thirdparty_ids=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# Validation wins over count (sqlfilters checked BEFORE the early
#   return) — invalid syntax must still be rejected in count mode
GET http://{{hostnport}}/api/index.php/receptions?count=1&sqlfilters=(bad%20syntax)
HTTP 400
[Asserts]
jsonpath "$.error" exists

# limit/page ignored in count mode — same count as bare count
GET http://{{hostnport}}/api/index.php/receptions?count=1&limit=2&page=3
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# pagination_data does not wrap the count-mode response
GET http://{{hostnport}}/api/index.php/receptions?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction (_filterObjectProperties); shape only
GET http://{{hostnport}}/api/index.php/receptions?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/receptions?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
#   (source-confirmed keys: total, page, page_count, limit)
GET http://{{hostnport}}/api/index.php/receptions?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/receptions?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# RCP3 candidate: pagination_data junk value — typed bool, all three
#   siblings answer 400 (strict binding). Pin 400; verify at run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions?limit=5&pagination_data=NOTABOOLEAN
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/receptions?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /receptions/{id}

# Positive: fetched by captured id — assert id round-trip and field
#   presence only. Verify the id serialization type with jq FIRST
#   (family precedent: string, C5/#43386, RCP6). If number, switch:
#     jsonpath "$.id" toString == "{{reception_id}}"
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions/{{reception_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{reception_id}}"
jsonpath "$.ref" exists

# id=0 — falsy-id family, RCP1 (C2). OBSERVED 2026-11-27 (manual curl
#   on commit 819a0925, before the first green hurl run): HTTP 200
#   with the all-null SPECIMEN object ("id": null, "ref": null,
#   "specimen": 0) — receptions joins the 200-SPECIMEN camp with
#   supplierorders and supplierinvoices, DIVERGING from
#   supplierproposals (404). PIN HISTORY (three deliberate states,
#   teaching case for the pin discipline): (1) BY-ANALOGY 200-specimen
#   (two-of-three sibling majority); (2) source read suggested 404
#   (get() throws RestException(404) on falsy fetch($id)) — re-pinned
#   404; (3) the INSTANCE overturned it: fetch(0) evidently yields a
#   truthy specimen state here, so the 404 branch is never reached.
#   OBSERVATION WINS over both analogy AND source reading — camps can
#   only be pinned OBSERVED. Tally evidence for #43307 (route: get()).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/receptions/0
HTTP 200
[Asserts]
jsonpath "$.specimen" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/receptions/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter typed int -> 400
GET http://{{hostnport}}/api/index.php/receptions/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/receptions/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /receptions/{id}/lines
# Source-confirmed: getLines() gates reception/lire FIRST, then fetch
#   ($id) -> 404 'Reception not found' on falsy, then
#   _checkAccessToResource() -> 403; returns the cleaned
#   ReceptionLineBatch rows already loaded by fetch() (fetch_lines()
#   for a reception born from a supplier order, fetch_lines_free()
#   otherwise). Lines MAY legitimately be empty — shape pinned only.

# Positive: lines of the captured reception — may be empty, must be a
#   collection
GET http://{{hostnport}}/api/index.php/receptions/{{reception_id}}/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent reception id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/receptions/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric reception id on the subresource -> 400
GET http://{{hostnport}}/api/index.php/receptions/abc/lines
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — RCP1 second camp (SIF7: camps are PER
#   ROUTE). OBSERVED 2026-11-27 (first green run, commit 819a0925):
#   HTTP 200 with an EMPTY ARRAY [] — the correct-empty-set subresource
#   camp, mirroring supplierinvoices (/0 -> 200-specimen, /0/lines ->
#   200 []) and supplierorders /0/contacts -> 200 []. DEFENSIBLE
#   design (not a bug): the constraint IS applied — a fetch(0)
#   specimen has no lines, so [] is the truthful answer for "lines of
#   reception 0"; the 404 'Reception not found' answer is reserved for
#   NONEXISTENT non-falsy ids (OBSERVED same run: /10/lines -> 404
#   "Not Found: Reception not found"). The alternative (404 on /0/lines
#   for consistency with the nonexistent-id case) is arguably cleaner,
#   but the falsy-id camps are a documented cross-module pattern
#   (#43307 tally), not a per-class defect. Tally evidence only.
GET http://{{hostnport}}/api/index.php/receptions/0/lines
HTTP 200
[Asserts]
jsonpath "$" exists
jsonpath "$" count == 0

# ---------------------------------------------------------------- RCP4 — falsy-param diff probe (MANUAL, outside run.sh)
# C4 three-for-three so far (SOF5 orders, SIF4 invoices, SPP4
#   proposals). Receptions closes the tally — BUT the source suggests
#   a possible DIVERGENCE (RCP4 note above: PHP "0" is truthy, the
#   IN-clause IS built, outcome depends on SQL). Empty diff = filter
#   DROPPED (family pattern holds); different totals = correct
#   enforcement (receptions DIVERGES — a data point either way).
#
# MANUAL (run with ${DOLIHOST} + ${ADMINKEY}, never via run.sh):
#   diff <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/receptions?thirdparty_ids=0&properties=id&pagination_data=true" \
#     | jq -c '.pagination.total') \
#        <(curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/receptions?properties=id&pagination_data=true" \
#     | jq -c '.pagination.total')

# ---------------------------------------------------------------- RCP6 — serialization jq probe (MANUAL)
# C5/#43386 tally: three-for-three id-as-STRING so far. One probe:
#   curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#     "http://${DOLIHOST}/api/index.php/receptions?limit=1" \
#     | jq '.[0].id, (.[0].id|type)'
# STRING -> bare-compare asserts above are correct as written; NUMBER
#   -> flip the round-trip asserts to the toString form and note the
#   flip in the C5 tally.

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to hurl
#   tests via run.sh. C6 coverage for this class is MANUAL, via curl.
#   SOURCE-CONFIRMED ORDER: hasRight('reception','lire') is checked
#   FIRST in all three GET methods, so every probe answers 403 BEFORE
#   the fetch/404 and SQL paths:
#     LOWKEY=<zero-rights-key> DOLIHOST=localhost:8080 \
#       bash -c 'for p in "?limit=1" "/0" "/0/lines" \
#         "?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=1"; do \
#         printf "probe %s -> " "${p}"; \
#         curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#           "http://${DOLIHOST}/api/index.php/receptions${p}"; done'
#   EXPECTED (family precedent, three-for-three identical; source makes
#   it four-for-four by construction): 403 clean JSON envelope
#   {"error":{"code":403,"message":"Forbidden"}} on all four — no
#   404-on-/0 leak, no raw SQL text. RESULT-FIELD:
#   C6 receptions result: PENDING (fill in at the manual run).

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Receptions EXPOSES GET /{id}/lines, and the WRITE-side helpers
#   (fetchLineOfReception) enforce fk_reception ownership per line —
#   the GET side inherits the scoping from fetch()'s line loading, so
#   the cross-object LINE LEAK probe applies within the GET-only
#   surface. MANUAL PROBE PLAN (GET-only; fixtures outside hurl):
#     1. Create/choose two receptions A and B where B has lines and A
#        does not (GUI or write-API fixtures OUTSIDE this file).
#     2. GET /receptions/{A}/lines and /{B}/lines with
#        ${DOLIHOST}+${DOLAPIKEY}; diff the returned line ids.
#     3. If A's list contains B-only line ids, file a BUG issue
#        (cross-object line leak) with observed outputs only.
#   Line ownership analogously: a line belonging to B must never appear
#   in GET /receptions/{A}/lines.
#
# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Receptions is the FOURTH and LAST member of the supplier family
#   (supplierorders #43388/#43423, supplierinvoices #43430/SIF8,
#   supplierproposals #43439/SPP7/SPP8, receptions this suite). With
#   its probes observed, the checklist supplier_family_common_issues.md
#   (C1-C4b, C5, C6, C8) closes for the whole family. Family-wide
#   confirmations become COMMENTS on the existing issues (#43388,
#   #43430, #43439, #43307, #43385, #43386, #42952, #43423) — never
#   duplicates; only genuinely new failure modes (RCP7 candidates: the
#   el.fk_source join, the external-user thirdparty_ids override) get
#   BUG/WISH files.
