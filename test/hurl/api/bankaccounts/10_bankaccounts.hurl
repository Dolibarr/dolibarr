# test/hurl/api/bankaccounts/10_bankaccounts.hurl
#
# PLACEMENT NOTE: FLAT FILE in the workdir per contributor instruction
#   (lesson from the multicurrencies episode, now convention): only ONE
#   file is delivered for phase 1 — 10_bankaccounts.hurl, no
#   directories. The runner reports it as
#   api/bankaccounts/10_bankaccounts.hurl (run.sh's auth-required batch
#   globs */10*.hurl — the filename MUST keep the 10_ prefix). Endpoint
#   name "bankaccounts" wins; module is "bank" — the README's
#   "Undecided discrepancy" note (endpoint vs. module name) applies
#   again, in line with every prior suite.
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# GET-only tests for the bankaccounts API — every GET endpoint of the
#   bankaccounts section of the instance swagger (class expected at
#   htdocs/compta/bank/class/api_bankaccounts.class.php — PATH IS
#   "EXPECTED", NOT YET CONFIRMED: the source read is part of the
#   first-run checklist; every source-derived statement below is
#   labelled accordingly).
#
# SPEC OF RECORD (SIF/SPP/RCP/SM/SH/PLOT/MCC discipline, now EIGHT-fold):
#   the instance's own swagger.json — re-pull at first run, ALWAYS:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/bankaccounts"'
#
# GET SURFACE (SOURCE-SEALED 2026-11-28 — full class source pasted and
#   analysed, closing the "EXPECTED, NOT CONFIRMED" caveat: the class
#   file api_bankaccounts.class.php declares EXACTLY these routes;
#   explorer paste agreed on every route and parameter; checklist
#   a)-h) closed as follows):
#     GET /bankaccounts                      (index)
#     GET /bankaccounts/{id}?includebalance  (get)
#     GET /bankaccounts/{id}/balance         (getAccountBalance)
#     GET /bankaccounts/{id}/lines           (getLines? sqlfilters)
#     GET /bankaccounts/{id}/lines/{line_id}/links (getLineLinks?)
#     GET /bankaccounts/lines/{line_id}      (getLine — TOP-LEVEL by-id
#                                             route for a LINE entity:
#                                             the "parent and
#                                             subresource live in
#                                             DIFFERENT camps" shape
#                                             from the receptions lesson)
#   Write routes (POST/PUT/DELETE on accounts, lines, reconcile,
#   transfer) exist but are OUT OF SCOPE for this GET-only suite.
#
#   a) BY-VALUE ROUTES: NONE in the explorer paste — no /byref/, no
#      /bylabel/ route is visible (bank accounts' natural by-value key,
#      ref/label/iban, has NO analogue of multicurrencies' bycode here,
#      despite the paste hinting accounts carry label/ref). CONFIRM
#      against the class source at first run: if a @url by-value route
#      exists in source but not in swagger, that is a BAF8 finding
#      (dead/unlisted route). Until then: NO by-value section.
#   b) TABLE ALIAS (productlots lesson): NOT yet source-sealed. The
#      bankaccount class historically queries "<prefix>bank_account as
#      ba"/"b" variants — the alias is a HYPOTHESIS ONLY. The t.*
#      sqlfilters pins below follow the family majority alias t; IF the
#      source read shows a different alias, ALL t.* pins flip to
#      regression guards and the real alias takes over (BAF8 note).
#      NEVER pin a sortfield/column before the alias is read from the
#      source SQL.
#   c) FILTER FLAT: index() per the paste exposes: sortfield, sortorder,
#      limit, page, category (long — a CATEGORICAL id-form filter! The
#      FIRST id-form filter in the family series — the 0/abc falsy
#      matrix HAS a target here, unlike multicurrencies), sqlfilters,
#      properties, count. NO socid/owner filter is visible in the paste
#      (bank accounts CAN carry an owner in the GUI but no socid query
#      filter appears) — verify against source; if a socid filter hides
#      there, add the shipments tostring-on-both-sides scoping probe
#      (BAF8 note). category being typed long makes it a BOUND typed
#      param: junk -> 400 expected (BAF2 streak).
#   d) SUBRESOURCES: THREE depth-2/depth-3 id-bearing routes:
#      {id}/balance, {id}/lines, {id}/lines/{line_id}/links — plus the
#      top-level /lines/{line_id} (line entity addressed OUTSIDE the
#      parent). Capture chain: list -> account id -> {id}/lines ->
#      line_id -> links round-trip. Per-route falsy camps on ALL of
#      them; receptions lesson: parent and subresource camps may
#      DIFFER — 0 is probed on every route independently (BAF1).
#   e) count parameter: PRESENT in the paste (docs: "Set to 1 to return
#      only the number of records... limit and page are then ignored")
#      — expect the multicurrencies-mode behaviour (count ignores
#      limit/page; unknown sortfield ignored in count mode). @since tag
#      unconfirmed until the source read. pagination_data is ABSENT
#      from the paste — UNBOUND hypothesis, expect silent passthrough
#      (#42952 probe in the unparameterised shape). sqlfilterlines:
#      ABSENT from the paste (lines subresource takes plain sqlfilters)
#      — two-probe regression guard kept.
#   f) PERMISSION GATES (SOURCE-SEALED 2026-11-28): module key is
#      'banque' (FRENCH spelling, level 'lire') — NOT 'bank': the
#      README's endpoint-vs-module discrepancy reaches the RIGHT KEY
#      itself. Gate order: GATE-FIRST on every route (index, get,
#      getLines, getLinks, getDetailAccountLine, getBalance all open
#      with the hasRight check) — the #43620 fetch-first oracle
#      pattern is ABSENT (BAF6 closed NEGATIVE, family divergence
#      datapoint). ONE spelling, no products/produit kin. Write gates:
#      'configurer' (post/put/delete/transfer), 'modifier' (addLine/
#      addLink/updateLine/deleteLine), and reconcile demands
#      lire + consolidate TOGETHER (double gate — POST, out of scope
#      but noted). GUI write-forces-read trait: still to be OBSERVED
#      (LOWKEY-flip section).
#   g) PHP truthiness lesson: "0" is FALSY in PHP — the category filter
#      at category=0 may be dropped by an if($category) guard (a falsy
#      DROP, i.e. category=0 behaves as unfiltered) or guarded with
#      ($category > 0) — the source read decides which clause is built;
#      the 0/abc matrix below is decided by the RUN, never by reading
#      intent (BAF3).
#   h) COPY-PASTE ARTEFACT WATCH: the explorer paste's get() response
#      model is a generic Restler Object dump (EncoderFunction/fix/
#      separatorChar boilerplate) — not a bank artefact per se, but the
#      docblock/example sweep at source read must watch for foreign
#      module strings (multicurrency had "warehouse objects" +
#      product_id in sqlfilters example — 503-live). If the
#      bankaccounts sqlfilters docblock example names a column the bank
#      table lacks, the docblock-example-syntax probe (t.<that column>)
#      is pinned as a BAF8 cross-probe exactly like MCF8.
#
# TABLE ALIAS STATUS: SOURCE-SEALED 2026-11-28 — the alias is the
#   plain family alias **t** on BOTH routes ("FROM <prefix>bank_account
#   AS t" in index(), "FROM <prefix>bank AS t" in getLines()). NO
#   divergence; the t.* pins are NORMATIVE. Related source-seals:
#   entity scope "WHERE t.entity IN (getEntity('bank_account'))" on
#   index (cross-entity = manual two-instance work, PENDING,
#   non-blocking); limit idiom if($limit)+plimit($limit+1)+inner page
#   clamp (multicurrencies KIN — limit<=0 -> UNBOUNDED list, +1
#   overshoot trimmed); sortorder via db->order (no coercion branch).
#
# LIMIT IDIOM: SOURCE-SEALED 2026-11-28 — if($limit) + plimit($limit+1)
#   + inner negative-page clamp + serving loop min($num, $limit<=0 ?
#   $num : $limit): the multicurrencies/stockmovements KIN. limit<=0
#   (incl. -1) skips plimit -> the UNBOUNDED list; the +1 overshoot
#   row is trimmed. The limit=-1/limit=0 probes below are SEALED
#   (200 non-empty on this fixture is now the PREDICTION, assert
#   stays collection-shape for portability).
#
# LIVE OBSERVATIONS FROM THE EXPLORER PASTE: none of the example hits
#   were executed against a live instance in the paste — the paste
#   contributes ROUTE/PARAM SHAPE only. All camp predictions below are
#   OBSERVED-BY-ANALOGY until the first run re-marks them.
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; bank module read right on the key; the bank module
#   enabled and at least ONE bank account on the instance (GUI: Home ->
#   Setup -> Modules/Bank -> activate; then create an account under
#   Bank/Cash, OR — the paste SHOWS a POST /bankaccounts route —
#   fixture via write-API OUTSIDE this hurl file). Gauge BEFORE the
#   first run:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/bankaccounts?limit=100&count=1"
#   Empty instance ({"count":0}) = MISSING FIXTURE, not a pin failure.
# Instans-commit: FIRST GREEN RUN OBSERVED 2026-11-28 — 68/68
#   requests in 5766 ms on <HASH PENDING>. Red-run history leading
#   here: 37 -> 41 -> 42 -> 45 -> 57 -> 66 -> 66 -> 68 requests (each
#   red round sealed one finding; no pin flipped without evidence).
#   RE-CONFIRMED second green run 2026-11-28: 68/68 in 4922 ms
#   (stable). Convention: append the hash of these green runs here;
#   pin history ... -> a6eb6f21 -> 3d7bc9ab -> 41b713fa -> <this run>.
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples: http://${DOLIHOST}/api/index.php/... —
#     never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#
# PORTABILITY: no hardcoded ids, refs, ibans or counts — everything
#   captured at runtime. VERIFY THE ROW SHAPE FIRST with jq before
#   trusting captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/bankaccounts?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/bankaccounts?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   BAF5 pre-state: the STRING id tally stands at SEVEN (supplierorders,
#   supplierinvoices, supplierproposals, receptions, stockmovements,
#   productlots, multicurrencies) vs ONE NUMBER (shipments). ANTAG
#   INGEN DEFAULT: the jq probe comes FIRST; all asserts below are
#   TYPE-AGNOSTIC. Bank-account numeric fields (balance from
#   ?includebalance=1, /balance response, amounts in lines) are prime
#   number-vs-string candidates — the multicurrency mixed-shape lesson
#   (rate STRING, date_sync NUMBER in the SAME object) applies:
#   probe EVERY numeric-looking field's type before any compare.
#   No asserts on free text — only status codes, ids, codes and shapes.
#
# FINDINGS CATALOGUED (BAF = BankAccounts Finding; catalogue prefix
#   pre-allocated; nothing OBSERVED yet — all pins carry their
#   pre-registration status and flip to OBSERVED <date> <commit> at the
#   first green run):
#   BAF1 (nine-episode tally #43307; per-ROUTE camps): id=0 probes on
#     SIX routes (get, balance, lines, links, top-level line, and the
#     parent of links). Camp candidates: 200-all-null SPECIMEN
#     (productlots KIN), 404-envelope (majority, incl. multicurrencies
#     on ALL id-bearing routes), 400-explicit-guard (stockmovements).
#     Pin per route, pin history MANDATORY.
#     OBSERVED 2026-11-28 (live probe, pre-green): get()/0 resolves as a
#     HYBRID never seen in the tally — bare /0 dies 400 with a message
#     blaming the OPTIONAL includebalance param ("Expecting integer
#     value"), while /0?includebalance=1 serves a 200 ALL-NULL SPECIMEN
#     ('specimen':0, 'balance':0 NUMBER, 'rappro':1, 'clos':0,
#     'sepa_category_purpose':"CORE", 'type_lib' array; every business
#     field null). The specimen camp gains a member; both 404-envelope
#     and stockmovements-guard facets are REFUTED for this route.
#     WIDENED 2026-11-28 (second live paste): the 200-family is NOT
#     confined to get() — /0/lines -> 200 [] (no parent-existence
#     check at all), /lines/0 -> 200 ALL-NULL LINE SPECIMEN (line-
#     flavoured nulls, product/order block attached),
#     0/lines/0/links AND 1/lines/0 AND 1/lines/1 AND 0/lines/1/links
#     -> ALL 200 [] — links NEVER rejects on any falsy combination.
#     SOURCE-SEALED 2026-11-28 (class source pasted and analysed):
#     the ROOT CAUSE is a fetch(0) TRUTHY anomaly — Account::fetch(0)
#     AND AccountLine::fetch(0) both return truthy (the guards
#     `if (!$result) throw 404` are passed by id=0!), so id=0 NEVER
#     404s on this class, while FAR-OUT ids (fetch returns 0/falsy)
#     DO 404. Final per-route map: get/0 -> 200 all-null specimen
#     (WITH a valid includebalance; bare -> 400, see BAF2);
#     /0/balance -> 200 (source: solde(1) already proven harmless on
#     the null account by the get()+includebalance=1 specimen whose
#     'balance' came back 0); /0/lines -> 200 [] (fk_account=0 filter,
#     fetch(0) passes the 404 guard); /lines/0 -> 200 line specimen;
#     /0/lines/*/links -> 200 []; FAR-OUT parents/lines -> 404 (fetch
#     falsy). The fetch(0)-truthy anomaly is itself a phase-2 BUG
#     candidate (a null-object response masquerading as data, and
#     solde/balance endpoints answering for a nonexistent account).
#     Lines on REAL account (rowid 1, "A LOT OF LINES") -> 200
#     populated — fixture note hardened: the SUITE now REQUIRES >=1
#     line on the first listed account (first-entry account is
#     suitable).
#   BAF2 (BY-ANALOGY, streak eight suites): strict 400 on BOUND typed
#     params (limit, page, count, path ids, category) vs SILENT
#     passthrough on UNBOUND (unknown params, pagination_data-if-
#     absent, sqlfilterlines-if-absent).
#     RUN-OBSERVED 2026-11-28 (37-request failing run): the get()
#     docblock's "{@choice 0,1}" on includebalance makes the BARE
#     GET /bankaccounts/{id} UNREACHABLE — 400 "Invalid value
#     specified for `includebalance`. Expecting integer value" even
#     with the parameter ABSENT. SECOND RUN the same day widened it:
#     includebalance=0 ALSO 400s (the validator reads 0 as ABSENT —
#     PHP falsy-empty AT THE BINDING LAYER — and then requires the
#     missing choice param). Empirical rule: bare -> 400, =0 -> 400,
#     =abc -> 400, ONLY =1 -> 200. An OPTIONAL parameter whose
#     DOCUMENTED DEFAULT is unreachable via HTTP: the ONLY known
#     case in the series, across two rounds.
#     Phase-2 BUG candidate (BCGC-kin, docblock notation vs. binding).
#   BAF3 (SOURCE-SEALED 2026-11-28): category guard is
#     'if ($category > 0)' — STRICT comparison: 0/negatives skip the
#     join+filter (unfiltered list), only positive ints bind the
#     categorie_account join. Drop-by-comparison, not drop-by-
#     truthiness — the PHP truthiness lesson nuanced live. The 0/abc
#     matrix pins are updated accordingly (category=0 -> unfiltered
#     200; category=abc -> typed 400 — streak probe stands).
#   BAF4 (SOURCE-SEALED 2026-11-28, closes the UNDECIDED): limit
#     idiom = if($limit) KIN (limit<=0 skips plimit -> unbounded
#     list; negative page clamped INSIDE the block; serving loop
#     trims the +1 overshoot — the multicurrencies idiom verbatim).
#     count = "@since 25.0.0", returned AFTER sqlfilters validation
#     and BEFORE order()/plimit() — validation-wins-over-count,
#     sortfield/limit/page all ignored in count mode (the
#     multicurrencies ordering EXACTLY; all count-mode pins stand).
#     pagination_data remains ABSENT (unbound, silent 200 expected).
#   BAF5 (OBSERVED 2026-11-28 — 3 live rounds, the failing 41-request
#     run, AND the offline jq probes; #43386/#43568 kin): id STRING
#     "1" in list output — the STRING tally grows to EIGHT
#     (shipments stays the lone NUMBER). The full serialisation
#     map is SEALED: real-account get(): balance NUMBER
#     (-397785.89714, computed), rappro/clos STRINGS ("1"/"0");
#     {id}/balance scalar: NUMBER, consistent with the
#     includebalance field on both surfaces; LINE: amount STRING
#     "0.00000000" (rate-analogue), datec/datev/dateo NUMBER
#     (date_sync-analogue); SPECIMEN (/0): rappro/clos/balance
#     NUMBER — SPECIMEN-VS-REAL divergence on the SAME field
#     (constructor-set numbers vs DB-loaded strings). Mixed types
#     in the same object confirmed twice over (account object AND
#     line object). The richest serialisation datapoint set of the
#     series so far — all four quadrants live: id/value,
#     computed/stored, account/line, specimen/real. Fase-2 tally
#     targets: #43307 (id = eighth STRING member), #43386 + #43568
#     (mixed-type map: amount-STRING "0.00000000" =
#     rate-analogue; datec/datev/dateo NUMBER = date_sync-analogue;
#     balance NUMBER on BOTH surfaces while stored flags stay
#     STRING — computed-vs-stored is the new split line).
#   BAF6 (SOURCE-SEALED 2026-11-28; OBSERVED same day via the LOWKEY
#     matrix — the big NEGATIVE result): gate order is GATE-FIRST on
#     EVERY route: index, get, getLines, getLinks, getDetailAccountLine
#     and getBalance all open with 'if (!hasRight('banque','lire'))
#     throw 403' BEFORE any fetch. The #43620 fetch-first
#     existence-oracle pattern is ABSENT from this class — CONFIRMED
#     LIVE: a zero-rights key got 403 on all 13 valid-form probes
#     INCLUDING /0?includebalance=1 (where the fetch(0) anomaly would
#     have leaked the specimen), and existing ids (AID=1, AID=2) are
#     indistinguishable from /99999999 (all 403). TWO divergence
#     notes pinned: (i) the BARE forms /0 and /99999999 -> 400 under
#     LOWKEY: the includebalance choice-binding VALIDATES BEFORE the
#     permission gate (binding-before-gate, benign: identical 400 for
#     existing and far-out ids, no existence leak); (ii) module key
#     spelling is 'banque'/'lire' (French) — ONE spelling, no
#     products/produit kin. GUI trait OBSERVED 2026-11-28 (bank
#     permissions screen: "Banks & Cash — Read financial accounts /
#     Create/modify/delete and compare transactions / Setup financial
#     accounts / Reconcile / Export / Transfers / Manage checks"):
#     GUI trait CORRECTED 2026-11-28 (maintainer observation; the
#     multicurrency precedent HOLDS here too): ticking any bank
#     write permission WITHOUT "Read financial accounts" is NOT
#     possible — the GUI FORCES read on write grants, so a true
#     write-only key is an IMPOSSIBLE state for bankaccounts as
#     well. The earlier "matrix rows are independent" reading
#     (made from the permissions-screen listing alone, before
#     interaction) is REFUTED. The C6 section 3 flip probe is
#     CLOSED as N/A-BY-DESIGN — fourth suite in the series where
#     the GUI-forces-read trait holds; worth a tally remark on the
#     #43620 kin thread (GUI-consistency dimension).er/oracle (fetch-before-
#     right on any of the six routes = #43620-pattern oracle, BOTH
#     facets via LOWKEY) + gate-key spelling (single vs double-spelled
#     module key) + GUI write-forces-read trait (bank may DIFFER from
#     multicurrency — testable write-only flip).
#   BAF7: spec-of-record lesson upheld by construction (fresh explorer
#     paste treated as hypothesis; live probes arbitrate; source seals
#     last). Closes at first green run.
#   BAF8 (WATCHLIST): dead/unlisted by-value routes (source vs
#     swagger), copy-paste artefacts (#43515/#43514/#43569 kin), alias
#     verification via the t.* 503 pins, category-filter deep-dive.

