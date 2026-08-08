#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Gate 5 (Parse) — MARKET.md §6.2: `php -l` across 8.0-8.5, mirroring
# tests/php-lint.sh in core. That script runs on whatever `php` is on PATH
# for the *whole* core tree; this is the same idea scoped to one package
# directory, called once per PHP version after the caller switches PATH to
# it (pr-validate.yml does this with repeated shivammathur/setup-php steps).
#
# Usage: tools/php-lint-package.sh <package-dir> <accumulator-file>
#
# Appends this run's result to a JSON array in <accumulator-file> (creating
# it if absent), keyed by the running PHP_VERSION, so a loop across several
# PHP versions builds one combined report rather than one file per version.
#
# Exit 0 = every file parses on this PHP version, 1 = at least one does not.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <package-dir> <accumulator-file>" >&2
  exit 2
fi

DIR="$1"
ACCUM="$2"
VERSION="$(php -r 'echo PHP_VERSION;')"

[ -f "$ACCUM" ] || echo '[]' > "$ACCUM"

FAILURES='[]'
CHECKED=0

while IFS= read -r -d '' file; do
  CHECKED=$((CHECKED + 1))
  if ! OUT="$(php -l "$file" 2>&1)"; then
    REL="${file#"$DIR"/}"
    LINE="$(printf '%s' "$OUT" | grep -oE 'on line [0-9]+' | grep -oE '[0-9]+' | head -1)"
    LINE="${LINE:-null}"
    MSG="$(printf '%s' "$OUT" | head -1 | sed 's/"/\\"/g')"
    FAILURES="$(jq -c --arg file "$REL" --argjson line "$LINE" --arg msg "$MSG" \
      '. + [{"file":$file, "line":$line, "message":$msg}]' <<<"$FAILURES")"
  fi
done < <(find "$DIR" -type f -name '*.php' -print0)

OK="true"
if jq -e 'length > 0' <<<"$FAILURES" >/dev/null; then
  OK="false"
fi

TMP="$(mktemp)"
jq -c --arg version "$VERSION" --argjson ok "$OK" --argjson checked "$CHECKED" --argjson failures "$FAILURES" \
  '. + [{"php_version":$version, "ok":$ok, "checked":$checked, "failures":$failures}]' \
  "$ACCUM" > "$TMP"
mv "$TMP" "$ACCUM"

echo "php -l on PHP ${VERSION}: ${CHECKED} file(s) checked, $(jq 'length' <<<"$FAILURES") failure(s)" >&2

[ "$OK" = "true" ]
