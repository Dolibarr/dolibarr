# test/hurl/api/holidays/10_holidays.hurl
#
# PLACEMENT NOTE: FLAT FILE in the workdir per contributor instruction
#   (convention since the multicurrencies episode): only ONE file is
#   delivered for phase 1 — 10_holidays.hurl, no directories. The
#   runner reports it as api/holidays/10_holidays.hurl (run.sh's
#   auth-required batch globs */10*.hurl — the filename MUST keep the
#   10_ prefix). Spec-of-record decision: the instance's own
#   swagger.json section is named "/holidays" (explorer paste
#   2026-11-28) — the prompt's /holidays-vs-"holiday-module" tension
#   resolves TRIVIALLY, PAI7-praecedens upheld; no rename needed.
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the holidays API — every GET endpoint of the class
#   Holidays (source: the full class source pasted 2026-11-28; requires
#   htdocs/holiday/class/holiday.class.php; the API class file itself is
#   the module-convention path holiday/class/api_holidays.class.php —
#   HYPOTHESIS honoured, not yet grep-verified on disk: PROMPT-c-lektion
#   pending a find/grep on the instance checkout).
#
# SPEC OF RECORD: the instance's own swagger.json — re-pull at first
#   run, ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/holidays"'
#   Fresh explorer paste (2026-11-28) CONFIRMED the section name
#   "/holidays" with EXACTLY these GET operations; every parameter below
#   agrees between paste and class source (HOL7 sealed by construction).
#
# GET SURFACE (SOURCE-SEALED 2026-11-28; explorer paste agrees on every
#   route and parameter — prompt checklist a)-h) closed as follows):
#     GET /holidays            (index — list leaves)
#     GET /holidays/{id}       (get — a single leave)
#   Write routes exist (POST /holidays, PUT/DELETE /holidays/{id}, and
#   the workflow POSTs /holidays/{id}/{validate|approve|refuse|cancel|
#   reopen}) but are OUT OF SCOPE for this GET-only suite. A POST route
#   DOES exist (unlike paiements) — fixtures MAY be made via the API,
#   though the GUI route (Paid Leave module) remains the documented
#   recipe.
#
#   a) BY-VALUE ROUTES: NONE in the explorer paste AND none in the
#      class source — no /byref/, no /bystatus/{status} route exists
#      (the prompt's plausible by-value hypothesis is REFUTED by both
#      sources). NO by-value section. Workflow status codes live only
#      as the object's status field inside get().
#   b) TABLE ALIAS (productlots lesson): read BEFORE any pin — the
#      alias is the plain family alias **t** ("FROM ...holiday AS t
#      LEFT JOIN ...holiday_extrafields AS ef ... INNER JOIN ...user AS
#      u", source-sealed). NO divergence. The t.* pins below are
#      NORMATIVE, not regression guards. EXTRA alias u (user join) is
#      ALSO filterable via sqlfilters — with a FORBIDDEN-FIELD list
#      (see e/g below), a novel facet of this episode.
#   c) FILTER FLAT: index() exposes EXACTLY: sortfield (default
#      "t.rowid"), sortorder (default 'ASC'), limit (default 100),
#      page (default 0), user_ids (string, {@pattern /^[0-9,]*$/i}),
#      sqlfilters, properties, pagination_data (bool, default false),
#      count (default 0). The prompt's fk_user-filter hypothesis is
#      CONFIRMED as user_ids — a REAL falsy-param-drop target (unlike
#      paiements' empty surface). NO status filter exists (workflow
#      statuses only in sqlfilters).
#   d) SUBRESOURCES: NONE. The multi-day span of a leave is carried as
#      date_debut/date_fin (+halfday) fields on the main object — no
#      lines table, no line route (CONFIRMED both sources). The
#      bankaccounts-era "first line subresource" hypothesis is REFUTED.
#      sqlfilterlines ABSENT — two-probe regression guard kept, family
#      precedent.
#   e) FORBIDDEN-FILTER-FIELDS (NOVEL FACET, source-sealed): index()
#      passes a $forbiddenfilterfields list into
#      forgeSQLFromUniversalSearchCriteria ('pass', 'pass_crypted',
#      'pass_temp', 'api_key', 'openid', 'national_registration_number',
#      'salary', 'salaryextra', 'birth', 'address', 'personal_mobile',
#      'personal_email', 'note_private', 'iplastlogin',
#      'ippreviouslogin' — llx_user sensitive columns, joined via u).
#      sqlfilters naming ANY of them must error BEFORE the query ->
#      RestException 400 via errormessage. Probed below (t.salary).
#      NOVEL probe shape for the family — first suite with a
#      source-sealed FIELD-NAME blacklist on sqlfilters.
#   f) PERMISSION GATES (SOURCE-SEALED 2026-11-28 — the BIG question
#      answered): ONE module key, TWO different consult-shapes:
#      index() allows read OR readall
#        (throws 403 only if BOTH are missing);
#      get() requires hasRight('holiday','read') PLAIN (no readall
#        alternative) -> a readall-without-read key would 403 on
#        /holidays/{id} while listing fine — a divergence facet to
#        probe with LOWKEY-flip keys (C6 section 2b).
#      Gate order: GATE-FIRST on BOTH routes (each opens with the
#      hasRight check, BEFORE any fetch) — holidays is the FOURTH
#      datapoint for the #43620 gate-order kin: multicurrencies
#      ORACLE, bankaccounts CLEAN, paiements CLEAN, holidays CLEAN
#      (source-sealed; LOWKEY probes in C6 confirm at run).
#      get() also runs DolibarrApi::_checkAccessToResource('holiday',
#      ...) AFTER a successful fetch.
#      GUI write-forces-read trait (five-suite streak): holiday is THE
#      candidate to BREAK it — the prompt's own reading: an applicant
#      employee MUST be able to create a leave without holding
#      holiday/read over others. The source AGREES: post() gates on
#      holiday/write (+ hierarchy), NOT on read — the code-level streak
#      breaker is PRE-REGISTERED. GUI-behaviour probe in C6 section 3.
#   g) PHP truthiness lessons, THREE live targets on this surface:
#      (i) user_ids: 'if ($user_ids)' — the STRING "0" is FALSY ->
#        dropped silently (200, full list); the PAI-g distinction:
#        DROP-by-truthiness, NOT a 'IN (0)' comparison. Probe pinned
#        below.
#      (ii) count: 'if ($count)' truthiness — count=2 truthy ->
#        count mode (PAI-g streak).
#      (iii) limit: 'if ($limit)' KIN — limit<=0 skips plimit ->
#        UNBOUNDED list; negative page clamped INSIDE the block;
#        serving loop trims via min($num, ($limit <= 0 ? $num :
#        $limit)). IDENTICAL family idiom to paiements (verbatim
#        if($limit)/plimit($limit+1) shape). sortorder: db->order, no
#        coercion branch visible.
#   h) COPY-PASTE ARTEFACT WATCH: the sqlfilters docblock example
#      "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')" is the
#      verbatim family-wide SO-% SUPPLIER-ORDER flavour (BAF-h/MCF8/
#      PAI8-iii kin). t.ref EXISTS on llx_holiday (HL-refs), so the
#      first clause is expected valid; t.date_creation's existence is
#      the flip-candidate (modern refactor may carry date_creation on
#      llx_holiday; the PAI-precedent column was 'datec' — DECIDED BY
#      THE LIVE PROBE + SCHEMA-SEAL). SCHEMA-SEAL REQUESTED from
#      contributor:
#        SHOW COLUMNS FROM llx_holiday;
#      (phpMyAdmin dump / command paste) — PENDING at first run; the
#      date_creation probe below is pinned provisionally 200 and is
#      the DESIGNATED flip-candidate of this suite (PAI8-iii reading:
#      503 = stale docblock example confirmed AGAIN).
#      Minor artefacts noted (comment-grade): the explorer's get()
#      response model is generic Restler EncoderFunction/fix/
#      separatorChar boilerplate (BAF-h precedent, not a holidays
#      artefact); the class docblock "@access protected" sits ABOVE
#      the class (Restler skeleton flavour); 'UserNotInHierachy'
#      misspelling recurs in four routes (write-side, out of GET
#      scope, phase-2 comment material); the _cleanObjectDatas() block
#      carries copy-pasted unset() families from OTHER modules
#      (cond_reglement, shipping_method_id, barcode_type*, mode_
#      reglement, name/lastname/firstname, state/country*, totalpaid*)
#      — none of which exist on a Holiday object: harmless dead
#      unset()s, #43515/#43514/#43569 dead-code kin, phase-2 material.
#
# EXPLORER PASTE EVIDENCE (pre-run, TREATED AS HYPOTHESIS until run 1):
#   The explorer's OWN curl example for get() hit /holidays/0 and the
#   documented response body was HTTP 404 {"error":{"code":404,
#   "message":"Not Found: Leave not found"}} (dated 2026-11-28,
#   Restler x-powered-by header). If live confirms, get()/0 joins the
#   404-ENVELOPE camp (supplierproposals/tasks/multicurrencies shape)
#   — the fetch(0)-truthy WRONG-ROW anomaly (PAI9, #43634) would be
#   REFUTED for holiday's fetch: NOT the same CommonObject-fetch
#   shape as paiements. /-1 and /-22 pinned by analogy, flipped at run.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; holiday/read right (or holiday/readall) on the key;
#   the holiday (Paid Leave) module enabled and at least ONE leave on
#   the instance (GUI: create via Paid Leave module, or POST /holidays
#   — the route EXISTS). DESIRABLE fixture depth: one leave with a
#   KNOWN owner (fk_user resolvable), one VALIDATED-or-later leave for
#   the workflow-timestamp fields, and (for C6 scoping) a second user
#   OUTSIDE the first user's hierarchy holding their own leave.
#   Gauge BEFORE the first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/holidays?limit=100&count=1"
#   Empty instance ({"count":0}) = MISSING FIXTURE, not a pin failure.
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples: http://${DOLIHOST}/api/index.php/... with
#     DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#   - INSTANS-COMMIT: FIRST GREEN RUN OBSERVED 2026-11-28 — 51/51
#     requests in 2123 ms on
#     **1c819c2bba808a57f4a487b8895fb2aaf01c8675** (HEAD -> develop,
#     origin/develop, origin/HEAD, merge of #43599 "NEW Add
#     MAIN_VATRULE6_CODE and MAIN_VATRULE7_CODE constants...", Laurent
#     Destailleur, 2026-11-28 12:25:12 +0100) — the SAME commit as the
#     paiements episode's green run (same-day suite pair).
#     Pin-history convention:
#     ... -> a6eb6f21 -> 3d7bc9ab -> 41b713fa -> 0235f081 -> 1c819c2b
#     (no advance; holidays ran on the identical HEAD).
#
# PORTABILITY: no hardcoded ids, refs or dates — everything captured
#   at runtime. VERIFY THE ROW SHAPE FIRST with jq before trusting
#   captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/holidays?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/holidays?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   HOL5 pre-state: the STRING-id tally stands at NINE (paiements
#   joined) vs ONE NUMBER (shipments). ANTAG INGEN DEFAULT: the jq
#   probe comes FIRST; all asserts below are TYPE-AGNOSTIC. Holiday
#   dates are the RICHEST yet: date_debut/date_fin (the leave period),
#   date_valid/date_approval/date_refuse/date_cancel (workflow
#   timestamps), plus halfday — probe EVERY numeric-looking field's
#   type before any compare (jq probes in C6 section 4). No asserts on
#   free text — only status codes, ids, codes and shapes.
#
# FINDINGS CATALOGUED (HOL = Holiday Finding; catalogue prefix
#   pre-allocated; OBSERVED items carry their date):
#   HOL1: id=0-camp (per route; pin-historik TVINGENDE). get()/0:
#     PIN HISTORY: (i) BY-PASTE 2026-11-28 — the explorer's own curl
#     example documents 404 "Leave not found" for /0; (ii) confirmed or
#     flipped at run 1 (marked OBSERVED). Camps: 404-envelope
#     (paste-favoured), 200-POPULATED-WRONG-ROW (PAI9-kin hypothesis —
#     would widen the fetch(0)-anomaly #43634 range), 200-all-null
#     SPECIMEN, 400-guard. The WHOLE non-positive family (/0, /-1,
#     /-22) probed per PAI9 discipline; far-out /99999999 -> 404
#     (fetch falsy, source-sealed).
#   HOL2 (BY-ANALOGY, streak ten suites): strict 400 on BOUND typed
#     params (limit, page, count, path id, pagination_data bool) vs
#     SILENT passthrough on UNBOUND (unknown params, sqlfilterlines).
#     CHOICE-ANNOTATION WATCH (bankaccounts lesson #43637-kin): NO
#     {@choice} annotation exists in the class — but user_ids carries
#     a {@pattern /^[0-9,]*$/i} annotation: the REGEX-sibling of the
#     choice-watch. If pattern binding 400s junk, it is binding-bound
#     (probe below); if it silently passes, NEW facet (pattern-ignored
#     variant of #43637-kin).
#   HOL3 (SOURCE-SEALED): user_ids is the falsy-param-drop target —
#     '0' dropped by TRUTHINESS (source 'if ($user_ids)'), NOT by
#     comparison; 'abc' decided by the {@pattern} binding (HOL2).
#     Unlike paiements (PAI3 EMPTY surface), this surface HAS an
#     id-form filter — the 0/abc matrix applies.
#   HOL4 (SOURCE-SEALED): limit idiom = if($limit) KIN verbatim
#     (limit<=0 -> UNBOUNDED; negative page clamped; +1 overshoot
#     trimmed). count = "@since 25.0.0 Parameter count" (docblock),
#     returned AFTER sqlfilters validation and BEFORE order()/plimit()
#     — validation-wins-over-count; limit/page/sortfield all ignored
#     in count mode. pagination_data IS a DECLARED bool parameter
#     here (unlike paiements where it was unbound): TRUE reshapes the
#     payload into {data:[], pagination:{total,page,page_count,limit}}
#     — the #42952 probe takes the PARAMETERISED shape; with limit=0
#     the source pins page_count==1 ('($limit > 0 ? ceil : 1)').
#   HOL5: serialisation (id AND the date/halfday fields — the
#     quadrant: id/value, computed/stored, main-object (no
#     subresource), specimen/real). Fixture jq round decides; targets
#     #43307 (tenth STRING member?) + #43386/#43568 (mixed-type map).
#   HOL6: gate order — GATE-FIRST on both routes predicted (fourth
#     #43620-kin datapoint); INDEX-vs-GET gate divergence (read OR
#     readall vs read-only) is a holidays-NOVEL facet; the
#     write-does-not-force-read reading is the STREAK-BREAKER
#     candidate (sixth suite datapoint, C6 section 3).
#   HOL7: spec-of-record lesson upheld by construction — the fresh
#     explorer paste CONFIRMED the section name /holidays (no
#     divergence note needed). Closes at first green run.
#   HOL9 (NEW 2026-11-28, OBSERVED): FORBIDDEN-CLAUSE SILENT STRIP —
#     the $forbiddenfilterfields list does NOT yield the source-suggested
#     RestException(400); naming a banned column (t.salary, u.salary,
#     u.pass — correct := operator) returns 200 while unknown columns
#     503, so banned clauses are dropped SILENTLY inside
#     forgeSQLFromUniversalSearchCriteria (strip confirmed by column-
#     existence contrast: u.pass exists nowhere on the query surface).
#     The anti-leak ban works, but WITHOUT an error signal — phase-2
#     material (WISH/BUG candidate: silent drop vs 400, security
#     observability). Probe 8 adds the strip-vs-execute pair.
#   HOL8: by-value routes NONE (refuted, both sources); dead unset()
#     families in _cleanObjectDatas (dead-code kin); docblock SO-%
#     artefact + t.date_creation flip-candidate; UserNotInHierachy
#     misspelling (write-side); schema-seal PENDING
#     (SHOW COLUMNS FROM llx_holiday).

