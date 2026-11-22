# test/hurl/api/objectlinks/10_objectlinks.hurl
# GET-only tests for the objectlinks API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner environment.
# Portability: no assumptions about which objects are linked in the instance.
#   PROCESSING ORDER (pinned from observation, 2026-11-22) — all four stages
#   now observed:
#     1. Required params — ALL FOUR identifying params are required:
#        fk_source+sourcetype AND fk_target+targettype.
#        (observed: targettype alone -> 400; fk_target+targettype alone -> 400;
#         fk_source+sourcetype alone -> 400; no params -> 400)
#        There are NO one-sided lookups on this route.
#     2. Value validation — FORMAT ONLY: non-numeric long params are rejected
#        with 400 (fk_source=notanumber, even though its targettype would have
#        been gated 403), but NEGATIVE longs PASS (fk_source=-1 reached the
#        gate: 403). Stage 2 is a typecast check, not a range check —
#        diagnostic for the upstream report.
#     3. Per-type read gate: targettype=thirdparty -> 403 (observed on full
#        well-formed queries). targettype=commande passes (observed: stage-4
#        probes reached 404s).
#     4. Match semantics (observed): a well-formed, permitted-pair query with
#        nonexistent ids answers 404 — the explorer-docs convention, NOT the
#        sibling-module 200-with-empty-collection convention. Also pinned:
#        id 0 both sides -> 404 (no falsy-id leak here, unlike members);
#        fk_target=-1 -> 404; huge ids -> 404.
#   EXTRA-PARAM BEHAVIOR (observed): limit=abc is IGNORED (404, no 400) — this
#   handler has a FIXED SIGNATURE (the five documented params) and silently
#   drops everything else. Cross-module difference vs members/memberstypes
#   (strict limit -> 400). The route is a targeted by-values lookup, not a
#   paginated list — limit/page/sortfield/sqlfilters are not part of it.
#   PERMISSIONS FINDING: the gate denied targettype=thirdparty with 403
#   {"code":403,"message":"Forbidden: denied access to get the objectlinks
#   targettype=thirdparty"} although the SAME key reads /thirdparties fine
#   (sibling suite runs green). Candidate for an upstream report (type-to-
#   rights mapping looks buggy for at least 'thirdparty').
#   Instance portability caveat: which types the key may read is instance- and
#   permission-dependent. Gate asserts are pinned to the OBSERVED denial
#   ('thirdparty') and OBSERVED pass ('commande'); other instances may differ.
# Only GET requests in this file — POST/DELETE /objectlinks are deliberately
# not exercised, not even as negative tests, to keep the suite strictly read-only.
#
# NOTE: object links are (source id/type, target id/type) tuples — no PII of
# their own, but ids may resolve to thirdparty/member records elsewhere. Asserts
# are therefore restricted to status codes, error shape and id round-trips,
# never content of the linked objects. No positive by-id round-trip exists in a
# read-only suite (minting a link id needs POST first; out of scope here).
# Parameter set (per API explorer), all query-type on GET /objectlinks:
#   fk_source     (long)   source id of object we link from
#   sourcetype    (string) type of the source object
#   fk_target     (long)   target id of object we link to
#   targettype    (string) type of the target object
#   relationtype  (string) type of the relation, usually null

# ---------------------------------------------------------------- Stage 1: required params

# OBSERVED — pinned: a type without its id is not a well-formed query; 400
# before any value validation or permission gate.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?targettype=thirdparty
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: target-side pair alone answers 400 — the source pair is
# required. Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_target=99999999&targettype=thirdparty
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: source-side pair alone ALSO answers 400 — the target pair
# is required too. Both pairs are mandatory; no one-sided lookups exist on this
# route. Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: relationtype alone answers 400 — both identifying pairs
# are absent. Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?relationtype=somerelation
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: bare GET /objectlinks with no criteria answers 400 — no
# unparameterized full-table dump exists on this route. (Good: the members
# route family allows broad listings; this one does not.)
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# ---------------------------------------------------------------- Stage 2: value validation (format only)

# OBSERVED — pinned: a non-numeric long parameter is rejected with 400 even
# though the query's targettype WOULD have been gated with 403 — value
# validation runs BEFORE the permission gate, and checks format only.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=notanumber&sourcetype=commande&fk_target=99999999&targettype=thirdparty
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: alphanumeric id (digits+letters) is rejected with 400 —
# target side. Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=12abc&targettype=thirdparty
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned: NEGATIVE long values PASS stage 2 — the request reached
# the gate (403 for targettype=thirdparty), proving stage 2 is a format
# typecast with no semantic-range check. Change this assert deliberately,
# never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=-1&sourcetype=commande&fk_target=99999999&targettype=thirdparty
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# Sourcetype with a SPACE: string-typed, so no long-validation applies — the
# gate is the next stage. VERIFY: currently pinned as gate-answered (403 for
# the denied target type, consistent with 'space-type passes the gate but the
# query dies later at the target gate'); if unmappable source types get their
# own denial, the message will name the sourcetype instead — pin what you see.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande%20extra&fk_target=99999999&targettype=thirdparty
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# ---------------------------------------------------------------- Stage 3: permission gate

