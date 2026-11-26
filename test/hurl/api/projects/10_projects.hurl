# test/hurl/api/projects/10_projects.hurl
# GET-only tests for the projects (Project) API — every GET endpoint listed
#   in the Swagger spec:
#     GET /projects                     (list)
#     GET /projects/{id}                (fetch by id)
#     GET /projects/{id}/contacts       (contacts of a project)
#     GET /projects/{id}/roles          (roles a user holds on a project)
#     GET /projects/{id}/tasks          (tasks of a project, incl. timespent)
#     GET /projects/{id}/timespent      (timespent from all its tasks)
#     GET /projects/alltimespent        (all timespent)
#     GET /projects/email_msgid/{email_msgid}   (fetch by email message id)
#     GET /projects/ref_ext/{ref_ext}           (fetch by external ref)
#     GET /projects/ref/{ref}                   (fetch by ref)
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Projects feature is always on in Dolibarr. The LIST
#   section captures "$[0]" and therefore requires at least one project to
#   exist. ADDITIONALLY, the real-category-id probe in the alltimespent
#   section captures "$[0]" from /categories and therefore requires at least
#   one category of any type to exist (same precondition as the categories
#   suite); no category id is EVER hardcoded — portability rule.
#
# Portability: no assumptions about specific projects, refs or counts; only
#   ids, refs and response shapes are asserted, never free-text content.
#   The email_msgid / ref_ext / ref-by-value probes use throwaway sentinel
#   values (never-present lookups), so they stay valid on any instance
#   regardless of stored data.
#
# Only GET requests in this file — POST/PUT/DELETE on /projects are
#   deliberately not exercised, not even as negative tests, to keep the
#   suite strictly read-only.
#
# PERMISSION DEPENDENCE: project read rights are required for all sections.
#   Whether the rights are held personally or inherited from a group does
#   not affect THIS file as written; if a 403 appears during the 4-profile
#   API-key matrix run, the matrix notes should record it as a permission
#   finding rather than a change to these pins (a general READ failure
#   profile, analogous to the product/member CLASS 2 pins in the categories
#   suite). The over-exposure probes below MUST be re-tested with a
#   low-privilege key (see FINDING 5).
#
# STATUS OF PINS: FULLY OBSERVED (full suite run green, 80+ requests,
#   2026-11-24; incremental corrections plus manual payload probes, all
#   2026-11-24) + DELIBERATE POST-FIX CHANGES (2026-11-26): after the
#   upstream fix for #43353 landed, the falsy-id pins for the four lookup
#   endpoints were flipped 200-specimen -> 404 deliberately and re-verified
#   live (instance on upstream develop, base commit 6e96f1694, 2026-11-26).
#   Every status code in this file is confirmed against a live instance.
#   The deliberate-change rule applies to all pins: "Change this assert
#   deliberately, never accidentally."
# FINDINGS pinned in this file (candidates for the upstream report):
#   FINDING 1 — RESOLVED UPSTREAM (2026-11-26): id=0 -> 200 with the
#     all-null SPECIMEN object (every field null, "specimen": 0) on the
#     main endpoint; /projects was in the 200-specimen camp of the
#     cross-module falsy-id tally (thirdparties, members, projects;
#     cf. #43307, categories: 500, invoices: 400, memberstypes et al.:
#     404). The #43353 fix makes /projects/0 answer 404 ("Project with
#     supplied id not found") — verified live 2026-11-26; pin flipped
#     deliberately below. Filed as issue #43353.
#   FINDING 2 (OBSERVED 2026-11-24, RESOLVED same day): the documented
#     category filter is BROKEN GENERALLY on the collection route:
#     /projects?category=X -> 200 (works with both real and unknown ids),
#     but /projects/alltimespent?category=X -> 503 for ANY category id —
#     confirmed with a runtime-captured REAL id against a 200 baseline on
#     the main list in the same run. ROOT CAUSE CONFIRMED via the error
#     body (2026-11-24): "Unknown column 'c.fk_categorie' in 'WHERE'" —
#     the alltimespent handler builds its category filter with table
#     alias 'c' but never adds the categorie_project join; additionally
#     the copied comparison uses c.fk_project = t.rowid where t is
#     projet_task, not projet (should be p.rowid). Copy-paste divergence
#     between the list handlers. STILL 503 after the #43353 fix
#     (2026-11-26) — separate root cause. Filed upstream as #43355
#     (+ #43383, closed as duplicate); fix in flight as PR #43361,
#     which adds the missing join AND corrects the comparison to
#     c.fk_project = p.rowid (a join-only patch would have produced
#     silently wrong results).
#     (Related, main list only: category=0 DROPS the filter entirely and
#     returns ALL projects — falsy-param family.)
#   FINDING 3 (OBSERVED 2026-11-24): all three by-value endpoints
#     (ref, ref_ext, email_msgid) show a consistent three-way split on
#     path-value fuzzing: well-formed unknown sentinel -> 404, malformed
#     "." -> 400 (validated before lookup), hostile-looking special
#     chars -> 403. Identical behaviour across all three endpoints.
#   FINDING 4 (OBSERVED 2026-11-24, CONTRACT BREAK): the 403 responses on
#     the special-char fuzz carry a NON-JSON body (hurl's jsonpath parse
#     fails), i.e. a framework-level input filter answers with its own
#     error page instead of the API's JSON error envelope. Same 403 on
#     all three by-value endpoints — the filter is resource-independent.
#     Verify whether the 403 originates from Dolibarr core or a
#     WAF/proxy in front of the instance before filing upstream.
#   FINDING 5 (OBSERVED 2026-11-24; CONFIRMED STILL PRESENT AFTER THE
#     #43353 FIX, 2026-11-26): id=0 on the tasks/roles/timespent
#     subresources does NOT return an empty result — the project
#     constraint is IGNORED and data from OTHER, real projects comes
#     back: /projects/0/tasks returned full task records (fk_project
#     2/4/10 in 2026-11-24; fk_project "4" records on 2026-11-26),
#     /projects/0/roles returned 13 role strings, /projects/0/timespent
#     returned aggregate rows with real dates (2026-11-26: max_date
#     2026-11-25, total_duration 33300/3600). Contrast:
#     /projects/0/contacts answers []. SCOPE OF THE #43353 FIX: handler-
#     local guards in the four lookup handlers only — /projects/0/
#     contacts STILL answers 200 [] after the fix, proving the root
#     Project::fetch(0) still succeeds; the three subresource handlers
#     lack the guard that get() itself demonstrates. Verification: a
#     no-permission key gets 403 on all /projects/0/* endpoints (both
#     2026-11-24 and 2026-11-26) — NOT a privilege-escalation path, only
#     keys holding projet.lire reach the defect. Filed upstream as
#     #43354 (consistency bug, with subset-key verification).
#   FINDING 6 — RESOLVED UPSTREAM (2026-11-26): the STRING-typed by-value
#     lookups accepted the falsy value "0" with 200 + specimen object
#     instead of 404 (note the contrast: "123" answered 404 correctly).
#     The #43353 fix makes all three routes answer 404 with their
#     handler-specific "not found" messages — verified live 2026-11-26;
#     pins flipped deliberately below. Filed as issue #43353.
#   PAGINATION TOTAL (source review + live probe 2026-11-26; NOT caught
#     by this suite's original loose asserts): with pagination_data=true,
#     /projects/alltimespent always returned pagination.total=0 (probe:
#     rows 3, total 0) — listTimespent() builds its count query via
#     str_replace('SELECT t.rowid', ...) which never matches its
#     'SELECT et.rowid, ...', so ->total was read off a data row. Filed
#     as #43435; RESOLVED UPSTREAM same day via PR #43436 (merged by
#     eldy; count now computed via DolibarrApi::_getCountOfRecords()).
#     Tightening the envelope assert below with
#     jsonpath "$.pagination.total" >= 1 was completed as a DELIBERATE
#     change (2026-11-26): fix pulled, non-zero total observed on the
#     local instance, assert added — suite green, 87/87.
# CROSS-MODULE PATTERNS — status after the 2026-11-24 runs (formerly P1-P6):
#   P1 CONFIRMED: non-numeric limit/page -> 400 strict validation.
#   P2 CONFIRMED: unknown sortfield -> 503 on both /projects and
#     /projects/alltimespent (unvalidated SQL flow, as on categories/
#     memberstypes). ORDER BY alias divergence on alltimespent filed
#     upstream as #43384.
#   P3 CONFIRMED: invalid sqlfilters syntax -> 400; unknown column ->
#     503 (syntax validated, column names not).
#   P4 CONFIRMED as FINDINGS 1/5/6 (id=0 -> 200 everywhere; constraint
#     sometimes ignored with over-exposure). FINDING 1/6 portion since
#     fixed upstream via #43353 (2026-11-26).
#   P5 CONFIRMED: includetimespent junk -> 400 (strict validation).
#     (includetimespent=1/2 EFFECT confirmed working 2026-11-26 via
#     payload diff — summary fields populate through task-object
#     mutation; the suspected no-op was a NON-ISSUE, draft closed.)
#   P6 CONFIRMED as FINDING 3 (string-path fuzz classes 404/400/403).
#   Also observed: negative limit -> 200 (coerced, memberstypes camp);
#   non-numeric userid -> 400; junk contacts `type` -> 200 ignored;
#   category=0 -> filter dropped (see FINDING 2 parenthetical).
#
# ---------------------------------------------------------------- GET /projects (list)

