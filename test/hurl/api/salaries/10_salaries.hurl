# test/hurl/api/salaries/10_salaries.hurl
#
# PLACEMENT NOTE: FLAT FILE, single phase-1 deliverable; runner globs
#   */10*.hurl (10_ prefix MANDATORY). SPEC OF RECORD: instance
#   swagger.json, section name **/salaries** (explorer paste
#   2026-11-28 CONFIRMED — prompt's /salaries-vs-module tension resolves
#   trivially, PAI7/HOL7 precedent upheld; no rename needed).
#
# ATTRIBUTION: generated with the help of Lumo AI (Proton), reviewed by
#   human contributors.
#
# GET-only tests for the salaries API (Salary class, llx_salary).
#   Source: htdocs/salaries/class/api_salaries.class.php (paste
#   2026-11-28 — docblock \file line is CORRECT here, no PAI8-ii path
#   lie). GET SURFACE (source-sealed):
#     GET /salaries              index(sortfield,sortorder,limit,page,
#                                  sqlfilters,count @since 25.0.0)
#     GET /salaries/{id}         get(id)
#     GET /salaries/payments     getAllPayments(sortfield,sortorder,
#                                  limit,page) — NO count, NO sqlfilters
#     GET /salaries/payments/{pid}  getPayments(pid)
#   Write routes exist (POST /salaries, PUT, POST/PUT {id}/payments)
#   but are OUT OF SCOPE (GET-only suite).
#
#   a) BY-VALUE ROUTES: NONE (both sources). No /byuser, no /byref.
#   b) TABLE ALIAS: **t** ("FROM <prefix>salary as t", source-sealed).
#      t.* pins NORMATIVE. No ef/u joins on index().
#   c) FILTER FLAT: index() exposes EXACTLY sortfield/sortorder/limit/
#      page/sqlfilters/count. NO user_ids, NO properties, NO
#      pagination_data (declared params ONLY). fk_user scoping is
#      DERIVED (readchild/self), NOT query-addressable — falsy-drop
#      0/abc matrix has NO id-form target (PAI3-empty precedent).
#   d) SUBRESOURCES: /payments list + /payments/{pid} ARE the
#      subresources of THIS api class (payment_salary table, alias t
#      joined to s=salary). sqlfilterlines ABSENT — two-probe guard.
#   e) count PRESENT on index only (@since 25.0.0; AFTER sqlfilters
#      validation, BEFORE order/plimit). pagination_data ABSENT
#      (unbound silent passthrough). No {@pattern}/{@choice} anywhere.
#   f) PERMISSION GATES (source-sealed): index/get/payments-list gate
#      on salaries/read OR readchild OR readall; getPayments(/payments/
#      {pid}) requires salaries/readall ONLY (NOVEL: stricter gate on
#      the single-item route than on the list!). Write =
#      salaries/write. GATE-FIRST on all four routes — FIFTH datapoint
#      for #43620 kin (multicurrencies ORACLE, bankaccounts/paiements/
#      holidays CLEAN). Entity scoping: index uses getEntity('user'),
#      getAllPayments uses getEntity('salary') — INCONSISTENCY, SAL8.
#   g) TRUTHINESS: 'if ($count)' live on index (count=2 truthy ->
#      count mode). 'if ($limit)' if($limit) KIN verbatim: limit<=0
#      skips plimit -> UNBOUNDED; negative page clamped inside.
#   h) COPY-PASTE ARTEFACT WATCH (SAL8): NO forbiddenfilterfields
#      list on index() — the holidays silent-strip mechanism is
#      ABSENT on its OWN sensitive surface (salary amounts!): probing
#      (t.salary:=:'x') must EXECUTE (unknown-col-free, 200/503
#      decides) — strip-vs-execute pair in C6. BROKEN PARENTHESIS in
#      getAllPayments(): the plain-read branch appends
#      "AND s.fk_user = N)" — stray ')' -> SQL syntax error -> 503
#      for ANY key holding read but NOT readall/readchild. POSTED AS
#      BUG: GitHub issue #43666 (2026-11-28; live confirmation still
#      pending, scoped key — C6 probe 3).
#      FIELDSPAYMENT docblock typo "mayment" (comment-grade).
#
# EXPLORER PASTE EVIDENCE (2026-11-28, treated as HYPOTHESIS until run):
#   /salaries/0 -> HTTP 200, ALL-NULL SPECIMEN body (id null, every
#   field null, specimen: 0) — salaries joins the 200-all-null camp
#   (productlots/bankaccounts), NOT holidays' 404-envelope, NOT
#   paiements' PAI9 wrong-row. /salaries/payments/0 -> HTTP 200, also
#   all-null BUT datec/datepaye/datep/datev render as "" EMPTY
#   STRINGS (#43661 kin facet on the payment subresource).
#
# Preconditions: {{hostnport}} in env; salaries/read right on the key;
#   >=1 salary record on the instance (GUI: Salaries module, or POST
#   /salaries — route EXISTS; mandatory fields fk_user, label, amount
#   PLUS table-required datesp/dateep — POST-gap FILED AS BUG:
#   GitHub issue #43667, 2026-11-28).
#   Gauge BEFORE first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/salaries?limit=100&count=1"
#   Empty instance {"count":0} = MISSING FIXTURE, not a pin failure.
#
# PORTABILITY: no hardcoded ids; jq row-shape probe FIRST:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/salaries?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   Asserts below TYPE-AGNOSTIC (#43307 tally: nine STRING vs two
#   NUMBER so far). No asserts on free text.
#
# FINDINGS CATALOGUED (SAL = Salary Finding):
#   SAL1: id=0-camp. PIN HISTORY: (i) BY-PASTE 2026-11-28 — /0 -> 200
#     all-null SPECIMEN (200-all-null camp); (ii) OBSERVED 2026-11-28
#     run 1: /0, /-1, /-22 AND far-out /99999999 ALL -> 200 all-null
#     specimen — the 404-envelope AND the wrong-row (PAI9) camps are
#     BOTH refuted on this surface; get() NEVER 404s (no existence
#     signal by status code — FILED AS BUG: GitHub issue #43668,
#     2026-11-28). Subresource payments/{pid} pinned on the
#     same pattern, flip reserved for run 2.
#   SAL2: strict 400 on bound typed params (limit/page/count/path id);
#     silent passthrough on unbound (streak twelve suites).
#   SAL3: DOCUMENTED CHOICE — no id-form filter; scoping derived;
#     empty-filter-surface note (PAI3 precedent).
#   SAL4: limit idiom = if($limit) KIN; count-mode ordering tests
#     (validation-wins-over-count; limit/page/sortfield ignored).
#   SAL5: serialisation — id + datep/datev/amount/salary field types
#     via manual jq (quadrant); payments subresource "" empty-string
#     dates facet (#43661 kin).
#   SAL6: gate order FIFTH datapoint; NOVEL facet: /payments/{pid}
#     gates on readall ONLY while /payments list accepts read OR
#     readall — an internal read-key user can LIST payments but is
#     403 on a SINGLE one (source-sealed; C6 probe 4 confirms).
#   SAL7: spec-of-record — swagger section /salaries CONFIRMED
#     2026-11-28; closes at first green run.
#   SAL8: artefacts/no-mechanism watch — NO forbidden-field list on
#     the salary surface itself (contrast HOL9); broken parenthesis in
#     getAllPayments read-branch (scoped-key 503 — FILED AS BUG:
#     GitHub issue #43666, 2026-11-28); getEntity('user')
#     vs getEntity('salary') inconsistency; docblock "mayment" typo;
#     dead unset() limited to rowid in _cleanObjectDatas (cleaner
#     than holidays' copy-paste families).
# Instans-commit: FIRST GREEN RUN OBSERVED 2026-11-28 — 55/55 requests
#   in 2036 ms on **bfd0813e115f5c2e00dfb4f0130747291eda82d2** (HEAD ->
#   develop, origin/develop, origin/HEAD, merge of 2a7bc22eee7 +
#   1c819c2bba8, Laurent Destailleur, 2026-11-28 17:56:48 +0100).
#   Pin-history convention:
#   ... -> a6eb6f21 -> 3d7bc9ab -> 41b713fa -> 0235f081 -> 1c819c2b
#        -> bfd0813e (advanced; holidays/paiements ran on 1c819c2b).
# RUN HISTORY (2026-11-28): run 1 RED at request 37 — the family pin
#   /salaries/99999999 pinned 404 returned 200 (fixture arrived mid-
#   run; payments fixture created after). Pins flipped per OBSERVED
#   evidence: far-out 99999999 -> 200 all-null specimen (jq round:
#   id null, label null, amount null — 404-envelope AND PAI9 camps
#   BOTH refuted; the route NEVER 404s). Payments subresource pinned
#   on the same specimen pattern. Run 2 GREEN 55/55 in 2036 ms — all
#   flipped pins held; no further flips.