# ---------------------------------------------------------------- GET /holidays (index)

# Positive: list, sort on the SOURCE-SEALED alias t, capture the first
#   row id for the get() round-trips. REQUIRES the fixture (>=1 leave).
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
holiday_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# HOL5 companion (type-agnostic asserts; the MANUAL jq probe in the C6
#   section decides string-vs-number offline): shape only.
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sortorder idiom (source-sealed: db->order, no coercion branch):
#   lowercase "desc" — expected 200. A 400/503 would REFUTE the
#   source-sealed idiom.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Unknown sortorder value: db->order's own sanitation decides. 200-with-
#   list is the primary expectation; 400/503 = NEW finding (HOL2 cand.).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a holiday-specific column (ref — HL-refs exist on llx_holiday)
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# HOL family pin: unknown sortfield -> query error -> RestException(503)
#   ("Unknown column ... in 'ORDER BY'"). Legal alias, bogus column.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# HOL4 (source-sealed clamp inside if($limit)): negative page -> clamped
#   to 0, no 500.
GET http://{{hostnport}}/api/index.php/holidays?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# HOL4 (source-sealed if($limit) idiom): negative limit SKIPS plimit ->
#   the UNBOUNDED list is served; 200 either way on a small fixture,
#   empty-vs-full is data-dependent, NOT asserted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL2: non-numeric limit (typed int) -> 400. Bound-param streak probe.
GET http://{{hostnport}}/api/index.php/holidays?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# HOL2: non-numeric page (typed int) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/holidays?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# HOL4 (pagination_data IS DECLARED here — bool typed): pagination_data
#   true reshapes the payload to {data:[...], pagination:{...}}.
#   Assert the ENVELOPE SHAPE (data + pagination), not values.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?limit=2&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" isCollection
jsonpath "$.pagination" exists
jsonpath "$.pagination.total" exists