# Positive: list, ascending sort, capture first row id for later lookups.
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
project_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a documented column of the project table
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED (2026-11-24): unknown sortfield flows into SQL ORDER BY -> 503
#   (as on categories/memberstypes; cross-module pattern P2).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# thirdparty_ids filter — documented, example form '1' or '1,2,3'
GET http://{{hostnport}}/api/index.php/projects?thirdparty_ids=1&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# thirdparty_ids — comma-separated form, never-present ids: empty or short list
GET http://{{hostnport}}/api/index.php/projects?thirdparty_ids=99999999%2C99999998&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# category filter — documented; unknown id simply yields a filtered
#   (possibly empty) list on the MAIN route
GET http://{{hostnport}}/api/index.php/projects?category=99999999&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# category=0 — OBSERVED (2026-11-24, manual probe): the filter is DROPPED
#   for the falsy value and ALL projects come back (manual probe with
#   properties=id showed the full id list, identical to no filter).
#   Same falsy-param family as former FINDINGS 1/6, here on a LIST
#   PARAMETER: count is not pinned (depends on data), but the request
#   must succeed.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?category=0&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Page 1 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — coerced, no 500 (memberstypes camp)
GET http://{{hostnport}}/api/index.php/projects?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED (2026-11-24): negative limit -> 200 (coerced, memberstypes camp)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED (2026-11-24): non-numeric limit -> 400 strict validation (P1)
GET http://{{hostnport}}/api/index.php/projects?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# OBSERVED (2026-11-24): non-numeric page -> 400, typed long like limit (P1)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- List filter: none besides the above
# NOTE: /projects has NO documented enum `type` filter (unlike /categories),
# so the categories type-sweep section has no equivalent here. thirdparty_ids
# and category are the only documented list filters beyond sort/pagination.

