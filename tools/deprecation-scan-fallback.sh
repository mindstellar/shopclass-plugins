#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Gate 7 (Deprecated API), static layer — MARKET.md §6.3: "warn, never fail."
# This is a stand-in for core's tools/ci/deprecation-scan.php, which does not
# exist anywhere yet (see the "Frozen interface" note in fetch-package-ci.sh).
# It is deliberately the simplest thing that can be correct: grep the changed
# package for each symbol name in deprecated-api.json (a file *core*
# generates — this script does not decide what is deprecated, only reports
# where a name core already flagged appears). pr-validate.yml only calls this
# when package-ci/deprecation-scan.php is absent, and drops it without
# changes to any other gate the moment core publishes the real one.
#
# Unlike the runtime layer (smoke-install's deprecation collector, which
# needs core's container image and does not exist yet either — that half of
# gate 7 has no fallback here and is reported as unavailable), this covers
# only static call sites, and only by name — it cannot tell a real call from
# a coincidental identifier, so false positives are possible. That is an
# accepted property of a *warning*, not of a gate that blocks a merge.
#
# Usage: tools/deprecation-scan-fallback.sh <package-dir> <deprecated-api.json>
#
# Prints {"warnings":[...]}. Always exits 0 — per MARKET.md §6.3, this layer
# "sets no failing exit code", by contract, same as the real thing will.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <package-dir> <deprecated-api.json>" >&2
  echo '{"warnings":[]}'
  exit 0
fi

DIR="$1"
INVENTORY="$2"

if [ ! -f "$INVENTORY" ]; then
  echo "deprecation-scan-fallback: ${INVENTORY} not found — skipping" >&2
  echo '{"warnings":[]}'
  exit 0
fi

# One {name, since, replacement, removal} per line, functions+hooks+filters+classes merged.
SYMBOLS="$(jq -c '[(.functions // []), (.hooks // []), (.filters // []), (.classes // [])] | add // []' "$INVENTORY")"

WARNINGS='[]'

while IFS= read -r -d '' file; do
  REL="${file#"$DIR"/}"
  while IFS= read -r sym; do
    [ -z "$sym" ] && continue
    NAME="$(jq -r '.name' <<<"$sym")"
    [ -z "$NAME" ] && continue
    # Bare identifier for a plain function; last path segment for Class::method,
    # matched as a whole word so "osc_check_plugin_update" doesn't also match
    # "osc_check_plugin_update_extended".
    NEEDLE="${NAME##*::}"
    ESCAPED="$(printf '%s' "$NEEDLE" | sed 's/[.[\*^$/]/\\&/g')"

    while IFS=: read -r lineno _rest; do
      [ -z "$lineno" ] && continue
      SINCE="$(jq -r '.since // "an earlier version"' <<<"$sym")"
      REPLACEMENT="$(jq -r '.replacement // empty' <<<"$sym")"
      REMOVAL="$(jq -r '.removal // empty' <<<"$sym")"
      MSG="\`${NAME}\` is deprecated since ${SINCE}"
      [ -n "$REMOVAL" ] && MSG="${MSG} and scheduled for removal in ${REMOVAL}"
      if [ -n "$REPLACEMENT" ]; then
        MSG="${MSG}. Replacement: \`${REPLACEMENT}\`."
      else
        MSG="${MSG}. No replacement."
      fi
      WARNINGS="$(jq -c --arg file "$REL" --argjson line "$lineno" --arg msg "$MSG" \
        '. + [{"level":"warning","code":"DEPRECATED_API","file":$file,"line":$line,"message":$msg}]' <<<"$WARNINGS")"
    done < <(grep -noE "\\b${ESCAPED}\\b" "$file" 2>/dev/null || true)
  done < <(jq -c '.[]' <<<"$SYMBOLS")
done < <(find "$DIR" -type f -name '*.php' -print0)

jq -n --argjson warnings "$WARNINGS" '{warnings: $warnings}'
exit 0
