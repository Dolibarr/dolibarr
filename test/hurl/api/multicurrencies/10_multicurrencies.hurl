# test/hurl/api/multicurrencies/10_multicurrencies.hurl
#
# PLACEMENT NOTE: this file lives at api/multicurrencies/10_multicurrencies.hurl
# (run.sh's auth-required batch only executes files matching the glob
# */10*.hurl). Directory is the ENDPOINT name "multicurrencies", not the
# module name "multicurrency" — the README's "Undecided discrepancy"
# note applies again; endpoint wins, in line with every prior suite.
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the multicurrencies API — every GET endpoint of the
#   class MultiCurrencies (source:
#   htdocs/multicurrency/class/api_multicurrencies.class.php,
#   SOURCE READ COMPLETED 2026-11-27: full class source pasted and
#   analysed; all GET routes/params sealed against BOTH the live
#   explorer paste AND the class source).
#
# SPEC OF RECORD (SIF/SPP/RCP/SM/SH/PLOT discipline, now SEVEN-fold):
#   the instance's own swagger.json — re-pull at first run, ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/multicurrencies"'
#
# GET SURFACE (SOURCE-SEALED 2026-11-27; explorer paste agreed on every
#   route and parameter — prompt checklist a)-h) closed as follows):
#     GET /multicurrencies            (index)
#     GET /multicurrencies/{id}       (get)
#     GET /multicurrencies/{id}/rates (getRates)
#     GET /multicurrencies/bycode/{code} (getByCode)
#   a) sqlfilterlines: ABSENT from index()'s signature (source-sealed;
#      a currency IS itself the "line" entity — no line table exists).
#      Expected silent ignore (#43430/#43439 family). Two-probe
#      regression guards kept below, family precedent (bad syntax -> 200).
#   b) count: PRESENT and SOURCE-SEALED: "@since 25.0.0 Parameter count"
#      docblock + explorer + signature. index() calls
#      _getCountOfRecords($sql) AFTER sqlfilters validation but BEFORE
#      order()/plimit() — validation-wins-over-count expected; limit/
#      page are IGNORED in count mode.
#   c) NO status enum, NO products/services filter, NO owner/socid —
#      currencies are global reference data; the filter surface is
#      EXACTLY: sortfield, sortorder, limit, page, sqlfilters,
#      properties, count. The sqlfilters docblock example
#      ("(t.product_id:=:1)...") is a product copy-paste artefact
#      (MCF8) — multicurrency has no product_id column.
#   d) RATE subresource EXISTS: GET {id}/rates (getRates; @url GET
#      {id}/rates). Capture chain: list -> id -> get() -> get()/rates
#      runtime. Rate fields after _cleanObjectDatasRate: id, rate,
#      date_sync ONLY (source-sealed).
#   e) NO pagination_data parameter on index() — DIVERGENT from the
#      family suites where it was documented; here it is UNBOUND ->
#      expected silent passthrough (200), so the #42952 probe takes
#      the unparameterised shape: limit=0 & pagination_data=true must
#      still serve the plain list, 200.
#   f) PERMISSION GATES (SOURCE-SEALED): ONE module key, THREE literal
#      levels, all hasRight('multicurrency', 'currency', <level>):
#      read (index/get/getByCode/getRates), write (post/put/updateRate),
#      delete (delete). NO double spelling inside the class (unlike
#      productlots' product/produit split), NO double gate (each GET
#      method consults exactly one right). ORDERING ANOMALY
#      (source-sealed, differs from the family norm of gate-first):
#      get(), getByCode() and getRates() FETCH FIRST, throw 404, and
#      check the permission ONLY AFTER a successful fetch — so a
#      zero-rights key gets 404 (not 403) on a nonexistent id, an
#      EXISTENCE ORACLE; only index() gates first. Probe plan in the
#      C6 section; gate-resolver check (LOWKEY flip plan) included.
#   g) PHP truthiness note (SHF3 lesson): there are NO id-form filters
#      at all in index(), so the falsy-param-drop 0/abc matrix has NO
#      target and is skipped with this source note (MCF3 resolved as
#      EMPTY FILTER SURFACE).
#   h) COPY-PASTE ARTEFACT WATCH (MCF8): found in source — the
#      sqlfilters docblock example naming product_id/date_creation
#      (columns a currency table does not have); the index() docblock
#      @return line says "Array of warehouse objects" (warehouse!);
#      the class-level require of multicurrency.class.php is commented
#      out. None of these affect GET behaviour; all are phase-2
#      issue-comment material (#43515/#43514/#43569/#43596 kin).
#
# TABLE ALIAS (productlots lesson): read BEFORE any pin — the alias is
#   the plain family alias **t** ("FROM <prefix>multicurrency as t",
#   source-sealed). NO divergence. The t.* pins below are therefore
#   normative, NOT regression guards.
# ENTITY SCOPE (SOURCE-SEALED): index() hard-restricts to "WHERE
#   t.entity IN (getEntity('multicurrency'))" (multi-entity managed);
#   no parameter exposes this; cross-entity session probes are manual
#   two-instance work (PENDING, non-blocking — see ownership section).
# LIMIT IDIOM (SOURCE-SEALED): 'if ($limit) { plimit($limit + 1,
#   $offset) }' with 'if ($page < 0) { $page = 0; }' clamp INSIDE the
#   if($limit) block — the stockmovements/shipments/productlots KIN,
#   NOT the plimit-always family shape: limit<=0 (incl. -1) skips
#   plimit -> the UNBOUNDED list is served. Serving loop bounds
#   min(num, limit<=0 ? num : limit), so the +1 overshoot row is
#   trimmed — page-size assertions remain exact.
# SORTORDER IDIOM (SOURCE-SEALED): '$this->db->order($sortfield,
#   $sortorder)' — NO strtoupper coercion branch visible in the class
#   (unlike productlots); db->order sanitises internally. Unknown
#   sortfield remains the 503-candidate; lowercase "desc" expected 200.
#
# LIVE OBSERVATIONS FROM THE EXPLORER PASTE (hypotheses, pre-run):
#     GET /multicurrencies/bycode/0 -> 404 envelope "Not Found:
#         Currency not found" (the falsy/string camp on the by-value
#         route resolves as 404 — the paste answers this one directly).
#     GET /multicurrencies/1 (existing currency) -> 200 with "id": "1"
#         (STRING), rate nested {id:"1", rate:"1",
#         date_sync: <NUMBER>} — string ids + string rate values,
#         numeric date_sync.
#     GET /multicurrencies/1/rates -> 200 ARRAY of the same
#         id/rate/date_sync shape (rate STRING).
#   These shape observations are PASTE-EVIDENCE (hypothetical until
#   the first run re-confirms them); the jq probes below decide MCF5.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; multicurrency/currency/read right on the key; the
#   multicurrency module enabled and at least ONE currency WITH a rate
#   on the instance (GUI: Home -> Setup -> Modules -> MultiCurrency ->
#   activate; then set a currency rate — or, if a POST route exists on
#   this API, fixture via write-API OUTSIDE this hurl file). Note: the
#   currency table can also be pre-populated on module activation
#   (EUR etc. depending on setup) — gauge BEFORE the first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/multicurrencies?limit=100&count=1"
#   Empty instance ({"count":0}) = MISSING FIXTURE, not a pin failure.
# Instans-commit: OBSERVED FIRST GREEN RUN 2026-11-27 — 49/49 requests
#   in 1653 ms on **41b713fac9fd0a75823d0f7f629bcbdffea8cb0b** (HEAD ->
#   develop, merge of #43562 "remove orphan prospect index page",
#   Laurent Destailleur, 2026-11-27 23:08:23 +0100). All pins held on
#   the first run; RESULT-FIELDS of the MANUAL C6 probes below remain
#   PENDING. Convention:
#   ... -> a6eb6f21 -> 3d7bc9ab -> 41b713fa.
# PLACEMENT (final): file created FLAT in the workdir per contributor
#   instruction 2026-11-27 ("frem over bare skal oprette en flad fil");
#   the runner reported it as api/multicurrencies/10_multicurrencies.hurl
#   (49 requests, green) — the deep-path header note above stays as the
#   canonical repo destination.
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples: http://${DOLIHOST}/api/index.php/... with
#     DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#
# PORTABILITY: no hardcoded ids, codes or counts — everything captured
#   at runtime (currency CODES are domain-free: shape pins only, never
#   pinned values). VERIFY THE ROW SHAPE FIRST with jq before trusting
#   captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/multicurrencies?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/multicurrencies?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/multicurrencies/1/rates" \
#       | jq '.[0].rate, (.[0].rate|type)'
#   MCF5 pre-state: the STRING id tally stands at SIX (supplierorders,
#   supplierinvoices, supplierproposals, receptions, stockmovements,
#   productlots) vs ONE NUMBER (shipments). The paste suggests STRING
#   here (= majority grows to seven). ANTAG INGEN DEFAULT: the jq
#   probe comes FIRST; all asserts below are TYPE-AGNOSTIC
#   (existence/shape/isCollection; bare compares only on
#   runtime-captured values used round-trip). Rate serialization
#   (number-vs-string across the same class' routes) is the
#   #43386-dates-lesson analogue — documented, not assumed.
#   No asserts on free text — only status codes, ids, codes and shapes.
#
# FINDINGS CATALOGUED (MCF = MultiCurrencies Finding; catalogue prefix
#   pre-allocated; OBSERVED items carry their date — instance commit
#   hash appended at the first green run):
#   MCF1 (OBSERVED 2026-11-27, commit 41b713fa — all id=0 probes green
#     on the first run; per-ROUTE camps): 404-envelope on get()/0,
#     get()/0/rates AND bycode/0. The specimen camp (productlots'
#     explicit initAsSpecimen() branch) LOSES here; the
#     supplierproposals/tasks 404-envelope camp gains a member — on a
#     class where even the BY-VALUE route obeys the same camp as the
#     id-routes (supports the deliberate-flip convention discussion,
#     #43572/#43307). Pin history: (1) 2026-11-27 BY-ANALOGY;
#     (2) 2026-11-27 source read CONFIRMED the prediction (fetch(0)
#     falsy, no initAsSpecimen() branch); (3) 2026-11-27 instance
#     probe CONFIRMED (green run 49/49, commit 41b713fa). Tally
#     evidence for #43307.
#   MCF2 (BY-ANALOGY, streak seven-for-seven incl. two outsiders):
#     strict 400 on BOUND typed params (limit, page, count, path id)
#     vs SILENT passthrough on UNBOUND (unknown params, sqlfilterlines,
#     pagination_data-as-unbound here!). Source read supports the
#     bound half (typed signatures + Restler binding).
#   MCF3 (RESOLVED BY SOURCE, documented choice): EMPTY FILTER SURFACE
#     beyond sqlfilters — index() declares NO id/form filters, so the
#     falsy-drop 0/abc matrix has NO target. Skipped with this source
#     note; reported in the episode summary (productlots lesson: an
#     empty surface is also an answer; no dead-bound parameter exists
#     here — signature and body agree on every parameter).
#   MCF4 (SOURCE-SEALED): limit idiom = if($limit) KIN — limit<=0
#     skips plimit (UNBOUNDED list); negative page clamped to 0.
#     pagination_data is ABSENT as a parameter (silent passthrough
#     expected, 200) — the #42952 probe records its shape accordingly.
#   MCF5 (OBSERVED 2026-11-27, commit 41b713fa, manual jq probe): id
#     STRING ("1"), nested rate.id STRING, rate VALUE STRING ("1"),
#     date_sync NUMBER — exactly the paste prediction. The STRING
#     tally grows to SEVEN vs shipments' lone NUMBER (#43386 +
#     #43568). The string rate VALUE (a numeric quantity serialized
#     as text) is the dates-lesson (#43386) analogue in a new
#     dimension: same class, same field family, mixed types per
#     field (rate STRING, date_sync NUMBER in the SAME object).
#   MCF6 (OBSERVED 2026-11-27, commit 41b713fa, LOWKEY zero-rights
#     matrix): the GATE-AFTER-FETCH ordering is LIVE — an existence
#     oracle. Zero-rights key: index() -> 403 "Insufficient rights to
#     read currency" (gate-first, clean), but get()/99999999, get()/0,
#     get()/99999999/rates and bycode/NOSUCHCODE all -> 404 "Currency
#     not found" BEFORE any permission check. A key with NO rights can
#     therefore enumerate WHICH currency ids/codes exist (403 on an
#     EXISTING id, captured live: lowkey /1 -> 403) vs do not exist
#     (404: lowkey /99999999, /0, /99999999/rates, /bycode/NOSUCHCODE
#     all live). Both oracle facets OBSERVED 2026-11-27. Strongest
#     phase-2 BUG candidate of the episode; kin to the #43596
#     permission-gating discussion. Pin history:
#     (1) source-sealed hypothesis 2026-11-27; (2) LOWKEY probe
#     CONFIRMED same day, commit 41b713fa — both facets (404 on
#     nonexistent targets, 403 on the existing id after a successful
#     fetch).
#   MCF7: spec-of-record lesson upheld by construction (fresh explorer
#     paste treated as hypothesis; probes arbitrate; source sealed
#     last). Closed at first green run.
#   MCF8 (OBSERVED-BY-SOURCE 2026-11-27): COPY-PASTE ARTEFACTS: the
#     sqlfilters docblock example naming product_id/date_creation
#     (non-existent columns for currencies) and the "warehouse
#     objects" @return line in a currency class. No GET-side effect;
#     phase-2 issue-comment material (#43515/#43514/#43569/#43596 kin).

