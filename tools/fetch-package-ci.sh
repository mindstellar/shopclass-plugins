#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Obtains the shared package-validation harness core publishes, into
# package-ci/ — docs/MARKET.md §6.4 ("one parser, no drift"): the header
# regexes and the compatibility comparison live once, in core, and every
# registry downloads the same copy rather than keeping a second one that
# could quietly diverge.
#
# Two paths, tried in order:
#
#   1. The newest mindstellar/shopclass release that carries a
#      shopclass-package-ci.tar.gz asset — the packaged, versioned bundle.
#   2. A shallow clone of the same release's tag (or --ref) and an assembly
#      of package-ci/ from the pieces core keeps in its tree: tools/package-lint.php,
#      tools/ci/*, oc-includes/osclass/classes/market/Compatibility.php, and
#      deprecated-api.json generated on the spot with
#      scripts/gen-deprecated-api.mjs.
#
# Path 2 is not a fallback of last resort today: core has not published the
# tarball yet (only package-lint.php, Compatibility.php and deprecated-api.json
# ship as loose release assets — see .github/workflows/build.yml in core), and
# core's tools/ci/ directory — deprecation-scan.php, annotate.php,
# smoke-install.sh, deprecation-collector/ — does not exist yet at all, in any
# branch or release. Both are called out explicitly rather than papered over:
# this script copies whatever of the six components it can find and writes
# package-ci/MANIFEST.json recording which ones actually arrived, so
# pr-validate.yml can run every gate that has a real implementation and skip
# — visibly, not silently — the ones that do not exist upstream yet.
#
# Usage:
#   tools/fetch-package-ci.sh [--out=DIR] [--core-repo=owner/name] [--ref=REF]
#
#   --out=DIR         where to assemble package-ci/  [default: package-ci]
#   --core-repo=R     the core repository              [default: mindstellar/shopclass]
#   --ref=REF         pin the fallback clone to this tag/branch instead of
#                      auto-detecting the newest release tag
#
# Exit codes: 0 = at least the required components (package-lint.php,
# Compatibility.php, deprecated-api.json) are present in package-ci/;
# 1 = even those could not be obtained; 2 = usage error.

set -euo pipefail

OUT="package-ci"
CORE_REPO="mindstellar/shopclass"
REF=""

for arg in "$@"; do
  case "$arg" in
    --out=*) OUT="${arg#--out=}" ;;
    --core-repo=*) CORE_REPO="${arg#--core-repo=}" ;;
    --ref=*) REF="${arg#--ref=}" ;;
    -h|--help)
      echo "Usage: $0 [--out=DIR] [--core-repo=owner/name] [--ref=REF]"
      exit 0
      ;;
    *)
      echo "Unknown option: $arg" >&2
      exit 2
      ;;
  esac
done

API="https://api.github.com/repos/${CORE_REPO}"
CURL=(curl -fsSL --retry 3 --retry-delay 2)

# --core-repo normally means "owner/name" on GitHub. It may also be a local
# path or an arbitrary git URL, purely so this script can be exercised
# against a working copy of core without a network round trip — production
# use (pr-validate.yml) always passes the default "owner/name" form, which
# takes the GitHub API / github.com clone path below.
case "$CORE_REPO" in
  http://*|https://*|git@*|/*|./*|../*)
    CLONE_URL="$CORE_REPO"
    IS_GITHUB_REPO=0
    ;;
  *)
    CLONE_URL="https://github.com/${CORE_REPO}.git"
    IS_GITHUB_REPO=1
    ;;
esac

log() { printf '%s\n' "$*" >&2; }

mkdir -p "$OUT"
OUT="$(cd "$OUT" && pwd)"

REQUIRED=(package-lint.php Compatibility.php deprecated-api.json)
OPTIONAL=(deprecation-scan.php annotate.php smoke-install.sh deprecation-collector)

have_required() {
  for f in "${REQUIRED[@]}"; do
    [ -e "${OUT}/${f}" ] || return 1
  done
  return 0
}

write_manifest() {
  local source="$1" ref="$2"
  {
    printf '{\n  "source": "%s",\n  "ref": "%s",\n  "components": {\n' "$source" "$ref"
    local all=("${REQUIRED[@]}" "${OPTIONAL[@]}")
    local n="${#all[@]}"
    local i=0
    for f in "${all[@]}"; do
      i=$((i + 1))
      local present="false"
      [ -e "${OUT}/${f}" ] && present="true"
      local comma=","
      [ "$i" -eq "$n" ] && comma=""
      printf '    "%s": %s%s\n' "$f" "$present" "$comma"
    done
    printf '  }\n}\n'
  } > "${OUT}/MANIFEST.json"
}

# ---------------------------------------------------------------------------
# Path 1: newest release carrying the packaged bundle.
# ---------------------------------------------------------------------------

