#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Final step of the validate job for one package: turns every gate's JSON
# result into GitHub annotations (::error / ::warning, so findings land
# inline on the diff) and a markdown fragment for the sticky PR comment
# (MARKET.md §6.2's closing paragraph). This script is the terminal pass/fail
# decision for the package — every earlier gate step in pr-validate.yml runs
# with continue-on-error so the full picture is always gathered before any
# verdict is rendered; THIS script's exit code is what fails the matrix job.
#
# If package-ci/annotate.php exists (core's shared renderer for the
# lint/deprecations/smoke trio — see the "Frozen interface" note in
# fetch-package-ci.sh), --annotate-php points pr-validate.yml at it and it is
# used for that trio's annotations and its own comment fragment; this script
# then appends the sections annotate.php does not know about (manifest,
# php -l, PHPCompatibility, style) below it. annotate.php does not exist
# anywhere yet as of this writing, so that branch is wired to the documented
# CLI contract but has never actually run — the --annotate-php-less fallback
# below is what every verification in this repo's history has exercised.
#
# Usage:
#   tools/render-comment.sh --slug=<s> \
#     --lint=<lint.json> --manifest=<manifest.json> \
#     --phplint=<phplint.json> --phpcompat=<phpcompat.json> \
#     --style=<style.json> \
#     [--deprecations=<deprecations.json>] [--smoke=<smoke.json>] \
#     [--annotate-php=<package-ci/annotate.php>] \
#     --out=<fragment.md>
#
# Exit 0 = no blocking finding in any gate. Exit 1 = at least one does.

set -euo pipefail

SLUG="" LINT="" MANIFEST="" PHPLINT="" PHPCOMPAT="" STYLE="" DEPRECATIONS="" SMOKE="" ANNOTATE_PHP="" OUT=""

for arg in "$@"; do
  case "$arg" in
    --slug=*) SLUG="${arg#--slug=}" ;;
    --lint=*) LINT="${arg#--lint=}" ;;
    --manifest=*) MANIFEST="${arg#--manifest=}" ;;
    --phplint=*) PHPLINT="${arg#--phplint=}" ;;
    --phpcompat=*) PHPCOMPAT="${arg#--phpcompat=}" ;;
    --style=*) STYLE="${arg#--style=}" ;;
    --deprecations=*) DEPRECATIONS="${arg#--deprecations=}" ;;
    --smoke=*) SMOKE="${arg#--smoke=}" ;;
    --annotate-php=*) ANNOTATE_PHP="${arg#--annotate-php=}" ;;
    --out=*) OUT="${arg#--out=}" ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

if [ -z "$SLUG" ] || [ -z "$LINT" ] || [ -z "$OUT" ]; then
  echo "Usage: $0 --slug=<s> --lint=<f> --manifest=<f> --phplint=<f> --phpcompat=<f> --style=<f> [--deprecations=<f>] [--smoke=<f>] [--annotate-php=<p>] --out=<f>" >&2
  exit 2
fi

BLOCKING=0
TRIO_MD="$(mktemp)"

read_json() {
  # $1 = path, default '{}' when missing/empty so every jq below can assume shape.
  if [ -n "${1:-}" ] && [ -s "$1" ]; then cat "$1"; else echo '{}'; fi
}

emit_annotations() {
  # $1 = findings array (each {level,code,file,line,message}), $2 = gate label for prefix
  jq -r --arg gate "$2" '
    .[]? |
    ( .level // "warning" ) as $lvl |
    ( .file // "" ) as $f |
    ( .line // "" ) as $ln |
    (if $f == "" then "" else "file=\($f)" + (if $ln == "" then "" else ",line=\($ln)" end) + " " end) as $loc |
    "::\($lvl) \($loc)title=\($gate) (\(.code // "FINDING"))::\(.message // "")"
  ' <<<"$1"
}

badge() { # $1=ok(true/false/pending)
  case "$1" in
    true) echo "✅" ;;
    false) echo "❌" ;;
    pending) echo "⏭️" ;;
    *) echo "⚠️" ;;
  esac
}

# =============================================================================
# Gates 1/3/4/8 (package-lint.php), 7 (deprecated API), 9 (smoke install):
# delegated to core's annotate.php when pr-validate.yml has one to point at,
# self-rendered otherwise. Either way this section ends with $TRIO_MD holding
# the markdown for these three rows and $BLOCKING updated.
# =============================================================================

if [ -n "$ANNOTATE_PHP" ] && [ -f "$ANNOTATE_PHP" ]; then
  ANNOTATE_ARGS=(--slug="$SLUG" --lint="$LINT" --comment-out="$TRIO_MD")
  [ -n "$DEPRECATIONS" ] && [ -s "$DEPRECATIONS" ] && ANNOTATE_ARGS+=(--deprecations="$DEPRECATIONS")
  [ -n "$SMOKE" ] && [ -s "$SMOKE" ] && ANNOTATE_ARGS+=(--smoke="$SMOKE")

  if ! php "$ANNOTATE_PHP" "${ANNOTATE_ARGS[@]}"; then
    BLOCKING=1
  fi
  [ -s "$TRIO_MD" ] || echo "_annotate.php produced no comment body._" > "$TRIO_MD"
