# test/hurl/api/tasks/10_tasks.hurl
# GET-only tests for the tasks (Task) API — every GET endpoint listed
#   in the Swagger spec:
#     GET /tasks                        (list)
#     GET /tasks/{id}                   (fetch by id)
#     GET /tasks/{id}/contacts          (contacts of a task)
#     GET /tasks/{id}/roles             (roles a user holds on a task)
#     GET /tasks/{id}/getTimeSpent/{timespent_id}   (one timespent line)
#     GET /tasks/{id}/timespent         (timespent lines of a task)
#
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Projects/Tasks feature is always on in Dolibarr.
#   The LIST section captures "$[0]" and therefore requires at least one
#   task to exist. The getTimeSpent section requires at least one task
#   WITH at least one time-spent line; if the captured task has none, the
#   timespent-dependent requests cannot run against real data — the file
#   still pins the negative/nonexistent paths portably. No ids are EVER
#   hardcoded — portability rule.
#
# Portability: no assumptions about specific tasks, refs or counts; only
#   ids, refs and response shapes are asserted, never free-text content.
#
# Only GET requests in this file — POST/PUT/DELETE on /tasks are
#   deliberately not exercised, not even as negative tests, to keep the
#   suite strictly read-only.
#
# PERMISSION DEPENDENCE: project/task read rights are required for all
#   sections. As with the projects suite, a 403 seen during the
#   4-profile API-key matrix run should be recorded as a permission
#   finding rather than a change to these pins.
#
# NOTE ON THE PROJECT SUITE'S OBSERVATIONS (same code family — several
#   DON'T recur here; each pin is annotated with its status):
#   - falsy id=0: ALL tasks endpoints -> 404 with the JSON envelope
#     (OBSERVED 2026-11-24, manual probes + hurl run) — NO 200-specimen,
#     NO constraint-ignored over-exposure; tasks sits firmly in the 404
#     camp. EXCEPTION: /tasks/{id}/getTimeSpent/0 -> 200 all-null blob
#     (without the "specimen" marker) — see its section.
#   - unvalidated sortfield -> SQL error 503 (P2),
#   - sqlfilters: bad syntax 400, unknown column 503 (P3),
#   - strict long validation on limit/page/userid/includetimespent (P1/P5).
#   Pins marked OBSERVED-BY-ANALOGY below follow the projects suite; the
#   deliberate-change rule applies to all pins: "Change this assert
#   deliberately, never accidentally."
#
# TYPE INCONSISTENCY (OBSERVED 2026-11-24, manual jq probes on a live
#   instance): the primary key is serialized as a STRING everywhere
#   except getTimeSpent — GET /tasks/{id} -> "1" (string),
#   GET /projects/{id} -> "2" (string), GET /tasks/{id}/timespent ->
#   "timespent_line_id":"1" (string), GET /projects/alltimespent ->
#   "task_id":"1" and even "element_duration":"32400" (string).
#   But GET /tasks/{id}/getTimeSpent/{timespent_id} -> "id": 1 (NUMBER).
#   Worse, the SAME timespent line is formatted THREE ways across the
#   three routes: getTimeSpent gives raw types (id 1, element_duration
#   32400 as NUMBER, element_date 1698537600 as EPOCH); tasks/{id}/
#   timespent strings everything ("timespent_line_duration":"32400",
#   epoch number kept); alltimespent additionally FORMATS dates as text
#   ("element_datehour":"2023-10-29 00:00:00"). Clients must parse both
#   epochs and formatted strings for dates, and number-vs-string for
#   durations, within one resource family.
#   Hence the toString filter on the id round-trip assert in the
#   getTimeSpent section below.
#
# TASKS-SPECIFIC FINDINGS (source-confirmed AND observed 2026-11-24):
#   TF1 (OBSERVED end-to-end): /tasks/{id}/getTimeSpent/{timespent_id}
#     has NO ownership check — cross-task data leak. Probe: /tasks/1/
#     getTimeSpent/2 -> 200 with fk_element=4, elementtype="task", i.e.
#     task 1's endpoint served task 4's timespent line. Source cause:
#     getTimeSpentByID() fetches task and line independently; contrast
#     timespentRecordChecks() (PUT/DELETE) which answers 404 'Timespent
#     not found in selected task'. Same asymmetry class as projects
#     FINDING 5, here confirmed on BOTH sides (write-side closed,
#     read-side open). Upstream candidate.
#   TF2 (OBSERVED 2026-11-24, diff probe): includetimespent=1 on
#     /tasks/{id} DOES work — it populates the task object's
#     timespent_* summary fields (timespent_total_duration "32400",
#     timespent_nblines "1", min/max dates). NOTE: getSummaryOfTimeSpent
#     writes onto $this->task directly, so the summary rides along even
#     though the local $timespent variable is discarded in the source.
#     includetimespent=2 targets ->lines (observed null without the
#     param; populate behaviour not separately probed).
#   TF3 (OBSERVED 2026-11-24, error body): /projects/alltimespent?
#     sortfield=t.element_date -> 503 body: "Error when retrieve
#     timestamp list : Unknown column 't.element_date' in 'ORDER BY'".
#     EXACTLY the projects-FINDING-2 bug class (handler alias does not
#     match its own SQL), now in ORDER BY instead of WHERE — the error
#     text names the culprit directly. Bonus: the message says
#     "timestamp list", a typo for "timespent" in the handler.
#
# ---------------------------------------------------------------- GET /tasks (list)