try_release_tarball() {
  if [ "$IS_GITHUB_REPO" -eq 0 ]; then
    log "fetch-package-ci: --core-repo is a local path/URL, not owner/name — skipping the GitHub release lookup"
    return 1
  fi

  log "fetch-package-ci: checking ${CORE_REPO} releases for shopclass-package-ci.tar.gz ..."

  local releases
  if ! releases="$("${CURL[@]}" "${API}/releases?per_page=20" 2>/dev/null)"; then
    log "fetch-package-ci: could not list releases for ${CORE_REPO} (offline, or rate-limited)"
    return 1
  fi

  local asset_url
  asset_url="$(printf '%s' "$releases" | jq -r '
    map(select(.draft == false))
    | sort_by(.published_at)
    | reverse
    | map(.assets[]? | select(.name == "shopclass-package-ci.tar.gz") | {url: .browser_download_url, tag: .tag_name})
    | .[0] // empty
    | .url // empty
  ')"

  if [ -z "$asset_url" ]; then
    log "fetch-package-ci: no release yet publishes shopclass-package-ci.tar.gz — falling back to a source clone"
    return 1
  fi

  local tmp
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' RETURN

  if ! "${CURL[@]}" -o "${tmp}/bundle.tar.gz" "$asset_url"; then
    log "fetch-package-ci: found the asset but the download failed"
    return 1
  fi

  tar -xzf "${tmp}/bundle.tar.gz" -C "$tmp"

  local extracted="${tmp}/package-ci"
  if [ ! -d "$extracted" ]; then
    # Tolerate a tarball whose top level *is* the contents rather than a
    # package-ci/ wrapper directory.
    extracted="$tmp"
  fi

  cp -a "${extracted}/." "${OUT}/"
  log "fetch-package-ci: assembled package-ci/ from the release tarball"
  return 0
}

# ---------------------------------------------------------------------------
# Path 2: shallow clone + assemble from source.
# ---------------------------------------------------------------------------

resolve_ref() {
  if [ -n "$REF" ]; then
    printf '%s' "$REF"
    return 0
  fi

  if [ "$IS_GITHUB_REPO" -eq 0 ]; then
    # Local path/URL, no releases API to ask — whatever is checked out there.
    printf '%s' "$(git -C "$CLONE_URL" symbolic-ref --short HEAD 2>/dev/null || echo HEAD)"
    return 0
  fi

  local tag
  tag="$("${CURL[@]}" "${API}/releases?per_page=20" 2>/dev/null \
    | jq -r 'map(select(.draft == false)) | sort_by(.published_at) | reverse | .[0].tag_name // empty' \
    || true)"

  if [ -n "$tag" ]; then
    printf '%s' "$tag"
    return 0
  fi

  # No releases reachable at all — last resort, whatever the remote's default
  # branch currently is.
  "${CURL[@]}" "${API}" 2>/dev/null | jq -r '.default_branch // "master"' || printf 'master'
}

try_source_clone() {
  local ref
  ref="$(resolve_ref)"
  log "fetch-package-ci: cloning ${CLONE_URL}@${ref} (depth 1) to assemble package-ci/ from source ..."

  local tmp
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' RETURN

  if ! git clone --quiet --depth 1 --branch "$ref" "$CLONE_URL" "${tmp}/core" 2>/dev/null; then
    log "fetch-package-ci: clone of ${CLONE_URL}@${ref} failed (network, or ref does not exist)"
    return 1
  fi

  local core="${tmp}/core"

  # Required trio — Phase 1 (docs/MARKET.md §10) shipped these; they are
  # published release assets today, so they always exist in the tree too.
  if [ -f "${core}/tools/package-lint.php" ]; then
    cp "${core}/tools/package-lint.php" "${OUT}/package-lint.php"
  else
    log "fetch-package-ci: MISSING tools/package-lint.php in ${CORE_REPO}@${ref}"
  fi

  if [ -f "${core}/oc-includes/osclass/classes/market/Compatibility.php" ]; then
    cp "${core}/oc-includes/osclass/classes/market/Compatibility.php" "${OUT}/Compatibility.php"
  else
    log "fetch-package-ci: MISSING oc-includes/osclass/classes/market/Compatibility.php in ${CORE_REPO}@${ref}"
  fi

  if [ -f "${core}/scripts/gen-deprecated-api.mjs" ] && command -v node >/dev/null 2>&1; then
    ( cd "$core" && node scripts/gen-deprecated-api.mjs --out="${OUT}/deprecated-api.json" )
  else
    log "fetch-package-ci: MISSING scripts/gen-deprecated-api.mjs in ${CORE_REPO}@${ref} (or no node on PATH) — deprecated-api.json not generated"
  fi

  # Optional quartet — the harness a concurrent effort is building in core's
  # tools/ci/. Copied verbatim when present; reported, not fabricated, when
  # not: this repo does not carry a second implementation of any of these.
  for f in deprecation-scan.php annotate.php smoke-install.sh; do
    if [ -f "${core}/tools/ci/${f}" ]; then
      cp "${core}/tools/ci/${f}" "${OUT}/${f}"
    else
      log "fetch-package-ci: tools/ci/${f} does not exist yet in ${CORE_REPO}@${ref} — that gate is skipped (visibly) until it ships"
    fi
  done

  if [ -d "${core}/tools/ci/deprecation-collector" ]; then
    cp -a "${core}/tools/ci/deprecation-collector" "${OUT}/deprecation-collector"
  else
    log "fetch-package-ci: tools/ci/deprecation-collector/ does not exist yet in ${CORE_REPO}@${ref} — runtime deprecation capture during smoke-install is skipped"
  fi

  write_manifest "source-clone" "$ref"
  return 0
}

# ---------------------------------------------------------------------------

if ! try_release_tarball; then
  try_source_clone || true
  # write_manifest already called by try_source_clone; if that function
  # itself failed before reaching it (e.g. clone failed outright), make sure
  # a manifest still exists so callers have something to inspect.
  [ -f "${OUT}/MANIFEST.json" ] || write_manifest "source-clone-failed" "${REF:-unknown}"
else
  write_manifest "release-tarball" "unknown"
fi

echo >&2
log "fetch-package-ci: package-ci/ contents:"
find "$OUT" -maxdepth 2 -mindepth 1 | sed "s|^${OUT}/|  |" >&2

if have_required; then
  log "fetch-package-ci: required components present (package-lint.php, Compatibility.php, deprecated-api.json)"
  exit 0
fi

log "fetch-package-ci: FAILED — one or more required components are missing, see above"
exit 1