# ---------------------------------------------------------------- GET /bankaccounts (index)

# Positive: list, sort on the HYPOTHESIS alias t (flip to regression
#   guard if the source read shows another alias), capture the first
#   row id for all round-trips. REQUIRES the fixture (>=1 account).
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
account_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Shape probe companion (type-agnostic asserts; the MANUAL jq probe in
#   the C6 section decides string-vs-number offline): shape only.
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sortorder idiom (source unread): lowercase "desc" — family-majority
#   expectation 200; a 400/503 would REFUTE the no-coercion reading.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Unknown sortorder value: 200-with-list primary expectation (family
#   majority); 400/503 = NEW finding.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on an account-specific column (label) — legal-alias hypothesis +
#   real column hypothesis; decided by this very probe.
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.label&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Family pin: unknown sortfield -> query error -> RestException(503)
#   ("Unknown column ... in 'ORDER BY'"). Doubles as ALIAS VERIFICATION
#   (BAF8): a 200 here means the alias hypothesis t is WRONG.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Limit idiom (BAF4 undecided): negative page — clamped-to-0 expected
#   (family majority); 400/500 = NEW finding.
GET http://{{hostnport}}/api/index.php/bankaccounts?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# BAF4 (limit idiom undecided): negative limit — if($limit) KIN serves
#   the UNBOUNDED list (200 non-empty); plimit-always KIN serves empty.
#   Assert stays DATA-DEPENDENT (collection shape only) until sealed.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# BAF2: non-numeric limit (typed int) -> 400. Bound-param streak probe.
GET http://{{hostnport}}/api/index.php/bankaccounts?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF2: non-numeric page (typed int) -> 400. Streak probe.
GET http://{{hostnport}}/api/index.php/bankaccounts?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# BAF4: pagination_data is ABSENT from the paste — UNBOUND, silent
#   passthrough expected, so limit=0 + pagination_data=true must serve
#   the plain LIST, 200 (the #42952 pattern in its unparameterised
#   shape). A 400/500 = NEW finding.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unbound unknown parameter: silent passthrough expected (BAF2 streak)
GET http://{{hostnport}}/api/index.php/bankaccounts?nosuchparameter=42&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: category filter (BAF3 — THE id-form filter)

# category with a real (or at least syntactically valid) id: 200 list;
#   filtering EFFECT is data-dependent (the instance may have no
#   tagged accounts) — shape asserted, count-vs-unfiltered diff left
#   to the manual 0/abc matrix in the C6 section.
GET http://{{hostnport}}/api/index.php/bankaccounts?category=1&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection
jsonpath "$" count <= 10

# BAF3 falsy arm: category=0 — falsy DROP expected if the guard is
#   if($category) (behaves as UNFILTERED, 200 full list); a typed bind
#   would yield an empty filtered list; neither is decided until the
#   run + source read. Assert stays shape-only.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?category=0&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# BAF2 x BAF3: category=abc — typed long -> 400 expected (BOUND param).
GET http://{{hostnport}}/api/index.php/bankaccounts?category=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF2: negative category (typed, binds fine): filtering behavior
#   data-dependent — shape only, never a value pin.
GET http://{{hostnport}}/api/index.php/bankaccounts?category=-5&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Index: count mode (BAF4, paste-documented)

# count=1: {"count": <n>} — number, no list payload (paste-documented:
#   "limit and page are then ignored")
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# count with a filter: count mode honours sqlfilters (ordering
#   validated at source read; multicurrency precedent: validated
#   BEFORE the count branch)
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)
HTTP 200
[Asserts]
jsonpath "$.count" exists

