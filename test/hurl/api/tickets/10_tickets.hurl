# test/hurl/api/tickets/10_tickets.hurl
# GET-only tests for the tickets API.
# Preconditions: {{hostnport}} in env; DOLAPIKEY supplied by the runner environment.
# STATUS OF PINS: FULLY OBSERVED (staged probe runs 2026-11-23, ending in a
#   green full-suite run: 36 requests). Every status code in this file has
#   been confirmed against a live instance across five staged runs; every
#   incorrect hypothesis was re-pinned to the observed reality before the
#   next run. The deliberate-change rule applies to all pins: "Change this
#   assert deliberately, never accidentally."
# Portability: no assumptions about instance data beyond AT LEAST ONE ticket
#   existing (the by-id section captures a real id from the list). The
#   positive by-ref/track_id paths are deliberately NOT captured — refs and
#   track ids are instance-specific and a capture-miss would abort the suite;
#   add positive coverage manually on an instance with known values.
# PII caveat: tickets carry customer correspondence (subject, message bodies,
#   emails, track ids). Asserts are therefore restricted to status codes,
#   error shape, envelope presence and id round-trips — never subjects,
#   messages, emails or names. The positive by-id round-trip pins only the id.
# Only GET requests in this file — the mutating endpoints (POST /tickets,
# PUT/DELETE /tickets/{id}, assign/close/contact link-unlink, POST
# /tickets/newmessage) are deliberately not exercised, not even as negative
#   tests, to keep the suite strictly read-only. The documents subresource
#   has its own suite (api/documents/10_documents_ticket.hurl) and is out of
#   scope here.
#
# Documented GET surface (per API explorer):
#   GET /tickets                    list    — socid, sortfield, sortorder,
#                                             limit, page, sqlfilters,
#                                             properties, loadcontacts (0/1),
#                                             pagination_data (boolean)
#   GET /tickets/{id}               by id   — path id
#   GET /tickets/ref/{ref}          by ref  — contact_list (0=full, 1=ids,
#                                             -1=none)
#   GET /tickets/track_id/{track_id} by track id — contact_list (same)
#   Explorer rows for by-ref/track_id list only 401/403/404 — proven
#   incomplete by FINDING 5: both routes DO emit 400 for empty segments.
#
# FINDINGS pinned across the module (candidates for the upstream report):
# FINDING 1 (OBSERVED 2026-11-23): socid validation is FORMAT-AWARE but NOT
#   SIGN-AWARE — socid=notanumber -> 400, while socid=-1 -> 200. Contrast
#   with expensereports' user_ids (sign-aware: -1 -> 400) and objectlinks'
#   format-only typecast. A third validation profile within the same API
#   family. RESIDUAL OPEN QUESTION (needs one curl probe): whether socid=-1
#   is APPLIED as a filter (total 0) or SILENTLY DROPPED (full listing);
#   the pin is deliberately loose (200 + envelope existence, no count).
# FINDING 2 (OBSERVED 2026-11-23): loadcontacts is STRICTLY ENUM-VALIDATED —
#   loadcontacts=maybe -> 400 (0/1 accepted), while undocumented garbage
#   sortorder=DESCx on the SAME route is IGNORED (200). The usual validation
#   asymmetry runs INVERTED here: a binary flag is strictly enforced while
#   the sort direction is not validated at all. Upstream-note candidate:
#   inconsistent validation culture within one request signature.
# FINDING 3 (OBSERVED 2026-11-23): sqlfilters validation is SPLIT on
#   tickets, exactly like expensereports/members — syntactically invalid
#   filter -> 400 (the contacts-style SYNTAX validator IS shared), while
#   UNKNOWN COLUMN -> 503 (the column name reaches the SQL engine
#   unvalidated). Same split for sortfield (unknown -> 503). Never a raw
#   SQL error dump or a 500.
# FINDING 4 (OBSERVED 2026-11-23): NON-POSITIVE IDS ARE ALL INVALID on the
#   by-id route — id=0 -> 400 AND id=-1 -> 400 (NOT 404). Tickets gates
#   EVERY id through format validation first; zero and negatives answer
#   400 while genuinely positive-but-nonexistent ids still 404 (observed
#   for 99999999 and 99999999999999). The id=0 tally becomes:
#   200-specimen (thirdparties, members), 400 (invoices, tickets — and
#   tickets extends the 400 gate to all negatives), 404 (memberstypes,
#   objectlinks, expensereports). Intra-module contrast: the by-id gate is
#   sign-strict while the socid filter is not (FINDING 1). The 400 error
#   bodies are not yet captured — pins are status-level plus loose
#   $.error-exists; a curl probe can tighten them.
# FINDING 5 (OBSERVED 2026-11-23): EMPTY LOOKUP SEGMENTS -> 400 on BOTH
#   string-lookup routes: /tickets/ref/ -> 400 and /tickets/track_id/ ->
#   400 (NOT 404) — the explorer's 401/403/404 row sets are incomplete.
#   Bare segments agree: /tickets/ref -> 400 and /tickets/track_id -> 400,
#   pointing to the {id}-gate interpretation (the string segment collapses
#   into a non-numeric {id} and FINDING 4's gate fires), though a routing
#   log would be needed to prove the mechanism definitively. Populated
#   nonexistent values answer 404 on both routes (sibling convention).
# CONFIRMED sibling patterns (OBSERVED 2026-11-23): pagination_data envelope
#   {data, pagination} present (expensereports FINDING-1 shape); strict
#   limit/page -> 400; non-numeric socid -> 400; empty socid treated as
#   absent; far-out page -> 200; properties projection honored and unknown
#   properties ignored; nonexistent positive ids -> 404 (incl. extreme
#   long); non-numeric id -> 400; nonexistent ref/track_id -> 404.