# HOL4: pagination_data=false -> the plain list shape (no envelope)
GET http://{{hostnport}}/api/index.php/holidays?limit=2&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL2: pagination_data junk (typed bool) -> 400. Bound-param streak.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?limit=2&pagination_data=junk
HTTP 400
[Asserts]
jsonpath "$.error" exists

# HOL4 (#42952 pattern, PARAMETERISED shape here): limit=0 skips plimit
#   AND pagination_data is declared — 200 with the envelope; the source
#   pins page_count==1 when limit<=0 ('($limit > 0 ? ceil(...) : 1)').
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" isCollection
jsonpath "$.pagination" exists
jsonpath "$.pagination.page_count" == 1

# Unbound unknown parameter: silent passthrough expected (HOL2 streak)
GET http://{{hostnport}}/api/index.php/holidays?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: user_ids filter (HOL3, source-sealed falsy-drop target)

# Valid multi-user filter: pattern-clean digits+commas -> 200
GET http://{{hostnport}}/api/index.php/holidays?user_ids=1,2,3&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# HOL3 (source-sealed TRUTHINESS drop: 'if ($user_ids)', the string "0"
#   is FALSY in PHP): user_ids=0 is DROPPED silently — the FULL list is
#   served, NOT an IN (0) filter. 200 with whatever the fixture holds.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?user_ids=0&limit=100
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL2/choice-watch REGEX-SIBLING ({@pattern /^[0-9,]*$/i}): junk must
#   be rejected at BINDING time -> 400. If 200 instead, the pattern is
#   silently ignored — NEW #43637-kin facet (pattern-ignored variant).
# PIN HISTORY: (i) 2026-11-28 pinned 400 (pattern-enforced hypothesis);
#   (ii) OBSERVED/flip at run 1.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?user_ids=abc&limit=5
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: count mode (HOL4, source-sealed @since 25.0.0)

