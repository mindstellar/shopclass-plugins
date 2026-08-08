#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Gate 2 (Manifest) — MARKET.md §6.2. package-lint.php (core) does not read
# shopclass.json at all: it validates index.php's header block and the file
# tree, nothing JSON-shaped. Schema validation is deliberately left to a
# generic validator against schema/package.schema.json — CONTRIBUTING.md §2
# says as much ("validate ... with any JSON Schema (draft 2020-12) validator
# — ajv (Node) or jsonschema (Python) both work"). This script is that step,
# plus the two checks a schema alone cannot express: slug/directory agreement
# and that icon/screenshot paths actually resolve on disk.
#
# Usage: tools/validate-manifest.sh <package-dir> <schema-file>
#
# Prints {"ok":bool,"errors":[...],"warnings":[...]} in the same shape as
# package-lint.php --json, so render-comment.sh treats both uniformly.
# Exit 0 = no errors, 1 = errors found, 2 = usage error.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <package-dir> <schema-file>" >&2
  exit 2
fi

DIR="$1"
SCHEMA="$2"
SLUG="$(basename "$DIR")"
MANIFEST="${DIR}/shopclass.json"

ERRORS='[]'
WARNINGS='[]'

add_error() {
  ERRORS="$(jq -c --arg msg "$1" '. + [{"level":"error","code":"MANIFEST","file":"shopclass.json","line":null,"message":$msg}]' <<<"$ERRORS")"
}

add_warning() {
  WARNINGS="$(jq -c --arg msg "$1" '. + [{"level":"warning","code":"MANIFEST","file":"shopclass.json","line":null,"message":$msg}]' <<<"$WARNINGS")"
}

if [ ! -f "$MANIFEST" ]; then
  add_error "shopclass.json is missing."
  jq -n --argjson errors "$ERRORS" --argjson warnings "$WARNINGS" '{ok:false, errors:$errors, warnings:$warnings}'
  exit 1
fi

if ! jq -e . "$MANIFEST" >/dev/null 2>&1; then
  add_error "shopclass.json is not valid JSON."
  jq -n --argjson errors "$ERRORS" --argjson warnings "$WARNINGS" '{ok:false, errors:$errors, warnings:$warnings}'
  exit 1
fi

# --- JSON Schema (draft 2020-12) -------------------------------------------
# --errors=json emits one text line ("<file> invalid") followed by a JSON
# array of {instancePath, schemaPath, keyword, params, message}; sed strips
# that first line so the rest parses as JSON.
AJV_OUT=""
AJV_OK=1
if AJV_OUT="$(npx --yes -p ajv-cli@5 -p ajv-formats@3 ajv validate \
      -s "$SCHEMA" -d "$MANIFEST" --spec=draft2020 -c ajv-formats --all-errors --errors=json 2>&1)"; then
  AJV_OK=0
fi

if [ "$AJV_OK" -ne 0 ]; then
  AJV_ERRORS_JSON="$(printf '%s\n' "$AJV_OUT" | sed '1d')"
  if jq -e . >/dev/null 2>&1 <<<"$AJV_ERRORS_JSON"; then
    while IFS= read -r line; do
      [ -z "$line" ] && continue
      add_error "shopclass.json${line}"
    done < <(jq -r '.[] | (.instancePath | if . == "" then "" else " " + . end) + " " + .message' <<<"$AJV_ERRORS_JSON")
  else
    # Unexpected shape (e.g. schema itself failed to load) — surface it whole
    # rather than silently dropping it.
    add_error "$(printf '%s' "$AJV_OUT" | tr '\n' ' ')"
  fi
fi

# --- slug / directory agreement --------------------------------------------
MANIFEST_SLUG="$(jq -r '.slug // empty' "$MANIFEST")"
if [ -z "$MANIFEST_SLUG" ]; then
  : # already an error from the schema (slug is required)
elif [ "$MANIFEST_SLUG" != "$SLUG" ]; then
  add_error "shopclass.json \"slug\" (\"${MANIFEST_SLUG}\") does not match the directory name (\"${SLUG}\")."
fi

# --- icon / screenshot paths resolve ----------------------------------------
ICON="$(jq -r '.icon // empty' "$MANIFEST")"
if [ -n "$ICON" ] && [ ! -f "${DIR}/${ICON}" ]; then
  add_error "shopclass.json \"icon\" points at \"${ICON}\", which does not exist in the package."
fi

while IFS= read -r src; do
  [ -z "$src" ] && continue
  if [ ! -f "${DIR}/${src}" ]; then
    add_error "shopclass.json references screenshot \"${src}\", which does not exist in the package."
  fi
done < <(jq -r '.screenshots[]?.src // empty' "$MANIFEST")

OK="true"
if jq -e 'length > 0' <<<"$ERRORS" >/dev/null; then
  OK="false"
fi

jq -n --argjson ok "$OK" --argjson errors "$ERRORS" --argjson warnings "$WARNINGS" \
  '{ok:$ok, errors:$errors, warnings:$warnings}'

[ "$OK" = "true" ]
