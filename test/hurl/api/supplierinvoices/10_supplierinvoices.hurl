# test/hurl/api/supplierinvoices/10_supplierinvoices.hurl
#
# ATTRIBUTION: This test suite was generated with the help of Lumo AI
#   (Lumo, the AI assistant from Proton — lumo.proton.me), reviewed and
#   maintained by human contributors. AI-assisted generation note applies
#   to all hurl tests, issue files and probe scripts in this series.
#
# PLACEMENT NOTE: in the real repo tree this file lives at
#   api/supplierinvoices/10_supplierinvoices.hurl
# run.sh only executes hurl files matching the glob */10*.hurl, so it
# MUST sit one directory deep. The helper script install_supplierinvoices.sh
# (next to run.sh) performs the move. In this flattened workspace the
# file sits at the root beside the other suites.
#
# GET-only tests for the supplier invoices (FactureFournisseur) API — every
#   GET endpoint documented in the Swagger spec for /supplierinvoices and
#   the class FactureFournisseursApi
#   (htdocs/fourn/class/api_supplier_invoices.class.php — SOURCE READ
#   2026-11-26, pasted Swagger spec, BEFORE pins were set):
#     GET /supplierinvoices                (index: list)
#     GET /supplierinvoices/{id}           (get: one invoice)
#     GET /supplierinvoices/{id}/lines     (getLines)
#     GET /supplierinvoices/{id}/payments  (getPayments)
#   NOTE vs supplierorders: SupplierInvoices exposes NO /contacts route,
#     NO by-value GET routes (no /ref, no /ref_ext) — the GET-only
#     surface is exactly the four above, but with ONE MORE subresource
#     than supplierorders (/payments).
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Suppliers/Invoices module enabled. The LIST section
#   captures "$[0]" and therefore requires at least one supplier invoice
#   to exist on the instance. The lines/payments sections require the
#   captured invoice to exist; both may legitimately be empty.
# Instance: INSTANS: commit 55dda317131005aac62bafcb15809c043a664c87
#   (HEAD -> hurl_tests_api_supplierinvoices, origin/develop, origin/HEAD,
#   develop; Laurent Destailleur, 2026-11-26 10:03:19 +0000,
#   "NEW: Increase size of label of social contributions to 128").
#   All OBSERVED pins in this file refer to this commit once the first
#   run has been executed against it; re-annotate each BY-ANALOGY pin
#   with an OBSERVED-date when verified.
# STATUS OF PINS: FULLY OBSERVED (2026-11-26): full 52-request suite
#   run green on the instance above (admin API key, ./run.sh
#   10_supplierinvoices), every BY-ANALOGY pin either confirmed or
#   corrected with an OBSERVED comment — corrections this run:
#   sqlfilterlines matrix (all five probes -> 200, SIF2),
#   pagination_data=NOTABOOLEAN -> 400 (strict typed-param binding),
#   /0/payments -> 400 (SIF7). "Change this assert deliberately,
#   never accidentally."
#   STILL PENDING (manual curl, outside run.sh): C6 zero-rights probes
#   and the SIF4 thirdparty_ids=0 diff probe — see the relevant
#   sections below and supplier_family_common_issues.md.
#
# ENVIRONMENT / VARIABLES:
#   - All requests: http://{{hostnport}}/api/index.php/... (runner env)
#   - Manual curl examples in issues/PRs: http://${DOLIHOST}/api/index.php/...
#     with DOLIHOST e.g. localhost:8080 — never a hardcoded host.
#   - DOLAPIKEY supplied by the runner environment (admin key).
#
# PORTABILITY: no hardcoded ids, refs or counts — everything captured at
#   runtime. VERIFY THE ROW SHAPE FIRST with jq before trusting captures:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/supplierinvoices?limit=1" | jq '.[0]'
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/supplierinvoices?limit=1" \
#       | jq '.[0].id, (.[0].id|type)'
#   supplierorders precedent: id is a STRING (C5/#43386 family). If id
#   here is a number, switch bare-compare asserts to the toString form:
#     jsonpath "$.id" toString == "{{invoice_id}}"
#   No asserts on free text — only status codes, ids and shapes.
#
# PERMISSION DEPENDENCE: read rights on supplier invoices are required
#   for all sections; get()/getLines()/getPayments() are expected to call
#   DolibarrApi::_checkAccessToResource(). A 403 during a 4-profile
#   API-key matrix run is a permission finding, not a pin change.
#   LOW-PRIVILEGE NEGATIVE CONTROL (C6, pinned below): with a zero-rights
#   key, list, /0 and a sqlfilterlines probe must answer 403 with a
#   clean JSON envelope and NO raw SQL text (supplierorders precedent:
#   permission gates run BEFORE the falsy-id path and the SQL build).
#   If {{lowkey}} is not supplied, these probes fail visibly — provision
#   the zero-rights key before claiming C6 coverage for this class.
#
# SOURCE-NOTED PARAMETER FACTS (pasted Swagger spec / class docs):
#   - index(): sortfield, sortorder, limit, page,
#     thirdparty_ids (example '1' or '1,2,3'),
#     status : draft | unpaid | paid | cancelled (FOUR values only —
#     narrower enum than supplierorders' seven),
#     sqlfilters, properties, pagination_data (default false;
#     page starts from 0).
#   - NOTE: the spec shows NO product_ids parameter on
#     supplierinvoices (unlike supplierorders). Do NOT probe a
#     product_ids filter here; if the class source shows one, that is a
#     SIF finding (spec/source divergence) — file it then.
#   - getLines(id), getPayments(id) take only the path id.
#   - The spec does NOT list sqlfilterlines on /supplierinvoices, but
#     the family checklist C1 assumes the shared handler has it. The
#     sweep below probes it ANYWAY: if the parameter is not accepted,
#     the OBSERVED statuses replace the pins and the spec/source
#     divergence itself is catalogue-worthy (SIF2).
#
# STATUS ANNOTATIONS: pins marked OBSERVED-BY-ANALOGY follow the
#   supplierorders/tasks/projects suites and MUST be re-verified at
#   first run and re-annotated with OBSERVED-date, or corrected.
#
# FINDINGS CATALOGUED (SIF = SupplierInvoices Finding; fill at first
#   run; candidates from the family checklist are pre-listed):
#   SIF1 (OBSERVED 2026-11-26, commit 55dda317): GET /supplierinvoices/0
#     -> 200 with the all-null SPECIMEN object ("specimen" present) —
#     the class JOINS the 200-specimen camp, same as supplierorders,
#     projects, thirdparties, members (#43307 tally). First-run pin
#     passed as provisionally set; re-annotate the /0 request comment
#     below with this OBSERVED date on the next edit pass. Note: per
#     SIF7 the SUBRESOURCES diverge (lines [], payments 400) — tally
#     entries must cite the route, not just the class.
#   SIF2 (FINALISED 2026-11-26, commit 55dda317): sqlfilterlines is NOT
#     a bound parameter on /supplierinvoices — the Swagger spec does
#     not list it, and the live endpoint SILENTLY IGNORES every value
#     (all five matrix probes -> 200 = the unfiltered list, incl.
#     invalid syntax that sibling supplierorders rejects with 400).
#     DIVERGENCE from the #43388 family: no comment on #43388 from this
#     resource; checklist C1 supplierinvoices row = "N/A — parameter
#     absent, silently ignored". Refined by the pagination_data=400
#     finding (OBSERVED same day): the endpoint IS strict on typed,
#     bound parameters — silence applies only to UNBOUND parameters
#     (params not declared on this class), which pass through with no
#     validation. Candidate issue reframed: unknown query params are
#     accepted without rejection on this endpoint (contrast typed-param
#     400 strictness).
#   SIF3 (candidate, C4b): strict 400 validation of thirdparty_ids
#     pattern and status enum — supplierorders enforced both (SOF3).
#   SIF4 (OBSERVED 2026-11-26, commit 55dda317, manual curl diff):
#     thirdparty_ids=0 DROPS the filter and returns ALL invoices —
#     pagination.total 46 vs 46 unfiltered, empty diff. Falsy-param
#     family confirmed again (SOF5, projects category=0). No separate
#     issue; same-class evidence for the falsy-param tally. Junk
#     non-falsy values still 400 (SIF3 pin passed).
#   SIF5 (candidate, C3): limit=0 + pagination_data=true -> 200 data
#     envelope (PR #42952 fixed all four family classes at once).
#   SIF6 (candidate, C5): mixed serialization in payloads (id string,
#     numbers elsewhere) — note, do not assert.
#   SIF7 (OBSERVED 2026-11-26, commit 55dda317): subresource id=0
#     behaviour DIFFERS within the class: /0/lines -> 200 empty array
#     (constraint applied, supplierorders-contacts-like), /0/payments
#     -> 400 error envelope (falsy id rejected; spec lists 400 for
#     this route). Lesson for the falsy-id tally (#43307/#43385):
#     camp must be probed PER ROUTE, not per class — get() joined the
#     200-specimen camp (SIF1) while its own payments subresource
#     rejects with 400.
#   SIF8 (CLOSED, OBSERVED 2026-11-26, commit 55dda317, manual curl):
#     count parameter works fully: ?count=1 -> 200 {"count":46}
#     (matches SIF4 total), honours filters (46->2 with status=unpaid),
#     status validation precedes the count return (400 for junk),
#     limit/page/pagination_data all ignored as documented. The
#     instance's OWN swagger.json (explorer) DOES list count — the
#     pasted Swagger snapshot was simply stale (pre-25.0.0). NO
#     divergence finding; no issue. Pinned in the count section as
#     regression guards only. Methodical note: ALWAYS pull the
#     instance's explorer/swagger.json as the spec of record, not a
#     pasted snapshot of unknown vintage.
#
# ---------------------------------------------------------------- GET /supplierinvoices (index)