else
  LINT_JSON="$(read_json "$LINT")"
  LINT_OK="$(jq -r '.ok // false' <<<"$LINT_JSON")"
  LINT_ERR="$(jq -c '.errors // []' <<<"$LINT_JSON")"
  LINT_WARN="$(jq -c '.warnings // []' <<<"$LINT_JSON")"
  emit_annotations "$LINT_ERR" "package-lint"
  emit_annotations "$LINT_WARN" "package-lint"
  [ "$LINT_OK" = "true" ] || BLOCKING=1

  DEPRECATIONS_AVAILABLE="false"
  DEPRECATIONS_JSON='{}'
  if [ -n "$DEPRECATIONS" ] && [ -s "$DEPRECATIONS" ]; then
    DEPRECATIONS_AVAILABLE="true"
    DEPRECATIONS_JSON="$(cat "$DEPRECATIONS")"
    emit_annotations "$(jq -c '.warnings // []' <<<"$DEPRECATIONS_JSON")" "deprecated API"
  fi

  SMOKE_AVAILABLE="false"
  SMOKE_JSON='{}'
  SMOKE_OK="pending"
  if [ -n "$SMOKE" ] && [ -s "$SMOKE" ]; then
    SMOKE_AVAILABLE="true"
    SMOKE_JSON="$(cat "$SMOKE")"
    SMOKE_OK="$(jq -r '.ok // false' <<<"$SMOKE_JSON")"
    emit_annotations "$(jq -c '.errors // []' <<<"$SMOKE_JSON")" "smoke install"
    emit_annotations "$(jq -c '.warnings // []' <<<"$SMOKE_JSON")" "smoke install"
    [ "$SMOKE_OK" = "true" ] || BLOCKING=1
  fi

  LINT_ROW="$(badge "$LINT_OK") $(jq 'length' <<<"$LINT_ERR") error(s), $(jq 'length' <<<"$LINT_WARN") warning(s)"
  if [ "$DEPRECATIONS_AVAILABLE" = "true" ]; then
    DEP_COUNT="$(jq '(.warnings // []) | length' <<<"$DEPRECATIONS_JSON")"
    DEPRECATIONS_ROW="⚠️ ${DEP_COUNT} warning(s) — never blocking"
  else
    DEPRECATIONS_ROW="$(badge pending) not available yet — core has not published \`tools/ci/deprecation-scan.php\`"
  fi
  if [ "$SMOKE_AVAILABLE" = "true" ]; then
    SMOKE_ROW="$(badge "$SMOKE_OK") $(jq '(.errors // []) | length' <<<"$SMOKE_JSON") error(s), $(jq '(.warnings // []) | length' <<<"$SMOKE_JSON") warning(s)"
  else
    SMOKE_ROW="$(badge pending) not available yet — core has not published \`tools/ci/smoke-install.sh\`"
  fi

  {
    echo "| 1, 3, 4, 8. Structure / Header / Compatibility fields / Security (\`package-lint.php\`) | ${LINT_ROW} |"
    echo "| 7. Deprecated API — warning only | ${DEPRECATIONS_ROW} |"
    echo "| 9. Smoke install | ${SMOKE_ROW} |"
  } > "$TRIO_MD"

  TRIO_DETAIL="$(mktemp)"
  {
    ALL_ERR="$(jq -s 'add' <<<"$LINT_ERR"$'\n''[]')"
    if [ "$(jq 'length' <<<"$ALL_ERR")" -gt 0 ]; then
      echo "<details><summary>package-lint.php errors (blocking)</summary>"
      echo
      jq -r '.[] | "- `\(.code)` " + (if .file then "**\(.file)" + (if .line then ":\(.line)" else "" end) + "** — " else "" end) + .message' <<<"$ALL_ERR"
      echo
      echo "</details>"
    fi
  } > "$TRIO_DETAIL"
  cat "$TRIO_DETAIL" >> "$TRIO_MD"
  rm -f "$TRIO_DETAIL"
fi

# =============================================================================
# Gate 2 (manifest schema) — always self-rendered; not in annotate.php's remit.
# =============================================================================
MANIFEST_JSON="$(read_json "$MANIFEST")"
MANIFEST_OK="$(jq -r '.ok // false' <<<"$MANIFEST_JSON")"
MANIFEST_ERR="$(jq -c '.errors // []' <<<"$MANIFEST_JSON")"
MANIFEST_WARN="$(jq -c '.warnings // []' <<<"$MANIFEST_JSON")"
emit_annotations "$MANIFEST_ERR" "manifest schema"
emit_annotations "$MANIFEST_WARN" "manifest schema"
[ "$MANIFEST_OK" = "true" ] || BLOCKING=1