# ---------------------------------------------------------------- sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid (empty) collection
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED (2026-11-24): unknown sqlfilters column -> 503 (invalid SQL,
#   as on members/categories; syntax validated, column names not — P3)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED (2026-11-24): invalid sqlfilters syntax -> 400 ("Bad syntax",
#   as on members/categories — P3)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.dateo%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/projects?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/projects?properties=id%2Cref%2Ctitle&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/projects?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/projects?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/projects?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/projects?sortfield=t.rowid&sortorder=DESC&thirdparty_ids=1&properties=id%2Cref%2Ctitle&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /projects/{id}

# Positive: fetched by captured id — assert id round-trip and field presence only
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{project_id}}"
jsonpath "$.ref" exists

# id=0 — formerly FINDING 1: HTTP 200 with the all-null SPECIMEN object.
#   FIXED UPSTREAM via #43353; CHANGED DELIBERATELY 2026-11-26 after a live
#   re-probe on upstream develop (base 6e96f1694, 2026-11-26): the endpoint
#   now answers 404 "Not Found: Project with supplied id not found" as it
#   should. /projects leaves the 200-specimen camp of the falsy-id tally
#   for the 404 camp; the specimen behaviour is archived in issue #43353
#   and ISSUE_1 (resolved).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/projects/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — type-checked as on siblings
GET http://{{hostnport}}/api/index.php/projects/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/projects/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /projects/{id}/contacts