# ------------------------------------------------------- Section A: list — baseline & pagination

# OBSERVED — pinned (2026-11-23, probe run): bare list answers 200 with an
# array body; capture of a real id for later sections succeeded.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?limit=1
HTTP 200
[Captures]
ticket_id: jsonpath "$[0].id"

# OBSERVED — pinned: canonical ASC sort answers 200.
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.ref&sortorder=asc&limit=1
HTTP 200

# OBSERVED — pinned: uppercase sortorder accepted like lowercase.
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.ref&sortorder=ASC&limit=1
HTTP 200

# OBSERVED — pinned: garbage sortorder DESCx is IGNORED (200) — sortorder is
# not strictly validated; only the presence of a sortfield drives sorting.
# Contrast with loadcontacts below (FINDING 2): same route, opposite culture.
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.ref&sortorder=DESCx&limit=1
HTTP 200

# OBSERVED — pinned (2026-11-23, probe run): UNKNOWN SORTFIELD answers 503 —
# the sortfield string reaches the SQL engine unvalidated, like
# expensereports/members/memberstypes (NOT the contacts-style 400). The
# FINDING-3 family extends to tickets. Must not 500-crash.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.nosuchfield&limit=1
HTTP 503

# OBSERVED — pinned: far-out page answers 200 with an (empty) array, never 500.
GET http://{{hostnport}}/api/index.php/tickets?page=999&limit=5
HTTP 200

# OBSERVED — pinned: strict limit validation — non-numeric long rejected 400
# (same as members/memberstypes/invoices/expensereports).
GET http://{{hostnport}}/api/index.php/tickets?limit=abc
HTTP 400

# OBSERVED — pinned: strict page validation — non-numeric page 400.
GET http://{{hostnport}}/api/index.php/tickets?page=abc&limit=1
HTTP 400

# OBSERVED — pinned (2026-11-23, probe run): pagination_data=true IS honored.
# The envelope keys are "data" and "pagination" (total, page, page_count,
# limit) — the expensereports FINDING-1 shape. Without the param the body is
# a bare array (baseline capture succeeded). Assumes at least one ticket
# exists; on an empty instance $.data count == 1 will fail legitimately.
GET http://{{hostnport}}/api/index.php/tickets?pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination" exists
jsonpath "$.pagination.limit" == 1
jsonpath "$.pagination.total" >= 1
jsonpath "$.data" count == 1

# OBSERVED — pinned (2026-11-23, probe run): the fully combined request —
# every documented list parameter at once — answers 200 with the
# data/pagination envelope. Regression anchor for the whole signature. Uses
# nonexistent socid (99999999) to keep the empty result deterministic.
GET http://{{hostnport}}/api/index.php/tickets?sortfield=t.ref&sortorder=asc&limit=2&page=0&socid=99999999&sqlfilters=(t.ref%3Alike%3A%27%25%27)&properties=id,ref&pagination_data=true
HTTP 200

# ------------------------------------------------------- Section B: list — socid & loadcontacts (module-specific filters)

# OBSERVED — pinned (2026-11-23, probe run): a filter on a nonexistent
# thirdparty id answers 200 with the envelope — total 0, data []. Positive
# ids are not existence-checked before the query — only format (see FINDING
# 1). Data-independent: socid 99999999 owns nothing on any instance.
GET http://{{hostnport}}/api/index.php/tickets?socid=99999999&pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination.total" == 0
jsonpath "$.data" count == 0

# OBSERVED — pinned (2026-11-23, probe run): non-numeric socid is REJECTED
# with 400 — the filter's FORMAT is validated (not silently dropped).
GET http://{{hostnport}}/api/index.php/tickets?socid=notanumber&limit=1
HTTP 400

