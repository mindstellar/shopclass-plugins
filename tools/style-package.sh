#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Gate 10 (Style) — MARKET.md §6.2: "php-cs-fixer PSR-12 dry-run as
# annotations. Third-party house style must not block a contribution." Always
# a warning, never a failure — the exit code of this script is informational
# only and pr-validate.yml must not let it fail the job.
#
# Usage: tools/style-package.sh <package-dir> <php-cs-fixer.phar>
#
# Prints {"ok":bool,"warnings":[...]} — "ok" here means "nothing to report",
# not "safe to merge"; there is no error bucket because this gate has none.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <package-dir> <php-cs-fixer.phar>" >&2
  exit 2
fi

DIR="$1"
FIXER="$2"

if [ ! -f "$FIXER" ]; then
  echo '{"ok":true,"warnings":[{"level":"warning","code":"STYLE_SETUP","file":null,"line":null,"message":"php-cs-fixer.phar not found; style gate skipped"}]}'
  exit 0
fi

OUT="$(php "$FIXER" fix --rules=@PSR12 --dry-run --diff --using-cache=no --no-interaction "$DIR" 2>&1 || true)"

WARNINGS='[]'
while IFS= read -r file; do
  [ -z "$file" ] && continue
  REL="${file#"$DIR"/}"
  WARNINGS="$(jq -c --arg f "$REL" \
    '. + [{"level":"warning","code":"STYLE_PSR12","file":$f,"line":null,"message":"Not PSR-12 formatted (php-cs-fixer --dry-run --diff)."}]' \
    <<<"$WARNINGS")"
done < <(printf '%s\n' "$OUT" | grep -oE '^\s*[0-9]+\)\s+\S+\.php' | grep -oE '\S+\.php' || true)

jq -n --argjson warnings "$WARNINGS" '{ok: (($warnings | length) == 0), warnings: $warnings}'
exit 0