# Contacts of the captured project — may be empty, must be a collection
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/contacts
HTTP 200
[Asserts]
jsonpath "$" isCollection

# `type` is documented as a query filter on this endpoint — fuzz a value
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/contacts?type=internal
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/contacts?type=external
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED (2026-11-24): junk type value ignored -> 200 + collection
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/contacts?type=NOSUCHTYPE
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent project id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/projects/99999999/contacts
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id on the subresource — type-checked with 400
GET http://{{hostnport}}/api/index.php/projects/abc/contacts
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — OBSERVED (2026-11-26, admin probe): HTTP 200
#   with [] — UNCHANGED by the #43353 fix. Key evidence for the fix's
#   scope: the root Project::fetch(0) still succeeds (otherwise this
#   handler would now throw 404); the fix was handler-local (guards in
#   the four lookup handlers only). The contact constraint IS applied,
#   so [] is the still-wrong-shaped consequence of the unresolved falsy-id
#   root — see #43354 (FINDING 5).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/0/contacts
HTTP 200
[Asserts]
jsonpath "$" exists

# ---------------------------------------------------------------- GET /projects/{id}/roles

# Roles of the connected user (userid omitted = 0 = connected user)
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/roles
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Explicit userid form — 0 (= connected user) and a never-present id
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/roles?userid=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/roles?userid=99999999
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED (2026-11-24): non-numeric userid -> 400 (typed long, strict)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/roles?userid=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: nonexistent project id -> 404
GET http://{{hostnport}}/api/index.php/projects/99999999/roles
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric project id -> 400
GET http://{{hostnport}}/api/index.php/projects/abc/roles
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the roles subresource — OVEREXPOSURE STILL PRESENT AFTER THE
#   #43353 FIX (FINDING 5; 2026-11-26, admin probe): HTTP 200 with 13
#   role strings from real projects (last row
#   "PROJECTCONTRIBUTOR,PROJECTLEADER"). The fix added guards to the four
#   lookup handlers only; this handler lacks the get()-style guard.
#   Payload-dependent, so only status is pinned (portability). Filed
#   upstream as #43354; fix pending.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/0/roles
HTTP 200
[Asserts]
jsonpath "$" exists

# ---------------------------------------------------------------- GET /projects/{id}/tasks

# Tasks of the captured project (includetimespent omitted = 0 = list only)
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/tasks
HTTP 200
[Asserts]
jsonpath "$" isCollection