# FINDING 1 — OBSERVED — pinned (2026-11-23, probe run): NEGATIVE socid
# answers 200, NOT 400 — socid is format-aware but NOT sign-aware, unlike
# expensereports' sign-aware user_ids (-1 -> 400) and unlike the 400 this
# very route gives for 'notanumber'. The pin is deliberately LOOSE: with
# pagination_data=true the envelope must exist, but NO count assert —
# whether -1 filters empty vs is silently dropped needs one curl probe;
# tighten to a strict assert once observed.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?socid=-1&pagination_data=true&limit=1
HTTP 200
[Asserts]
jsonpath "$.pagination" exists

# OBSERVED — pinned (2026-11-23, probe run): EMPTY socid is treated as
# absent (the baseline listing is served, 200) — the members convention for
# empty filters holds on tickets too.
GET http://{{hostnport}}/api/index.php/tickets?socid=&limit=1
HTTP 200

# OBSERVED — pinned (2026-11-23, probe run): loadcontacts=1 is accepted and
# does not disturb the envelope/listing.
GET http://{{hostnport}}/api/index.php/tickets?loadcontacts=1&limit=1
HTTP 200

# FINDING 2 — OBSERVED — pinned (2026-11-23, probe run): garbage
# loadcontacts is REJECTED with 400 — the flag is STRICTLY ENUM-VALIDATED
# (0/1 only). Inverted asymmetry vs the same route: undocumented
# sortorder=DESCx is ignored (200) while this binary flag is enforced.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?loadcontacts=maybe&limit=1
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned (2026-11-23, probe run): loadcontacts=0 (the documented
# no) is accepted like 1 — the enum closes both ways (0/1, nothing else).
GET http://{{hostnport}}/api/index.php/tickets?loadcontacts=0&limit=1
HTTP 200

# ------------------------------------------------------- Section C: list — sqlfilters

# OBSERVED — pinned (2026-11-23, probe run): documented-syntax filter
# (always-true LIKE) answers 200.
GET http://{{hostnport}}/api/index.php/tickets?sqlfilters=(t.ref%3Alike%3A%27%25%27)&limit=1
HTTP 200

# OBSERVED — pinned (2026-11-23, probe run): always-FALSE filter answers 200
# with an empty collection — the list convention (200-empty), NOT the by-id
# 404 convention. No pagination_data, so the body is a bare empty array;
# emptiness pinned with count == 0 (a wildcard with no matches yields an
# empty LIST, which "exists" — never pin emptiness with "not exists").
# Data-independent.
GET http://{{hostnport}}/api/index.php/tickets?sqlfilters=(t.rowid%3A%3D%3A%27-1%27)&limit=1
HTTP 200
[Asserts]
jsonpath "$[*]" count == 0

# FINDING 3 — OBSERVED — pinned (2026-11-23, probe run): UNKNOWN sqlfilters
# COLUMN answers 503 — the column name reaches the SQL engine unvalidated
# (FINDING-3 family, expensereports/members pattern). Must never be a raw
# SQL error dump or a 500.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?sqlfilters=(t.nosuchcolumn%3A%3D%3A%271%27)&limit=1
HTTP 503

# FINDING 3 — OBSERVED — pinned (2026-11-23, probe run): SYNTACTICALLY
# INVALID sqlfilters (space inside the operator) is REJECTED with 400 — the
# contacts-style SYNTAX validator IS shared. Together with the 503 pin
# above: sqlfilters validation is SPLIT on tickets — syntax checked, column
# names not (FINDING 3).
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets?sqlfilters=(t.datec%20%3E%3A%20%272000-01-01%27)&limit=1
HTTP 400
[Asserts]
jsonpath "$.error.code" == 400

# OBSERVED — pinned (2026-11-23, probe run): multiple criteria joined per
# docs syntax ("(...) and (...)") — 200.
GET http://{{hostnport}}/api/index.php/tickets?sqlfilters=(t.ref%3Alike%3A%27%25%27)%20and%20(t.rowid%3A%3E%3A%270%27)&limit=1
HTTP 200

# ------------------------------------------------------- Section D: list — properties projection

# OBSERVED — pinned (2026-11-23, probe run): properties projection honored
# when non-empty (200).
GET http://{{hostnport}}/api/index.php/tickets?properties=id,ref&limit=1
HTTP 200

# OBSERVED — pinned (2026-11-23, probe run): unknown property name is
# IGNORED (200) — property names are not validated, unlike limit/page
# (strict) — the recurring validation asymmetry.
GET http://{{hostnport}}/api/index.php/tickets?properties=nosuchproperty&limit=1
HTTP 200

# ------------------------------------------------------- Section E: by id

# OBSERVED — pinned (2026-11-23, probe run): positive round-trip on a
# captured id answers 200 and echoes the id back. The ONLY content
# assertion in the suite (the id itself — no PII, no subject/message/email/
# track_id asserts).
GET http://{{hostnport}}/api/index.php/tickets/{{ticket_id}}
HTTP 200
[Asserts]
jsonpath "$.id" == {{ticket_id}}

# OBSERVED — pinned (2026-11-23, probe run): nonexistent positive id -> 404
# per the sibling/API-explorer convention — positive ids DO reach the query
# (contrast FINDING 4).
GET http://{{hostnport}}/api/index.php/tickets/99999999
HTTP 404

# FINDING 4 — OBSERVED — pinned (2026-11-23, probe run): id=0 -> 400, NOT
# 404 — tickets joins invoices in the 400 column of the id=0 tally
# (200-specimen: thirdparties, members; 400: invoices, tickets;
# 404: memberstypes, objectlinks, expensereports). Zero is treated as an
# INVALID id, not an unknown object. Status-level pin plus loose $.error
# exists; a curl probe should capture the body before a precise shape
# assert is pinned.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/0
HTTP 400
[Asserts]
jsonpath "$.error" exists

# FINDING 4 (extended) — OBSERVED — pinned (2026-11-23, probe run):
# NEGATIVE id -> 400, NOT 404 — the sibling 404 convention does NOT hold.
# Together with the id=0 pin: the by-id gate rejects ALL non-positive ids
# as invalid (400) and only lets positive ids through to the query.
# Intra-module contrast: socid=-1 in the list passes the same kind of gate
# as 200 (FINDING 1) — the by-id gate is sign-strict, the socid filter is
# not. Status-level pin; tighten the error-shape assert after the curl
# probe.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/-1
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned (2026-11-23, probe run): non-numeric id -> 400
# (members/memberstypes/objectlinks convention) — completes the gate: only
# well-formed positive longs reach the query.
GET http://{{hostnport}}/api/index.php/tickets/notanumber
HTTP 400

# OBSERVED — pinned (2026-11-23, probe run): extremely large id is handled
# as a long, not truncated — 404, not 500. Confirms the gate lets large
# POSITIVE ids through and only existence decides afterwards.
GET http://{{hostnport}}/api/index.php/tickets/99999999999999
HTTP 404

# ------------------------------------------------------- Section F: by ref

# NOTE: the positive-ref path is NOT captured — refs are instance-specific
# and a capture-miss would abort the suite. Only negative shapes are pinned;
# positive-ref coverage: add manually on an instance with a known ref.

# OBSERVED — pinned (2026-11-23, probe run): nonexistent ref -> 404 (the
# explorer's 404 row; sibling by-id convention holds for populated refs).
GET http://{{hostnport}}/api/index.php/tickets/ref/NOSUCHREF99999
HTTP 404

# FINDING 5 — OBSERVED — pinned (2026-11-23, probe run): EMPTY ref -> 400,
# NOT 404 — proving the explorer's 401/403/404 row set incomplete: this
# route DOES emit 400. Bare /tickets/ref also answers 400 (Section H),
# pointing to the {id}-gate interpretation, though the mechanism is not
# provable from black-box responses alone. Status-level pin; curl probe
# pending for the error body.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/ref/
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ------------------------------------------------------- Section G: by track_id

# NOTE: same capture policy as Section F. track_id is the tracking id shown
# to customers — treat it as PII-adjacent: never assert its value, only
# statuses.

# OBSERVED — pinned (2026-11-23, probe run): nonexistent track_id -> 404 —
# mirrors the ref route; populated values follow the sibling 404
# convention on both string-lookup routes.
GET http://{{hostnport}}/api/index.php/tickets/track_id/NOSUCHTRACK99999
HTTP 404

# FINDING 5 — OBSERVED — pinned (2026-11-23, probe run): EMPTY track_id ->
# 400, mirroring the ref/ result exactly — both string-lookup routes reject
# empty segments as invalid rather than unknown. No intra-module divergence
# between ref and track_id on the empty-value question.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/track_id/
HTTP 400
[Asserts]
jsonpath "$.error" exists

# ------------------------------------------------------- Section H: route-collision probes (bare static segments)

# OBSERVED — pinned (2026-11-23, probe run): bare /tickets/ref answers 400 —
# the {id} route's non-numeric gate claims the bare static segment ('ref'
# fails the long parse, FINDING 4's gate fires). No dedicated bare-ref
# handler. Drift here means the router lost static-over-dynamic precedence
# or gained a bare-ref route.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/ref
HTTP 400
[Asserts]
jsonpath "$.error" exists

# OBSERVED — pinned (2026-11-23, probe run): bare /tickets/track_id answers
# 400 — same collision resolution as bare /tickets/ref; both bare static
# segments fall to the {id} gate.
# Change this assert deliberately, never accidentally.
GET http://{{hostnport}}/api/index.php/tickets/track_id
HTTP 400
[Asserts]
jsonpath "$.error" exists