# ---------------------------------------------------------------- GET /salaries (index)

# Positive: list, source-sealed alias t, capture first id.
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
salary_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Sortorder idiom: lowercase desc accepted (db->order, no coercion).
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Unknown sortorder: db->order sanitation decides; 200 primary.
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a salary-specific column (label — real llx_salary column).
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.label&sortorder=ASC&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Family pin: unknown sortfield -> query error -> 503.
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 pagination.
GET http://{{hostnport}}/api/index.php/salaries?page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Far-out page -> empty array, not an error.
GET http://{{hostnport}}/api/index.php/salaries?page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# SAL4: negative page clamped inside if($limit) -> no 500.
GET http://{{hostnport}}/api/index.php/salaries?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# SAL4: limit<=0 skips plimit -> UNBOUNDED; not value-asserted.
GET http://{{hostnport}}/api/index.php/salaries?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SAL2: limit typed int — junk -> 400.
GET http://{{hostnport}}/api/index.php/salaries?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# SAL2: page typed int — junk -> 400.
GET http://{{hostnport}}/api/index.php/salaries?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# SAL4/e): pagination_data ABSENT (unbound) — silent passthrough,
#   plain list expected (the #42952 unparameterised shape).
GET http://{{hostnport}}/api/index.php/salaries?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SAL2: unbound unknown parameter — silent passthrough.
GET http://{{hostnport}}/api/index.php/salaries?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: count mode (SAL4, @since 25.0.0)

