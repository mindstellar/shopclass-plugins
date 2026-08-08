# Contributing

Submissions are open — add your package and open a pull request. Automated CI (§3) checks
whether it *works*: structure, manifest, header parse, compatibility fields, `php -l` across
supported PHP versions, the PHP 8.0 floor, a dangerous-construct scan, and a real container
smoke-install. A green run is necessary but not sufficient — a maintainer still reviews whether
the package *belongs* in the registry: is it in scope for a classifieds CMS, does it duplicate an
existing package without a clear reason to exist, does it look maintained, and does it actually do
what it claims. That's a short, human judgement call, not a second technical gate — CI already
covers the technical half. Review is best-effort by a small maintainer team, so expect it to take
a few days, not minutes; there's no SLA.

The rules a package must satisfy are specified once, in `docs/PACKAGE-SPEC.md` in the core
repository. This document does not repeat them — it walks through the mechanics of submitting.
If something here disagrees with PACKAGE-SPEC, PACKAGE-SPEC wins.

## 1. Fork and add your package

Fork this repository, then add your plugin under `plugins/<slug>/`, matching the shape described in
PACKAGE-SPEC §1-§2:

```
plugins/<slug>/
  index.php          required — the header block is the source of truth for name/version
  shopclass.json      required — catalog metadata; validate against schema/package.schema.json
  README.md           recommended — long description, rendered on the detail screen
  CHANGELOG.md        recommended — newest entry must match the Version: header
  LICENSE             required — GPL-compatible, matching the SPDX id in shopclass.json
  assets/icon.svg     optional — artwork is optional; core renders a placeholder if you skip it
  .distignore         excludes dev files from the built zip
```

`plugins/sample-forms/` and `plugins/sample-widgets/` in this repository are worked examples —
copy their layout rather than starting from a blank directory.

Use `slug` as the directory name, the `shopclass.json` `slug` field, and (recommended) the `Short
Name` header — all three must agree.

## 2. Check your package locally before opening a PR

`package-lint.php` is the same validator CI runs, published as a release asset on the
core repository so the two never drift. It needs one companion file, `Compatibility.php`, which is
attached to the same release; download both into one directory and it finds them itself. Pin them to
the core version you are targeting:

```bash
CORE_VERSION=6.1.0   # the "Requires Shopclass" version you're targeting
BASE="https://github.com/mindstellar/shopclass/releases/download/${CORE_VERSION}"

curl -fsSL -O "${BASE}/package-lint.php"
curl -fsSL -O "${BASE}/Compatibility.php"

php package-lint.php --type=plugin --core=${CORE_VERSION} plugins/<slug>
```

Both files are attached from Shopclass 6.1.0 onward. Against an earlier core, clone the repository
and run `tools/package-lint.php` from inside the checkout instead.

Exit code `0` means no errors — warnings do not fail a build and are printed alongside. Add
`--json` for machine-readable output (the shape the sticky PR comment is built from).

Also validate `shopclass.json` against `schema/package.schema.json` with any JSON Schema
(draft 2020-12) validator — `ajv` (Node) or `jsonschema` (Python) both work.

## 3. What CI checks

`pr-validate.yml` runs ten gates against only the package(s) your PR touches — structure,
manifest schema, header parse, compatibility fields, `php -l` across supported versions, a
PHPCompatibility scan for the PHP 8.0 floor, a dangerous-construct security scan, a smoke install
in a real container, and a style check. The blocking-versus-warning split for each gate is
PACKAGE-SPEC §8 — read that table rather than a summary of it here, since a restated copy is
exactly the kind of thing that drifts. A finding lands as an inline annotation on your diff, and a
single **sticky** comment (edited in place on every push, never duplicated) summarises ✅ / ⚠️ / ❌
per gate. A PR can merge with warnings outstanding; it cannot merge with an error outstanding.

## 4. How a release is cut

Once your PR merges to `main`, `release.yml` detects that your package's `Version:` header changed
(against the previous commit on `main`), builds `<slug>_<version>.zip` from your package directory
(honouring `.distignore`, single top-level directory named for the slug), computes its sha256, and
creates a tag `<slug>-v<version>` with a GitHub Release named `<Name> <version>` — body taken from
your `CHANGELOG.md` section for that version. Re-running on a version that was already released is
a no-op (idempotent), and a failed build never leaves a tag behind. `release.yml` then triggers
`catalog.yml`, which rebuilds the published catalog — live within minutes at
`https://mindstellar.github.io/shopclass-plugins/v1/…` — so a released version is what a site's
Browse/Update tab sees next. See `docs/MARKET.md` §7 in the core repository for the full build and
catalog steps.

Two things about the catalog worth knowing before you ship: the browse list's default sort is
"Recently updated," driven by your newest release's publish time, not anything you set — a
package with no recent releases sinks toward the bottom regardless of quality. And each release's
`downloads` figure in the catalog is GitHub's own release-asset download count (also selectable as
a "Most downloaded" sort) — it includes CI runs, mirrors, and repeat downloads, so treat it as a
popularity signal, not an install count.

## 5. Registering an externally-hosted package instead

If your plugin already lives in its own repository with its own release process, you do not need
to hand over your source tree. Add a single file, `external/<slug>.json`, following
`schema/external.schema.json` — slug, type, a `source` pointing at your GitHub repo, an
`asset_pattern` regex matching your release zip's filename, and the same catalog-facing metadata
(`categories`, `short_description`, and so on) an in-repo package carries in `shopclass.json`. The
catalog builder resolves your releases directly, reading the real header block out of the
zip so the catalog can never drift from what you actually shipped. See `docs/MARKET.md` §3.2 for
the full shape.
