#!/usr/bin/env python3
"""Probe the objectlinks gate matrix and write pinned hurl files.

Type list source: GET /setup/modules on the target instance (same auth
as the probes). The response is a JSON object keyed by module name; its
keys, in response order, become the type list. If that call fails, the
script falls back to objecttypes.json next to this file (and says so).

Phase 1 (probe): one GET per (sourcetype, targettype) pair over ALL
types, with deliberately nonexistent ids (99999999). Only the HTTP
status code is recorded — no content is read or stored.

Phase 2 (pin): writes 10_objectlinks_<sourcetype>.hurl for every type,
pinning each request to its OBSERVED code, and writes matrix_summary.txt
with counts and the 403 pairs (gate findings). Overwrites earlier
generated/reference files.

Environment (same as the hurl runner):
  hostnport  host:port of the Dolibarr instance
  DOLAPIKEY  API key

Usage: python3 run_objectlinks_matrix.py
Strictly read-only: only GET requests are issued.
"""
import collections
import datetime
import json
import os
import sys
import urllib.error
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
NONEXIST_ID = "99999999"

HOSTNPORT = os.environ.get("hostnport")
API_KEY = os.environ.get("DOLAPIKEY")
if not HOSTNPORT or not API_KEY:
    sys.exit("need env vars hostnport and DOLAPIKEY (as supplied to hurl)")

BASE = "http://%s/api/index.php" % HOSTNPORT


def load_types():
    """Fetch the type list from GET /setup/modules; fallback to objecttypes.json."""
    url = "%s/setup/modules" % BASE
    req = urllib.request.Request(url)
    req.add_header("DOLAPIKEY", API_KEY)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read().decode("utf-8"))
        if not isinstance(data, dict) or not data:
            raise ValueError("unexpected /setup/modules payload: %r" % type(data))
        print("type list from /setup/modules: %d types" % len(data))
        return list(data.keys())
    except Exception as exc:
        print("/setup/modules unavailable (%s)" % exc, file=sys.stderr)
        fallback = os.path.join(HERE, "objecttypes.json")
        if not os.path.exists(fallback):
            sys.exit("fallback %s missing too — aborting" % fallback)
        print("falling back to %s" % fallback, file=sys.stderr)
        with open(fallback, encoding="utf-8") as fh:
            return json.load(fh)["types"]


TYPES = load_types()


def probe(src, tgt):
    url = (
        "%s/objectlinks?fk_source=%s&sourcetype=%s"
        "&fk_target=%s&targettype=%s"
        % (BASE, NONEXIST_ID, src, NONEXIST_ID, tgt)
    )
    req = urllib.request.Request(url)
    req.add_header("DOLAPIKEY", API_KEY)
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return resp.status
    except urllib.error.HTTPError as err:
        return err.code
    except Exception as exc:  # connection refused, timeout, DNS, ...
        print("probe failed %s->%s: %s" % (src, tgt, exc), file=sys.stderr)
        return None


# ----------------------------------------------------------------- phase 1
results = {}
for src in TYPES:
    for tgt in TYPES:
        results[(src, tgt)] = probe(src, tgt)

with open(os.path.join(HERE, "matrix_results.csv"), "w", encoding="utf-8") as fh:
    fh.write("sourcetype,targettype,status\n")
    for (src, tgt), code in results.items():
        fh.write("%s,%s,%s\n" % (src, tgt, code if code is not None else "FAIL"))

# ----------------------------------------------------------------- phase 2
HEADER = """# test/hurl/api/objectlinks/10_objectlinks_{src}.hurl
# GET-only matrix: sourcetype={src} x ALL {n} object types (incl. itself).
# GENERATED from an observed probe run ({stamp}) by run_objectlinks_matrix.py
# (type list from GET /setup/modules on this instance):
# every request below is pinned to the status OBSERVED on this instance —
# including the per-type gate denials (403). Regenerate after changing
# instance permissions; treat any manual edit as deliberate.
# Reference model from 10_objectlinks.hurl: required params -> format-only
# validation -> per-type read gate -> match semantics (404 on no-match).
# Ids are deliberately nonexistent (99999999); no real links are touched.
# Only GET requests — the suite is strictly read-only.
"""

REQ = """
# -> {tgt}{selfnote}
GET http://{{{{hostnport}}}}/api/index.php/objectlinks?fk_source={nid}&sourcetype={src}&fk_target={nid}&targettype={tgt}
{pin}
"""


def pin_lines(code):
    if code == 200:
        # Observed 200 on a nonexistent-id query: shape-check only, and treat
        # a collection with rows as suspect data exposure on next run.
        return (
            "HTTP 200\n[Asserts]\njsonpath \"$\" isCollection\n"
            "# OBSERVED 200 for nonexistent ids — check rows are empty:"
        )
    if code == 404:
        return 'HTTP 404\n[Asserts]\njsonpath "$.error" exists'
    if code in (400, 403):
        return 'HTTP %d\n[Asserts]\njsonpath "$.error.code" == %d' % (code, code)
    if code is None:
        # Probe never completed — emit unpinned so the next hurl run reveals it.
        return "# UNPINNED: probe failed (connection/timeout) — pin after next run"
    # Any other observed code (401, 405, 500, 503, ...) — pin as seen.
    return 'HTTP %d\n[Asserts]\njsonpath "$.error" exists' % code


counts = collections.Counter()
denied_pairs = []
for src in TYPES:
    parts = [
        HEADER.format(src=src, n=len(TYPES), stamp=datetime.date.today())
    ]
    for tgt in TYPES:
        code = results[(src, tgt)]
        counts[code] += 1
        if code == 403:
            denied_pairs.append((src, tgt))
        parts.append(
            REQ.format(
                tgt=tgt,
                selfnote=" (self)" if tgt == src else "",
                nid=NONEXIST_ID,
                src=src,
                pin=pin_lines(code),
            )
        )
    with open(
        os.path.join(HERE, "10_objectlinks_%s.hurl" % src), "w", encoding="utf-8"
    ) as fh:
        fh.write("".join(parts))

# ----------------------------------------------------------------- summary
with open(os.path.join(HERE, "matrix_summary.txt"), "w", encoding="utf-8") as fh:
    fh.write("objectlinks gate matrix — %d pairs probed\n\n" % len(results))
    fh.write("status counts:\n")
    for code, n in sorted(counts.items(), key=lambda kv: str(kv[0])):
        fh.write("  %-5s %d\n" % (code, n))
    fh.write("\n403 pairs (per-type gate denials):\n")
    for src, tgt in denied_pairs:
        fh.write("  %s -> %s\n" % (src, tgt))

print("wrote %d files + matrix_results.csv + matrix_summary.txt" % len(TYPES))
print("status counts:", dict(counts))
if denied_pairs:
    print("403 pairs (%d): %s" % (len(denied_pairs), ", ".join("%s>%s" % p for p in denied_pairs)))