# FINDING — pinned: the per-type read gate denies targettype=thirdparty with
# 403 although the same key reads /thirdparties directly. Documented in the
# API explorer only as a bare "403 Forbidden" row — now with a concrete trigger.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=thirdparty
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403
jsonpath "$.error.message" contains "targettype=thirdparty"

# OBSERVED — pinned: EMPTY relationtype is treated as absent, not as a filter
# criterion — same deterministic 403.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=thirdparty&relationtype=
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# OBSERVED — pinned: relationtype containing a space passes through the gate
# without crashing it — same 403.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=thirdparty&relationtype=my%20relation
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# OBSERVED — pinned: self-link query, both sides the SAME denied type and id —
# the gate is the only stage that can answer — deterministic 403.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=thirdparty&fk_target=99999999&targettype=thirdparty
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# OBSERVED — pinned: same id on both sides with DIFFERENT types (a legal tuple
# shape — not a self-link) is handled without 500; denied target type wins.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=thirdparty
HTTP 403
[Asserts]
jsonpath "$.error.code" == 403

# ---------------------------------------------------------------- Stage 4: match semantics (observed)

# OBSERVED — pinned: full query on a PERMITTED pair (commande->commande) with
# nonexistent ids answers 404 — the explorer-docs convention, NOT the sibling
# 200-with-empty-collection convention. Cross-module inconsistency; candidate
# for the upstream report.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: id 0 on both sides answers 404 — no falsy-id leak on this
# route, unlike /members/0/subscriptions which returned real rows.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=0&sourcetype=commande&fk_target=0&targettype=commande
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: negative target id on a permitted pair answers 404 —
# consistent with the nonexistent-id base pin.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=-1&targettype=commande
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: extremely large ids are handled as longs, not truncated —
# still 404, not a 500.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999999999&sourcetype=commande&fk_target=99999999&targettype=commande
HTTP 404
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- By id (type-independent, observed)

# The /objectlinks/{id} route resolves a link row directly — no type given, so
# no gate applies. Per docs: 404 on a bad id.

# OBSERVED — pinned: nonexistent id -> 404 per docs.
GET http://{{hostnport}}/api/index.php/objectlinks/99999999
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: id 0 answers 404 — NOT a specimen. Sibling-module tally
# for id=0 becomes: 200-specimen (thirdparties, members), 400 (invoices),
# 404 (memberstypes, objectlinks). /objectlinks is a pure association class —
# the 404 is the sane answer.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/objectlinks/0
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: negative id -> 404, like nonexistent ids.
GET http://{{hostnport}}/api/index.php/objectlinks/-1
HTTP 404
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned: non-numeric id -> 400 (like memberstypes).
GET http://{{hostnport}}/api/index.php/objectlinks/notanumber
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ---------------------------------------------------------------- Extra params: ignored by a fixed-signature handler

# OBSERVED — pinned: limit=abc is IGNORED — the query proceeds to stage 4 and
# answers 404 (no-match), not 400. Unlike members/memberstypes (strict limit ->
# 400), this handler silently drops params outside its fixed signature. Every
# probe in this block therefore pins the stage-4 base code (404) plus
# "must not 500" — the params are noise, not features.
# Cross-module difference worth the upstream report: no limit, no page, no
# sortfield, no sqlfilters on a by-values lookup.
# Change these asserts deliberately, never accidentally.

# Non-numeric limit — ignored, stage-4 answer stands.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande&limit=abc
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Far-out page — ignored, stage-4 answer stands; never 500.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande&page=999&limit=2
HTTP 404
[Asserts]
jsonpath "$.error" exists

# Unknown sortfield — ignored here (no SQL injection surface on this route,
# unlike the 503s on memberstypes/members); stage-4 answer stands.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande&sortfield=t.nosuchfield
HTTP 404
[Asserts]
jsonpath "$.error" exists

# sqlfilters, unknown column — ignored (no SQL touchpoint); stage-4 answer
# stands. VERIFY: if this ever answers 503/400 instead, the handler DOES parse
# sqlfilters and the members findings apply here too.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande&sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)
HTTP 404
[Asserts]
jsonpath "$.error" exists

# sqlfilters, invalid syntax — ignored like the rest; stage-4 answer stands.
# VERIFY: 400 here would reveal a shared syntax validator after all.
GET http://{{hostnport}}/api/index.php/objectlinks?fk_source=99999999&sourcetype=commande&fk_target=99999999&targettype=commande&sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)
HTTP 404
[Asserts]
jsonpath "$.error" exists
