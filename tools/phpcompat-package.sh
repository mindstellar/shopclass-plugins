#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Gate 6 (PHP floor) — MARKET.md §6.2: PHPCompatibility, `--runtime-set
# testVersion 8.0-`. package-lint.php does not run this (it is a
# dependency-free single file with no room for a PHPCompatibility install);
# this script drives phpcs directly against the isolated install in
# tools/lint/ (mirrors core's own tools/lint/, kept out of oc-includes/vendor
# for the same reason: it must never ship inside a package).
#
# Usage: tools/phpcompat-package.sh <package-dir>
#
# Prints {"ok":bool,"errors":[...],"warnings":[...]}. Exit 0 = no findings
# (this gate fails on ANY PHPCompatibility hit, error or warning severity —
# PACKAGE-SPEC §8 lists "PHP 8.0 floor via PHPCompatibility" as blocking,
# full stop), 1 = findings present, 2 = usage/setup error.

set -euo pipefail

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 <package-dir>" >&2
  exit 2
fi

DIR="$1"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHPCS="${HERE}/lint/vendor/bin/phpcs"

if [ ! -x "$PHPCS" ]; then
  echo "phpcompat-package: ${PHPCS} not found — run 'composer install --working-dir=tools/lint' first" >&2
  exit 2
fi

REPORT="$("$PHPCS" --standard=PHPCompatibility --runtime-set testVersion 8.0- \
  --extensions=php --report=json "$DIR" 2>/dev/null || true)"

if [ -z "$REPORT" ]; then
  echo '{"ok":false,"errors":[{"level":"error","code":"PHPCOMPAT_SETUP","file":null,"line":null,"message":"phpcs produced no output"}],"warnings":[]}'
  exit 2
fi

jq --arg dir "$DIR" '
  (.files // {}) as $files
  | [ $files | to_entries[] | .key as $file | .value.messages[] |
      {
        level: (if .type == "ERROR" then "error" else "warning" end),
        code: ("PHPCOMPAT_" + .source),
        file: ($file | sub("^" + $dir + "/"; "")),
        line: .line,
        message: .message
      }
    ] as $all
  | {
      ok: (($all | map(select(.level=="error" or .level=="warning")) | length) == 0),
      errors: ($all | map(select(.level=="error"))),
      warnings: ($all | map(select(.level=="warning")))
    }
' <<<"$REPORT"

TOTAL="$(jq '.totals.errors + .totals.warnings' <<<"$REPORT")"
[ "$TOTAL" -eq 0 ]
