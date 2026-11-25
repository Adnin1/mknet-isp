#!/usr/bin/env python3
"""
MK Net local PHP syntax auditor.

The former script contained FTP/DB credentials, uploaded the whole workspace,
committed every file, and pushed directly to main. This replacement is
intentionally read-only. It does not deploy, connect to FTP, stage files,
commit, or push. Use the reviewed release package and owner-approved deployment
procedure for production changes.
"""
from __future__ import annotations

import shutil
import subprocess
import sys
from pathlib import Path


REPO_DIR = Path(__file__).resolve().parents[1]


def main() -> int:
    php = shutil.which("php")
    if not php:
        print("BLOCKED: PHP CLI was not found. Install PHP CLI and retry.")
        return 2

    php_files = sorted(
        p for p in REPO_DIR.rglob("*.php")
        if not any(part in {".git", "vendor", "scratch", ".system_generated"} for part in p.parts)
        and "storage/backups" not in p.as_posix()
    )
    if not php_files:
        print("FAIL: no PHP files found.")
        return 1

    failures: list[tuple[str, str]] = []
    for path in php_files:
        result = subprocess.run(
            [php, "-l", str(path)],
            cwd=REPO_DIR,
            capture_output=True,
            text=True,
            timeout=20,
            check=False,
        )
        if result.returncode != 0:
            detail = (result.stderr or result.stdout or "PHP lint failed").strip()
            failures.append((path.relative_to(REPO_DIR).as_posix(), detail))

    if failures:
        print(f"FAIL: {len(failures)} of {len(php_files)} PHP files failed syntax validation.")
        for relative_path, detail in failures:
            print(f"- {relative_path}: {detail}")
        return 1

    print(f"PASS: {len(php_files)} PHP files passed syntax validation.")
    print("NOT_DEPLOYED: this script is read-only and cannot access FTP or push to GitHub.")
    print("Use a reviewed release package and owner-approved deployment procedure.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except subprocess.TimeoutExpired as exc:
        print(f"FAIL: PHP lint timed out for {exc.cmd}.")
        raise SystemExit(1)
    except OSError as exc:
        print(f"FAIL: local validation could not run: {exc}")
        raise SystemExit(1)
