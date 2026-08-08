#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Maps the files changed on this branch to the plugins/<slug> (and
# themes/<slug>, for parity with the sibling registry) packages they touch,
# and prints a JSON array of slugs for the pr-validate.yml matrix.
# docs/MARKET.md §6.1.
#
# Usage: tools/changed-packages.sh <base-ref>
#   <base-ref>  e.g. "origin/main" — diffed with the triple-dot form, so only
#               commits unique to this branch are considered, not commits
#               main picked up meanwhile. Requires a fetch-depth: 0 checkout
#               (or at least that <base-ref> is reachable locally).
#
# A PR touching only root files (README.md, CONTRIBUTING.md, ...) has no
# path under plugins/ or themes/ at all and prints "[]" — the caller (the
# GitHub Actions `if:` on the matrix job) is what turns that into a visible
# skipped-not-silent result; this script's only job is the mapping.
#
# A package that is deleted outright (every changed path under its slug is
# gone at HEAD) has nothing left to lint, so it is reported on stderr and
# left out of the JSON array rather than handed to package-lint.php against
# a directory that no longer exists.

set -euo pipefail

if [ "$#" -ne 1 ] || [ -z "${1:-}" ]; then
  echo "Usage: $0 <base-ref>" >&2
  exit 2
fi

BASE_REF="$1"

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

if ! git rev-parse --verify --quiet "${BASE_REF}^{commit}" >/dev/null; then
  echo "changed-packages: base ref '${BASE_REF}' is not resolvable locally — was the checkout fetch-depth: 0?" >&2
  exit 2
fi

# -M: rename detection, so a package moved wholesale (old path deleted, new
# path added in the same commit) resolves to the slug it lives at now.
# --diff-filter intentionally omitted: every status letter is handled below,
# and an unhandled one (e.g. a type change) still carries a path worth
# mapping.
mapfile -t CHANGES < <(
  git diff --name-status -M "${BASE_REF}...HEAD" -- plugins themes 2>/dev/null || true
)

declare -A TOUCHED=()   # slug -> 1 : appears anywhere in the diff
declare -A SURVIVES=()  # slug -> 1 : at least one changed path still exists at HEAD

map_path() {
  local path="$1"
  if [[ "$path" =~ ^(plugins|themes)/([^/]+)/ ]]; then
    local slug="${BASH_REMATCH[2]}"
    TOUCHED["$slug"]=1
    if [ -e "$path" ]; then
      SURVIVES["$slug"]=1
    fi
  fi
}

for line in "${CHANGES[@]:-}"; do
  [ -z "$line" ] && continue
  status="${line%%$'\t'*}"
  rest="${line#*$'\t'}"

  case "$status" in
    R*)
      # "R100<TAB>old/path<TAB>new/path" — two path fields.
      old_path="${rest%%$'\t'*}"
      new_path="${rest#*$'\t'}"
      map_path "$old_path"
      map_path "$new_path"
      ;;
    *)
      map_path "$rest"
      ;;
  esac
done

OUT=()
for slug in "${!TOUCHED[@]}"; do
  if [ -n "${SURVIVES[$slug]:-}" ]; then
    OUT+=("$slug")
  else
    echo "changed-packages: '${slug}' was removed entirely — nothing to validate, excluded from the matrix" >&2
  fi
done

if [ "${#OUT[@]}" -eq 0 ]; then
  echo "[]"
  exit 0
fi

printf '%s\n' "${OUT[@]}" | sort -u | jq -R -s -c 'split("\n") | map(select(length > 0))'