# ---------------------------------------------------------------- GET /multicurrencies (index)

# Positive: list, ascending sort on the SOURCE-SEALED alias t, capture
#   the first row id for the get()/rates round-trips. REQUIRES the
#   fixture (>=1 currency).
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
currency_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# MCF5 companion (type-agnostic asserts; the MANUAL jq probe in the
#   portability block decides string-vs-number offline): shape only.
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# MCF-sortorder (source-sealed: db->order, no visible coercion
#   branch): lowercase "desc" — db->order accepts case-insensitively;
#   expected 200. A 400/503 would REFUTE the source-sealed idiom.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Unknown sortorder value: db->order's own sanitation decides (product
#   lots coerced; this class delegates). 200-with-list is the primary
#   expectation; a 400/503 would be a NEW finding (MCF6 candidate).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a currency-specific column (code) — legal alias + real column
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.code&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# MCF family pin: unknown sortfield -> query error -> RestException(503)
#   ("Unknown column ... in 'ORDER BY'"). Legal alias, bogus column.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# MCF4 (source-sealed clamp inside if($limit)): negative page -> clamped
#   to 0, no 500.
GET http://{{hostnport}}/api/index.php/multicurrencies?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# MCF4 (source-sealed if($limit) idiom): negative limit SKIPS plimit ->
#   the UNBOUNDED list is served; 200 either way on a small fixture,
#   empty-vs-full is data-dependent, NOT asserted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# MCF2: non-numeric limit (typed int) -> 400. Bound-param streak probe.
GET http://{{hostnport}}/api/index.php/multicurrencies?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# MCF2: non-numeric page (typed int) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/multicurrencies?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# MCF4 + e) note: pagination_data is NOT a declared parameter here
#   (divergent from the family suites) — silent passthrough expected,
#   so limit=0 (plimit skipped, unbounded) with pagination_data=true
#   must still serve the plain LIST, 200. The #42952 pattern reaches
#   here only in this unparameterised shape. A 400/500 would be a NEW
#   finding (MCF6 candidate).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unbound unknown parameter: silent passthrough expected (MCF2 streak)
GET http://{{hostnport}}/api/index.php/multicurrencies?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: count mode (MCF-b, source-sealed @since 25.0.0)