# Positive: list, ascending sort, capture first row id for later lookups.
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
task_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a documented column of the task table
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.ref&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# OBSERVED-BY-ANALOGY (projects P2): unknown sortfield flows into SQL
#   ORDER BY -> 503. Re-pin against a live instance if it differs.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — coerced, no 500 (memberstypes camp)
GET http://{{hostnport}}/api/index.php/tasks?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# OBSERVED-BY-ANALOGY (projects): negative limit -> 200 (coerced)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (projects P1): non-numeric limit -> 400 strict validation
GET http://{{hostnport}}/api/index.php/tasks?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# OBSERVED-BY-ANALOGY (projects P1): non-numeric page -> 400, typed long
GET http://{{hostnport}}/api/index.php/tasks?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- List filter: sqlfilters
# NOTE: /tasks has NO documented list filter parameters beyond
#   sort/order/limit/page/sqlfilters (unlike /projects which documents
#   thirdparty_ids and category). Only sqlfilters is swept here.

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid (empty) collection
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.ref%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (projects P3): unknown sqlfilters column -> 503
#   (invalid SQL; syntax validated, column names not)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# OBSERVED-BY-ANALOGY (projects P3): invalid sqlfilters syntax -> 400
#   ("Bad syntax")
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.dateo%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/tasks?sqlfilters=(t.ref%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/tasks?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/tasks?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/tasks?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/tasks?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/tasks?sortfield=t.rowid&sortorder=DESC&sqlfilters=(t.rowid%3A%3E%3A%270%27)&properties=id%2Cref%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- GET /tasks/{id}

# Positive: fetched by captured id — assert id round-trip and field presence only
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{task_id}}"
jsonpath "$.ref" exists

# id=0 — OBSERVED (2026-11-24, hurl run): HTTP 404 with the standard
#   JSON error envelope — NOT the 200 all-null specimen that /projects/0
#   returns (projects FINDING 1). The tasks handler treats 0 like any
#   nonexistent id, which is arguably the CORRECT behaviour. Divergence
#   within the same code family — noteworthy for the upstream report.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/tasks/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — type-checked as on siblings
GET http://{{hostnport}}/api/index.php/tasks/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/tasks/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /tasks/{id}/contacts

# Contacts of the captured task — may be empty, must be a collection
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/contacts
HTTP 200
[Asserts]
jsonpath "$" isCollection

# `type` is documented as a query filter on this endpoint — fuzz values.
# NOTE: if the parameter is silently ignored, that is a documentation/
#   API divergence worth reporting upstream (check whether filtering
#   actually changes the result via a manual probe).
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/contacts?type=internal
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/contacts?type=external
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (projects): junk type value ignored -> 200 + collection
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/contacts?type=NOSUCHTYPE
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent task id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/tasks/99999999/contacts
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id on the subresource — type-checked with 400
GET http://{{hostnport}}/api/index.php/tasks/abc/contacts
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — OBSERVED (2026-11-24, manual probe):
#   HTTP 404 with the standard JSON error envelope ("Task not found").
#   GOOD NEWS: the constraint IS applied on tasks/contacts, unlike
#   /projects/0/contacts which answers 200 [] — but ALSO unlike projects
#   FINDING 5, there is NO over-exposure anywhere in the tasks suite.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/0/contacts
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /tasks/{id}/roles