# Positive: list, ascending sort on the conventional sortfield, capture
#   first row id for later subresource lookups.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
invoice_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on another conventional column of the invoice table (ref)
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (supplierorders SOF / tasks P2 / projects): unknown
#   sortfield flows into SQL ORDER BY -> 503. Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — coerced, no 500 (tasks/projects/supplierorders camp)
GET http://{{hostnport}}/api/index.php/supplierinvoices?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED-BY-ANALOGY: negative limit -> 200 (coerced)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (tasks/projects/supplierorders P1): non-numeric
#   limit -> 400
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY: non-numeric page -> 400
GET http://{{hostnport}}/api/index.php/supplierinvoices?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# limit=0 with pagination_data — formerly DivisionByZeroError, fixed by
#   PR #42952 on all four supplier-family classes at once. OBSERVED-BY-
#   ANALOGY (confirmed on supplierorders 2026-11-25); re-verify here
#   (checklist C3 closes with this run).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# ---------------------------------------------------------------- Index filters: documented params

# thirdparty_ids — spec example '1' or '1,2,3'; "0" is syntactically
#   consistent with the digit/comma pattern (never hardcoded to real ids).
#   SIF4 candidate (falsy-param family, SOF5): manually diff
#   .pagination.total of this request (with pagination_data=true added)
#   against the unfiltered baseline two requests below — empty diff
#   means the filter is DROPPED for the falsy 0. Count NOT pinned
#   (data-dependent); status pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?thirdparty_ids=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unfiltered baseline with pagination envelope for the SIF4 diff probe
#   (manual: compare .pagination.total of the two requests)
GET http://{{hostnport}}/api/index.php/supplierinvoices?properties=id&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists

# thirdparty_ids — junk value violating the digit/comma pattern:
#   SIF3 candidate (supplierorders SOF3 enforced the documented pattern
#   with 400). Verify at first run; a silently-ignored 200 would be a
#   DIVERGENCE from supplierorders — pin whichever is OBSERVED.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?thirdparty_ids=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists

# status — all four documented keyword values; each must be accepted
#   even if no matching invoices exist (empty collection is fine)
GET http://{{hostnport}}/api/index.php/supplierinvoices?status=unpaid
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/supplierinvoices?status=paid
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/supplierinvoices?status=draft
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/supplierinvoices?status=cancelled
HTTP 200
[Asserts]
jsonpath "$" isCollection

# status — unknown keyword: SIF3 candidate. supplierorders 400'd unknown
#   status keywords (SOF3); pin 400 BY-ANALOGY, verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?status=NOSUCHSTATUS
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Index filter: sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: valid (empty) collection
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid collection
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (supplierorders/projects/tasks): unknown sqlfilters
#   column -> 503 (syntax validated, column names not). Verify at first run.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY: invalid sqlfilters syntax -> 400
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.datef%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — SIF2 candidate / checklist C1: the #43388 matrix.
#   CAVEAT: the pasted Swagger spec does NOT list sqlfilterlines on
#   /supplierinvoices (unlike supplierorders). If the parameter is NOT
#   accepted at all, these requests answer something unexpected — that
#   ITSELF is a finding (spec/source divergence): re-pin whatever is
#   OBSERVED and catalogue it as SIF2 accordingly. Pins below assume the
#   supplierorders-family handler.
# Change these asserts deliberately, never accidentally.