# BAF4 (validation-wins-over-count hypothesis): sqlfilters syntax
#   errors 400 BEFORE the count branch is reached.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# BAF4 (count-ignores-order hypothesis, paste: limit/page ignored): an
#   UNKNOWN sortfield must be IGNORED in count mode -> 200. Decisive
#   count-mode ordering probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$.count" exists

# BAF4: count + limit/page both IGNORED -> 200 {"count": n}.
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&limit=5&page=1
HTTP 200
[Asserts]
jsonpath "$.count" exists

# BAF2: limit stays typed even in count mode — junk -> 400.
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF2: count itself is typed (long) — junk -> 400.
GET http://{{hostnport}}/api/index.php/bankaccounts?count=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index: documented filter surface (sqlfilters, properties)

# sqlfilters — always-true filter on the HYPOTHESIS alias t (doubles as
#   alias verification via its 503-twin below)
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: the if($sqlfilters) guard skips parsing,
#   list still served
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE on the account-specific column label, matching
#   nothing: still a valid collection (shape-only; no value pinned)
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.label%3Alike%3A%27ZZZ-NO-SUCH-LABEL%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Family pin: unknown (legal-alias) column -> query error -> 503
#   ("Unknown column ... in 'WHERE'"). Alias verification twin of the
#   always-true probe above (BAF8).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# SOURCE-SEALED-IN-FAMILY (forgeSQLFromUniversalSearchCriteria ->
#   RestException(400)): invalid sqlfilters syntax -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.rowid%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilters=(t.label%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — restrict to a property name; restricted list served
GET http://{{hostnport}}/api/index.php/bankaccounts?properties=id&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — junk property name: _filterObjectProperties ignores
#   unknown names; list served unchanged, 200 (family behaviour).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?properties=nosuchproperty&limit=3
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilterlines — BAF-watch (paste: parameter ABSENT)
# index() shows NO sqlfilterlines parameter in the paste (a bank
#   account is itself the entity; its lines live under {id}/lines
#   which takes plain sqlfilters). Expected silent ignore
#   (#43430/#43439 family). Two-probe regression guard: if bad syntax
#   stops being 200, a binding appeared — re-pin and comment the
#   breadth tally.
# Change these asserts deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilterlines=%28t.rowid%3A%3E%3A%270%27%29&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/bankaccounts?sqlfilterlines=BAD%20SYNTAX%20HERE&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- GET /bankaccounts/{id} (get)
#
# SOURCE-SEALED 2026-11-28 (class source analysed; run-confirmed by
#   the 37-request failing run, which hit this at line 522): the BARE
#   GET {id} route is UNREACHABLE — the get() docblock annotates
#   includebalance with "{@choice 0,1}", and Restler's validator
#   treats the CHOICE-set as REQUIRED EVEN WHEN THE PARAMETER IS
#   ABSENT: bare GET /bankaccounts/{id} -> 400 ("Invalid value
#   specified for `includebalance`. Expecting integer value"). Every
#   get() probe below therefore carries an explicit includebalance
#   value; the bare-form 400 is pinned as BAF2 evidence (the only
#   KNOWN route in the series where an OPTIONAL documented parameter
#   is de-facto required by the validator). This is also a phase-2
#   BUG candidate (BCGC-kin: docblock choice notation breaking the
#   documented default) — cross-reference #43596 (parameter binding)
#   and the SIF/SPP binding findings.
#
# Positive: round-trip the runtime-captured id WITH the ONLY WORKING
#   form of the bound param — includebalance=1. EMPIRICAL RULE
#   (sealed across two live rounds 2026-11-28): bare -> 400,
#   includebalance=0 -> 400, includebalance=abc -> 400, ONLY
#   includebalance=1 -> 200. The Restler choice-validator treats 0
#   as ABSENT (PHP falsy-empty at the BINDING layer) and the
#   {@choice 0,1} annotation then makes the param required -> 400
#   "Expecting integer value". The DOCUMENTED default
#   ($includebalance = 0, "not computed" branch) is UNREACHABLE via
#   HTTP — the only served shape is the WITH-balance one. Upgrades
#   the phase-2 BUG candidate: docblock notation vs binding again,
#   and the PHP truthiness lesson now demonstrated at the validator
#   layer (the "0" falsy string, this time killing a documented
#   default value).
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}?includebalance=1
HTTP 200
[Asserts]
jsonpath "$.id" exists

