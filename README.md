# shopclass-plugins

The plugin registry for [Shopclass](https://github.com/mindstellar/osclass) — the package
contract, the plugins themselves, and (once built) the catalog that a site's admin panel browses.

> [!IMPORTANT]
> **This registry is not yet accepting submissions.** Pull-request validation runs, but there is no
> catalog and no release automation yet (`docs/MARKET.md` Phases 4 and 7 in the core repository), so
> a merged package still cannot be published or installed by anyone. Validation also expects a
> Shopclass release carrying the shared validator; until one exists, a run builds it from core's
> source instead. The most useful contribution today is an issue rather than a pull request — see
> `.github/ISSUE_TEMPLATE/`.

## What this is

A plain GitHub repository, not a server. There is no market backend, no API keys, no accounts —
every moving part is a repository, a pull request, a release asset, or a static JSON file. The
full system design lives in `docs/MARKET.md` in the core repository; the package contract every
plugin here must satisfy lives in `docs/PACKAGE-SPEC.md`.

## How a site owner browses this today

There is no Browse tab yet — that is Phase 6 of the ecosystem plan, and it depends on the catalog
(Phase 4) and the core catalog client (Phase 5), neither of which exists yet either. Until then,
"browsing" this registry means reading the `plugins/` tree on GitHub, same as any other repository.
Each package's `README.md` documents what it does and how to configure it; each has its own
`CHANGELOG.md`.

Once Phase 4 ships, this repository publishes a static catalog to a `catalog` branch (served by
GitHub Pages, mirrored on `raw.githubusercontent.com`), and once Phase 6 ships, that catalog is
what powers a **Browse** tab on the Plugins screen in the admin panel — see `docs/MARKET.md` §5 and
§8.2 in the core repository for the exact contract.

## Two hosting models

A package registered here lives in one of two shapes:

- **In-repo** — `plugins/<slug>/` contains the package's full source: `index.php`, `shopclass.json`,
  `README.md`, `LICENSE`, and so on, exactly as it deploys to `oc-content/plugins/<slug>/`. This is
  the right shape for a package with no release process of its own, or one this project maintains
  directly. See `plugins/sample-forms/` and `plugins/sample-widgets/` for worked examples.
- **Externally hosted** — `external/<slug>.json` is a one-file registration pointing at a package
  that lives in its *own* repository, with its own releases. The catalog builder resolves the
  latest matching release asset, reads the real header block out of the zip, and produces a catalog
  entry identical in shape to an in-repo package — core cannot tell the two apart. This is the right
  shape for an author who already maintains their plugin elsewhere and does not want to hand over
  their source tree. See `docs/MARKET.md` §3.2 for the manifest shape and `schema/external.schema.json`
  for its schema.

## Repository layout

```
plugins/<slug>/       in-repo packages — index.php, shopclass.json, README.md, LICENSE, ...
external/<slug>.json  registrations for packages hosted in their own repository
schema/               JSON Schema for shopclass.json, external/<slug>.json, and the category list
```

`schema/categories.json` is the fixed category vocabulary both manifest types draw from.

## Catalog URLs (planned)

Once Phase 4 ships, the catalog is published at
`raw.githubusercontent.com/mindstellar/shopclass-plugins/catalog/v1/*.json` (and mirrored on GitHub
Pages). The file shapes — `updates.json`, `index.json`, `packages/<slug>.json`, `categories.json` —
are specified in `docs/MARKET.md` §5 of the core repository. None of these files exist yet; this
section is here so the URLs are documented in one place once they do.

## Contributing

See `CONTRIBUTING.md` — and read the banner above first.

## License

GPL-3.0-or-later. See `LICENSE`.