# sqlfilterlines with t.-aliased LINE column — OBSERVED 2026-11-26,
#   instance commit 55dda317131005aac62bafcb15809c043a664c87: HTTP 200 —
#   CONTRARY to supplierorders, where this exact probe answered 503
#   "Unknown column 't.qty' in 'WHERE'" (the #43388 alias trap). Two
#   readings, disambiguated by the NEXT probes below: (a) the parameter
#   IS bound and t.-aliases resolve on line columns here too, or
#   (b) the parameter is SILENTLY IGNORED (the spec does not list it on
#   /supplierinvoices) and 200 is just the unfiltered list. Decisive
#   evidence: sqlfilterlines=(bad syntax) -> 400 proves binding, -> 200
#   proves ignoring; and sqlfilterlines=(t.nosuchcolumn:=:'1') -> 503
#   proves binding, -> 200 proves ignoring. Interpret the whole matrix
#   before touching SIF2/#43388: identical ignoring-behaviour across
#   ALL five probes = spec/source divergence finding (SIF2), NOT a
#   family-width confirmation of #43388.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines with a BARE line column — OBSERVED 2026-11-26, commit
#   55dda317131005aac62bafcb15809c043a664c87: HTTP 200 — but under the
#   SEALED silent-ignore verdict this 200 is the unfiltered list, NOT a
#   working filter (contrast supplierorders where this bare form was
#   the WORKING filter). Kept as a portability regression pin only.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilterlines=(qty%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines via t.-alias on a real HEADER column — OBSERVED
#   2026-11-26, commit 55dda317131005aac62bafcb15809c043a664c87: HTTP
#   200 — same silent-ignore 200, kept as regression pin only.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilterlines=(t.rowid%3A%3E%3A%270%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — unknown column: OBSERVED 2026-11-26, instance commit
#   55dda317131005aac62bafcb15809c043a664c87: HTTP 200 — CONTRARY to the
#   supplierorders/#43388 family, where this exact probe answered 503
#   with a raw SQL message. Combined with t.qty -> 200 (above), the
#   dominant hypothesis is now: sqlfilterlines is NOT a bound parameter
#   on this endpoint (the pasted Swagger spec indeed does not list it)
#   and EVERY value is silently ignored — the 200s are the unfiltered
#   list. DECISIVE CROSS-CHECKS before SIF2 is finalised:
#     a) the NEXT probe ((bad syntax)) must answer 400 if the parameter
#        is bound — a 200 there seals the silent-ignoring verdict;
#     b) grep the class source — decisive on its own:
#          grep -n sqlfilterlines \
#            htdocs/fourn/class/api_supplier_invoices.class.php
#        No hit = parameter does not exist on this class; the two 200s
#        are explained and SIF2 becomes a spec/source-divergence +
#        silent-ignore finding, NOT a family-width #43388 confirmation
#        (no comment on #43388 from this resource then; the family
#        checklist C1 supplierinvoices row gets "N/A — parameter
#        absent").
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilterlines=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilterlines — invalid syntax: OBSERVED 2026-11-26, instance commit
#   55dda317131005aac62bafcb15809c043a664c87: HTTP 200 — the DECISIVE
#   probe. supplierorders/#43388 answers 400 "Bad syntax" validation
#   here; this endpoint serves the unfiltered list instead. Sealed
#   verdict for the whole matrix: sqlfilterlines is NOT a bound
#   parameter on /supplierinvoices — every value is silently ignored
#   (t.qty -> 200, qty -> 200, t.rowid -> 200, t.nosuchcolumn -> 200,
#   bad syntax -> 200: five identical unfiltered-list answers). SIF2
#   finalised as: DOCUMENTED/PARAMETER divergence — the Swagger spec
#   for /supplierinvoices does not list sqlfilterlines, and the live
#   endpoint silently ignores it instead of rejecting unknown query
#   params. NOT a #43388 family-width confirmation; no comment on
#   #43388 from this resource. Checklist C1 supplierinvoices row =
#   "N/A — parameter absent, silently ignored". Contrast finding for
#   the family tally: sibling classes DIVERGE on this parameter's
#   very existence.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?sqlfilterlines=(bad%20syntax)&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- PARAM count (added 25.0.0, NOT in the pasted Swagger snapshot)
# Source docblock (api_supplier_invoices.class.php) documents @param
#   int $count: "Set to 1 to return only the number of records matching
#   the filters, as an array with the key 'count', instead of the list
#   of records (limit and page are then ignored)" — @since 25.0.0.
#   The pasted Swagger snapshot did NOT list this parameter — likely
#   version skew (generated from an older release), NOT necessarily a
#   generation bug. DECISIVE CHECK against the instance itself:
#     curl -s -H "DOLAPIKEY: ${ADMINKEY}" \
#       "http://${DOLIHOST}/api/index.php/explorer/swagger.json" \
#       | jq '.paths."/supplierinvoices".get.parameters[].name'
#   count listed -> pasted spec was stale, no divergence finding;
#   count MISSING on the instance's own swagger.json -> real
#   documentation-generation finding (Restler swallows the @param).
# ALL FIVE PINS BELOW: OBSERVED 2026-11-26, commit 55dda317, manual
#   curl (SIF8). Behaviour is fully correct and the instance's own
#   explorer/swagger.json DOES list count — the pasted Swagger
#   snapshot was stale (pre-25.0.0), NOT a documentation bug. This
#   section pins the feature so it cannot silently regress.
#   Spec of record going forward: the instance's
#   /explorer/swagger.json, never a pasted snapshot of unknown
#   vintage.
# Change these asserts deliberately, never accidentally.