# Positive: includebalance=1 — the documented extra property; the
#   balance VALUE is a BAF5 number-vs-string candidate (type asserted
#   agnostically here; the manual jq probe decides).
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}?includebalance=1
HTTP 200
[Asserts]
jsonpath "$.id" exists
jsonpath "$.balance" exists

# includebalance=0 — pin of the EMPIRICAL RULE's falsy arm: 0 is
#   rejected by the choice-validator exactly like the bare form
#   (treated as absent -> required -> 400). The "if($includebalance)
#   drop is benign" hypothesis is REFUTED at the binding layer; the
#   documented default branch is unreachable over HTTP.
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}?includebalance=0
HTTP 400
[Asserts]
jsonpath "$.error" exists

# includebalance=abc — UNBOUND string hypothesis (query long in the
#   paste => BOUND -> 400 expected, streak probe). A 200 here would
#   re-classify the param as unbound (BAF2 refinement).
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}?includebalance=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF5 SPECIMEN-VS-REAL PIN (OBSERVED across THREE live rounds
#   2026-11-28): get()/0?includebalance=1 (the all-null specimen)
#   serves rappro/clos/balance as NUMBERS (1/0/0), but the SAME
#   route on the REAL account serves rappro/clos as STRINGS ("1",
#   "0") — run-verified by the failing asserts that expected
#   isNumber. The "numerals arrive as numbers" reading from round 1
#   is thereby REFUTED for real rows: DB-loaded properties serialize
#   as STRING, specimen/constructor-set ones as NUMBER — the
#   multicurrency mixed-shape lesson (#43386) now confirmed in a NEW
#   dimension: SPECIMEN-VS-REAL divergence on the SAME field of the
#   SAME route. Asserts are TYPE-AGNOSTIC from here (the captures
#   above freeze the observed values for the run report; the manual
#   jq probe in C6 section 1 records the offline type decision).
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}?includebalance=1
HTTP 200
[Captures]
bank_rappro: jsonpath "$.rappro"
bank_clos: jsonpath "$.clos"
bank_balance_field: jsonpath "$.balance"
bank_type_lib: jsonpath "$.type_lib"
[Asserts]
jsonpath "$.id" exists
jsonpath "$.balance" exists
jsonpath "$.rappro" exists
jsonpath "$.clos" exists
jsonpath "$.type_lib" isCollection

