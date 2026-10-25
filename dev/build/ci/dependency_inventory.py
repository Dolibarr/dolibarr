#!/usr/bin/env python3
"""Inventory bundled dependency metadata without installing or changing packages.

This is intentionally read-only. It reports metadata that can be found in the
checkout and compares exact version pins from composer.json[.disabled] with
version metadata shipped beside bundled libraries. Missing metadata is reported,
not guessed.
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path
from typing import Any

EXACT_VERSION = re.compile(r"^v?\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$")
PRUNE_DIRS = {
    ".git", ".cache", ".github", "node_modules", "vendor", "tests", "test",
    "examples", "docs", "doc", "fonts", "dist", "build", "coverage",
}


def read_json(path: Path) -> dict[str, Any] | None:
    try:
        value = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, UnicodeError, json.JSONDecodeError) as exc:
        print(f"WARNING: cannot parse {path}: {exc}", file=sys.stderr)
        return None
    return value if isinstance(value, dict) else None


def package_metadata(root: Path) -> list[dict[str, Any]]:
    records: dict[str, dict[str, Any]] = {}
    scan_roots = [root / "htdocs" / "includes", root / "htdocs" / "public"]

    for scan_root in scan_roots:
        if not scan_root.is_dir():
            continue
        for current, dirs, files in __import__("os").walk(scan_root):
            dirs[:] = sorted(d for d in dirs if d not in PRUNE_DIRS and not d.startswith("."))
            directory = Path(current)
            composer_path = directory / "composer.json"
            package_path = directory / "package.json"
            version_path = directory / "VERSION"

            for metadata_path in (composer_path, package_path):
                if metadata_path.name not in files:
                    continue
                metadata = read_json(metadata_path)
                if metadata is None:
                    continue
                name = metadata.get("name")
                version = metadata.get("version")
                if not isinstance(name, str) or not name.strip():
                    name = directory.name
                if not isinstance(version, str) or not version.strip():
                    version = None
                record = records.setdefault(
                    str(metadata_path.relative_to(root)),
                    {"name": name, "path": str(metadata_path.relative_to(root)),
                     "version": version, "version_source": None},
                )
                if version:
                    record["version_source"] = str(metadata_path.relative_to(root))

            if "VERSION" in files:
                try:
                    version = version_path.read_text(encoding="utf-8").strip()
                except (OSError, UnicodeError) as exc:
                    print(f"WARNING: cannot read {version_path}: {exc}", file=sys.stderr)
                    continue
                if not version:
                    continue
                # Attach VERSION to package metadata in the same directory when present.
                matching = [record for record in records.values()
                            if Path(record["path"]).parent == directory.relative_to(root)]
                if matching:
                    for record in matching:
                        record["version"] = version
                        record["version_source"] = str(version_path.relative_to(root))
                else:
                    relative = str(version_path.relative_to(root))
                    records[relative] = {
                        "name": directory.name, "path": relative,
                        "version": version, "version_source": relative,
                    }

    return sorted(records.values(), key=lambda item: (item["name"].lower(), item["path"]))


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--root", type=Path, default=Path(__file__).resolve().parents[3],
                        help="repository root (defaults to the checkout containing this script)")
    parser.add_argument("--output", type=Path, help="write JSON report to this path instead of stdout")
    args = parser.parse_args()
    root = args.root.resolve()

    active = root / "composer.json"
    disabled = root / "composer.json.disabled"
    manifest_path = active if active.is_file() else disabled if disabled.is_file() else None
    manifest = read_json(manifest_path) if manifest_path else None
    if manifest_path and manifest is None:
        return 2

    declared = (manifest or {}).get("require", {})
    if not isinstance(declared, dict):
        declared = {}
    installed = package_metadata(root)
    installed_by_name = {item["name"]: item for item in installed}
    comparisons = []
    for name, constraint in sorted(declared.items()):
        if name == "php" or not isinstance(constraint, str):
            continue
        item = installed_by_name.get(name)
        actual = item.get("version") if item else None
        exact = bool(EXACT_VERSION.fullmatch(constraint))
        comparisons.append({
            "name": name,
            "declared_constraint": constraint,
            "manifest_is_active": manifest_path == active,
            "bundled_metadata_found": item is not None,
            "bundled_version": actual,
            "bundled_version_source": item.get("version_source") if item else None,
            "exact_pin_mismatch": bool(exact and actual and constraint.lstrip("v") != actual.lstrip("v")),
            "exact_pin_not_verifiable": bool(exact and not actual),
            "constraint_is_exact_pin": exact,
        })

    report = {
        "repository": "amipm178/dolibarr",
        "manifest": str(manifest_path.relative_to(root)) if manifest_path else None,
        "manifest_active": bool(manifest_path == active),
        "lockfile_present": (root / "composer.lock").is_file(),
        "note": "Metadata inventory only; it does not prove that every listed component is used at runtime or identify all security advisories.",
        "declared_dependencies": comparisons,
        "bundled_metadata": installed,
        "summary": {
            "declared_dependency_count": len(comparisons),
            "bundled_metadata_count": len(installed),
            "exact_pin_mismatches": sum(1 for item in comparisons if item["exact_pin_mismatch"]),
            "exact_pins_not_verifiable": sum(1 for item in comparisons if item["exact_pin_not_verifiable"]),
        },
    }
    rendered = json.dumps(report, indent=2, ensure_ascii=False) + "\n"
    if args.output:
        output = args.output if args.output.is_absolute() else root / args.output
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(rendered, encoding="utf-8")
        print(f"Wrote dependency inventory to {output}")
        print(json.dumps(report["summary"], indent=2))
    else:
        print(rendered, end="")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