# --- gate 5: php -l across versions ------------------------------------------
PHPLINT_JSON="$(read_json "$PHPLINT")"
[ "$(jq 'type=="array"' <<<"$PHPLINT_JSON" 2>/dev/null)" = "true" ] || PHPLINT_JSON='[]'
PHPLINT_OK="$(jq -e 'map(.ok) | all' <<<"$PHPLINT_JSON" >/dev/null 2>&1 && echo true || echo false)"
[ "$(jq 'length' <<<"$PHPLINT_JSON")" -gt 0 ] || PHPLINT_OK=true
while IFS= read -r finding; do
  [ -z "$finding" ] && continue
  emit_annotations "[$finding]" "php -l"
done < <(jq -c '.[] | .php_version as $v | .failures[]? | {level:"error", code:"PHP_PARSE", file:.file, line:.line, message:("PHP " + $v + ": " + .message)}' <<<"$PHPLINT_JSON" 2>/dev/null)
[ "$PHPLINT_OK" = "true" ] || BLOCKING=1

# --- gate 6: PHPCompatibility ------------------------------------------------
PHPCOMPAT_JSON="$(read_json "$PHPCOMPAT")"
PHPCOMPAT_OK="$(jq -r '.ok // false' <<<"$PHPCOMPAT_JSON")"
PHPCOMPAT_ERR="$(jq -c '.errors // []' <<<"$PHPCOMPAT_JSON")"
PHPCOMPAT_WARN="$(jq -c '.warnings // []' <<<"$PHPCOMPAT_JSON")"
emit_annotations "$PHPCOMPAT_ERR" "PHPCompatibility"
emit_annotations "$PHPCOMPAT_WARN" "PHPCompatibility"
[ "$PHPCOMPAT_OK" = "true" ] || BLOCKING=1

# --- gate 10: style (never blocking) ----------------------------------------
STYLE_JSON="$(read_json "$STYLE")"
STYLE_WARN="$(jq -c '.warnings // []' <<<"$STYLE_JSON")"
emit_annotations "$STYLE_WARN" "style"

# =============================================================================
# Markdown fragment
# =============================================================================

MANIFEST_ROW="$(badge "$MANIFEST_OK") $(jq 'length' <<<"$MANIFEST_ERR") error(s), $(jq 'length' <<<"$MANIFEST_WARN") warning(s)"
PHPLINT_ROW="$(badge "$PHPLINT_OK") $(jq '[.[] | .failures[]?] | length' <<<"$PHPLINT_JSON") failure(s) across $(jq 'length' <<<"$PHPLINT_JSON") PHP version(s)"
PHPCOMPAT_ROW="$(badge "$PHPCOMPAT_OK") $(jq 'length' <<<"$PHPCOMPAT_ERR") error(s), $(jq 'length' <<<"$PHPCOMPAT_WARN") warning(s)"
STYLE_ROW="⚠️ $(jq 'length' <<<"$STYLE_WARN") warning(s) — never blocking"

OVERALL="✅ Passing"
[ "$BLOCKING" -eq 0 ] || OVERALL="❌ Failing"

{
  echo "### \`${SLUG}\` — ${OVERALL}"
  echo
  echo "| Gate | Result |"
  echo "|---|---|"
  grep '^|' "$TRIO_MD" || true
  echo "| 2. Manifest (\`shopclass.json\` schema) | ${MANIFEST_ROW} |"
  echo "| 5. Parse (\`php -l\`, 8.0–8.5) | ${PHPLINT_ROW} |"
  echo "| 6. PHP 8.0 floor (PHPCompatibility) | ${PHPCOMPAT_ROW} |"
  echo "| 10. Style (PSR-12) — warning only | ${STYLE_ROW} |"
  echo

  grep -v '^|' "$TRIO_MD" || true

  ALL_ERRORS="$(jq -s 'add' <<<"$MANIFEST_ERR"$'\n'"$PHPCOMPAT_ERR")"
  if [ "$(jq 'length' <<<"$ALL_ERRORS")" -gt 0 ]; then
    echo "<details><summary>Other errors (blocking)</summary>"
    echo
    jq -r '.[] | "- `\(.code)` " + (if .file then "**\(.file)" + (if .line then ":\(.line)" else "" end) + "** — " else "" end) + .message' <<<"$ALL_ERRORS"
    echo
    echo "</details>"
    echo
  fi

  ALL_WARNINGS="$(jq -s 'add' <<<"$MANIFEST_WARN"$'\n'"$PHPCOMPAT_WARN"$'\n'"$STYLE_WARN")"
  if [ "$(jq 'length' <<<"$ALL_WARNINGS")" -gt 0 ]; then
    echo "<details><summary>Other warnings — not blocking, may be merged as-is</summary>"
    echo
    jq -r '.[] | "- `\(.code)` " + (if .file then "**\(.file)" + (if .line then ":\(.line)" else "" end) + "** — " else "" end) + .message' <<<"$ALL_WARNINGS"
    echo
    echo "</details>"
    echo
  fi
} > "$OUT"

rm -f "$TRIO_MD"

exit "$BLOCKING"
