# shopclass-plugins

The plugin registry for [Shopclass](https://github.com/mindstellar/shopclass) — the package
contract, the plugins themselves, and the catalog a site's admin panel browses, installs, and
updates from.

Submissions are open, by pull request — see `CONTRIBUTING.md` for the walkthrough, what CI
checks, and what a maintainer reviews beyond that.

## What this is

A plain GitHub repository, not a server. There is no market backend, no API keys, no accounts —
every moving part is a repository, a pull request, a release asset, or a static JSON file. The
full system design lives in `docs/MARKET.md` in the core repository; the package contract every
plugin here must satisfy lives in `docs/PACKAGE-SPEC.md`.

## How a site owner browses this today

A Shopclass site's Plugins screen has a **Browse** tab, populated from this registry's catalog:
thumbnail, name, author, short description, and a compatibility badge, sorted by "Recently
updated" by default with a "Most downloaded" option. Install and Update run from there directly —
see `docs/MARKET.md` §5 and §8.2 in the core repository for the exact contract. A package's
`README.md` renders on its detail screen; reading the `plugins/` tree on GitHub works too, same as
any other repository.

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

## Catalog URLs

`catalog.yml` publishes to the `catalog` branch, served two ways:

- GitHub Pages: `https://mindstellar.github.io/shopclass-plugins/v1/*.json`
- Mirror: `https://raw.githubusercontent.com/mindstellar/shopclass-plugins/catalog/v1/*.json`

The file shapes — `updates.json`, `index.json`, `packages/<slug>.json`, `categories.json` — are
specified in `docs/MARKET.md` §5 of the core repository. `updates.json` and `packages/<slug>.json`
also carry a `downloads` figure per version and per package — GitHub's own release-asset download
count, not an install count — and `index.json` carries `updated_at`, which drives the Browse tab's
default sort. `catalog.yml` rebuilds on every release, daily, and on demand.

## Contributing

See `CONTRIBUTING.md`.

## License

GPL-3.0-or-later. See `LICENSE`.