# count=1: {"count": n} — not a list.
GET http://{{hostnport}}/api/index.php/salaries?count=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# Source-sealed truthiness 'if ($count)': count=2 is truthy -> STILL
#   count mode, not a list of 2.
GET http://{{hostnport}}/api/index.php/salaries?count=2
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count honours sqlfilters (validated BEFORE the count branch).
GET http://{{hostnport}}/api/index.php/salaries?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" exists

# Validation-wins-over-count: bad syntax 400s before counting.
GET http://{{hostnport}}/api/index.php/salaries?count=1&sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# count returns BEFORE order()/plimit(): unknown sortfield IGNORED -> 200.
GET http://{{hostnport}}/api/index.php/salaries?count=1&sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count + limit/page: both ignored in count mode.
GET http://{{hostnport}}/api/index.php/salaries?count=1&limit=5&page=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# SAL2: limit stays typed in count mode — junk -> 400.
GET http://{{hostnport}}/api/index.php/salaries?count=1&limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# SAL2: count itself typed long — junk -> 400.
GET http://{{hostnport}}/api/index.php/salaries?count=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: sqlfilters surface (SAL8 no-forbidden-list facet)

# Always-true filter on alias t.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# Always-false filter: valid empty collection.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Empty sqlfilters: guard skips parsing, list served.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# LIKE on a salary-specific column, matching nothing.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.label%3Alike%3A%27ZZZ-NO-SUCH-LABEL%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Family pin: unknown legal-alias column -> 503.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Docblock-example clause 1 (fk_user:=:6 — llx_salary HAS fk_user).
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.fk_user%3A%3D%3A%276%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SAL8 DOCBLOCK FLIP-CANDIDATE: docblock example clause 2 names
#   t.datep (>:'20250101'). PIN HISTORY: (i) 2026-11-28 pinned 200
#   (datep exists on llx_salary per explorer-paste fields datep/datev;
#    SCHEMA-SEAL SHOW COLUMNS FROM llx_salary PENDING); (ii)
#   OBSERVED/flip at run 1 — 503 means stale-docblock artefact AGAIN
#   (rename to the sealed column).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.datep%3A%3E%3A%2720250101%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SAL8 strip-vs-execute CONTROL: t.salary is a REAL llx_salary column
#   and this class has NO forbiddenfilterfields list — the clause must
#   EXECUTE (not silently strip, contrast HOL9/#43663).
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.salary%3A%3E%3A%270%27)&limit=100
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SOURCE-SEALED: invalid sqlfilters syntax -> 400.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.rowid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Injection fuzz: quote inside value — never 500.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilters=(t.label%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — SAL4 (parameter ABSENT)
# Guard 1: valid-form must be silently ignored (200).
GET http://{{hostnport}}/api/index.php/salaries?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2
# Guard 2 (decisive, #43388 vs #43430/#43439): bad syntax -> 200 =
#   silently ignored (expected); 400 = bound.
GET http://{{hostnport}}/api/index.php/salaries?sqlfilterlines=(t.rowid%20%3E%3A%20%27x%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# ---------------------------------------------------------------- Combined smoke (all documented filters at once)
GET http://{{hostnport}}/api/index.php/salaries?sortfield=t.rowid&sortorder=DESC&limit=3&page=0&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$" count <= 3

# Count-mode combined smoke (count + filter + sort, sort ignored).
GET http://{{hostnport}}/api/index.php/salaries?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&sortfield=t.rowid&sortorder=DESC
HTTP 200
[Asserts]
jsonpath "$.count" exists

# ---------------------------------------------------------------- GET /salaries/{id}

# Positive round-trip: captured list id resolves.
GET http://{{hostnport}}/api/index.php/salaries/{{salary_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# SAL1 WIDENED (OBSERVED 2026-11-28 run 1): even FAR-OUT /99999999
#   serves the ALL-NULL SPECIMEN — 200, not 404. Combined with the
#   non-positive family, get() NEVER 404s: Salary::fetch() does not
#   reject unknown ids on this surface. NOVEL facet: NO existence
#   oracle, and NO 404-envelope — an API consumer cannot distinguish
#   missing from existing ids by status code alone. Pinned 200 with
#   body-silence (never body-assert the specimen). FILED AS BUG:
#   GitHub issue #43668 (2026-11-28).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/99999999
HTTP 200

# SAL2: non-numeric path id (typed long) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/salaries/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# SAL1: id=0. PIN HISTORY: (i) BY-PASTE 2026-11-28 — 200, ALL-NULL
#   specimen body (id null, specimen 0 — the 200-all-null camp);
#   (ii) OBSERVED/flip at run 1. NEVER body-assert: a wrong-row
#   answer (PAI9 shape) is body-silent by design.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/0
HTTP 200

# SAL1: /-1 — non-positive family discipline; paste favoured 200
#   all-null (fetch(0)-truthy kin); OBSERVED/flip at run 1.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/-1
HTTP 200

# SAL1: /-22 — same family pin as /-1.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/-22
HTTP 200

# Unbound unknown parameter on get(): silent passthrough.
GET http://{{hostnport}}/api/index.php/salaries/{{salary_id}}?nosuchparameter=1
HTTP 200
[Asserts]
jsonpath "$.id" exists

# ---------------------------------------------------------------- GET /salaries/payments (getAllPayments subresource)

# Positive: payments list, capture a payment id. Admin key (has
#   readall) avoids the SAL8 broken-parenthesis read-branch.
GET http://{{hostnport}}/api/index.php/salaries/payments?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
payment_pid: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# SAL8 NOTE: getAllPayments declares ONLY sortfield/sortorder/limit/
#   page (no count, no sqlfilters — both UNBOUND here).
# Unbound count on payments list: silent passthrough expected.
GET http://{{hostnport}}/api/index.php/salaries/payments?count=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unbound sqlfilters on payments list: silent passthrough expected.
#   NOTE: the SQL joins payment_salary t + salary s — a filter on s.*
#   would only fire if BOUND; unbound means ignored.
GET http://{{hostnport}}/api/index.php/salaries/payments?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# SAL2: limit junk on payments list (typed) -> 400.
GET http://{{hostnport}}/api/index.php/salaries/payments?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# SAL2: page junk on payments list -> 400.
GET http://{{hostnport}}/api/index.php/salaries/payments?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# SAL4: negative limit on payments list — same if($limit) KIN.
GET http://{{hostnport}}/api/index.php/salaries/payments?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# SAL2: unbound unknown parameter on payments list.
GET http://{{hostnport}}/api/index.php/salaries/payments?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- GET /salaries/payments/{pid}

# Positive round-trip: captured payments-list id resolves (admin key
#   holds readall — the route's only accepted right).
GET http://{{hostnport}}/api/index.php/salaries/payments/{{payment_pid}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# SAL1 (payments subresource) WIDENED (OBSERVED run 1): family pin —
#   far-out pid behaves like the main route's specimen shape is NOT
#   yet pinned for the subresource; pinned 200 provisionally per the
#   main-route OBSERVED specimen pattern; flip at run 2 if 404.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/payments/99999999
HTTP 200

# SAL2: non-numeric pid (typed long) -> 400.
GET http://{{hostnport}}/api/index.php/salaries/payments/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# SAL1: pid=0. PIN HISTORY: BY-PASTE 2026-11-28 — 200 ALL-NULL
#   specimen (with "" empty-string datec/datepaye/datep/datev —
#   #43661 kin on the subresource). OBSERVED/flip at run 1.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/payments/0
HTTP 200

# SAL1: /-1 payments family pin.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/payments/-1
HTTP 200

# SAL1: /-22 payments family pin.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/salaries/payments/-22
HTTP 200

# ---------------------------------------------------------------- C6 MANUAL PROBES (shell-agnostic, PENDING until run)
# All manual probes: POSIX loop, export on its own line, NO
#   placeholders in commands; jq --arg for interpolation; curl with
#   http://${DOLIHOST}/api/index.php/... — never a hardcoded host.
#   Never {{lowkey}} in this file; LOWKEY only via manual curl.
#
# 1) ZERO-RIGHTS MATRIX (LOWKEY) — covers all four GET gates. SAL6
#    predicts GATE-FIRST everywhere: 403 uniform, incl. /99999999 and
#    /0. Any non-403 (esp. 404/200) instantiates the #43620 oracle —
#    BOTH facets pre-registered as BUG candidates. FIFTH datapoint.
#      export LOWKEY=<zero-rights-key>
#      export DOLIHOST=localhost:8080
#      for p in "?limit=1" "/99999999" "/0" "/abc" \
#        "payments?limit=1" "payments/0" \
#        "?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=1" \
#        "?limit=0&pagination_data=true"; do \
#        printf "probe salaries%s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#          "http://${DOLIHOST}/api/index.php/salaries${p}"; done
#    EXPECTED (SAL6, source-sealed): 403 on ALL eight (/abc may 400
#    under binding-before-gate — the BAF6(i) flexible detail).
#    RESULT-FIELD: PENDING.
#
# 2) READ-KEY FLIP (the getPayments readall-ONLY gate, SAL6 NOVEL
#    facet): grant the SAME user salaries/READ (plain, NO readall) —
#    GUI-check: is a read-without-readall grant constructible? — then:
#      export RKEY=<read-plain-key>
#      for p in "?limit=1" "payments?limit=1" "payments/1" \
#        "/<a runtime-captured salary id>"; do \
#        printf "probe salaries%s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${RKEY}" \
#          "http://${DOLIHOST}/api/index.php/salaries${p}"; done
#    EXPECTED (source-sealed): list AND get(/id) -> 200 IF the user
#    owns the records or holds readchild (self-scoping); payments
#    LIST -> 200 (accepts read) but payments/{pid} -> 403 (demands
#    readall PLAIN) — an internal read-key user can LIST payments yet
#    is denied a SINGLE one. If payments/{pid} 200s, the gate reading
#    was wrong — flip.
#    RESULT-FIELD: PENDING.
#
# 3) BROKEN-PARENTHESIS PROBE (SAL8, source-sealed code defect): the
#    read-but-not-readall/readchild branch of getAllPayments() appends
#    "AND s.fk_user = N)" — a stray right-parenthesis -> SQL syntax
#    error -> 503 for the RKEY of probe 2 (if its user holds NO
#    readchild):
#      export RKEY=<read-plain-key>
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${RKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries/payments?limit=5"
#    EXPECTED (source-sealed): 503 (syntax error in WHERE). A 200
#    means the branch was not entered (readchild also held?) or the
#    code differs from the paste — flip. NOTE: probe 2's payments
#    EXPECTED value above presumes a readchild-holding user; THIS
#    probe 3 (read-childless key) decides the 503.
#    RESULT-FIELD: PENDING. FILED AS BUG: GitHub issue #43666
#    (2026-11-28; #43515-kin severity: payments list unusable for
#    plain-read users).
#
# 4) EXTERNAL-USER SCOPING (source-sealed: index() scopes fk_user =
#    self unless readchild (getAllChildIds) / readall; get() 404s
#    foreign ids AFTER fetch — "salary not found", no existence
#    oracle on the main route for scoped keys):
#      export EKEY=<external-user-key with salaries/read>
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries?limit=10"
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries/<a foreign salary id>"
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries/99999999"
#    EXPECTED: list shows ONLY own (or children's) salaries — possibly
#    EMPTY (empty scoping IS an answer, PAI-C6 discipline); foreign id
#    -> 404 "salary not found" (same envelope as far-out — the scoped
#    key CANNOT distinguish existing-foreign from nonexistent: NO
#    oracle on /salaries/{id}, contrast paiements'/holidays'
#    fetched-then-checked 403); /99999999 -> 404.
#    RESULT-FIELD: PENDING.
#
# 5) SERIALISATION JQ PROBES (SAL5 decides):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries?limit=5" \
#        | jq '.[0].id, (.[0].id|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries/<id>" \
#        | jq '.id, (.id|type), .amount, (.amount|type), .salary, \
#              (.salary|type), .datep, (.datep|type), .datev, \
#              (.datev|type), .datesp, (.datesp|type), .dateep, \
#              (.dateep|type), .paye, (.paye|type), .fk_user, \
#              (.fk_user|type)'
#    Tally targets: #43307 (STRING vs NUMBER member #12),
#    #43386+#43568 (mixed-type quadrant). Payments-side probes:
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries/payments/<pid>" \
#        | jq '.id, (.id|type), .total, .amount, .datec, (.datec|type), \
#              .datepaye, (.datepaye|type)'
#    Paste predicts "" empty strings on unset payment dates (#43661
#    kin). RESULT-FIELD: PENDING.
#
# 6) FILTER-LEAKAGE GUARD (sqlfilters rowid equality; tostring BOTH
#    sides — the shipments string-vs-number trap):
#      export DOLIHOST=localhost:8080
#      export SID=<a runtime-captured salary id>
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/salaries?sqlfilters=(t.rowid%3A%3D%3A%27${SID}%27)&limit=100" \
#        | jq --arg sid "${SID}" '[.[] | select((.id|tostring) != $sid)] | length'
#    Expected 0. Nonzero = leakage -> BUG issue (phase 2).
#    RESULT-FIELD: PENDING.
#
# 7) SOURCE-READ CHECKLIST — CLOSED 2026-11-28 (full class source
#    pasted; htdocs/salaries/class/api_salaries.class.php, class
#    Salaries; docblock \file line CORRECT — no PAI8-ii path lie):
#    (i) gates: index/get/getAllPayments = salaries/read OR readchild
#        OR readall GATE-FIRST; getPayments = salaries/readall ONLY;
#        write = salaries/write (post/put/addPayment/updatePayment);
#        delete routes COMMENTED OUT (dead code, #43515-kin);
#    (ii) aliases: index t on llx_salary; getAllPayments t=
#        payment_salary + s=salary join;
#    (iii) NO id-form filters; scoping derived (SAL3);
#    (iv) limit idiom: if($limit) KIN on both lists;
#    (v) @since: count 25.0.0 (index ONLY); no {@choice}/{@pattern};
#    (vi) artefacts: broken ")" in getAllPayments read-branch;
#        getEntity('user') vs getEntity('salary') inconsistency;
#        "mayment" typo; commented-out delete()s; _cleanObjectDatas
#        cleans only rowid (no copy-paste families);
#    (vii) by-value routes: NONE (closed NEGATIVE).
#    Retained grep for re-runs:
#      grep -n "function index\|function get\|hasRight\|@url\|@param\|@since\|FROM " \
#        htdocs/salaries/class/api_salaries.class.php

# ---------------------------------------------------------------- FAMILY STATUS AT OPENING
# Salaries OPENS with a NOVEL gate facet: the single-payment route
#   /salaries/payments/{pid} demands readall while its own LIST route
#   accepts plain read — the first intra-class gate DOWNGRADE-UPGRADE
#   split of the series (SAL6). Second NOVEL facet: NO
#   forbiddenfilterfields on the MOST salary-sensitive surface in the
#   family (SAL8 — contrast HOL9's silent strip on holidays). Third:
#   the broken-parenthesis read-branch (503 for plain-read users on
#   /salaries/payments) — pre-registered phase-2 BUG candidate. All
#   findings remain CATALOGUE-only (episode decision: no comment
#   drafts; own BUG/WISH files only for REAL new shapes, phase 2, on
#   explicit request). Existing tallies gain EVIDENCE on the existing
#   issues (#43307, #43386, #43568, #43430/#43439, #42952, #43515,
#   #43514, #43569, #43596, #43620, #43634, #43661, #43663, #43664) —
#   never duplicates.