# includetimespent=1 — summary of time spent included
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/tasks?includetimespent=1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# includetimespent=2 — full detail of time spent lines
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/tasks?includetimespent=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# includetimespent=0 — explicit default
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/tasks?includetimespent=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED (2026-11-24): junk includetimespent -> 400 (strict validation,
#   like categories' include_childs — P5)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/tasks?includetimespent=NOTANUMBER
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: nonexistent project id -> 404
GET http://{{hostnport}}/api/index.php/projects/99999999/tasks
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric project id -> 400
GET http://{{hostnport}}/api/index.php/projects/abc/tasks
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the tasks subresource — OVEREXPOSURE STILL PRESENT AFTER THE
#   #43353 FIX (FINDING 5; 2026-11-26, admin probe): HTTP 200 with FULL
#   task records from real projects (fk_project "4" observed on
#   2026-11-26; fk_project 2/4/10 on 2026-11-24). The fix added guards to
#   the four lookup handlers only; this handler lacks the get()-style
#   guard. Payload-dependent, so only status is pinned (portability).
#   Filed upstream as #43354; fix pending.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/0/tasks
HTTP 200
[Asserts]
jsonpath "$" exists

# ---------------------------------------------------------------- GET /projects/{id}/timespent

# Timespent of the captured project (aggregated from all its tasks)
GET http://{{hostnport}}/api/index.php/projects/{{project_id}}/timespent
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent project id -> 404
GET http://{{hostnport}}/api/index.php/projects/99999999/timespent
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric project id -> 400
GET http://{{hostnport}}/api/index.php/projects/abc/timespent
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the timespent subresource — OVEREXPOSURE STILL PRESENT AFTER THE
#   #43353 FIX (FINDING 5; 2026-11-26, admin probe): HTTP 200 with 3
#   aggregate rows from OTHER projects (2026-11-26: max_date 2026-11-25,
#   total_duration 33300; a 3600s row dated 2024-04-20). The fix added
#   guards to the four lookup handlers only; this handler lacks the
#   get()-style guard. Payload-dependent, so only status is pinned
#   (portability). Filed upstream as #43354; fix pending.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/0/timespent
HTTP 200
[Asserts]
jsonpath "$" exists

# ---------------------------------------------------------------- GET /projects/alltimespent

# Collection route check: alltimespent must be served as a LIST, not be
#   swallowed by the {id} route pattern (route-order hazard — assert the
#   shape, plus it must never fail with a "{id}=alltimespent" type error).
GET http://{{hostnport}}/api/index.php/projects/alltimespent
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented list parameters must be honoured on the collection route too
GET http://{{hostnport}}/api/index.php/projects/alltimespent?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED (2026-11-24): unknown sortfield -> 503 on the collection route
#   too (unvalidated SQL flow, P2). NOTE the ORDER BY alias divergence on
#   this route (sortfield=t.element_date rejected, "unknown column") was
#   filed upstream as #43384.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/alltimespent?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/projects/alltimespent?page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# properties projection on the collection route
GET http://{{hostnport}}/api/index.php/projects/alltimespent?properties=id%2Cduration&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data envelope on the collection route — NOTE (#43435): the
#   pagination.total=0 defect (str_replace count query never matched
#   'SELECT et.rowid, ...'; live probe 2026-11-26: rows 3, total 0) was
#   filed as #43435 and FIXED UPSTREAM same day via PR #43436 (merged by
#   eldy; total now via DolibarrApi::_getCountOfRecords()). PIN TIGHTENED
#   DELIBERATELY (2026-11-26): fix pulled, non-zero total observed on the
#   local instance, jsonpath "$.pagination.total" >= 1 added — the old
#   loose pin ($.data + $.pagination.limit only) existed precisely
#   because the broken count query would have failed such an assert.
GET http://{{hostnport}}/api/index.php/projects/alltimespent?limit=10&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 10
jsonpath "$.pagination.total" >= 1

# thirdparty_ids filter on the collection route
GET http://{{hostnport}}/api/index.php/projects/alltimespent?thirdparty_ids=1&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# category filter on the collection route — FINDING 2. OBSERVED
#   (2026-11-24, hurl run): HTTP 503 for an unknown category id; STILL 503
#   after the #43353 fix (2026-11-26) — separate root cause. Filed as
#   #43355 (+ #43383, closed as duplicate); fix in flight as PR #43361 —
#   when that lands and is observed locally, flip BOTH category pins
#   below (this one and the real-id probe) deliberately to 200.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/alltimespent?category=99999999&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# FOLLOW-UP PROBE for FINDING 2: re-test with a REAL category id,
#   captured at RUNTIME — never hardcoded, since category ids are
#   instance-specific.
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=ASC&limit=1
HTTP 200
[Captures]
probe_category_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$[0].id" exists

# Main list with the captured real id — baseline; OBSERVED (2026-11-24):
#   200 (the category filter works with real ids on the main route)
GET http://{{hostnport}}/api/index.php/projects?category={{probe_category_id}}&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# alltimespent with the captured real id — DECISIVE PROBE RESULT
#   (OBSERVED 2026-11-24, hurl run): HTTP 503 EVEN WITH A VALID category
#   id — the baseline request /projects?category={{probe_category_id}}
#   answered 200 with the very same id in the same run. CONCLUSION
#   (FINDING 2 resolved): the category filter on /projects/alltimespent
#   is broken for ALL ids, not just unknown ones. Re-confirmed 2026-11-26
#   (503, same c.fk_categorie SQL error). Filed as #43355; fix in flight
#   as PR #43361.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/alltimespent?category={{probe_category_id}}&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# sqlfilters on the collection route
GET http://{{hostnport}}/api/index.php/projects/alltimespent?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Non-numeric limit on the collection route — strict validation expected
GET http://{{hostnport}}/api/index.php/projects/alltimespent?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- GET /projects/email_msgid/{email_msgid}

# Negative: never-present message id -> 404 (string-typed path param, so
#   any well-formed string is legitimate input). Sentinel chosen to be
#   implausible on any instance.
GET http://{{hostnport}}/api/index.php/projects/email_msgid/NO-SUCH-MSGID-ZZZ-0001@example.invalid
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED (2026-11-24): malformed "." -> 400 (validated before lookup,
#   not a miss)
GET http://{{hostnport}}/api/index.php/projects/email_msgid/.
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED (2026-11-24): hostile-looking special chars -> 403 with a
#   NON-JSON body (FINDING 4 — framework-level input filter, answers
#   outside the API error envelope). Body assert omitted deliberately.
GET http://{{hostnport}}/api/index.php/projects/email_msgid/%20%3C%3E%22%27
HTTP 403

# Numeric-looking msgid (not an email message id format, but string-typed)
GET http://{{hostnport}}/api/index.php/projects/email_msgid/123
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Falsy "0" as msgid — formerly FINDING 6 (HTTP 200 + specimen object).
#   FIXED UPSTREAM via #43353; CHANGED DELIBERATELY 2026-11-26: the
#   endpoint now answers 404 "Not Found: Project with supplied email_msgid
#   not found".
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/email_msgid/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /projects/ref_ext/{ref_ext}

# Negative: never-present ref_ext -> 404
GET http://{{hostnport}}/api/index.php/projects/ref_ext/NO-SUCH-REFEXT-ZZZ-0001
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED (2026-11-24): malformed "." -> 400 (consistent across all
#   by-value endpoints)
GET http://{{hostnport}}/api/index.php/projects/ref_ext/.
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED (2026-11-24): special chars -> 403 (same framework-level
#   rejection as email_msgid; body assert omitted, non-JSON body)
GET http://{{hostnport}}/api/index.php/projects/ref_ext/%20%3C%3E%22%27
HTTP 403

# Numeric-looking ref_ext
GET http://{{hostnport}}/api/index.php/projects/ref_ext/123
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Falsy "0" as ref_ext — formerly FINDING 6 (HTTP 200 + specimen object).
#   FIXED UPSTREAM via #43353; CHANGED DELIBERATELY 2026-11-26: now answers
#   404 "Not Found: Project with supplied ref_ext not found".
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/ref_ext/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /projects/ref/{ref}

# Negative: never-present ref -> 404. NOTE: a real project ref looks like
#   'PJ2401-0001'; the sentinel below must not collide. If the instance
#   stores projects with odd refs, adjust the sentinel, not the assert.
GET http://{{hostnport}}/api/index.php/projects/ref/ZZZ-NO-SUCH-REF-0001
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED (2026-11-24): malformed "." -> 400 (same as the other
#   by-value endpoints)
GET http://{{hostnport}}/api/index.php/projects/ref/.
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED (2026-11-24): special chars -> 403 (third confirmation of the
#   framework-level filter; body assert omitted, non-JSON body)
GET http://{{hostnport}}/api/index.php/projects/ref/%20%3C%3E%22%27
HTTP 403

# Numeric-looking ref
GET http://{{hostnport}}/api/index.php/projects/ref/123
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Falsy "0" as ref — formerly FINDING 6 (HTTP 200 + specimen object).
#   FIXED UPSTREAM via #43353; CHANGED DELIBERATELY 2026-11-26: now answers
#   404 "Not Found: Project with supplied ref not found".
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/projects/ref/0
HTTP 404
[Asserts]
jsonpath "$.error" exists