# Bare count — the whole-list total
GET http://{{hostnport}}/api/index.php/supplierinvoices?count=1
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# count honours the status filter
GET http://{{hostnport}}/api/index.php/supplierinvoices?count=1&status=unpaid
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# status validation wins over count (checked BEFORE the early return)
GET http://{{hostnport}}/api/index.php/supplierinvoices?count=1&status=NOSUCHSTATUS
HTTP 400
[Asserts]
jsonpath "$.error" exists

# limit/page ignored in count mode — same count as bare count
GET http://{{hostnport}}/api/index.php/supplierinvoices?count=1&limit=2&page=3
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# pagination_data does not wrap the count-mode response
GET http://{{hostnport}}/api/index.php/supplierinvoices?count=1&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.count" >= 0

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/supplierinvoices?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/supplierinvoices?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data junk value — OBSERVED 2026-11-26, instance commit
#   55dda317131005aac62bafcb15809c043a664c87: HTTP 400 — the parameter
#   is typed boolean and junk is STRICTLY rejected at binding time
#   (unlike the tasks/projects coercion camp this pin was modelled on).
#   IMPORTANT SIDE-EFFECT for SIF2: this 400 proves the endpoint is NOT
#   generally silent about junk — it is strict on TYPED, BOUND
#   parameters (pagination_data) while completely ignoring UNBOUND
#   ones (sqlfilterlines, which does not exist on this class). One
#   coherent Restler-typing picture, not two inconsistent behaviours.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices?limit=5&pagination_data=NOTABOOLEAN
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/supplierinvoices?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /supplierinvoices/{id}

