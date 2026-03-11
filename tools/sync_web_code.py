#!/usr/bin/env python3
from __future__ import annotations

import argparse
import fnmatch
import hashlib
import json
import shutil
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable


@dataclass(frozen=True)
class Pair:
    source: Path
    target: Path


def file_hash(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        while True:
            chunk = handle.read(1024 * 1024)
            if not chunk:
                break
            digest.update(chunk)
    return digest.hexdigest()


def should_ignore(relative_path: str, ignore_globs: Iterable[str]) -> bool:
    unix_path = relative_path.replace("\\", "/")
    for pattern in ignore_globs:
        if fnmatch.fnmatch(unix_path, pattern):
            return True
    return False


def collect_files(root: Path, ignore_globs: list[str]) -> dict[str, Path]:
    files: dict[str, Path] = {}
    if not root.exists():
        return files

    for path in root.rglob("*"):
        if not path.is_file():
            continue
        relative = path.relative_to(root).as_posix()
        if should_ignore(relative, ignore_globs):
            continue
        files[relative] = path
    return files


def sync_pair(pair: Pair, ignore_globs: list[str], check_only: bool, delete_extra: bool) -> tuple[int, int, int]:
    source_files = collect_files(pair.source, ignore_globs)
    target_files = collect_files(pair.target, ignore_globs)

    differing: list[str] = []
    missing: list[str] = []
    extra: list[str] = []

    for relative, source_path in source_files.items():
        target_path = pair.target / relative
        if relative not in target_files:
            missing.append(relative)
            if not check_only:
                target_path.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(source_path, target_path)
            continue

        if file_hash(source_path) != file_hash(target_path):
            differing.append(relative)
            if not check_only:
                target_path.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(source_path, target_path)

    for relative, target_path in target_files.items():
        if relative in source_files:
            continue
        extra.append(relative)
        if delete_extra and not check_only:
            target_path.unlink(missing_ok=True)

    if missing or differing or (extra and delete_extra):
        mode = "CHECK" if check_only else "SYNC"
        print(f"[{mode}] {pair.source} -> {pair.target}")
        for relative in missing:
            print(f"  + {relative}")
        for relative in differing:
            print(f"  ~ {relative}")
        if delete_extra:
            for relative in extra:
                print(f"  - {relative}")
        elif extra:
            print("  ! Extra files exist in target (run with --delete to remove):")
            for relative in extra:
                print(f"    {relative}")

    return (len(missing), len(differing), len(extra))


def load_manifest(manifest_path: Path, repo_root: Path) -> tuple[list[Pair], list[str]]:
    raw = json.loads(manifest_path.read_text(encoding="utf-8"))
    pairs = [
        Pair(source=repo_root / item["source"], target=repo_root / item["target"])
        for item in raw["pairs"]
    ]
    ignore_globs: list[str] = raw.get("ignore_globs", [])
    return pairs, ignore_globs


def main() -> int:
    parser = argparse.ArgumentParser(description="Sync/check duplicated SMF web code trees.")
    parser.add_argument("--check", action="store_true", help="Check for drift without writing files.")
    parser.add_argument("--delete", action="store_true", help="Delete files that exist only in target.")
    parser.add_argument(
        "--manifest",
        type=str,
        default="tools/web_sync_manifest.json",
        help="Path to sync manifest (default: tools/web_sync_manifest.json).",
    )
    args = parser.parse_args()

    repo_root = Path(__file__).resolve().parents[1]
    manifest_path = (repo_root / args.manifest).resolve()
    if not manifest_path.exists():
        print(f"Manifest not found: {manifest_path}", file=sys.stderr)
        return 2

    pairs, ignore_globs = load_manifest(manifest_path, repo_root)

    total_missing = 0
    total_differing = 0
    total_extra = 0

    for pair in pairs:
        if not pair.source.exists():
            print(f"Source path not found: {pair.source}", file=sys.stderr)
            return 2
        pair.target.mkdir(parents=True, exist_ok=True)

        missing, differing, extra = sync_pair(
            pair=pair,
            ignore_globs=ignore_globs,
            check_only=args.check,
            delete_extra=args.delete,
        )
        total_missing += missing
        total_differing += differing
        total_extra += extra

    if args.check:
        if total_missing or total_differing or (args.delete and total_extra):
            print(
                f"Drift detected: missing={total_missing}, differing={total_differing}, extra={total_extra}",
                file=sys.stderr,
            )
            return 1
        if total_extra:
            print(
                f"No content drift. Extra files in targets: {total_extra} (use --delete to enforce exact mirror)."
            )
            return 0
        print("No web code drift detected.")
        return 0

    print(
        f"Sync complete: copied_missing={total_missing}, copied_differing={total_differing}, extra_in_target={total_extra}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())