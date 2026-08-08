#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Detects which plugins/<slug> packages had their Version: header change
# between <base-ref> and HEAD, for release.yml (docs/MARKET.md §7). Modeled
# on changed-packages.sh (§6.1), but the signal here is narrower than "a file
# under this package changed" — it is specifically "the released version
# number changed" — because that is the only thing a release should ever be
# keyed on.
#
# Usage: tools/detect-version-changes.sh <base-ref>
#   <base-ref>  usually `${{ github.event.before }}` from a push event.
#               Resolved locally, so the checkout needs enough history to
#               reach it (fetch-depth: 0, as with changed-packages.sh).
#
# A <base-ref> that cannot be resolved locally — the all-zeros SHA GitHub
# sends for a branch's first push, or history a shallow checkout can't see —
# is treated as "nothing to compare against" and prints "[]" rather than
# guessing, so a first push or a rewritten history never triggers a mass
# release.
#
# Output: a JSON array of {slug, version, name}, one per package whose
# Version: header differs between the two refs (including a package that
# didn't exist at <base-ref> at all — a brand new package's first version is
# a release too). "[]" when nothing changed.

set -euo pipefail

if [ "$#" -ne 1 ] || [ -z "${1:-}" ]; then
  echo "Usage: $0 <base-ref>" >&2
  exit 2
fi

BASE_REF="$1"

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

if ! git rev-parse --verify --quiet "${BASE_REF}^{commit}" >/dev/null; then
  echo "detect-version-changes: base ref '${BASE_REF}' is not resolvable locally (first push to this branch, or a shallow checkout) — nothing to compare against, reporting no releases." >&2
  echo "[]"
  exit 0
fi

# extract_field <ref> <path> <label> — reads one header field out of a file
# at a given ref via `git show`, tolerating the file (or the field) not
# existing there at all. The trailing `|| true` matters: with `pipefail`
# active, a nonexistent path (git show fails) or a field with no match (grep
# fails) would otherwise abort the whole script under `set -e`.
extract_field() {
  local ref="$1" path="$2" label="$3"
  git show "${ref}:${path}" 2>/dev/null \
    | grep -oP "(?i)^[ \t*/]*${label}:\s*\K[^\r\n]*" \
    | head -n1 \
    | sed -E 's/[[:space:]]+$//' \
    || true
}

mapfile -t CHANGED_SLUGS < <(
  git diff --name-only -M "${BASE_REF}...HEAD" -- 'plugins/*/index.php' 2>/dev/null \
    | sed -E 's#^plugins/([^/]+)/index\.php$#\1#' \
    | sort -u
)

OUT=()
for slug in "${CHANGED_SLUGS[@]:-}"; do
  [ -z "$slug" ] && continue
  path="plugins/${slug}/index.php"

  if [ ! -f "$path" ]; then
    echo "detect-version-changes: '${slug}' has no index.php at HEAD (removed) — nothing to release." >&2
    continue
  fi

  new_version="$(extract_field HEAD "$path" Version)"
  if [ -z "$new_version" ]; then
    echo "detect-version-changes: '${slug}' has no Version: header at HEAD — skipping." >&2
    continue
  fi

  old_version="$(extract_field "$BASE_REF" "$path" Version)"

  if [ "$new_version" = "$old_version" ]; then
    continue
  fi

  name="$(extract_field HEAD "$path" "Plugin Name")"
  [ -z "$name" ] && name="$slug"

  OUT+=("$(jq -n --arg slug "$slug" --arg version "$new_version" --arg name "$name" \
    '{slug: $slug, version: $version, name: $name}')")
  echo "detect-version-changes: '${slug}' Version changed '${old_version:-<none>}' -> '${new_version}'" >&2
done

if [ "${#OUT[@]}" -eq 0 ]; then
  echo "[]"
  exit 0
fi

printf '%s\n' "${OUT[@]}" | jq -s -c 'sort_by(.slug)'
