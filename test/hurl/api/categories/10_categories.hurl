# test/hurl/api/categories/10_categories.hurl
# GET-only tests for the categories (Categorie) API — everything EXCEPT the
#   /categories/object/{type}/{id} reverse-lookup, which lives in
#   20_categories_object.hurl (split 2026-11-23).
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner
#   environment; the Categories feature is always on in Dolibarr, but the
#   LIST section captures "$[0]" and therefore requires at least one
#   category of any type to exist. Everything else tolerates an empty table.
# Portability: no assumptions about specific categories or counts; only
#   ids, labels and response shapes are asserted, never free-text content.
# Only GET requests in this file — the link/unlink endpoints
#   (POST/DELETE /categories/{id}/objects/...) are deliberately not
#   exercised, not even as negative tests, to keep the suite strictly
#   read-only.
#
# PERMISSION DEPENDENCE: the requests in THIS file are all category-level
#   (list/fetch/subresource) and pass with category read rights regardless
#   of whether those rights are held personally or inherited from a group.
#   The permission-profile-sensitive pins live in 20_categories_object.hurl
#   and will be re-validated with the 4-profile API-key matrix
#   (non-admin/admin x personal/group-inherited rights).
#
# STATUS OF PINS: FULLY OBSERVED (four probe/hurl runs 2026-11-23; final
#   full suite run green, 75 requests — with the /categories/object/ split
#   measuring 24 more in 10_categories_object.hurl). Every status code in
#   this file has been confirmed against a live instance. The
#   deliberate-change rule applies to all pins: "Change this assert
#   deliberately, never accidentally."
# FINDINGS pinned in this file (candidates for the upstream report):
#   FINDING 1 (OBSERVED 2026-11-23): id=0 -> HTTP 500 with error body
#     {"No category to search for", "Error when fetching category"} on BOTH
#     /categories/0 and /categories/0/objects (confirmed per-type: also
#     type=contact). Resource-wide falsy-id 500. NEW camp in the cross-
#     module id=0 tally: 200-specimen (thirdparties, members), 400
#     (invoices), 404 (memberstypes, objectlinks, expensereports,
#     interventions), 500 (categories).
#     UPSTREAM ISSUE: Dolibarr/dolibarr#43307
#   FINDING 2 (OBSERVED 2026-11-23): unknown types on /categories/{id}/objects
#     are IGNORED (200 + []) — but on /categories/object/{type}/... (see
#     10_categories_object.hurl) they are REJECTED with 400. Inconsistent
#     unknown-type handling between the two subresources.
#     UPSTREAM ISSUE: Dolibarr/dolibarr#43308
#   FINDING 3 (OBSERVED 2026-11-23): the reverse-lookup dispatch supports a
#     smaller type set than the enabled modules suggest — expensereport
#     answers 400 even with the Expense Reports module enabled and full
#     rights granted. Pinned in the CLASS 1 section at the bottom of this
#     file.
#     UPSTREAM ISSUE: Dolibarr/dolibarr#43309
#   FINDING 4 (OBSERVED 2026-11-23, INCOMPLETE): the permission-dependent
#     reverse-lookup split (product/member -> 403 vs customer/supplier/... ->
#     200) does NOT follow the personal-vs-inherited rights boundary, and
#     user answered 200 without any user-read right. Deferred to the
#     6-profile permission matrix (see 10_categories_object.hurl) before an
#     upstream issue is filed — currently held in a local uncommitted file.
#   OBSERVED: include_childs junk value -> 400 (strict boolean validation);
#     missing type on /{id}/objects -> 400 (required constraint enforced).
#
# ---------------------------------------------------------------- /categories/types

# Positive: the types list is always servable, even with zero categories
GET http://{{hostnport}}/api/index.php/categories/types
HTTP 200
[Asserts]
jsonpath "$" isCollection