# Negative, BARE form — binding-before-fetch pin (OBSERVED live
#   twice: under ADMINKEY here, and earlier in the LOWKEY matrix):
#   the includebalance choice-validation fires for far-out ids
#   exactly as for id=0 — a UNIFORM 400, no existence leak.
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.message" contains "includebalance"

# Negative, VALID form — the fetch-semantics pin: far-out id WITH
#   includebalance=1 -> Account::fetch(99999999) is falsy -> 404
#   envelope 'account not found' (BAF1 far-out camp, source-sealed;
#   the fetch(0)-truthy anomaly applies ONLY to 0).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999?includebalance=1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF2: non-numeric path id (typed) -> 400
GET http://{{hostnport}}/api/index.php/bankaccounts/abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative id, VALID form (the bare /-1 400s on the includebalance
#   binding exactly like every bare form — already pinned twice
#   above, not duplicated): Account::fetch(-1) expected falsy ->
#   404 'account not found'. A 200 here would mean the fetch(0)
#   truthy anomaly extends to negatives (would refute the
#   source-reading — fetch guards typically reject id<0 outright).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/-1?includebalance=1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF1 CAMPS OBSERVED (live probe 2026-11-28): id=0 resolves as a
#   HYBRID unseen in the tally. BARE /0 -> 400 blaming the OPTIONAL
#   includebalance param ("Invalid value specified for `includebalance`.
#   Expecting integer value") — Restler validates the query param
#   BEFORE the route body runs, with NO includebalance present at all.
#   /0?includebalance=0 -> 400 (falsy DROP: the integer binds, the
#   if($includebalance) branch skips, ... and the route still 400s —
#   the drop does NOT rescue id=0). /0?includebalance=1 -> 200
#   ALL-NULL SPECIMEN (initAsSpecimen()-shaped: 'specimen':0,
#   'balance':0, 'rappro':1, 'clos':0, sepa defaults, 'type_lib' array;
#   every business field null) — the specimen camp, productlots-KIN.
#   Pin history: (1) 2026-11-28 BY-ANALOGY 404 predicted; (2)
#   2026-11-28 live probe REFUTED, hybrid observed.
#   The includebalance=1 probe is MOVED INTO the get() section below;
#   this pair pins the hybrid exactly.
# Change these asserts deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/0
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.message" contains "includebalance"

GET http://{{hostnport}}/api/index.php/bankaccounts/0?includebalance=0
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /bankaccounts/{id}/balance (subresource)

# Positive: balance of the runtime-captured account. The response is a
#   bare scalar per the paste ("double") — TYPE is exactly the BAF5
#   question: asserted existence-of-body only here.
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}/balance
HTTP 200
[Asserts]
body exists

