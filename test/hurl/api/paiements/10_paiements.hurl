# test/hurl/api/paiements/10_paiements.hurl
#
# PLACEMENT NOTE: FLAT FILE in the workdir per contributor instruction
#   (convention since the multicurrencies episode): only ONE file is
#   delivered for phase 1 — 10_paiements.hurl, no directories. The
#   runner reports it as api/paiements/10_paiements.hurl (run.sh's
#   auth-required batch globs */10*.hurl — the filename MUST keep the
#   10_ prefix). Endpoint name "paiements" (plural) wins; module is
#   "paiement" (singular) — the README's "Undecided discrepancy" note
#   applies again, in line with every prior suite.
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the paiements API — every GET endpoint of the
#   class Paiements (source: htdocs/compta/paiement/class/
#   api_paiements.class.php — PATH CORRECTED 2026-11-28: the file is
#   the modulebuilder convention path htdocs/compta/paiement/class/
#   with the file name api_paiements.class.php (PLURAL); an earlier
#   revision of this header wrongly asserted "api_paiement.class.php
#   NOT api_paiements.class.php" — inverted; the plural file name was
#   correct all along. SOURCE READ COMPLETED 2026-11-28: full class
#   source pasted and analysed; all GET
#   routes/params sealed against BOTH the live explorer paste AND the
#   class source).
#
# SPEC OF RECORD (SIF/SPP/RCP/SM/SH/PLOT/MCC/BAC discipline, now
#   NINE-fold): the instance's own swagger.json — re-pull at first run,
#   ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/paiements"'
#   The fresh explorer paste (2026-11-28) CONFIRMED the section is named
#   /paiements — no rename needed; the prompt's "/paiements OR /payments"
#   tension resolves trivially this time. PAI7 upheld by construction.
#
# GET SURFACE (SOURCE-SEALED 2026-11-28; explorer paste agreed on every
#   route and parameter — prompt checklist a)-h) closed as follows):
#     GET /paiements            (index)
#     GET /paiements/{id}       (get)
#   Write routes (PUT /paiements/{id}, DELETE /paiements/{id}) exist but
#   are OUT OF SCOPE for this GET-only suite. NO POST route exists in
#   swagger (fixtures must be made via GUI or write-API outside hurl).
#
#   a) BY-VALUE ROUTES: NONE in the explorer paste AND none in the
#      class source — no /byref/, no /byinvoice/ route exists (the
#      prompt's plausible invoice-ref-route hypothesis is REFUTED by
#      both sources). NO by-value section. A payment's per-invoice
#      split lives ONLY as fields (amounts / multicurrency_amounts)
#      inside get().
#   b) TABLE ALIAS (productlots lesson): read BEFORE any pin — the
#      alias is the plain family alias **t** ("FROM <prefix>paiement AS
#      t", source-sealed). NO divergence. The t.* pins below are
#      therefore NORMATIVE, not regression guards.
#   c) FILTER FLAT: index() exposes EXACTLY: sortfield (default
#      "t.rowid"), sortorder (default 'ASC'), limit (default 100), page
#      (default 0), sqlfilters, properties, count (default 0). NO
#      facture-id filter, NO socid filter — the prompt's
#      "invoice-id-filter is PLAUSIBLE" hypothesis is REFUTED: external
#      thirdparty scoping is DERIVED from the authenticated user
#      (DolibarrApiAccess::$user->socid, EXISTS/NOT EXISTS subqueries
#      against paiement_facture/facture), NOT query-addressable. The
#      falsy-param-drop 0/abc matrix therefore has NO target (PAI3
#      resolved EMPTY FILTER SURFACE, MCF3-praecedens).
#   d) SUBRESOURCES: NONE. The per-invoice amounts structure (amounts[],
#      multicurrency_amounts[]) is embedded-only. CONFIRMED LIVE
#      2026-11-28 (three multi-invoice payments: rowid 123, 149, 1267
#      — ALL return amounts [] and multicurrency_amounts []): the
#      embedded split is NEVER filled by get() — a CONFIRMED dead-field
#      on the GET surface (PAI8-iv, phase-2 BUG candidate: no way to
#      see which invoices a payment settled via this API).
#   e) count parameter: PRESENT and SOURCE-SEALED: "@since 25.0.0
#      Parameter count" docblock + explorer + signature. index() calls
#      _getCountOfRecords($sql) AFTER sqlfilters validation but BEFORE
#      order()/plimit() — validation-wins-over-count expected; limit/
#      page IGNORED in count mode (explorer docs agree verbatim).
#      pagination_data is ABSENT from both sources — UNBOUND, expect
#      silent passthrough (200); the #42952 probe takes the
#      unparameterised shape (multicurrencies precedent). sqlfilterlines
#      ABSENT (no line table on this API) — two-probe regression guard
#      kept, family precedent.
#   f) PERMISSION GATES (SOURCE-SEALED 2026-11-28 — the prompt's BIG
#      question answered): ONE module key, consulted identically on
#      BOTH GET routes: hasRight('facture', 'lire'). NOT 'banque', NOT a
#      'paiement' key — the endpoint-vs-RIGHT-key divergence is itself
#      noteworthy (endpoint "paiements" gated by the INVOICE read
#      right; facture/creer gates put(), facture/paiement gates
#      delete()). Gate order: GATE-FIRST on every route (both index()
#      and get() open with the hasRight check, BEFORE any fetch) —
#      paiements is the THIRD datapoint for the #43620 gate-order kin:
#      multicurrencies ORACLE (fetch-before-right), bankaccounts CLEAN,
#      paiements CLEAN (source-sealed; LOWKEY probes in the C6 section
#      confirm at run). get() also runs _checkAccessToPaymentForExternalUser()
#      AFTER a successful fetch (external-user-only facet: ALL invoices
#      of the payment must belong to the user's thirdparty; nbok>0 AND
#      nbko==0) — internal-admin keys sail through, manual external-key
#      probes PENDING. GUI write-forces-read trait: still to be OBSERVED
#      (LOWKEY-flip section — would be the FIFTH suite).
#   g) PHP truthiness lesson: NO id-form filters exist in index(), so
#      the drop-by-truthiness vs drop-by-comparison distinction has no
#      target on this surface. The one $count truthiness gate
#      ("if ($count)") IS live: count=1 truthy -> count mode; count=0
#      falsy -> list. Probe pinned accordingly (count=2 must ALSO enter
#      count mode — any nonzero value).
#   h) COPY-PASTE ARTEFACT WATCH: the sqlfilters docblock example
#      "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')" is the
#      verbatim family-wide SO-% SUPPLIER-ORDER flavour (BAF-h/MCF8 kin)
#      — t.ref DOES exist on llx_paiement (refs like PAY2310-0001), so
#      the first clause is syntactically valid; t.date_creation's column
#      existence was decided by the live probe pinned below: OBSERVED
#      2026-11-28 (run 1, 29 requests, red) — the probe 503s: the
#      column does NOT exist on llx_paiement; the MCF8-analog artefact
#      is CONFIRMED live (stale docblock example), pinned 503.
#      SCHEMA-SEALED 2026-11-28 (contributor phpMyAdmin dump): the
#      real column is **datec** — the example is doubly wrong
#      (invented name + wrong flavour). ALSO FOUND IN SOURCE (phase-2 material, out of GET
#      scope but recorded NOW): delete() evaluates
#      $this->paiement->delete(DolibarrApiAccess::$user) TWICE — once in
#      the "== 0" branch and again in the "< 0" branch — a live
#      double-execution pattern (a failing delete would run twice).
#      BUG candidate, kin to the #43515/#43514/#43569 dead-code family.
#      Minor artefacts: the class docblock/file header say
#      "api_paiement.class.php" while the class is Paiements (plural);
#      the constructor carries "@url GET /" (Restler skeleton remnant);
#      the explorer's get() response model is generic Restler
#      EncoderFunction/fix/separatorChar boilerplate (BAF-h precedent —
#      not a paiements artefact per se).
#
# LIMIT IDIOM: SOURCE-SEALED 2026-11-28 — if($limit) KIN:
#   'if ($limit) { if ($page < 0) { $page = 0; } $offset = $limit*$page;
#   plimit($limit + 1, $offset) }' with the serving loop trimming via
#   min($num, ($limit <= 0 ? $num : $limit)): the multicurrencies/
#   bankaccounts/stockmovements/shipments/productlots KIN verbatim.
#   limit<=0 (incl. -1) SKIPS plimit -> the UNBOUNDED list is served;
#   negative page clamped to 0 INSIDE the block; +1 overshoot row
#   trimmed. SORTORDER: '$this->db->order($sortfield, $sortorder)' — no
#   coercion branch visible; lowercase "desc" expected 200.
#
# LIVE OBSERVATIONS (paste-evidence, pre-run — the jq probes decide):
#   GET /paiements/1 (real payment) -> 200 with: "id": "1" (STRING),
#     ref "PAY2310-0001", montant/amount/multicurrency_amount STRING
#     "300.00000000", date AND datepaye NUMBER 1697544000, statut
#     STRING "0" vs specimen/pos_change NUMBER 0, amounts[] and
#     multicurrency_amounts[] EMPTY, type_code "VIR", bank_account "1",
#     bank_line "3", fk_account "1", num_payment "" — a rich mixed-type
#     map (PAI5).
#   GET /paiements/0 -> HTTP 200 (live probe 2026-11-28, response code
#     observed; BODY not yet pasted — the fetch(0)-truthy specimen vs
#     populated-object facet is pinned by the first run's asserts
#     below).
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; facture/lire right on the key; the facture (and bank)
#   modules enabled and at least ONE payment on the instance (GUI:
#   pay an invoice under Thirdparties/Bank — there is NO POST route on
#   this API, so fixtures happen OUTSIDE hurl). A payment spanning TWO
#   or more invoices is DESIRABLE for the amounts[] depth-watch (d) but
#   NOT mandatory — the API exposes no per-invoice route regardless.
#   Gauge BEFORE the first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/paiements?limit=100&count=1"
#   Empty instance ({"count":0}) = MISSING FIXTURE, not a pin failure.
# Instans-commit: FIRST GREEN RUN OBSERVED 2026-11-28 — 43/43 requests
#   in 2034 ms on **1c819c2bba808a57f4a487b8895fb2aaf01c8675** (HEAD ->
#   develop, origin/develop, origin/HEAD, merge of #43599 "NEW Add
#   MAIN_VATRULE6_CODE and MAIN_VATRULE7_CODE constants...", Laurent
#   Destailleur, 2026-11-28 12:25:12 +0100).
#   Pin-history convention:
#   ... -> a6eb6f21 -> 3d7bc9ab -> 41b713fa -> 0235f081 -> 1c819c2b.
# RUN HISTORY (2026-11-28): run 1 RED 29/55 (t.date_creation 503 vs
#   optimistic 200 — flipped to OBSERVED 503, PAI8-iii; expect the
#   -1 pin to pass after the PAI9 flip). Manual round COMPLETE except
#   deferred probes 3 (external key) and 5 (multi-invoice payment).
#   Run 2 GREEN 43/43 in 2034 ms — all flipped pins held (t.date_
#   creation 503, -1 -> 200 wrong-row, /0 -> 200); no further flips.
#   ALL C6 RESULT-FIELDS now OBSERVED or CLOSED (external scoping
#   round completed same day; write-only CLOSED N/A-by-design).
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples: http://${DOLIHOST}/api/index.php/... with
#     DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#
# PORTABILITY: no hardcoded ids, refs or amounts — everything captured
#   at runtime. VERIFY THE ROW SHAPE FIRST with jq before trusting
#   captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/paiements?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/paiements?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/paiements/1" \
#       | jq '.amount, (.amount|type), .date, (.date|type), .amounts'
#   PAI5 pre-state: the STRING id tally stands at EIGHT (supplierorders,
#   supplierinvoices, supplierproposals, receptions, stockmovements,
#   productlots, multicurrencies, bankaccounts) vs ONE NUMBER
#   (shipments). OBSERVED 2026-11-28 (manual jq round): id STRING —
#   ninth member confirmed (see PAI5 in the findings catalogue). The
#   jq probe discipline stands: all asserts below are TYPE-AGNOSTIC. The amount-vs-date split (amount STRING
#   "300.00000000", date NUMBER) is the multicurrency rate/date_sync
#   analogue IN THE SAME OBJECT — probe every numeric-looking field's
#   type before any compare. No asserts on free text — only status
#   codes, ids, codes and shapes.
#
# FINDINGS CATALOGUED (PAI = Paiements Finding; catalogue prefix
#   pre-allocated; OBSERVED items carry their date):
#   PAI1 (nine-episode tally #43307; per-ROUTE camps; fetch(0)-truthy
#     anomaly #43634 as EXPLICIT hypothesis — paiements is the natural
#     third tester, same CommonObject-fetch family): id=0 probed on
#     BOTH id-bearing routes (get(); index is list-only).
#     OBSERVED 2026-11-28 (live probe): get()/0 -> HTTP 200. The
#     404-envelope camp (supplierproposals, tasks, multicurrencies) is
#     REFUTED for this route; paiements joins the 200-family.
#     WIDENED 2026-11-28 (manual probe round, admin key): the body
#     facet is a CAMP SHAPE NEVER SEEN in the tally — /0 returns the
#     FULLY POPULATED body of payment rowid 1 (ref PAY2310-0001,
#     amount "300.00000000", bank_line "3"), NOT an all-null specimen
#     (productlots/bankaccounts facet) and NOT the right row. WIDENED
#     AGAIN same round: the WHOLE NON-POSITIVE family serves row 1 —
#     /0, /-1, /-2 AND /-22 all -> 200 with id "1" — while /99999999
#     404s cleanly. Per-route map: get()/non-positive-id -> 200
#     populated WRONG ROW (rowid 1); get()/far-out -> 404. Strongest
#     phase-2 BUG candidate of the episode: a WRONG-ROW answer
#     masquerading as the requested payment (silently serves the
#     first/lowest payment for any non-positive id) — PAI9 below.
#     Pin history: (1) BY-ANALOGY 2026-11-28; (2) live probe CONFIRMED
#     200 on /0 same day; (3) body facet + negative family OBSERVED
#     same day (manual round, /0, /-1, /-2, /-22 all row 1).
#   PAI9 (NEW 2026-11-28, OBSERVED live): WRONG-ROW ON NON-POSITIVE
#     IDS — Paiement::fetch() passes truthy for id<=0 AND loads row
#     data instead of failing, so get() serves the FIRST payment for
#     every id in {0, -1, -2, -22} with HTTP 200. Distinct from
#     #43634's all-null-specimen shape (the object is POPULATED and
#     points at the WRONG payment — a correctness/data-integrity
#     bug, not merely a shape quirk). /99999999 stays 404 (fetch
#     falsy) — the guard passes for non-positive ids ONLY. BUG-file
#     candidate, phase 2; kin to #43634 but NOT covered by its tally
#     (new shape).
#   PAI2 (BY-ANALOGY, streak nine suites): strict 400 on BOUND typed
#     params (limit, page, count, path id) vs SILENT passthrough on
#     UNBOUND (unknown params, pagination_data, sqlfilterlines).
#     CHOICE-ANNOTATION WATCH (bankaccounts lesson #43637-kin): CLOSED
#     BY SOURCE — the class carries NO {@choice} annotation anywhere
#     (no includebalance analogue); the bare-path binding quirk of
#     bankaccounts BAF2 has NO counterpart here (bare get() is
#     reachable).
#   PAI3 (RESOLVED BY SOURCE, documented choice): EMPTY FILTER SURFACE
#     beyond sqlfilters/properties/count — index() declares NO id-form
#     filters (no facture-id, no socid query param; external scoping is
#     user-derived). The falsy-drop 0/abc matrix has NO target; skipped
#     with this source note (MCF3-praecedens).
#   PAI4 (SOURCE-SEALED 2026-11-28): limit idiom = if($limit) KIN
#     (limit<=0 skips plimit -> UNBOUNDED list; negative page clamped;
#     +1 overshoot trimmed). count = "@since 25.0.0", returned AFTER
#     sqlfilters validation and BEFORE order()/plimit() —
#     validation-wins-over-count; limit/page/sortfield all ignored in
#     count mode. pagination_data ABSENT (unbound, silent 200 expected).
#   PAI5 (OBSERVED 2026-11-28, manual jq round — paste evidence
#     CONFIRMED; #43386 + #43568 kin): id STRING "1" — the NINTH
#     STRING member (#43307 tally: nine STRING vs one NUMBER,
#     shipments). amount/montant/multicurrency_amount STRING
#     "300.00000000"; date/datepaye NUMBER 1697544000; statut STRING
#     "0" WHILE specimen/pos_change NUMBER 0 — mixed types in the
#     SAME object, the full quadrant sealed on the main object (no
#     subresource exists): id/value, computed/stored (bank_account/
#     bank_line STRINGS vs specimen/pos_change NUMBERs),
#     specimen/real facet CLOSED (no specimen shape appears: /0
#     serves the WRONG-ROW body instead, PAI9). Phase-2 tally
#     targets: #43307 (ninth STRING member), #43386 + #43568
#     (mixed-type map: amount STRING = rate-analogue; date NUMBER =
#     date_sync-analogue).
#   PAI6 (SOURCE-SEALED 2026-11-28; OBSERVED live same day via the
#     LOWKEY matrix): gate order GATE-FIRST on BOTH routes
#     (hasRight('facture','lire') opens index() AND get() before any
#     fetch) — CONFIRMED: 403 uniform on ?limit=1, /99999999, /0,
#     sqlfilterlines-guard, pagination_data-probe under a zero-rights
#     key. The #43620 fetch-first oracle is ABSENT — paiements is the
#     THIRD datapoint for the kin: multicurrencies ORACLE,
#     bankaccounts CLEAN, paiements CLEAN. ONE divergence facet
#     OBSERVED: /abc -> 400 under LOWKEY (binding-before-gate, the
#     bankaccounts BAF6(i) reading — typed path-id validator runs
#     before the gate; benign, no existence leak). Gate-key note: the
#     right is the FACTURE module's read right — endpoint/module/
#     right triple "paiements"/"paiement"/"facture" spans THREE
#     different names (a novel divergence shape for the #43596
#     gating kin). GUI write-forces-read trait: the LOWKEY-flip read
#     round succeeded (WKEY with customer+vendor invoice read grants
#     -> 200 on list AND get()); the write-only grant attempt is
#     PENDING (contributor deferral) — fifth-suite datapoint open.
#   PAI7: spec-of-record lesson upheld by construction — the fresh
#     explorer paste CONFIRMED the section name /paiements (no
#     divergence note needed, unlike every predecessor). Closes at
#     first green run.
#   PAI8 (WATCHLIST + SOURCE-SEALED items): (i) NO by-value routes
#     (refuted hypothesis, both sources); (ii) PATH CORRECTED
#     2026-11-28 by contributor: the actual file is
#     htdocs/compta/facture/class/api_paiements.class.php (plural, in
#     the FACTURE directory) — the class's own docblock \file line
#     wrongly claims "compta/paiement/class/api_paiement.class.php"
#     (singular, wrong directory): a REAL docblock-vs-filesystem
#     divergence, comment-grade artefact; (iii) docblock SO-% artefact
#     + t.date_creation probe — OBSERVED 2026-11-28 run 1: 503,
#     artefact CONFIRMED live (contributor phpMyAdmin dump seals the
#     schema: the real column is `datec`, so the docblock example is
#     doubly wrong — non-existent column name AND supplier-order
#     flavour); (iv)
#     amounts[]/multicurrency_amounts[] dead-field — CONFIRMED LIVE
#     2026-11-28 on THREE multi-invoice payments (123/149/1267, each
#     spanning 2 invoices): ALL serve empty arrays — phase-2 BUG
#     candidate; (v) the delete() DOUBLE-CALL pattern (== 0 then < 0
#     branches each invoking delete()) — live double-execution BUG
#     candidate, #43515/#43514/#43569 kin; (vi) constructor "@url
#      tests. None affect GET behaviour except (iv) (empty
#     split data); phase-2 issue-comment material.
#   PAI8-iia NOTE 2026-11-28 (contributor phpMyAdmin schema dump):
#     llx_paiement has NO date_creation column — the real creation
#     timestamp column is **datec** (datetime, NULL). The docblock
#     example's second clause is therefore doubly wrong: invented
#     column name + supplier-order SO-% flavour. The schema also
#     confirms amount/multicurrency_amount/pos_change as
#     double(24,8) (matching the "0.00000000"-style STRING output)
#     and ref/num_paiement varchar — PAI5 storage-side corroboration.