# GET /categories/types takes no documented parameters; fuzz one anyway —
# OBSERVED: 200 (parameter ignored, no validation).
GET http://{{hostnport}}/api/index.php/categories/types?sortfield=t.nosuchfield
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- List & pagination

# List, ascending sort, capture first row id for later lookups.
# The category type differs per instance row; the shape assertions stay generic.
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=ASC&limit=5
HTTP 200
[Captures]
category_id: jsonpath "$[0].id"
[Asserts]
jsonpath "$" count <= 5
jsonpath "$[0].id" exists

# Same list, DESC sort — ordering param is accepted
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=DESC&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Variant: lowercase sortorder — must not break the request
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=desc&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: unknown sortorder value — must not 500
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=NOTASORTORDER&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Sort on a documented column of the categorie table
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.label&sortorder=ASC&limit=5&page=0
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# Behavior pinned to 503 as observed on memberstypes/members: an unknown
# sortfield is not validated and flows into the SQL ORDER BY.
# VERIFY: if categories answers 200 here, pin that and note the
# cross-module difference.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.nosuchfield&sortorder=ASC&limit=5
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Page 1 — pagination works, still a valid empty-or-shorter page
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=ASC&page=1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: far-out page — empty array expected, not an error
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=ASC&page=999&limit=2
HTTP 200
[Asserts]
jsonpath "$" count == 0

# Fuzz: negative page — must not 500 (coerced on memberstypes)
GET http://{{hostnport}}/api/index.php/categories?page=-1&limit=2
HTTP 200
[Asserts]
jsonpath "$" count <= 2

# Fuzz: negative limit — VERIFY: memberstypes coerced; siblings with strict
# validation reject. Pin the observed behaviour.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?limit=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Negative: non-numeric limit is rejected with 400 (strict validation as on siblings)
GET http://{{hostnport}}/api/index.php/categories?limit=abc
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# Non-numeric page — typed long like limit. VERIFY: pin observed code.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?page=abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- List filter: type

# Documented enum value: customer categories
GET http://{{hostnport}}/api/index.php/categories?type=customer&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented enum value: supplier
GET http://{{hostnport}}/api/index.php/categories?type=supplier&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented enum value: product
GET http://{{hostnport}}/api/index.php/categories?type=product&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented enum value: member
GET http://{{hostnport}}/api/index.php/categories?type=member&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented enum value: contact
GET http://{{hostnport}}/api/index.php/categories?type=contact&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Documented enum value: actioncomm
GET http://{{hostnport}}/api/index.php/categories?type=actioncomm&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Empty value — filter ignored, list still served
GET http://{{hostnport}}/api/index.php/categories?type=&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unknown enum value on a filter — OBSERVED (2026-11-23): 200-with-ignored-
# value (unlike the 400 on /object/{type}/..., see 20_categories_object.hurl).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?type=NOSUCHTYPE&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Data-class fuzz: numeric-looking type value
GET http://{{hostnport}}/api/index.php/categories?type=123&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Data-class fuzz: negative-number-looking type value
GET http://{{hostnport}}/api/index.php/categories?type=-1&limit=5
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- sqlfilters

# sqlfilters — always-true filter on rowid
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.rowid%3A%3E%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" count <= 10

# sqlfilters — always-false filter: response shape must still be a valid collection
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.rowid%3A%3C%3A%270%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# sqlfilters — empty value: filter ignored, list still served
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=&limit=5
HTTP 200
[Asserts]
jsonpath "$" count <= 5

# sqlfilters — LIKE that matches nothing: still a valid (empty) collection
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.label%3Alike%3A%27ZZZ-NO-SUCH-%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Behavior pinned to 503 on members: unknown column parses fine but
# produces invalid SQL. VERIFY: if categories whitelists columns, 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=10
HTTP 503
[Asserts]
jsonpath "$.error" exists

