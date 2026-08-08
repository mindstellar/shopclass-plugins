<!--
Automated validation runs on this pull request and reports back as a single comment it keeps
updated. It cannot publish anything yet — there is no catalog (see the banner in README.md), so a
merged package is not installable. The checklist below mirrors the gates CI enforces
(docs/PACKAGE-SPEC.md §8 in the core repository); ticking a box is your attestation, not a
guarantee, but it tells the reviewer where to look first.
-->

## Package

Which package does this PR touch? `plugins/<slug>` or `external/<slug>.json`:

## Checklist

- [ ] Directory/file name equals the `slug` in `shopclass.json` (or `external/<slug>.json`)
- [ ] `shopclass.json` (or `external/<slug>.json`) validates against its schema in `schema/`
- [ ] `index.php`'s header block declares `Plugin Name`, `Description`, `Version`, `Author`, and is
      the *first* comment in the file (no field name appears earlier in the file — see
      PACKAGE-SPEC §3.1 on header hijacking)
- [ ] `Version:` is greater than the previously released version and matches the newest
      `CHANGELOG.md` entry
- [ ] `Requires Shopclass` / `Tested up to` / `Requires PHP` are set, or deliberately omitted
      (omitting is valid — it just installs as "compatibility not declared")
- [ ] `php -l` passes, and nothing in the diff requires PHP below 8.0
- [ ] No `eval()`, `assert()` on a string, `system()`/`exec()`/`shell_exec()`/`passthru()`/
      `proc_open()`, or `base64_decode()` of a long literal (vendored dependencies are exempt —
      see PACKAGE-SPEC §9)
- [ ] State-changing admin actions call `osc_csrf_check()`; request data goes through `Params::` and
      is sanitised on input, escaped on output
- [ ] I ran `tools/package-lint.php` locally against this package and it exited `0` (see
      CONTRIBUTING.md §2 for the exact command)
- [ ] `LICENSE` is present and GPL-compatible, matching the SPDX id in `shopclass.json`

## What does this package do?

<!-- One or two sentences. Long-form description belongs in the package's own README.md. -->

## Anything a reviewer should know?

<!-- e.g. a deliberate compatibility warning, data the plugin intentionally retains on uninstall, etc. -->