# count=1: {"count": <n>} — number, no list payload
GET http://{{hostnport}}/api/index.php/holidays?count=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# HOL4 (source-sealed truthiness gate 'if ($count)'): count=2 is also
#   truthy -> STILL count mode ({"count": n}, not a list of 2).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?count=2
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count with a filter: count mode honours sqlfilters (validated BEFORE
#   the count branch, source-sealed)
GET http://{{hostnport}}/api/index.php/holidays?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" exists

# HOL4 (validation-wins-over-count, source-sealed order): sqlfilters
#   syntax errors 400 BEFORE the count branch is reached
GET http://{{hostnport}}/api/index.php/holidays?count=1&sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# HOL4 (source-sealed: count returns BEFORE order()/plimit()): an
#   UNKNOWN sortfield must be IGNORED in count mode -> 200. Decisive
#   count-mode ordering probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?count=1&sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count + limit/page: both IGNORED in count mode (source-sealed AND the
#   explorer docs verbatim) -> 200 {"count": n}
GET http://{{hostnport}}/api/index.php/holidays?count=1&limit=5&page=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# HOL2: limit stays typed (int) even in count mode — junk -> 400
GET http://{{hostnport}}/api/index.php/holidays?count=1&limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# HOL2: count itself is typed (long) — junk -> 400
GET http://{{hostnport}}/api/index.php/holidays?count=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: documented filter surface (sqlfilters, properties)

# sqlfilters — always-true filter on the source-sealed alias t
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: the if($sqlfilters) guard skips parsing,
#   list still served
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE on the holiday-specific column ref, matching
#   nothing: still a valid collection (shape-only; no value pinned)
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-REF%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL family pin: unknown (legal-alias) column -> query error -> 503
#   ("Unknown column ... in 'WHERE'").
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# HOL8 cross-probe (docblock-example syntax): the docblock's OWN first
#   clause (t.ref LIKE 'SO-%') — t.ref EXISTS on llx_holiday, so this
#   must behave like any syntactically-valid filter (200, likely empty
#   collection). The SO-% flavour is supplier-order material (family
#   artefact, comment-grade).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.ref%3Alike%3A%27SO-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL8 DESIGNATED FLIP-CANDIDATE: the docblock example's second clause
#   names t.date_creation. PIN HISTORY: (i) 2026-11-28 pinned
#   optimistically as 200 (column-exists hypothesis — modern holiday
#   refactor may carry date_creation); (ii) OBSERVED/flip at run 1:
#   503 means the MCF8-/PAI8-iii artefact is confirmed AGAIN (stale
#   docblock naming a non-existent column) — then SCHEMA-SEAL with
#   SHOW COLUMNS FROM llx_holiday decides the real name (PAI
#   precedent: datec).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.date_creation%3A%3E%3A%2720160101%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# HOL-e / HOL9: the source-sealed FORBIDDEN-FIELD blacklist
#   (u.*-sensitive columns) does NOT throw 400 — it STRIPS the
#   forbidden clause SILENTLY. PIN HISTORY: (i) 2026-11-28 pinned 400
#   (source-read hypothesis: errormessage -> RestException(400));
#   (ii) OBSERVED 2026-11-28 run 1: 200 on (t.salary:=:'100'); (iii)
#   OBSERVED same day (manual round, admin key, correct := operator):
#   t.salary -> 200, u.salary -> 200, u.pass -> 200 — while the
#   control (t.rowid:=:'1') also 200s AND unknown columns 503 (the
#   t.nosuchcolumn pin went GREEN in the same run). Since u.pass has
#   NO column on the query surface yet still 200s, the clause never
#   reaches the query: forgeSQLFromUniversalSearchCriteria drops
#   forbidden clauses SILENTLY — the ban WORKS but without the 400
#   error signal the docblock/source reading predicted. NEW facet,
#   first in family. Decisive strip-vs-execute probe + RESULT-FIELD
#   in the C6 section (probe 8).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.salary%3A%3D%3A%27100%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SOURCE-SEALED (forgeSQLFromUniversalSearchCriteria errormessage ->
#   RestException(400)): invalid sqlfilters syntax -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.rowid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/holidays?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — restrict to a property name; restricted list served
GET http://{{hostnport}}/api/index.php/holidays?properties=id&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — junk property name: _filterObjectProperties ignores
#   unknown names; list served unchanged, 200 (family behaviour).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?properties=nosuchproperty&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — HOL4 (source-sealed: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-sealed; a leave
#   HAS no line table on this API — the multi-day span is carried as
#   date_debut/date_fin on the main object). Expected silent ignore
#   (#43430/#43439 family). Two-probe regression guards kept, family
#   precedent.
# Guard 1: valid-form sqlfilterlines must be silently ignored (200
GET http://{{hostnport}}/api/index.php/holidays?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2
# Guard 2 (decisive, #43388 vs #43430/#43439): BAD syntax -> 200 means
#   silently ignored (expected, parameter absent); 400 means BOUND.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays?sqlfilterlines=(t.rowid%20%3E%3A%20%27x%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# ---------------------------------------------------------------- Combined smoke (all documented filters at once)
GET http://{{hostnport}}/api/index.php/holidays?sortfield=t.rowid&sortorder=DESC&limit=3&page=0&user_ids=1,2,3&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id,ref,fk_user,date_debut,date_fin
HTTP 200
[Asserts]
jsonpath "$" count <= 3

# Count-mode combined smoke (count + filter + sort all — sort ignored)
GET http://{{hostnport}}/api/index.php/holidays?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&sortfield=t.rowid&sortorder=DESC
HTTP 200
[Asserts]
jsonpath "$.count" exists

# ---------------------------------------------------------------- GET /holidays/{id}

# Positive round-trip: the captured list id must resolve (200)
GET http://{{hostnport}}/api/index.php/holidays/{{holiday_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# Family pin: far-out id -> fetch returns falsy -> RestException(404)
#   ("Leave not found")
GET http://{{hostnport}}/api/index.php/holidays/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# HOL2: non-numeric path id (typed long) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/holidays/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# HOL1: id=0. PIN HISTORY: (i) 2026-11-28 pinned 404 BY-PASTE — the
#   explorer's OWN curl example documents 404 "Leave not found" for
#   /holidays/0; (ii) OBSERVED/flip at run 1. A 200 here would widen
#   the fetch(0)-anomaly (#43634) into holiday's fetch — wrong-row
#   shape NEVER to be body-asserted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# HOL1: negative family, /-1 — pinned by 404-envelope analogy (the
#   paste favours that camp); OBSERVED/flip at run 1. NEVER assert the
#   body: a wrong-row answer (PAI9 shape) is body-silent by design.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# HOL1: negative family, /-22 — same camp pin as /-1 (PAI9 family
#   discipline: probe the WHOLE non-positive family).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/holidays/-22
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Unbound unknown parameter on get(): silent passthrough expected
GET http://{{hostnport}}/api/index.php/holidays/{{holiday_id}}?nosuchparameter=1
HTTP 200
[Asserts]
jsonpath "$.id" exists

# ---------------------------------------------------------------- C6 MANUAL PROBES (shell-agnostic, PENDING until run)
# All manual probes: POSIX loop, export on its own line, NO
#   placeholders in commands; jq --arg for variable interpolation —
#   never a bare variable pipeline inside the jq string. curl with
#   http://${DOLIHOST}/api/index.php/... — never a hardcoded host.
#   Never {{lowkey}} in this file; LOWKEY only via manual curl.
#
# 1) ZERO-RIGHTS MATRIX (LOWKEY) — covers BOTH GET gates (index and
#    get). HOL6 predicts GATE-FIRST on both routes: a zero-rights key
#    gets 403 EVERYWHERE — including /99999999 and /0 (no fetch-first
#    oracle possible). ANY non-403 answer (esp. a 404 on a far-out id,
#    or a 200 on /0) would instantiate the #43620 oracle pattern —
#    BOTH facets pre-registered as BUG candidates.
#      export LOWKEY=<zero-rights-key>
#      export DOLIHOST=localhost:8080
#      for p in "?limit=1" "/99999999" "/0" "/abc" \
#        "?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=1" \
#        "?limit=0&pagination_data=true"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#          "http://${DOLIHOST}/api/index.php/holidays${p}"; done
#    EXPECTED (HOL6, source-sealed): 403 on ALL six probes (uniform
#    deny — the anti-oracle shape; /abc may 400 under binding-first,
#    the BAF6(i) boejelige detalje). RESULT-FIELD: OBSERVED 2026-11-28
#    (manual round, zero-rights key): 403 on ?limit=1, /99999999, /0,
#    sqlfilterlines-guard, pagination_data-probe — CONFIRMED GATE-FIRST,
#    the #43620 fetch-first oracle is ABSENT live (HOL6 closed, FOURTH
#    datapoint: multicurrencies ORACLE, bankaccounts CLEAN, paiements
#    CLEAN, holidays CLEAN). ONE divergence facet: /abc -> 400 "Bad
#    Request: invalid value specified for `id`" under the zero-rights
#    key — BINDING-BEFORE-GATE, the bankaccounts BAF6(i)/paiements
#    facet verbatim (typed path-id validator before the gate; benign,
#    no existence leak). Binding -> gate -> fetch, the family reading
#    holds.
#
# 2) LOWKEY-FLIP READ (gate-resolver + the INDEX/GET divergence): grant
#    the SAME user holiday/READALL WITHOUT holiday/read (the GUI
#    should permit it — readall is an admin-ish right), then probe:
#      export RAKEKEY=<readall-no-read-key>
#      for p in "?limit=1" "/<a runtime-captured holiday id>"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${RAKEKEY}" \
#          "http://${DOLIHOST}/api/index.php/holidays${p}"; done
#    EXPECTED (source-sealed): the LIST 200s (index allows read OR
#    readall) while /{id} 403s (get() demands read PLAIN) — a
#    holidays-novel gate divergence facet. If BOTH 200, the GUI forced
#    read onto the grant (fifth/sixth-suite trait); if BOTH 403, the
#    source reading was wrong — flip.
#    RESULT-FIELD: CLOSED 2026-11-28, N/A-BY-DESIGN (contributor GUI
#    check): the GUI CANNOT grant readall without read — checking
#    "Read all leave requests" forces "Read leave requests" and
#    removing one removes the other (coupled checkboxes). The
#    readall-without-read key is UNCONSTRUCTIBLE via the GUI; the
#    index-vs-get gate divergence remains SOURCE-ONLY theory. Suite
#    datapoint: SIXTH suite with GUI-coupled read rights — but the
#    coupling shape is NOVEL (readall->read pairwise, not write->read).
#
# 3) LOWKEY-FLIP WRITE (the STREAK-BREAKER candidate): attempt a key
#    with holiday/write but NO holiday/read (the prompt's candidate to
#    break the five-suite GUI-forces-read streak; the SOURCE agrees —
#    post() gates on holiday/write + hierarchy, never on read):
#      export WKEY=<write-only-key>
#      for p in "?limit=1" "/1"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${WKEY}" \
#          "http://${DOLIHOST}/api/index.php/holidays${p}"; done
#    EXPECTED per source: if the GUI grants write-without-read, GETs
#    403 (no read right) — the STREAK BREAKS (sixth datapoint,
#    first write-yes-read-no surface). RESULT-FIELD: CLOSED
#    2026-11-28, N/A-BY-DESIGN (contributor GUI check): "nej, det kan
#    man ikke" — the GUI REFUSES to grant holiday/write without
#    holiday/read; the write-without-read key is UNCONSTRUCTIBLE.
#    The SIX-suite GUI-forces-read streak SURVIVES its strongest
#    challenger (the prompt's own candidate: an applicant employee
#    who should be able to write a leave without read). Noteworthy
#    divergence: SOURCE and GUI DISAGREE in intent — the code would
#    allow write-without-read (post() gates on holiday/write +
#    hierarchy only), the GUI forbids constructing the key. Trait
#    datapoint: SIXTH consecutive suite. Phase-2 comment material.
#
# 4) EXTERNAL-USER SCOPING (source-sealed: index() scopes by
#    getEntity('holiday') +, WITHOUT holiday/readall, by
#    "t.fk_user IN (getAllChildIds(1))" — hierarchy scoping; get()
#    gates on read then _checkAccessToResource AFTER fetch):
#      export EKEY=<external-user-key>
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?limit=10"
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays/<a foreign-user id>"
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays/99999999"
#    EXPECTED: list shows ONLY the user's own hierarchy's leaves
#    (possibly empty — empty scoping is ALSO an answer, PAI-C6
#    discipline); a foreign leave -> 403 "Access not allowed..." (the
#    _checkAccessToResource message) vs 404 on /99999999 — the
#    fetch-404 runs BEFORE the access check, so external keys CAN
#    distinguish existing-from-nonexistent ids (existence oracle,
#    #43620-kin datapoint). RESULT-FIELD: OBSERVED 2026-11-28 (second
#    manual round, external user "externaljon" granted plain
#    holiday/READ only): ALL THREE facets CONFIRMED LIVE:
#    (i) scoping LIST: ?limit=10 -> 200 with EMPTY ARRAY [] — the
#    external user has no subordinates, so the getAllChildIds
#    hierarchy scope yields NOTHING (empty scoping IS the answer,
#    PAI-C6 discipline); no foreign row leaks;
#    (ii) FOREIGN leave: /1 (owner user 2, no hierarchy link) -> 403
#    "Forbidden: Access not allowed for login externaljon" — the
#    _checkAccessToResource check fires with its own message AFTER
#    the read gate passed (read held, ownership failed);
#    (iii) EXISTENCE ORACLE CONFIRMED: /99999999 -> 404 "Not Found:
#    Leave not found" — the fetch-404 runs BEFORE the ownership
#    check, so this key CAN distinguish existing-from-nonexistent
#    ids (the same mild, benign oracle paiements showed for granted
#    external users on a gate-first class). Probe round COMPLETE.
#
# 5) SERIALISATION JQ PROBES (HOL5 decides):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?limit=5" \
#        | jq '.[0].id, (.[0].id|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays/<id>" \
#        | jq '.id, (.id|type), .date_debut, (.date_debut|type), \
#              .date_fin, (.date_fin|type), .date_valid, \
#              (.date_valid|type), .halfday, (.halfday|type), \
#              .status, (.status|type)'
#    Paste prediction: UNKNOWN (no row pasted yet) — the jq probes
#    decide; expect the RICHEST date map of the series (six date-ish
#    fields + halfday + status). Tally targets: #43307 (STRING vs
#    NUMBER member #10), #43386 + #43568 (mixed-type quadrant).
#    RESULT-FIELD: OBSERVED 2026-11-28 (manual jq round, admin key):
#    list .[0].id = 1 NUMBER and get() id 1 NUMBER — holidays is the
#    SECOND NUMBER member of the id tally (#43307: nine STRING vs TWO
#    NUMBER — shipments, holidays; the fetch(0) wrong-row family and
#    the string-id family DISAGREE on this surface: NUMBER id + 404
#    envelope on /0, the cleanest route shape of the series).
#    Date quadrant: date_debut 1795824000 NUMBER; halfday 0 NUMBER;
#    status 1 NUMBER; BUT date_valid "" EMPTY STRING and date_
#    approval "" EMPTY STRING — NOT null, NOT 0, a NOVEL shape for
#    the series (paiements' unfilled fields were null): workflow
#    timestamps render as EMPTY STRINGS when unset. Mixed-type in
#    the same object confirmed (NUMBERs + empty-STRING dates) —
#    #43386/#43568 kin with a new facet. Phase-2 comment material.
#
# 6) USER_IDS LEAKAGE GUARD (tostring on BOTH sides — the shipments
#    string-vs-number trap):
#      export DOLIHOST=localhost:8080
#      export UID=<a user id holding a leave>
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?user_ids=${UID}&limit=100" \
#        | jq --arg uid "${UID}" '[.[] | select((.fk_user|tostring) != $uid)] | length'
#    Expected 0. Nonzero = leakage -> BUG issue (phase 2).
#    RESULT-FIELD: OBSERVED 2026-11-28 (manual round, admin key) —
#    probe INVALID as first run: ${UID} was exported AFTER the call
#    (empty string), so the jq --arg compared fk_user against ""
#    (row 2 leaked by CONSTRUCTION of the probe, not by the API).
#    Re-run with the export BEFORE the call (user_ids=2 + uid 2 ->
#    expected 0). OBSERVED same round, corrected shape: user_ids=4
#    (a user holding NO leave) returned 0 rows — the filter itself
#    works and the length-0 answer on an unrelated uid corroborates
#    NO leakage. The user_ids=2 re-run remains the decisive pair.
#      export UID=2   # BEFORE the curl line
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?user_ids=2&limit=100" \
#        | jq --arg uid "2" '[.[] | select((.fk_user|tostring) != $uid)] | length'
#    Decisive pair COMPLETED same round: user_ids=2 with the export
#    BEFORE the call -> length 0 — NO foreign rows. Combined with
#    user_ids=4 -> 0 rows (filter works on an unrelated uid), the
#    user_ids filter shows NO leakage. CLEAN, closed. (First-round
#    "leak" was the probe's own empty-variable artifact, recorded
#    above as the CID lesson.)
#
# 8) FORBIDDEN-CLAUSE STRIP VS EXECUTE (HOL9 decides): if the blacklist
#    DROPS the clause silently, both probes return the SAME row count
#    (the filter is absent from the query); if the clause EXECUTES,
#    LIKE '%' matches all rows and LIKE 'ZZZ...' matches none.
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?sqlfilters=(u.pass%3Alike%3A%27%25%27)&limit=100" \
#        | jq 'length'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/holidays?sqlfilters=(u.pass%3Alike%3A%27ZZZ-NONE%27)&limit=100" \
#        | jq 'length'
#    RESULT-FIELD: OBSERVED 2026-11-28 (manual round, admin key) —
#    BOTH probes returned length 1 (the instance holds exactly one
#    leave): identical counts on LIKE '%' vs LIKE 'ZZZ-NONE' prove
#    the forbidden clause NEVER reaches the query — the SILENT STRIP
#    is CONFIRMED. HOL9 closed: the ban works (no filtering on u.pass
#    is possible) but with NO error signal. Phase-2 WISH candidate
#    (400 would be more observable than silent drop).
#
# 9) SOURCE-READ CHECKLIST — CLOSED 2026-11-28 (full class source
#    pasted and analysed; class Holidays):
#    (i) gate keys + order: index() = read OR readall GATE-FIRST;
#        get() = read PLAIN GATE-FIRST; write = holiday/write (post,
#        validate, cancel, reopen); approve/refuse = holiday/approve;
#        delete = holiday/delete — HOL6 predicted (clean family);
#    (ii) table alias: **t** (FROM holiday AS t, LEFT JOIN
#        holiday_extrafields AS ef, INNER JOIN user AS u) — t.* pins
#        NORMATIVE; u.* filterable EXCEPT the forbidden list;
#    (iii) id-form filter: user_ids CONFIRMED (falsy-drop target);
#    (iv) limit idiom: if($limit) + plimit($limit+1) + inner page
#        clamp — HOL4 closed (family KIN verbatim);
#    (v) @since tags: index "@since 23.0.0" + "@since 25.0.0 Parameter
#        count"; NO {@choice} annotations; {@pattern} on user_ids
#        (HOL2 watch);
#    (vi) copy-paste artefacts: SO-% docblock example (t.date_creation
#        flip-candidate); dead unset() families in
#        _cleanObjectDatas; UserNotInHierachy misspelling; "Insufficiant
#        rights" misspelling (post/validate/approve, write-side);
#        explorer get() model = Restler boilerplate — all HOL8,
#        phase-2 material;
#    (vii) unlisted by-value routes: NONE in source or swagger —
#        HOL8 closed NEGATIVE.
#
# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Leaves tie to USERS (fk_user; INNER JOIN llx_user u) — NOT to
#   thirdparties. NO socid analogue exists; paiements'
#   external-socid round has NO direct transplant. Scoping design
#   (FROM THE SOURCE, not from analogy):
#   1) ENTITY: "t.entity IN (getEntity('holiday'))" — cross-entity
#      probe = manual two-instance work, PENDING, non-blocking.
#   2) HIERARCHY: without holiday/readall, index() adds
#      "AND t.fk_user IN (childids)" — the C6 section 4 probe; an
#      external key with zero children sees an EMPTY list (empty
#      scoping = an answer).
#   3) FILTER-LEAKAGE: user_ids equality guarded by C6 section 6
#      (fk_user tostring both sides); sqlfilters rowid equality
#      inherits the family guard shape if needed later.
#   4) WORKFLOW: status transitions live on the write routes (out of
#      GET scope); the GET surface exposes status/date_valid/
#      date_approval/date_refuse/date_cancel as FIELDS only (HOL5).
#   RESULT-FIELDS: PENDING.

# ---------------------------------------------------------------- FAMILY STATUS AT OPENING
# Holidays OPENS as the richest filter surface of the series (a real
#   id-form filter with a {@pattern} annotation — a family FIRST),
#   carries the first source-sealed FORBIDDEN-FIELD sqlfilters
#   blacklist, and is the pre-registered STREAK-BREAKER candidate for
#   the GUI-forces-read trait (write gates on holiday/write, never on
#   read — the code agrees with the prompt's expectation). Existing
#   tallies gain EVIDENCE as COMMENTS on the existing issues (#43307,
#   #43386, #43568, #43430/#43439, #42952, #43515/#43514/#43569,
#   #43596, #43620, #43634, #43637) — never duplicates. New BUG/WISH
#   Markdown files only in phase 2, on explicit request.
