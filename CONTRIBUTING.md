# Contributing

> [!IMPORTANT]
> **This registry is not yet accepting submissions.** Release automation (`release.yml`) and the
> catalog build (`catalog.yml`) both exist and publish for real once core ships
> `package-ci/build-catalog.php` (`docs/MARKET.md` §7 in the core repository) — until then, a
> catalog build run is a visible, deliberate no-op rather than a failure. What's still missing is
> the other half: no core release yet reads the catalog (`docs/MARKET.md` Phase 5), so even a merged
> and released package cannot be discovered or installed by a site today. The most useful
> contribution today is an issue rather than a pull request — see `.github/ISSUE_TEMPLATE/`.

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

`package-lint.php` is the same validator the (future) CI runs, published as a release asset on the
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

## 3. What CI checks (once core's half of it has shipped)

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
`catalog.yml`, which rebuilds the catalog once core has published `package-ci/build-catalog.php`
(see the root `README.md`'s status note) — until then this step is a visible skip, not a failure.
See `docs/MARKET.md` §7 in the core repository for the full build and catalog steps.

## 5. Registering an externally-hosted package instead

If your plugin already lives in its own repository with its own release process, you do not need
to hand over your source tree. Add a single file, `external/<slug>.json`, following
`schema/external.schema.json` — slug, type, a `source` pointing at your GitHub repo, an
`asset_pattern` regex matching your release zip's filename, and the same catalog-facing metadata
(`categories`, `short_description`, and so on) an in-repo package carries in `shopclass.json`. The
(future) catalog builder resolves your releases directly, reading the real header block out of the
zip so the catalog can never drift from what you actually shipped. See `docs/MARKET.md` §3.2 for
the full shape.