# Roles of the connected user (userid omitted = 0 = connected user)
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/roles
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Explicit userid form — 0 (= connected user) and a never-present id
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/roles?userid=0
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/roles?userid=99999999
HTTP 200
[Asserts]
jsonpath "$" isCollection

# OBSERVED-BY-ANALOGY (projects): non-numeric userid -> 400 (typed long, strict)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/roles?userid=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: nonexistent task id -> 404
GET http://{{hostnport}}/api/index.php/tasks/99999999/roles
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric task id -> 400
GET http://{{hostnport}}/api/index.php/tasks/abc/roles
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the roles subresource — OBSERVED (2026-11-24, manual probe,
#   inferred): manual probes showed /tasks/0/contacts and /tasks/0/
#   timespent both -> 404 ("Task not found"), and NO over-exposure was
#   seen anywhere in the tasks family — in sharp contrast to projects
#   FINDING 5, where /projects/0/roles leaked 13 role strings from real
#   projects. The roles subresource is therefore pinned to 404 as well.
#   Re-pin if a direct probe of /tasks/0/roles ever shows otherwise.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/0/roles
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- GET /tasks/{id}/timespent

# Timespent of the captured task — may be empty, must be a collection
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/timespent
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: nonexistent task id -> 404
GET http://{{hostnport}}/api/index.php/tasks/99999999/timespent
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric task id -> 400
GET http://{{hostnport}}/api/index.php/tasks/abc/timespent
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the timespent subresource — OBSERVED (2026-11-24, manual
#   probe): HTTP 404 with the standard JSON error envelope ("Task not
#   found"). Constraint properly applied — /projects/0/timespent leaked
#   aggregate rows (projects FINDING 5), but tasks does NOT. Also pins
#   the notable divergence: the parent /tasks/{id} checks existence,
#   while getTimeSpent does NOT (see its section below).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/0/timespent
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ------------------------------------------------- GET /tasks/{id}/getTimeSpent/{timespent_id}
# One specific time-spent line. Needs a REAL task id WITH at least one
#   time-spent line — the first task of the list ({{task_id}}) may well
#   have NONE (OBSERVED 2026-11-24: hurl run failed on the naive
#   "capture $[0] from the first task's timespent" approach).
#   Deterministic strategy: probe /projects/alltimespent, whose rows
#   necessarily belong to tasks that HAVE time spent, and capture the
#   owning task id (fk_task) from row 0 — NEVER hardcoded (portability
#   rule). Precondition therefore: at least one time-spent line exists
#   anywhere on the instance. All other tests in this file stay valid
#   without that precondition.
#   NOTE: if the alltimespent row shape lacks fk_task, this capture
#   pinpoints it cleanly — inspect a manual probe of /projects/
#   alltimespent and adjust the field names here.

# Step 1: find a task that provably has time spent.
#   NOTE (OBSERVED 2026-11-24, hurl run): sortfield=t.element_date ->
#   503 on this route — the alltimespent SQL projection does not expose
#   element_date as a sortable column (same unvalidated-sortfield flow
#   as P2, this time hitting a LEGITIMATE column name). sortfield=t.rowid
#   is confirmed working on this route (projects suite, OBSERVED 200).
#   ROW SHAPE (OBSERVED 2026-11-24, manual probe): aggregate rows keyed
#   element_duration, element_datehour, fk_user, time_note, thm,
#   user_login/user_firstname/user_lastname, project_id/project_ref/
#   project_title, task_id/task_ref/task_label, soc_id/soc_name. So the
#   owning task is exposed as task_id — NOT fk_task (which is why the
#   first attempt failed: "No query result" on $[0].fk_task).
GET http://{{hostnport}}/api/index.php/projects/alltimespent?limit=1&sortfield=t.rowid&sortorder=ASC
HTTP 200
[Captures]
ts_task_id: jsonpath "$[0].task_id"
[Asserts]
jsonpath "$" isCollection
jsonpath "$[0].task_id" exists

# Step 2: capture a real timespent id from that task's OWN timespent
#   collection — the canonical source of ids used by getTimeSpent.
#   ROW SHAPE (OBSERVED 2026-11-24, manual probe): aggregate rows keyed
#   fk_project, project_ref/label/public, fk_task, task_ref/label, and
#   timespent_line_* (id, ref_ext, date, datehour, withhour, duration,
#   fk_user, fk_product, thm, note) — the line id is timespent_line_id,
#   NOT id (which is why the $[0].id capture failed).
GET http://{{hostnport}}/api/index.php/tasks/{{ts_task_id}}/timespent
HTTP 200
[Captures]
timespent_id: jsonpath "$[0].timespent_line_id"
[Asserts]
jsonpath "$" isCollection
jsonpath "$[0].timespent_line_id" exists

# Positive: the captured line, fetched directly — id round-trip.
#   NOTE: the response id is a NUMBER, captures are STRINGS. Bare
#   variable interpolation still compares as string (OBSERVED
#   2026-11-24, two attempts), so the toString FILTER converts the
#   actual JSON number to a string before comparison.
GET http://{{hostnport}}/api/index.php/tasks/{{ts_task_id}}/getTimeSpent/{{timespent_id}}
HTTP 200
[Asserts]
jsonpath "$.id" toString == "{{timespent_id}}"

# Ownership probe (SOURCE-CONFIRMED FINDING, 2026-11-24): the
#   getTimeSpentByID handler fetches the task and the timespent line
#   INDEPENDENTLY — it never checks that the line belongs to the asked
#   task (contrast timespentRecordChecks(), used by PUT/DELETE, which
#   throws 404 'Timespent not found in selected task'). fk_element +
#   elementtype='task' IS the owning task id, so this assert IS the
#   ownership check the server omits.
GET http://{{hostnport}}/api/index.php/tasks/{{ts_task_id}}/getTimeSpent/{{timespent_id}}
HTTP 200
[Asserts]
jsonpath "$.fk_element" toString == "{{ts_task_id}}"
jsonpath "$.elementtype" == "task"

# Negative: nonexistent timespent id on a real task -> 404
#   (contrast the falsy-timespent_id=0 probe below: 200 null blob)
GET http://{{hostnport}}/api/index.php/tasks/{{ts_task_id}}/getTimeSpent/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: nonexistent task id AND nonexistent timespent id -> 404
GET http://{{hostnport}}/api/index.php/tasks/99999999/getTimeSpent/99999998
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: negative task id, real timespent id -> 404 (constraint must hold)
GET http://{{hostnport}}/api/index.php/tasks/-1/getTimeSpent/{{timespent_id}}
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric task id -> 400
GET http://{{hostnport}}/api/index.php/tasks/abc/getTimeSpent/1
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# Negative: non-numeric timespent id -> 400
GET http://{{hostnport}}/api/index.php/tasks/{{task_id}}/getTimeSpent/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the timespent_id path param — NEW FINDING (OBSERVED
#   2026-11-24, manual probe with a REAL task id): /tasks/1/
#   getTimeSpent/0 -> HTTP 200 with an ALL-NULL OBJECT — every field
#   null, but NO "specimen" field (unlike /projects/0 and the falsy
#   string lookups there). Two problems in one: (a) falsy timespent_id=0
#   bypasses the not-found path entirely and (b) the null blob does not
#   even carry the specimen marker that /projects/{id}=0 produces, so
#   this one is harder to detect programmatically. Same falsy-id family
#   (projects FINDINGS 1/6), new shape. Candidate for the upstream
#   falsy-id tally (Dolibarr/dolibarr#43307).
#   NOTE the asymmetry: the task id IS existence-checked here (see the
#   /tasks/0/... pins above), but timespent_id=0 is not.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tasks/{{ts_task_id}}/getTimeSpent/0
HTTP 200
[Asserts]
jsonpath "$.id" exists