# ---------------------------------------------------------------- GET /paiements (index)

# Positive: list, sort on the SOURCE-SEALED alias t, capture the first
#   row id for the get() round-trips. REQUIRES the fixture (>=1 payment).
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
payment_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# PAI5 companion (type-agnostic asserts; the MANUAL jq probe in the C6
#   section decides string-vs-number offline): shape only.
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sortorder idiom (source-sealed: db->order, no coercion branch):
#   lowercase "desc" — db->order accepts case-insensitively; expected
#   200. A 400/503 would REFUTE the source-sealed idiom.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Unknown sortorder value: db->order's own sanitation decides. 200-with-
#   list is the primary expectation; 400/503 = NEW finding (PAI6 cand.).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a paiement-specific column (ref — real column, refs PAY...-0001)
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# PAI family pin: unknown sortfield -> query error -> RestException(503)
#   ("Unknown column ... in 'ORDER BY'"). Legal alias, bogus column.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# PAI4 (source-sealed clamp inside if($limit)): negative page -> clamped
#   to 0, no 500.
GET http://{{hostnport}}/api/index.php/paiements?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# PAI4 (source-sealed if($limit) idiom): negative limit SKIPS plimit ->
#   the UNBOUNDED list is served; 200 either way on a small fixture,
#   empty-vs-full is data-dependent, NOT asserted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PAI2: non-numeric limit (typed int) -> 400. Bound-param streak probe.
GET http://{{hostnport}}/api/index.php/paiements?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# PAI2: non-numeric page (typed int) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/paiements?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# PAI4 + e) note: pagination_data is NOT a declared parameter here —
#   silent passthrough expected, so limit=0 (plimit skipped, unbounded)
#   with pagination_data=true must still serve the plain LIST, 200. The
#   #42952 pattern reaches here only in this unparameterised shape.
#   A 400/500 would be a NEW finding (PAI6 candidate).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unbound unknown parameter: silent passthrough expected (PAI2 streak)
GET http://{{hostnport}}/api/index.php/paiements?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: count mode (PAI-e, source-sealed @since 25.0.0)