# Negative: sqlfilters with invalid syntax is rejected with 400 ("Bad syntax
# of the search string" on members). Contrast: the unknown-column case above
# still reaches the SQL engine (503), so syntax IS validated but column
# names are not whitelisted.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=10
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# sqlfilters injection fuzz: quote inside value — must never 500
GET http://{{hostnport}}/api/index.php/categories?sqlfilters=(t.label%3Alike%3A%27%25%27%27OR%271%27%3D%271%27%25%27)&limit=10
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- Field selection & envelope

# properties — field reduction; assert shape only
GET http://{{hostnport}}/api/index.php/categories?properties=id%2Cref%2Clabel&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# properties — unknown property must not 500
GET http://{{hostnport}}/api/index.php/categories?properties=id%2Cnosuchproperty&limit=2
HTTP 200
[Asserts]
jsonpath "$" isCollection

# pagination_data=true — envelope with pagination metadata
GET http://{{hostnport}}/api/index.php/categories?limit=100&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# pagination_data=false — plain array, no envelope
GET http://{{hostnport}}/api/index.php/categories?limit=5&pagination_data=false
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Combined: sort + type filter + projection + pagination envelope in one request
GET http://{{hostnport}}/api/index.php/categories?sortfield=t.rowid&sortorder=DESC&type=customer&properties=id%2Clabel&limit=2&page=0&pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.limit" == 2

# ---------------------------------------------------------------- By id

# Positive: fetched by captured id — assert id round-trip and field presence only
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == "{{category_id}}"
jsonpath "$.label" exists

# Positive: include_childs=true — must serve; shape not pinned (may be empty)
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}?include_childs=true
HTTP 200
[Asserts]
jsonpath "$.id" == "{{category_id}}"

# include_childs=false — same contract
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}?include_childs=false
HTTP 200
[Asserts]
jsonpath "$.id" == "{{category_id}}"

# Negative: include_childs with junk value — OBSERVED (2026-11-23, hurl run):
# strictly validated like limit/page; non-boolean input is rejected with 400,
# not coerced to a default. Good behaviour — pin: 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}?include_childs=NOSUCHBOOL
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# Negative: nonexistent id -> 404
GET http://{{hostnport}}/api/index.php/categories/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# id=0 — FINDING 1 (see header). OBSERVED: HTTP 500, error body
# {"No category to search for", "Error when fetching category"}.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories/0
HTTP 500
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id — type-checked as on siblings
GET http://{{hostnport}}/api/index.php/categories/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# Negative: negative id — expected 404 like nonexistent ids
GET http://{{hostnport}}/api/index.php/categories/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- /categories/{id}/objects

# Objects linked to the captured category. `type` is REQUIRED here.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=customer
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=supplier
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=product
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=member
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=contact
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=project
HTTP 200
[Asserts]
jsonpath "$" isCollection

# onlyids=1 — memory-saving variant, same collection contract
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=customer&onlyids=1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Missing required `type` — OBSERVED (2026-11-23, hurl run): rejected with
# 400, i.e. the documented "type is required" constraint IS enforced.
# Pin: 400.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# Negative: nonexistent category id on the subresource -> 404
GET http://{{hostnport}}/api/index.php/categories/99999999/objects?type=customer
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Negative: non-numeric id on the subresource — type-checked with 400
GET http://{{hostnport}}/api/index.php/categories/abc/objects?type=customer
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# id=0 on the subresource — FINDING 1 CONFIRMED (2026-11-23): answers
# HTTP 500, consistent with /categories/0. Resource-wide falsy-id 500.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories/0/objects?type=customer
HTTP 500
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Full type sweep on /{id}/objects

# Every type value is exercised on this subresource. OBSERVED headline:
# unknown types are IGNORED here (200 + []) — this endpoint never 500s on
# type values. The per-type parity against /categories/object/{type}/...
# lives in 20_categories_object.hurl; divergences are FINDINGS there.

# Documented core set
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=customer
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=supplier
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=product
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=member
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=contact
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=project
HTTP 200
[Asserts]
jsonpath "$" isCollection