# Negative: balance of a far-out id -> 404 SOURCE-SEALED (gate-first
#   hasRight('banque','lire'), then fetch -> falsy for far-out ids ->
#   404 'account not found')
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999/balance
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF2: non-numeric id on the subroute (typed) -> 400
GET http://{{hostnport}}/api/index.php/bankaccounts/abc/balance
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF1 subroute camp OBSERVED (live paste 2026-11-28, source-sealed
#   same day: Account::fetch(0) is TRUTHY, the !$result 404-guard
#   passes, solde(1) runs on the null account and serves 0):
#   id=0 on the balance subresource -> 200 with a ZERO balance, not
#   404. The majority-prediction camp is REFUTED here. Per-route
#   discipline preserved (the get() route kept the 200/400 hybrid).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/0/balance
HTTP 200
[Asserts]
body exists

# ---------------------------------------------------------------- GET /bankaccounts/{id}/lines (subresource)

# Positive: lines of the runtime-captured account. An account with NO
#   transactions serves an empty array — both shapes pass this assert.
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}/lines
HTTP 200
[Captures]
bank_line_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" isCollection

# sqlfilters on the lines route (paste-documented param): always-true,
#   hypothesis alias t (same BAF8 flip logic as index)
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}/lines?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters bad syntax -> 400 (family pin; forgeSQL error path)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}/lines?sqlfilters=(t.rowid%20%3E%3A%20%27x%27)
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: lines of a far-out id -> 404 expected
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF2: non-numeric id on the lines subroute -> 400
GET http://{{hostnport}}/api/index.php/bankaccounts/abc/lines
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF1 {id}/lines camp OBSERVED-LIVE (2026-11-28 paste AND this run):
#   id=0 -> 200 [] — fetch(0) is truthy, the 404-guard passes, and
#   the fk_account=0 filter serves an empty list. Not 404: the
#   fetch(0)-truthy anomaly explains this route too (source-sealed).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/0/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- GET /bankaccounts/{id}/lines/{line_id}/links (depth-3)

# Positive round-trip ONLY when the parent account has at least one
#   line: the capture bank_line_id is taken from the lines probe ABOVE
#   (multicurrencies rate_row_id precedent: fixture-dependent captures
#   are legitimate — precondition documented in the header: the FIRST
#   listed account must have >=1 transaction; 2026-11-28 live paste
#   confirms account rowid 1 carries "A LOT OF LINES"). PARSE LESSON
#   (2026-11-28): [Filters] is NOT a valid hurl section in the runner's
#   version (valid: QueryStringParams, FormParams, MultipartFormData,
#   Cookies, Options) — conditional skipping is impossible; the
#   capture-chain dependency IS the skip mechanism (a failed capture
#   fails only its own request, loudly).
# Positive round-trip on the captured line:
GET http://{{hostnport}}/api/index.php/bankaccounts/{{account_id}}/lines/{{bank_line_id}}/links
HTTP 200
[Asserts]
jsonpath "$" isCollection

# BAF1 depth-3 camps OBSERVED (live paste 2026-11-28): links NEVER
#   rejects — 200 [] on ALL four falsy combinations probed
#   (0/lines/0, 1/lines/0, 1/lines/1, 0/lines/1): no parent fetch, no
#   line fetch, no permission-visible rejection. The 404-envelope camp
#   is REFUTED here; the route serves an unconditional empty list.
#   Pin history: (1) 2026-11-28 BY-ANALOGY 404 predicted; (2)
#   2026-11-28 live paste REFUTED, 200-[] camp observed.
# Negative: far-out parent AND far-out line — SOURCE-SEALED: getLinks
#   fetches the ACCOUNT first and throws 404 'account not found' when
#   fetch is falsy (far-out ids ARE falsy — only fetch(0) is the
#   truthy anomaly). The by-live-analogy 200 flip is REFUTED by
#   source; 404 stands.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999/lines/99999999/links
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF1 {id}/lines camp OBSERVED (live paste 2026-11-28): id=0 on the
#   lines subroute -> 200 [] (EMPTY list, NOT 404): the route filters
#   by fk_account without any parent-existence check. The 404-envelope
#   camp is REFUTED for this route.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/0/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Far-out parent on lines — SOURCE-SEALED: getLines() fetches the
#   account and throws 404 'account not found' on falsy fetch; only
#   id=0 passes (truthy anomaly). The BY-LIVE-ANALOGY 200 flip is
#   REFUTED by source; 404 stands.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# The paste shows the LINE entity addressable OUTSIDE the parent
#   (receptions lesson made literal): {id} is NOT in this route, so
#   the falsy camp here belongs to the LINE, not the account.
# BAF1 top-level-line camp OBSERVED (live paste 2026-11-28):
#   /lines/0 -> 200 ALL-NULL SPECIMEN, line flavoured ('parent_element':
#   "", fk_product/datec/datev/amount/bank_account_ref/... families
#   present, plus a LARGE product/order block never present in the
#   account specimen — cross-class copy-paste clone of commonobject
#   properties, BAF8 watch item for the source read). The line
#   specimen's rappro/clos/ptype labels are NULL (contrast: account
#   specimen had 'rappro':1, 'clos':0). The 404-envelope camp is
#   REFUTED for this route too.
# Positive: the runtime-captured line id (capture chain = the skip
#   mechanism, see the links probe note).
GET http://{{hostnport}}/api/index.php/bankaccounts/lines/{{bank_line_id}}
HTTP 200
[Asserts]
jsonpath "$.id" exists

# BAF1 per-route pin OBSERVED (live paste 2026-11-28; run-confirmed
#   at the 66-request round, line 931): /lines/0 -> 200 all-null
#   LINE specimen. A vestigial 404 from the source-round rewrite
#   was corrected here — pin history: (1) by-analogy 404 predicted;
#   (2) live paste REFUTED (200 specimen observed); (3) source
#   sealed (fetch(0)-truthy anomaly passes the !$result guard);
#   (4) run CONFIRMED 200 (fourth facet).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/lines/0
HTTP 200
[Asserts]
jsonpath "$.id" exists

# Far-out line — SOURCE-SEALED: getDetailAccountLine() fetches the
#   LINE and throws 404 'account Line not found' on falsy fetch; the
#   by-live-analogy 200-flip is REFUTED by source (fetch(farout) is
#   falsy — only fetch(0) is the truthy anomaly); 404 stands, and
#   the falsy-vs-farout SPLIT is confirmed source-side (id=0 ->
#   200 specimen, far-out -> 404): a NEW tally distinction, fully
#   explained by the fetch(0)-truthy anomaly.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/lines/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# BAF2: non-numeric line id (typed long in the paste) -> 400
GET http://{{hostnport}}/api/index.php/bankaccounts/lines/abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# BAF1 top-level-line camp OBSERVED (paste 2, source-sealed, and
#   RUN-CONFIRMED at the 66-request round, line 937): /lines/0 ->
#   200 all-null LINE specimen. A DUPLICATE probe of the one pinned
#   above — kept (not deduplicated mid-red/green cycle) and aligned
#   to the same 200 camp; pin history shared with the probe above.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/bankaccounts/lines/0
HTTP 200
[Asserts]
jsonpath "$.id" exists