# count=1: {"count": <n>} — number, no list payload
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count with a filter: count mode honours sqlfilters (validated BEFORE
#   the count branch, source-sealed)
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" exists

# MCF-b (validation-wins-over-count, source-sealed order): sqlfilters
#   syntax errors 400 BEFORE the count branch is reached
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# MCF-b (source-sealed: count returns BEFORE order()/plimit()): an
#   UNKNOWN sortfield must be IGNORED in count mode -> 200. Decisive
#   count-mode ordering probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count + limit/page: both IGNORED in count mode (source-sealed) ->
#   200 {"count": n}; count stays the sole payload key.
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&limit=5&page=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# MCF2: limit stays typed (int) even in count mode — junk -> 400
#   (typed binding validates before the body runs).
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# MCF2: count itself is typed (long) — junk -> 400
GET http://{{hostnport}}/api/index.php/multicurrencies?count=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: documented filter surface (sqlfilters, properties)

# sqlfilters — always-true filter on the source-sealed alias t
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: the if($sqlfilters) guard skips parsing,
#   list still served
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE on the currency-specific column code, matching
#   nothing: still a valid collection (shape-only; no value pinned)
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.code%3Alike%3A%27ZZZ-NO-SUCH-CODE%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# MCF family pin: unknown (legal-alias) column -> query error -> 503
#   ("Unknown column ... in 'WHERE'").
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# MCF8 cross-probe (source-sealed): the docblock's OWN example column
#   t.product_id is NOT a multicurrency column — this must 503 like
#   any unknown column (if it EVER returns 200, the source changed:
#   MCF8 revision).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.product_id%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# SOURCE-SEALED (forgeSQLFromUniversalSearchCriteria errormessage ->
#   RestException(400)): invalid sqlfilters syntax -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.rowid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilters=(t.code%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — restrict to a property name; restricted list served
GET http://{{hostnport}}/api/index.php/multicurrencies?properties=id&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — junk property name: _filterObjectProperties ignores
#   unknown names; list served unchanged, 200 (family behaviour).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?properties=nosuchproperty&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — MCF-a (source-sealed: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-sealed
#   2026-11-27; a currency IS itself the "line" entity — no line
#   table exists). Expected silent ignore (#43430/#43439 family).
#   Two-probe regression guard: if bad syntax stops being 200, a
#   binding appeared — re-pin and comment the breadth tally.
# Change these asserts deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilterlines=%28t.rowid%3A%3E%3A%270%27%29&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/multicurrencies?sqlfilterlines=BAD%20SYNTAX%20HERE&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- GET /multicurrencies/{id} (get)

# Positive: round-trip the runtime-captured id
GET http://{{hostnport}}/api/index.php/multicurrencies/{{currency_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists
jsonpath "$.code" exists

# Negative: far-out id -> 404 envelope (MCF1 non-zero camp)
GET http://{{hostnport}}/api/index.php/multicurrencies/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# MCF2: non-numeric path id (typed) -> 400
GET http://{{hostnport}}/api/index.php/multicurrencies/abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative id: fetch fails -> 404 expected; a 400/500 would be a NEW
#   finding (MCF6 candidate)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# MCF1 CAMPS DECIDER: id=0 — the specimen camp (productlots explicit
#   branch) is IMPOSSIBLE here (source-sealed: no initAsSpecimen()
#   branch in this class; fetch(0) is falsy -> the 404-envelope camp
#   is predicted). A 200 here (specimen or otherwise) would REFUTE
#   the source reading and be a strong MCF6 candidate.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /multicurrencies/{id}/rates (getRates)

# Positive: rates of the runtime-captured currency. Shape-sealed:
#   array of {id, rate, date_sync} ONLY (source-sealed cleaning).
#   Rate VALUES are string-vs-number candidates (MCF5) — asserted
#   TYPE-agnostically here; the manual jq probe decides.
GET http://{{hostnport}}/api/index.php/multicurrencies/{{currency_id}}/rates
HTTP 200
[Captures]
rate_row_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" isCollection
jsonpath "$[0].id" exists
jsonpath "$[0].rate" exists
jsonpath "$[0].date_sync" exists

# Negative: rates of a far-out id -> 404 (same fetch-first ordering)
GET http://{{hostnport}}/api/index.php/multicurrencies/99999999/rates
HTTP 404
[Asserts]
jsonpath "$.error" exists

# MCF2: non-numeric id on the subroute (typed) -> 400
GET http://{{hostnport}}/api/index.php/multicurrencies/abc/rates
HTTP 400
[Asserts]
jsonpath "$.error" exists

# MCF1 subroute camp: id=0 on the subresource — same fetch-first 404
#   prediction; per-route camp discipline: pinned for THIS route
#   separately.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/0/rates
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /multicurrencies/bycode/{code} (getByCode)

# MCF8-by-value route series (EXISTS here — the classic ISO 4217
#   candidate confirmed in BOTH paste and source). Sentinel probes.
# Paste-evidence: bycode/0 -> 404 envelope (falsy/string camp on the
#   by-value route resolves as 404 on this surface).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/bycode/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Sentinel: nonexistent-but-plausible code -> 404 envelope
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/bycode/NOSUCHCODE
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Sentinel: dot-in-path variant (hurl-escaped special chars would be
#   403 non-JSON per family lore; the dot stays a routing probe) —
#   non-200 expected either way.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/multicurrencies/bycode/%2E
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Positive: a real code round-trip — captured from the currency list
#   at runtime (codes are domain-free; nothing hardcoded).
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=ASC&limit=1&properties=code
HTTP 200
[Captures]
currency_code: jsonpath "$[0].code"

GET http://{{hostnport}}/api/index.php/multicurrencies/bycode/{{currency_code}}
HTTP 200
[Asserts]
jsonpath "$.code" exists
jsonpath "$.name" exists

# ---------------------------------------------------------------- COMBINED SMOKE (all documented filters at once)

# English section name per the hook discipline. Everything documented
#   by the source-sealed signature in ONE call.
GET http://{{hostnport}}/api/index.php/multicurrencies?sortfield=t.rowid&sortorder=DESC&limit=5&page=0&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id,code,name
HTTP 200
[Asserts]
jsonpath "$" isCollection
jsonpath "$" count <= 5

# Count-mode combined smoke
GET http://{{hostnport}}/api/index.php/multicurrencies?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&sortfield=t.rowid&sortorder=DESC
HTTP 200
[Asserts]
jsonpath "$.count" exists

# ---------------------------------------------------------------- C6 MANUAL PROBES (shell-agnostic, PENDING until run)
# All manual probes: POSIX loop, export on its own line, NO
#   placeholders in commands; jq --arg for variable interpolation —
#   never a bare variable pipeline inside the jq string. curl with
#   http://${DOLIHOST}/api/index.php/... — never a hardcoded host.
#   Never {{lowkey}} in this file; LOWKEY only via manual curl.
#
# 1) ZERO-RIGHTS MATRIX (LOWKEY) — covers ALL FOUR GET gates. The
#    gate-AFTER-FETCH ordering (MCF6 candidate, source-sealed)
#    predicts: index() -> 403 (gate-first), but get()/rates/bycode()
#    -> 404 on nonexistent targets BEFORE the 403 — an existence
#    oracle. Note the paste-evidence support: the explorer's own
#    example hit /multicurrencies/1 fine, so targets matter.
#      export LOWKEY=<zero-rights-key>
#      export DOLIHOST=localhost:8080
#      for p in "?limit=1" "/99999999" "/0" \
#        "/99999999/rates" "/bycode/NOSUCHCODE" \
#        "?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=1"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#          "http://${DOLIHOST}/api/index.php/multicurrencies${p}"; done
#    EXPECTED (source-sealed MCF6): 403 on "?limit=1" ONLY; 404 on the
#    four fetch-first probes (existence oracle — THE FINDING if
#    live); 403 on the sqlfilterlines guard (index() gates first).
#    RESULT-FIELD: OBSERVED 2026-11-27, commit 41b713fa — CONFIRMED
#    EXACTLY AS PREDICTED: 403 on ?limit=1 AND on the sqlfilterlines
#    guard; 404 (clean envelope "Currency not found") on /99999999,
#    /0, /99999999/rates, /bycode/NOSUCHCODE. Oracle demonstration
#    completed with BOTH facets live: lowkey /1 -> 403 (exists, gate
#    fires after the successful fetch) vs lowkey /99999999 -> 404
#    (does not exist, fetch fails first). The existence oracle is
#    LIVE and fully evidenced (MCF6).
#
# 2) LOWKEY-FLIP (gate-resolver check): grant the SAME user
#    multicurrency/currency READ only, re-probe the positive trio
#    (list + get/<real id> + bycode/<real code> — all three should
#    200), THEN flip to WRITE-only (all GETs should 403):
#      export WKEY=<currency-write-only-key>
#      for p in "?limit=1" "/1" "/bycode/EUR"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${WKEY}" \
#          "http://${DOLIHOST}/api/index.php/multicurrencies${p}"; done
#    (id/code above are illustrative for the manual probe — substitute
#    runtime-captured values.) No type-blindness risk here: ONE
#    permission block serves every route (source-sealed), unlike the
#    productlots products/produit split. RESULT-FIELD: OBSERVED
#    2026-11-27 (permissions granted via GUI: "Multicurrency ->
#    Read currencies and their rates"): read-only -> 200 on all three
#    positive probes (list, get/1, bycode/EUR — the latter returning a
#    DECIMAL rate as STRING "0.133265812", widening MCF5: rate stays
#    STRING for whole and decimal values alike). WRITE-ONLY FLIP:
#    CLOSED AS N/A-BY-DESIGN 2026-11-27 — maintainer confirms the GUI
#    does not permit Create/Update WITHOUT Read (ticking write forces
#    "Read currencies and their rates"), so a true write-only key
#    CANNOT be constructed. The earlier 200-on-trio round is thereby
#    explained as implicit read; the source's read-gate on the GET
#    routes is unreachable for write keys by model design. No finding —
#    a structural constraint of the permission model, documented for
#    completeness. Gate-resolver check FULLY CLOSED: one permission
#    block serves all routes; no type-blindness (the productlots
#    products/produit kin is absent here). RE-RUN (still with forced
#    read): 200/200/200 again — consistent; and the with-rights
#    existence contrast holds: /1 -> 200, /99999999 -> 404 (correct
#    positive/negative behaviour under valid rights).
#
# 3) SERIALISATION JQ PROBES (MCF5 decides):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/multicurrencies?limit=5" \
#        | jq '.[0].id, (.[0].id|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/multicurrencies/1/rates" \
#        | jq '.[0].rate, (.[0].rate|type), .[0].date_sync, (.[0].date_sync|type)'
#    Paste prediction: id STRING, rate STRING, date_sync NUMBER.
#    RESULT-FIELD: PENDING FIRST RUN.

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Currencies are GLOBAL reference data — NO socid/owner probes apply
#   (no thirdparty scoping on this surface, source-sealed). The manual
#   plan replaces the family's socid probes with:
#   1) ENTITY-SCOPING (source-sealed: index() restricts to
#      "t.entity IN (getEntity('multicurrency'))"): multi-entity
#      managed. Cross-entity probe = manual two-instance work —
#      PENDING, non-blocking. Worth one read of
#      multicurrency.class.php::fetch() before any cross-entity
#      session (the API class itself never re-checks entity after
#      fetch).
#   2) FILTER-LEAKAGE: sqlfilters guard — a filtered list must never
#      return rows outside the filter (tostring on BOTH sides — the
#      shipments string-vs-number trap):
#        export DOLIHOST=localhost:8080
#        export CID=<a runtime-captured currency rowid>
#        curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#          "http://${DOLIHOST}/api/index.php/multicurrencies?sqlfilters=(t.rowid%3A%3D%3A%27${CID}%27)&limit=100" \
#          | jq '[.[] | select((.id|tostring) != (CID|tostring))] | length'
#      Expected 0. Nonzero = leakage -> BUG issue (phase 2).
#      RESULT-FIELD: OBSERVED 2026-11-27, commit 41b713fa — jq guard
#      (with --arg cid, correcting the first attempt's quoting error)
#      returned 0 foreign rows. NO leakage. CLEAN, closed.
#   3) BY-CODE UNIQUENESS: bycode/<code> must serve ONLY the requested
#      currency:
#        export DCODE=<runtime-captured code>
#        curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#          "http://${DOLIHOST}/api/index.php/multicurrencies/bycode/${DCODE}" \
#          | jq --arg c "${DCODE}" '.code == $c'
#      Expected true. FALSE = wrong currency served -> BUG (phase 2).
#      RESULT-FIELD: OBSERVED 2026-11-27, commit 41b713fa — probe
#      returned TRUE (DCODE=DKK, bycode served exactly that
#      currency). CLEAN, closed.
#   RESULT-FIELDS: PENDING FIRST RUN (all three).

# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Multicurrencies is the FIRST suite whose GET surface includes BOTH a
#   by-value route (/bycode/{code}) AND a subresource (/{id}/rates);
#   the falsy-id camp discipline therefore runs on FOUR routes
#   (index is list-only). Gate-after-fetch ordering is the new
#   family-shape observation (MCF6 pre-registration). Existing tallies
#   (#43307 falsy-id camps, #43386 + #43568 serialization,
#   #43430/#43439 sqlfilterlines breadth, #42952 post-merge footprint,
#   #43515/#43514/#43569 dead code, #43596 gating kin) gain EVIDENCE
#   as COMMENTS on the existing issues — never duplicates. New BUG/
#   WISH Markdown files only in phase 2, on explicit request.