# Positive: fetched by captured id — assert id round-trip and field
#   presence only. Verify the id serialization type with jq FIRST
#   (supplierorders precedent is string, C5/#43386). If number, switch:
#     jsonpath "$.id" toString == "{{invoice_id}}"
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices/{{invoice_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{invoice_id}}"
jsonpath "$.ref" exists

# id=0 — falsy-id family, SIF1. OBSERVED 2026-11-26, instance commit
#   55dda317131005aac62bafcb15809c043a664c87 (hurl run passed here):
#   HTTP 200 with the all-null SPECIMEN object — the class joins the
#   200-specimen camp (supplierorders, projects, thirdparties, members
#   — #43307 tally). NOTE: the id=0 camp diverges WITHIN this class —
#   see SIF7: /0/lines -> 200 [], /0/payments -> 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices/0
HTTP 200
[Asserts]
jsonpath "$.specimen" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/supplierinvoices/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — path parameter is typed long -> 400
GET http://{{hostnport}}/api/index.php/supplierinvoices/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/supplierinvoices/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /supplierinvoices/{id}/lines

# Positive: lines of the captured invoice — may be empty, must be a
#   collection. jq-inspect the line row shape at first run (the bare
#   qty sqlfilterlines column depends on it).
GET http://{{hostnport}}/api/index.php/supplierinvoices/{{invoice_id}}/lines
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent invoice id on the subresource -> 404
#   (spec documents 403/404 for this route; a full-rights admin key
#   should not hit the 403 permission branch)
GET http://{{hostnport}}/api/index.php/supplierinvoices/99999999/lines
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric invoice id on the subresource -> 400
GET http://{{hostnport}}/api/index.php/supplierinvoices/abc/lines
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — falsy-id family (SIF7). supplierorders'
#   contacts answered a correct EMPTY array at id=0 (constraint
#   applied). Pin empty-collection shape BY-ANALOGY; jq-inspect the
#   body before strengthening.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices/0/lines
HTTP 200
[Asserts]
jsonpath "$" exists
jsonpath "$" count == 0

# ---------------------------------------------------------------- GET /supplierinvoices/{id}/payments
# Route supplierorders does NOT have as a GET — new-class coverage here.
# Spec documents 400/403/404/405 failure modes for this route.

# Positive: payments of the captured invoice — may be empty, must be a
#   collection. jq-inspect the payment row shape at first run.
GET http://{{hostnport}}/api/index.php/supplierinvoices/{{invoice_id}}/payments
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent invoice id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/supplierinvoices/99999999/payments
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric invoice id on the subresource -> 400
GET http://{{hostnport}}/api/index.php/supplierinvoices/abc/payments
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the payments subresource — falsy-id family (SIF7). OBSERVED
#   2026-11-26, instance commit 55dda317131005aac62bafcb15809c043a664c87:
#   HTTP 400 with the standard error envelope — CONTRARY to the empty-
#   array camp of /lines (200 []) and of supplierorders /contacts at
#   the same probe. getPayments VALIDATES the falsy id instead of
#   resolving it to an empty payment set; the pasted spec indeed
#   lists 400 among this route's failure codes. SUBRESOURCE DIVERGENCE
#   WITHIN ONE CLASS: /0/lines -> 200 [], /0/payments -> 400. Note for
#   the falsy-id tally (#43307/#43385 families): the id=0 camp must be
#   probed PER ROUTE, not per class — parent get() joined the
#   200-specimen camp while the payments subresource rejects.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/supplierinvoices/0/payments
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- LOW-PRIVILEGE NEGATIVE CONTROL (C6)
# NOT AUTOMATED: the zero-rights (LOWKEY) key is not available to hurl
#   tests started via run.sh (and will not be for now), so no LOWKEY
#   probes are embedded or shipped as a companion file. C6 coverage for
#   this class is MANUAL, via curl:
#     LOWKEY=<zero-rights-key> DOLIHOST=localhost:8080 \
#       bash -c 'for p in "?limit=1" "/0" \
#         "?sqlfilterlines=(t.qty%3A%3E%3A%270%27)&limit=1"; do \
#         printf "probe %s -> " "${p}"; \
#         curl -s -w "\nHTTP %{http_code}\n" -H "DOLAPIKEY: ${LOWKEY}" \
#           "http://${DOLIHOST}/api/index.php/supplierinvoices${p}"; done'
#   EXPECTED (supplierorders precedent 2026-11-25): 403 clean JSON
#   envelope on all three — no specimen on /0 (permission gate BEFORE
#   the SIF1 falsy-specimen path; a 200 with specimen here would be a
#   REAL access-control finding), no raw SQL text on the filter probe
#   (gate BEFORE SQL build; note SIF2 — the param is unbound here, so
#   this also tests whether the silent-ignore path sits behind the
#   permission gate).
# C6 RESULT FOR SUPPLIERINVOICES: CONFIRMED 2026-11-26 (manual curl,
#   zero-rights key, commit 55dda317): all three probes -> 403
#   {"error":{"code":403,"message":"Forbidden"}} — clean envelope, no
#   specimen on /0 (permission gate BEFORE the SIF1 falsy-specimen
#   path), no raw SQL text on the filter probe. Matches the
#   supplierorders precedent exactly; permission checks run before
#   both the falsy-id path and the (here: unbound-parameter) filter
#   path. Recorded in supplier_family_common_issues.md C6.

# ---------------------------------------------------------------- OWNERSHIP / CROSS-OBJECT NOTE
# SupplierInvoices exposes NO contacts route (unlike supplierorders) and
#   lines/payments are strictly scoped to the parent invoice id — the
#   contacts-linkage cross-object probe (supplierorders C7/304 episode)
#   is NOT applicable within the GET-only surface of this class.
#   MANUAL PROBE PLAN (not a hurl pin, no fixtures via GET-only):
#     1. Find two supplier invoices A and B on the instance where B has
#        payments and A does not (GUI or write-API fixtures outside
#        this file; write-side is out of scope here).
#     2. GET /supplierinvoices/{A}/payments and
#        /supplierinvoices/{B}/payments with ${DOLIHOST}+${DOLAPIKEY};
#        diff the returned payment ids.
#     3. If A's list contains B-only payment ids, file a BUG issue
#        (cross-object payment leak) with the observed outputs only.
#   Lines ownership analogously: a line belonging to B must never
#   appear in GET /supplierinvoices/{A}/lines.