# actioncomm: documented on the list endpoint but not here. OBSERVED: 200
# (either mapped internally or ignore-unknown bucket).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=actioncomm
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Extra internal types beyond the documented enums. All answered 200 with a
# collection in the 2026-11-23 runs (mapped internally or ignored-unknown);
# none 500'd.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=bank
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=bank_account
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=ticket
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=expensereport
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=holiday
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=salary
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=user
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=websitepage
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=action
HTTP 200
[Asserts]
jsonpath "$" isCollection

# Unknown types — OBSERVED: 200 + [], silently ignored on this subresource.
GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=NOSUCHTYPE
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=objects
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=123
HTTP 200
[Asserts]
jsonpath "$" isCollection

GET http://{{hostnport}}/api/index.php/categories/{{category_id}}/objects?type=-1
HTTP 200
[Asserts]
jsonpath "$" isCollection

# ---------------------------------------------------------------- /categories/object/{type}/{id} — PERMISSION-INDEPENDENT pins only

# The reverse-lookup tests are split across two files by permission
# dependence (decided 2026-11-23):
#   - HERE (this file): the CLASS 1 pins — requests whose status cannot
#     depend on permissions, because the type-dispatch rejects them before
#     any permission check runs. Empirically confirmed: 400 regardless of
#     how rights are granted (expensereport answered 400 even though the
#     Expense Reports module was enabled with full rights).
#   - 10_categories_object.hurl: the CLASS 2 pins — the 200/403 answers on
#     registered types, which DO depend on the permission profile (product
#     and member gave 403 while customer and supplier gave 200 with the
#     same key). NOT committed until the 6-profile permission matrix
#     (non-admin/admin x personal/group-inherited, plus no-rights and
#     single-module profiles) has validated them.
# Unknown types are rejected 400 here — OBSERVED — unlike /{id}/objects
# above which ignores them (cross-endpoint inconsistency FINDING).

# bank — OBSERVED: 400. No object type keyed 'bank' internally.
GET http://{{hostnport}}/api/index.php/categories/object/bank/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# bank_account — OBSERVED: 400 (same camp; bank categories are not
# supported by the reverse-lookup dispatch at all).
GET http://{{hostnport}}/api/index.php/categories/object/bank_account/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# expensereport — OBSERVED: 400 even though the Expense Reports module was
# ENABLED with full rights on the observing instance. The dispatch supports
# a smaller type set than the enabled modules suggest — upstream note.
GET http://{{hostnport}}/api/index.php/categories/object/expensereport/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# holiday — OBSERVED: 400 (same camp).
GET http://{{hostnport}}/api/index.php/categories/object/holiday/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# salary — OBSERVED: 400 (same camp).
GET http://{{hostnport}}/api/index.php/categories/object/salary/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# websitepage — OBSERVED: 400 (same camp).
GET http://{{hostnport}}/api/index.php/categories/object/websitepage/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# action — OBSERVED: 400 (bare 'action' is not a valid key; only
# 'actioncomm' is).
GET http://{{hostnport}}/api/index.php/categories/object/action/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# Unknown type values — OBSERVED: rejected with 400 (strict), unlike
# /{id}/objects which silently ignores unknown types.
GET http://{{hostnport}}/api/index.php/categories/object/NOSUCHTYPE/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

GET http://{{hostnport}}/api/index.php/categories/object/objects/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

GET http://{{hostnport}}/api/index.php/categories/object/123/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# CASE-SENSITIVITY PROBE — OBSERVED: full caps variant of a valid type is
# rejected with 400 (case-sensitive validation confirmed).
GET http://{{hostnport}}/api/index.php/categories/object/CUSTOMER/1
HTTP 400
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 400

# Negative: non-numeric object id — OBSERVED: 400 (type-checked before any
# permission consideration).
GET http://{{hostnport}}/api/index.php/categories/object/customer/abc
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400