# count=1: {"count": <n>} — number, no list payload
GET http://{{hostnport}}/api/index.php/paiements?count=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# PAI-g (source-sealed truthiness gate 'if ($count)'): count=2 is also
#   truthy -> STILL count mode ({"count": n}, not a list of 2).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?count=2
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count with a filter: count mode honours sqlfilters (validated BEFORE
#   the count branch, source-sealed)
GET http://{{hostnport}}/api/index.php/paiements?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" exists

# PAI-e (validation-wins-over-count, source-sealed order): sqlfilters
#   syntax errors 400 BEFORE the count branch is reached
GET http://{{hostnport}}/api/index.php/paiements?count=1&sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# PAI-e (source-sealed: count returns BEFORE order()/plimit()): an
#   UNKNOWN sortfield must be IGNORED in count mode -> 200. Decisive
#   count-mode ordering probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?count=1&sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count + limit/page: both IGNORED in count mode (source-sealed AND the
#   explorer docs verbatim) -> 200 {"count": n}; count stays the sole
#   payload key.
GET http://{{hostnport}}/api/index.php/paiements?count=1&limit=5&page=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# PAI2: limit stays typed (int) even in count mode — junk -> 400
#   (typed binding validates before the body runs).
GET http://{{hostnport}}/api/index.php/paiements?count=1&limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# PAI2: count itself is typed (long) — junk -> 400
GET http://{{hostnport}}/api/index.php/paiements?count=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: documented filter surface (sqlfilters, properties)

# sqlfilters — always-true filter on the source-sealed alias t
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: the if($sqlfilters) guard skips parsing,
#   list still served
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE on the paiement-specific column ref, matching
#   nothing: still a valid collection (shape-only; no value pinned)
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-REF%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PAI family pin: unknown (legal-alias) column -> query error -> 503
#   ("Unknown column ... in 'WHERE'").
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# PAI8 cross-probe (docblock-example syntax): the docblock's OWN first
#   clause (t.ref LIKE 'SO-%') — t.ref EXISTS on llx_paiement, so this
#   must behave like any syntactically-valid filter (200, likely empty
#   collection — no payment refs start with SO-). A 400 here would
#   refute the syntactic reading; PAI8 artefact note stays
#   comment-grade (the SO-% flavour is supplier-order material).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.ref%3Alike%3A%27SO-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# PAI8 decisive column-existence probe: the docblock example's second
#   clause names t.date_creation. PIN HISTORY: (i) 2026-11-28 pinned
#   optimistically as 200 (column-exists hypothesis); (ii) OBSERVED
#   2026-11-28 run 1 (29 requests, red): 503 — the MCF8-analog artefact
#   is CONFIRMED live, t.date_creation does NOT exist on llx_paiement
#   (stale docblock example; supplier-orders SO-% flavour names a
#   column the payment table lacks — exactly the multicurrency
#   product_id shape). Facet (i) preserved, refuted; (ii) pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.date_creation%3A%3E%3A%2720160101%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# SOURCE-SEALED (forgeSQLFromUniversalSearchCriteria errormessage ->
#   RestException(400)): invalid sqlfilters syntax -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.rowid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/paiements?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — restrict to a property name; restricted list served
GET http://{{hostnport}}/api/index.php/paiements?properties=id&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — junk property name: _filterObjectProperties ignores
#   unknown names; list served unchanged, 200 (family behaviour).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?properties=nosuchproperty&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — PAI-e (source-sealed: parameter ABSENT)
# index() declares NO sqlfilterlines parameter (source-sealed
#   2026-11-28; a paiement IS itself the payment entity — no line
#   table exists on this API: the per-invoice split is embedded
#   fields, not a route). Expected silent ignore (#43430/#43439
#   family). Two-probe regression guards kept below, family precedent.
# Guard 1: valid-form sqlfilterlines must be silently ignored (200 list)
GET http://{{hostnport}}/api/index.php/paiements?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2
# Guard 2 (decisive, #43388 vs #43430/#43439): BAD syntax -> 200 means
#   silently ignored (expected, parameter absent); 400 means BOUND.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements?sqlfilterlines=(t.rowid%20%3E%3A%20%27x%27)&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# ---------------------------------------------------------------- Combined smoke (all documented filters at once)
GET http://{{hostnport}}/api/index.php/paiements?sortfield=t.rowid&sortorder=DESC&limit=3&page=0&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id,ref,amount
HTTP 200
[Asserts]
jsonpath "$" count <= 3

# Count-mode combined smoke (count + filter + sort both ignored)
GET http://{{hostnport}}/api/index.php/paiements?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&sortfield=t.rowid&sortorder=DESC
HTTP 200
[Asserts]
jsonpath "$.count" exists

# ---------------------------------------------------------------- GET /paiements/{id}

# Positive round-trip: the captured list id must resolve (200)
GET http://{{hostnport}}/api/index.php/paiements/{{payment_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# Family pin: far-out id -> fetch returns falsy -> RestException(404)
GET http://{{hostnport}}/api/index.php/paiements/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# PAI2: non-numeric path id (typed long) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/paiements/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative path id: PIN HISTORY: (i) 2026-11-28 pinned 404
#   (fetch(-1) falsy hypothesis, family hybrid precedent); (ii)
#   OBSERVED same day (manual round, admin key): 200 — the
#   PAI9 WRONG-ROW shape: fetch() passes truthy for negative ids and
#   SERVES payment rowid 1. Pinned 200 (existence only — the body
#   belongs to a different row; never assert its content).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements/-1
HTTP 200
[Asserts]
jsonpath "$.id" exists

# PAI1/PAI9 (OBSERVED 2026-11-28): id=0 -> 200, body = payment rowid
#   1's POPULATED object (ref PAY2310-0001) — the WRONG-ROW shape,
#   NOT an all-null specimen. Existence-only assert stays (never
#   assert the body of a wrong-row answer; row-1 dependence would
#   be fixture-coupling).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/paiements/0
HTTP 200

# Unbound unknown parameter on get(): silent passthrough expected
GET http://{{hostnport}}/api/index.php/paiements/{{payment_id}}?nosuchparameter=1
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
#    get). PAI6 predicts GATE-FIRST on both routes: a zero-rights key
#    gets 403 EVERYWHERE — including /99999999 and /0 (no fetch-first
#    oracle possible). ANY non-403 answer (esp. a 404 on a far-out id,
#    or a 200 specimen on /0) would instantiate the #43620 oracle
#    pattern — BOTH facets pre-registered as BUG candidates.
#      export LOWKEY=<zero-rights-key>
#      export DOLIHOST=localhost:8080
#      for p in "?limit=1" "/99999999" "/0" "/abc" \
#        "?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=1" \
#        "?limit=0&pagination_data=true"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#          "http://${DOLIHOST}/api/index.php/paiements${p}"; done
#    EXPECTED (PAI6, source-sealed): 403 on ALL six probes (uniform
#    deny — the anti-oracle shape). RESULT-FIELD: OBSERVED 2026-11-28
#    (manual round, zero-rights key): 403 on ?limit=1, /99999999, /0,
#    sqlfilterlines-guard and pagination_data-probe — CONFIRMED
#    GATE-FIRST, the #43620 fetch-first oracle is ABSENT live (PAI6
#    closed, third datapoint: multicurrencies ORACLE, bankaccounts
#    CLEAN, paiements CLEAN). ONE divergence facet OBSERVED: /abc ->
#    400 "Bad Request: invalid value specified for `id`" under the
#    zero-rights key — BINDING-BEFORE-GATE, exactly the bankaccounts
#    BAF6(i) facet: the typed path-id validator runs BEFORE the
#    permission gate. Benign leakage-wise (identical 400 regardless
#    of existence; /0 under LOWKEY stayed 403, so the PAI9 wrong-row
#    answer does NOT bypass the gate for zero-rights keys). RESULT
#    now matches the #43620-kin reading: binding -> gate -> fetch.
#
# 2) LOWKEY-FLIP (gate-resolver + GUI-trait check): grant the SAME user
#    facture/lire READ only, re-probe the positive pair (list +
#    get/<real id> — both should 200), THEN attempt a WRITE-only grant
#    (facture/creer WITHOUT facture/lire — check whether the GUI
#    permits it; the four-suite streak predicts the GUI FORCES read on
#    write grants, making a true write-only key impossible):
#      export WKEY=<facture-write-only-key>
#      export PID=<a runtime-captured payment id>
#      for p in "?limit=1" "/${PID}"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${WKEY}" \
#          "http://${DOLIHOST}/api/index.php/paiements${p}"; done
#    If the GUI refuses write-without-read, CLOSE as N/A-by-design and
#    document the trait (FIFTH suite). RESULT-FIELD: PARTIALLY
#    OBSERVED 2026-11-28 — the flip itself succeeded: with the READ
#    grants ("Read customer invoices (and payments)" — and vendor
#    invoices) the WKEY got 200 on BOTH ?limit=1 (full populated list
#    row: id "1", ref PAY2310-0001) AND /1 (200 populated object).
#    Gate-resolver: ONE permission block (facture module read) serves
#    BOTH routes — no productlots-type multi-block ambiguity here.
#    GUI trait OBSERVED 2026-11-28 (contributor: "write only is
#    impossible, the GUI prevents it"): the WRITE-ONLY half is CLOSED
#    as N/A-BY-DESIGN — the FIFTH suite in the series where the GUI
#    forces read on write grants (multicurrency, bank, and now
#    facture-family precedent). Full flip round CLOSED: read-only ->
#    200 on both routes; write-only -> impossible state. The WKEY
#    positive round ALSO yielded the PAI5 confirmation (list and
#    get() both id STRING).
#
# 3) EXTERNAL-USER SCOPING (source-sealed _checkAccessToPaymentForExternalUser
#    + the index() EXISTS/NOT EXISTS subqueries): with an EXTERNAL key
#    bound to a thirdparty, index() must serve ONLY payments whose
#    invoices ALL belong to that thirdparty, and get() on a MIXED
#    payment (invoices of two thirdparties) must 403 with
#    "Access to this payment not allowed for this user":
#      export EKEY=<external-user-key>
#      export PID=<a payment of another thirdparty>
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements?limit=10"
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${EKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements/${PID}"
#    Expected: list contains only own-thirdparty payments; foreign
#    payment id -> 403. NOTE the ordering: the external-user check
#    runs AFTER the fetch (fetch-and-404 first) — a nonexistent id
#    404s BEFORE the 403 (source-sealed, a mild existence oracle for
#    external keys; probe /99999999 under EKEY too). RESULT-FIELD:
#    OBSERVED 2026-11-28 (external key bound to a thirdparty, granted
#    "Read customer invoices (and payments)") — ALL FOUR facets
#    CONFIRMED LIVE:
#    (i) scoping LIST: ?limit=100 -> 200 with EXACTLY 5 payments
#    (ids 150, 151, 524, 581, 962 — all Credit-card type CB, thirdparty
#    own rows) vs the admin's 1295 — the EXISTS/NOT EXISTS subqueries
#    filter correctly; count=1 agrees: {"count":5};
#    (ii) FOREIGN payment: /123 -> 403 "Access to this payment not
#    allowed for this user" — the EXTERNAL-USER CHECK fired with its
#    OWN distinct message (vs the plain hasRight "Forbidden");
#    (iii) EXISTENCE ORACLE CONFIRMED: /99999999 -> 404 "Paiement not
#    found" UNDER THE GRANTED EXTERNAL KEY — the fetch-404 runs BEFORE
#    the _checkAccessToPaymentForExternalUser check, so external keys
#    CAN distinguish existing-from-nonexistent ids (a mild, benign
#    oracle — worth a #43620-kin comment, datapoint: oracle exists for
#    EXTERNAL users even on a gate-first class);
#    (iv) PRE-GRANT round OBSERVED earlier same day: 403 uniform on
#    all probes (list, /123, /0, /99999999) — gate-first holds for
#    external users too; /0 under the pre-grant key 403'd, so the
#    PAI9 wrong-row answer does NOT bypass the READ gate.
#    (v) BONUS FACET OBSERVED 2026-11-28: /0 under the GRANTED
#    external key -> 403 "Access to this payment not allowed for this
#    user" — the external check catches the PAI9 wrong-row answer
#    (row 1 belongs to another thirdparty). The wrong-row bug does
#    NOT leak to external users: they receive the clean 403, while
#    internal admin keys get the misleading populated body of row 1.
#    The PAI9 wrong-row exposure is INTERNAL-ONLY (admin/internal
#    API keys). Probe round COMPLETE — every C6 RESULT-FIELD is now
#    OBSERVED or CLOSED.
#
# 4) SERIALISATION JQ PROBES (PAI5 decides):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements?limit=5" \
#        | jq '.[0].id, (.[0].id|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements/1" \
#        | jq '.amount, (.amount|type), .date, (.date|type), \
#              .statut, (.statut|type), .specimen, (.specimen|type), \
#              .amounts, .multicurrency_amounts'
#    Paste prediction: id STRING, amount STRING "300.00000000", dates
#    NUMBER, statut STRING, specimen/pos_change NUMBER, amounts [].
#    RESULT-FIELD: OBSERVED 2026-11-28 (manual jq round, admin key) —
#    CONFIRMED EXACTLY: list .[0].id = "1" STRING (NINTH STRING
#    member; #43307 tally now nine STRING vs one NUMBER-shipments);
#    get()/1: amount "300.00000000" STRING, date/datepaye 1697544000
#    NUMBER, statut "0" STRING, specimen 0 NUMBER, amounts [] and
#    multicurrency_amounts []. The full mixed-type quadrant is SEALED
#    on the main object (PAI5 closes at next green run).
#
# 5) AMOUNTS[] DEAD-FIELD WATCH (PAI8-iv): find a payment KNOWN to span
#    >=2 invoices (query the paiement_facture table via the GUI or DB;
#    the API exposes no per-invoice route), then:
#      export PID=<a multi-invoice payment id>
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements/${PID}" \
#        | jq '.amounts, .multicurrency_amounts'
#    If BOTH stay empty on a known multi-invoice payment -> dead-field
#    BUG candidate (phase 2). RESULT-FIELD: OBSERVED 2026-11-28
#    (manual round, admin key) — CONFIRMED on THREE multi-invoice
#    payments (rowid 123, 149, 1267 — each spanning exactly 2 invoices
#    per llx_paiement_facture GROUP BY): ALL THREE return amounts []
#    AND multicurrency_amounts []. The embedded per-invoice split is
#    NEVER filled by get() on this API — PAI8-iv upgraded from WATCH
#    to CONFIRMED dead-field finding; phase-2 BUG candidate (the GET
#    surface exposes no way to see which invoices a payment settled).
#
# 6) FILTER-LEAKAGE GUARD (sqlfilters on index; tostring on BOTH
#    sides — the shipments string-vs-number trap):
#      export DOLIHOST=localhost:8080
#      export PID=<a runtime-captured payment id>
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/paiements?sqlfilters=(t.rowid%3A%3D%3A%27${PID}%27)&limit=100" \
#        | jq --arg pid "${PID}" '[.[] | select((.id|tostring) != $pid)] | length'
#    Expected 0. Nonzero = leakage -> BUG issue (phase 2).
#    RESULT-FIELD: OBSERVED 2026-11-28 (manual jq round, PID=1) — 0
#    foreign rows; sqlfilters rowid-equality serves EXACTLY the
#    requested row. NO leakage. CLEAN, closed.
#
# 7) SOURCE-READ CHECKLIST — CLOSED 2026-11-28 (full class source
#    pasted and analysed; api_paiement.class.php, class Paiements):
#    (i) gate keys + order: hasRight('facture','lire') GATE-FIRST on
#        BOTH GET routes; write = facture/creer (put); delete =
#        facture/paiement — PAI6 closed (clean family, third
#        #43620-kin datapoint predicted);
#    (ii) table alias: **t** ("FROM <prefix>paiement AS t") — the t.*
#        pins are NORMATIVE — PAI8 flip moot;
#    (iii) NO id-form filters; external scoping user-derived — PAI3
#        closed (empty filter surface);
#    (iv) limit idiom: if($limit) + plimit($limit+1) + inner page
#        clamp — PAI4 closed (family KIN);
#    (v) @since tags: count = "@since 25.0.0 Parameter count"; no
#        {@choice} annotations anywhere — PAI2 choice-watch closed;
#    (vi) copy-paste artefacts: SO-% docblock example (t.ref exists,
#        valid syntax; t.date_creation decided by the live probe);
#        file/class name divergence; constructor "@url GET /" remnant;
#        PLUS the delete() double-call pattern (live, out of GET
#        scope) — all PAI8, phase-2 material;
#    (vii) unlisted by-value routes: NONE in source or swagger —
#        PAI8 closed NEGATIVE.
#    The grep command is retained for RE-runs (instance moves):
#      grep -n "function index\|function get\|hasRight\|@url\|@param\|@since\|FROM " htdocs/compta/facture/class/api_paiements.class.php

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Payments tie to INVOICES (paiement_facture join table; facture.fk_soc
#   for thirdparty linkage) — but NO query filter exposes any of it
#   (source-sealed): the shipments socid-scoping probes have NO target
#   on this surface, and the multicurrency entity plan applies:
#   1) ENTITY-SCOPING: index() restricts to
#      "t.entity IN (getEntity('paiement'))" when the object is
#      ismultientitymanaged (source-visible branch) — cross-entity
#      probe = manual two-instance work, PENDING, non-blocking.
#   2) EXTERNAL scoping is user-derived (the C6 section 3 probe) —
#      string-vs-number care applies to the socid comparison only
#      server-side; nothing to tostring client-side (no socid in the
#      response pasted).
#   3) FILTER-LEAKAGE on t.rowid: C6 section 6 guard covers the only
#      filterable column family.
#   RESULT-FIELDS: PENDING.

# ---------------------------------------------------------------- FAMILY STATUS AFTER THIS EPISODE
# Paiements has the SMALLEST GET surface of the series so far: TWO
#   routes, no subresource, no by-value route, no id-form filter — and
#   yet three live kin-facts land on it at once: the fetch(0)-truthy
#   anomaly (#43634) gains its natural third datapoint (PAI1, OBSERVED
#   live), the #43620 gate-order kin gains its third datapoint
#   (PAI6, clean family, source-sealed), and the string-id tally
#   (#43307/#43386/#43568) heads toward its ninth STRING member (PAI5).
#   The embedded amounts[] emptiness watch (PAI8-iv) and the delete()
#   double-call find (PAI8-v) are paiements-exclusive additions.
#   Existing tallies gain EVIDENCE as COMMENTS on the existing issues —
#   never duplicates. New BUG/WISH Markdown files only in phase 2, on
#   explicit request.