# ---------------------------------------------------------------- COMBINED SMOKE (all documented filters at once)

# English section name per the hook discipline. Everything documented
#   by the paste's index() signature in ONE call (list mode).
GET http://{{hostnport}}/api/index.php/bankaccounts?sortfield=t.rowid&sortorder=DESC&limit=5&page=0&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id,label
HTTP 200
[Asserts]
jsonpath "$" isCollection
jsonpath "$" count <= 5

# Count-mode combined smoke (count documented to ignore limit/page)
GET http://{{hostnport}}/api/index.php/bankaccounts?count=1&sqlfilters=(t.rowid%3A%3E%3A%270%27)&sortfield=t.rowid&sortorder=DESC
HTTP 200
[Asserts]
jsonpath "$.count" exists

# ---------------------------------------------------------------- C6 MANUAL PROBES (shell-agnostic, PENDING until run)
# All manual probes: POSIX loop, export on its own line, NO
#   placeholders in commands; jq --arg for variable interpolation —
#   never a bare variable pipeline inside the jq string (the CID
#   lesson, hard-learned in the multicurrencies episode). curl with
#   http://${DOLIHOST}/api/index.php/... — never a hardcoded host.
#   Never {{lowkey}} in this file; LOWKEY only via manual curl.
#
# 0) FIXTURE GAUGE (before the first run):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?limit=100&count=1"
#    {"count":0} = MISSING FIXTURE (enable the bank module, create one
#    account in the GUI — or POST /bankaccounts outside this file).
#    RESULT-FIELD: OBSERVED 2026-11-28 — {"count":5} (five accounts,
#    fixture OK; captures AID=1 (label danskebank83), LID=1).
#
# 1) SERIALISATION JQ PROBES (BAF5 decides; FIRST in the sequence —
#    the row shape gates everything downstream):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?limit=5" \
#        | jq '.[0].id, (.[0].id|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?limit=1" \
#        | jq 'keys'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts/1?includebalance=1" \
#        | jq '.balance, (.balance|type)'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts/1/balance"
#    Paste prediction: id shape UNKNOWN (seven-STRING majority vs the
#    shipments lone NUMBER — no default assumed); balance type
#    UNKNOWN (number-vs-string = the #43386 dimension). Record BOTH
#    facets in the result. RESULT-FIELD: OBSERVED 2026-11-28 —
#    DECISIONS ALL SEALED:
#    (a) list id STRING "1" — the STRING tally grows to EIGHT
#        (bankaccounts joins; shipments stays the lone NUMBER);
#        label STRING "danskebank83";
#    (b) get()/1?includebalance=1 on the real account: balance
#        NUMBER -397785.89714 (computed value serialized as
#        NUMBER!), rappro STRING "1", clos STRING "0" — MIXED TYPES
#        IN THE SAME OBJECT (the multicurrency rate-STRING/
#        date_sync-NUMBER pattern, now on bank accounts);
#    (c) {id}/balance bare scalar: NUMBER -397785.89714, matching
#        the includebalance field exactly (consistent computation,
#        consistent NUMBER type on BOTH surfaces);
#    (d) line fields: amount STRING "0.00000000" (the
#        multicurrency-rate analogue — a numeric quantity as text),
#        datec/datev/dateo ALL NUMBER — the rate/dates pair
#        transplanted onto bank lines;
#    (e) specimen /0?includebalance=1: rappro NUMBER 1, clos
#        NUMBER 0, balance NUMBER 0 — SPECIMEN-VS-REAL split
#        confirmed offline (specimen numbers, real rows strings).
#
# 2) ZERO-RIGHTS MATRIX (LOWKEY) — covers ALL SIX GET gates. Interpret
#    ONLY AFTER the source read seals the gate order (BAF6). If the
#    class fetches before hasRight (the #43620 pattern), the oracle
#    predicts: index() -> 403 (gate-first), but get/balance/lines/
#    links/line -> 404 on NONEXISTENT targets BEFORE the 403, and 403
#    on an EXISTING id — both facets must be captured:
#      export LOWKEY=<zero-rights-key>
#      export DOLIHOST=localhost:8080
#      for p in "?limit=1" "/99999999" "/0" \
#        "/99999999/balance" "/99999999/lines" \
#        "/99999999/lines/99999999/links" "/lines/99999999" \
#        "?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=1"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#          "http://${DOLIHOST}/api/index.php/bankaccounts${p}"; done
#    SOURCE-SEALED UPDATE (2026-11-28, after the class read): the
#    #43620 oracle pattern is REFUTED for this class — EVERY GET
#    route opens with 'if (!hasRight('banque','lire')) throw 403'
#    BEFORE any fetch. EXPECTED becomes: 403 on ALL eight probes
#    (zero oracle). The binding-quirk note below retains its value:
#    a 400/404 instead of 403 anywhere = NEW gate-order divergence.
#    RESULT-FIELD: OBSERVED 2026-11-28 (LOWKEY zero-rights matrix,
#    13 probes + 2 existing-id controls, AID=1 and AID=2): 403 on
#    ALL probes EXCEPT the two bare-form probes — /99999999 and
#    /0 (WITHOUT includebalance) both -> 400. Reading sealed: the
#    includebalance choice-binding runs BEFORE the permission gate
#    (Restler validation precedes the route method), while the
#    hasRight check precedes every fetch. So NO existence oracle
#    (403 uniformly on valid forms, existing and far-out ids
#    indistinguishable: AID=1 -> 403, AID=2 -> 403, /99999999 ->
#    403), but a BINDING-BEFORE-GATE divergence IS live: a
#    zero-rights key receives includebalance-validation errors for
#    account ids it has no right to know about. Attacker value:
#    NONE (validation errors leak no existence info — the 400
#    fires identically for /0 and /99999999). Facets of BAF6:
#    (i) no #43620 fetch-first oracle — CONFIRMED NEGATIVE;
#    (ii) binding-before-gate — OBSERVED, benign leakage-wise,
#    pinned as the divergence datapoint instead.
#    Also probe an EXISTING id under LOWKEY for the 403 facet:
#      export AID=<a runtime-captured account id>
#      curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts/${AID}"
#    LIVE-PROBE ADDITION (2026-11-28): get()/0 WITHOUT includebalance
#    returns 400 (the includebalance binding quirk — BAF1 hybrid), so
#    under a LOWKEY the \"/0\" probe will likely ALSO yield 400, not
#    404: record the 400 accordingly, and probe
#    \"/0?includebalance=1\" separately — a 200 there would mean the
#    specimen response BYPASSES the read gate entirely (a stronger
#    BAF6 candidate: the all-null specimen served to a zero-rights
#    key). Either way: ANY non-403 answer from a zero-rights key on
#    these routes proves routing/binding happens BEFORE the gate.
#    RESULT-FIELD: PENDING FIRST RUN.
#
# 3) LOWKEY-FLIP (gate-resolver + write-only check): GUI trait
#    CORRECTED 2026-11-28 by maintainer observation — the bank
#    permissions GUI FORCES read when granting write, exactly as
#    multicurrency's did: a true write-only key is an IMPOSSIBLE
#    state here too. CLOSED as N/A-BY-DESIGN (the earlier
#    rows-are-independent reading, made from the screen listing
#    alone, is refuted). The probes stay for RE-verification only
#    if a future Dolibarr version decouples the rows.
#      export WKEY=<bank-write-only-key>
#      for p in "?limit=1" "/1" "/1/balance"; do \
#        printf "probe %s -> " "${p}"; \
#        curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${WKEY}" \
#          "http://${DOLIHOST}/api/index.php/bankaccounts${p}"; done
#    (ids illustrative — substitute runtime-captured values.)
#    If the GUI refuses write-without-read, CLOSE as N/A-by-design and
#    document the trait (multicurrency precedent).
#    RESULT-FIELD: CLOSED N/A-BY-DESIGN 2026-11-28 — maintainer
#    confirms the GUI forces read on any write grant; write-only is
#    an impossible state (multicurrency precedent, now FOUR-fold in
#    the series).
#
# 4) CATEGORY FALSY-DROP MATRIX (BAF3 decisive diff):
#      export DOLIHOST=localhost:8080
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?count=1" \
#        | jq '.count'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?category=0&count=1" \
#        | jq '.count'
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?category=999999&count=1" \
#        | jq '.count'
#    Readings: category=0 count == unfiltered count AND category=farout
#    count == 0 -> the filter BINDS but 0 drops through a falsy guard;
#    category=0 == farout == 0 -> the 0 binds as a real (impossible)
#    filter; 400 on any -> typed reject unexpected for 0. RESULT-FIELD:
#    OBSERVED 2026-11-28 — unfiltered: 5; category=0: 5 (== unfiltered:
#    the > 0 guard DROPS 0, unfiltered list served); category=999999: 0
#    (filter BINDS on positive ints, no rows tagged). Exactly the
#    source-sealed 'if ($category > 0)' behaviour. CLEAN, closed.
#
# 5) FILTER-LEAKAGE GUARD (sqlfilters on index; tostring on BOTH
#    sides — the shipments string-vs-number trap):
#      export DOLIHOST=localhost:8080
#      export AID=<a runtime-captured account id>
#      curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#        "http://${DOLIHOST}/api/index.php/bankaccounts?sqlfilters=(t.rowid%3A%3D%3A%27${AID}%27)&limit=100" \
#        | jq --arg aid "${AID}" '[.[] | select((.id|tostring) != $aid)] | length'
#    Expected 0. Nonzero = leakage -> BUG issue (phase 2).
#    RESULT-FIELD: OBSERVED 2026-11-28 — jq guard (tostring on BOTH
#    sides, --arg aid) returned 0 foreign rows (AID=1). NO leakage.
#    CLEAN, closed.
#
# 6) SOURCE-READ CHECKLIST — CLOSED 2026-11-28 (full class source
#    pasted and analysed; api_bankaccounts.class.php):
#    (i) gate keys + order: hasRight('banque', 'lire') GATE-FIRST on
#        ALL six GET routes (index, get, getLines, getLinks,
#        getDetailAccountLine, getBalance); write/configurer;
#        modifier; reconcile = lire + consolidate DOUBLE gate —
#        BAF6 closed as a NEGATIVE (no oracle);
#    (ii) table alias: **t** on BOTH tables (bank_account AS t,
#        bank AS t) — the t.* pins are NORMATIVE — BAF8 flip moot;
#    (iii) category guard: if ($category > 0) STRICT comparison —
#        BAF3 closed (drop-by-comparison);
#    (iv) limit idiom: if($limit) + plimit($limit+1) + inner page
#        clamp — BAF4 closed (multicurrencies KIN);
#    (v) @since tags: count = "Parameter count" (@since 25.0.0),
#        reconcile = "@since 25.0.0 Initial implementation";
#        includebalance carries {@choice 0,1} — the BAF2 quirk root;
#    (vi) copy-paste artefacts: the getLines() docblock example
#        "(t.ref:like:'SO-%')" names the SUPPLIER-ORDER ref pattern
#        on the BANK table (t.ref EXISTS as a real bank column, so
#        the example WORKS syntactically — unlike the multicurrency
#        product_id, NOT a 503 here; the SO-% prefix hint is however
#        a supplier-orders foreign flavour — BAF8 comment-grade
#        note, no probe pin needed);
#    (vii) unlisted by-value routes: NONE — the class declares NO
#        @url by-ref/by-label route — BAF8 closed NEGATIVE.
#    The grep command is retained for RE-runs (instance moves):
#      grep -n "function index\|function get\|hasRight\|@url\|@param\|@since\|FROM " htdocs/compta/bank/class/api_bankaccounts.class.php

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# Bank accounts MAY carry an owner (proprio/socid field family) but the
#   paste exposes NO socid query filter — unlike shipments (which had
#   socid scoping probes) this surface looks closer to the
#   multicurrency plan pending the source read:
#   1) If a socid/owner filter HIDES in source but not swagger, add the
#      shipments scoping probes (tostring on BOTH sides) — BAF8 note.
#   2) If accounts are truly unsocoped: ENTITY-scoping question
#      applies (index() WHERE t.entity IN (getEntity(...)) is the
#      family-default hypothesis) — cross-entity probe = manual
#      two-instance work, PENDING, non-blocking.
#   3) ACCOUNT-vs-THIRDPARTY cross-object: bank accounts reference
#      thirdparties for some account types — any leak probe waits for
#      the source read (no speculative probes without a real field).
#   RESULT-FIELDS: PENDING.

# ---------------------------------------------------------------- FAMILY STATUS BEFORE THIS EPISODE
# Bankaccounts is the FIRST suite whose GET surface combines: THREE
#   subresources of differing depths ({id}/balance, {id}/lines,
#   {id}/lines/{line_id}/links), a TOP-LEVEL sibling route for the
#   line entity (/lines/{line_id}), and an id-form list filter
#   (category) — the first genuine target for the falsy-PARAM matrix
#   since the series began. Existing tallies (#43307 falsy-id camps,
#   #43386 + #43568 serialization, #43430/#43439 sqlfilterlines
#   breadth, #42952 post-merge footprint, #43515/#43514/#43569 dead
#   code, #43596 permission-gating kin, #43620 gate order) gain
#   EVIDENCE as COMMENTS on the existing issues — never duplicates.
#   New BUG/WISH Markdown files only in phase 2, on explicit request.
