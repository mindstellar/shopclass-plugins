#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Builds the distributable zip for one plugins/<slug> package (docs/MARKET.md
# §7, PACKAGE-SPEC §2): a single top-level directory named for the slug,
# honouring .distignore, plus a sha256 sidecar.
#
# Usage: tools/build-release-zip.sh <slug> <out-dir>
#   <slug>     directory under plugins/ to package
#   <out-dir>  where to write <slug>_<version>.zip and its .sha256 (created
#              if it doesn't exist)
#
# The version in the zip's filename is read from the package's own Version:
# header at build time — this never takes a version on the command line,
# so what ships can never drift from what index.php actually declares.
#
# Human-readable progress goes to stderr; stdout carries only machine-
# readable `key=value` lines (version=, zip_path=, sha256_path=) meant to be
# appended straight to $GITHUB_OUTPUT.
#
# Exit codes: 0 ok; 1 the package directory or its Version: header is
# missing; 2 usage error.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <slug> <out-dir>" >&2
  exit 2
fi

SLUG="$1"
OUT_DIR="$2"

ROOT="$(git rev-parse --show-toplevel)"
SRC="${ROOT}/plugins/${SLUG}"

if [ ! -d "$SRC" ]; then
  echo "build-release-zip: plugins/${SLUG} does not exist." >&2
  exit 1
fi

if [ ! -f "${SRC}/index.php" ]; then
  echo "build-release-zip: plugins/${SLUG}/index.php is missing." >&2
  exit 1
fi

VERSION="$(grep -oP '(?i)^[ \t*/]*Version:\s*\K[^\r\n]*' "${SRC}/index.php" | head -n1 | sed -E 's/[[:space:]]+$//')"
if [ -z "$VERSION" ]; then
  echo "build-release-zip: plugins/${SLUG}/index.php has no Version: header." >&2
  exit 1
fi

mkdir -p "$OUT_DIR"
OUT_DIR="$(cd "$OUT_DIR" && pwd)"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

STAGE="${WORK}/${SLUG}"
mkdir -p "$STAGE"

# rsync's --exclude-from reads one pattern per line, the same minimal set
# PACKAGE-SPEC §2 asks every .distignore to carry (.git, .github,
# node_modules, tests, *.map, .distignore itself) — that list lives in each
# package's own .distignore, not duplicated here. rsync pattern syntax is
# close to, but not identical to, .gitignore's; the patterns actually in use
# today are plain literals and globs, which behave the same under both.
EXCLUDES=(--exclude=.git)
if [ -f "${SRC}/.distignore" ]; then
  EXCLUDES+=(--exclude-from="${SRC}/.distignore")
fi

rsync -a "${EXCLUDES[@]}" "${SRC}/" "${STAGE}/"

ZIP_PATH="${OUT_DIR}/${SLUG}_${VERSION}.zip"
rm -f "$ZIP_PATH"

if command -v zip >/dev/null 2>&1; then
  ( cd "$WORK" && zip -X -r -q "$ZIP_PATH" "$SLUG" )
else
  # zip(1) ships on every GitHub-hosted runner; this fallback exists only so
  # the script also runs somewhere that doesn't have it (local verification),
  # producing the same single-top-level-directory shape.
  echo "build-release-zip: 'zip' not found on PATH, falling back to Python's zipfile module." >&2
  python3 - "$WORK" "$SLUG" "$ZIP_PATH" <<'PY'
import os
import sys
import zipfile

work, slug, out = sys.argv[1], sys.argv[2], sys.argv[3]
root = os.path.join(work, slug)
with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as zf:
    for dirpath, _dirnames, filenames in os.walk(root):
        for name in sorted(filenames):
            full = os.path.join(dirpath, name)
            arc = os.path.relpath(full, work)
            zf.write(full, arc)
PY
fi

SHA256_PATH="${ZIP_PATH}.sha256"
sha256sum "$ZIP_PATH" | awk '{print $1}' > "$SHA256_PATH"

echo "build-release-zip: wrote ${ZIP_PATH}" >&2

echo "version=${VERSION}"
echo "zip_path=${ZIP_PATH}"
echo "sha256_path=${SHA256_PATH}"
