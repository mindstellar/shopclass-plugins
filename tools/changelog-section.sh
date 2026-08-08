#!/usr/bin/env bash
#
# This file is part of Shopclass (Mindstellar).
# Copyright (c) 2021-2026 Mindstellar Community
#
# Distributed under the GNU General Public License v3.0 or later. See LICENSE.
#
# SPDX-License-Identifier: GPL-3.0-or-later
#
# Extracts one version's section out of a package CHANGELOG.md, for
# release.yml's release body (docs/MARKET.md §7: "body taken from the
# package's CHANGELOG.md section for that version"). Unlike core's own
# release-notes extraction in mindstellar/shopclass's build.yml — which stops
# at the first "### " subheading because core's own body is deliberately a
# short intro paragraph — this prints the section whole, since a package's
# CHANGELOG.md is the entire per-version note, not a paragraph plus a link
# to more detail elsewhere.
#
# Usage: tools/changelog-section.sh <CHANGELOG.md> <version>
#
# Prints the trimmed body between a "## <version>" heading (an exact match,
# optionally bracketed, e.g. "## [1.2.0]") and the next "## " heading, or end
# of file. Prints nothing (exit 0) if the file or the version's heading
# doesn't exist — the caller decides what a missing changelog means.

set -euo pipefail

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <CHANGELOG.md> <version>" >&2
  exit 2
fi

FILE="$1"
VERSION="$2"

[ -f "$FILE" ] || exit 0

awk -v ver="$VERSION" '
  /^## / {
    if (found) exit
    heading = $0
    sub(/^## +/, "", heading)
    sub(/[[:space:]]+$/, "", heading)
    gsub(/^\[|\]$/, "", heading)
    if (heading == ver) { found = 1 }
    next
  }
  found { buf[++n] = $0 }
  END {
    start = 1
    end = n
    while (start <= end && buf[start] ~ /^[[:space:]]*$/) start++
    while (end >= start && buf[end] ~ /^[[:space:]]*$/) end--
    for (i = start; i <= end; i++) print buf[i]
  }
' "$FILE"
