# external/

Registrations for packages hosted in their own repository, one file per package:
`external/<slug>.json`, validated against `../schema/external.schema.json`.

No package is registered here yet — the two packages currently in this registry
(`sample-forms`, `sample-widgets`) are both in-repo, under `../plugins/`. See
`../CONTRIBUTING.md` §5 for how to add an externally-hosted registration, and
`../docs/MARKET.md` §3.2 in the core repository (`mindstellar/shopclass`) for the full
manifest shape.

This file exists only so the directory isn't empty — git does not track empty directories.
Delete this line's usefulness the moment the first real `<slug>.json` lands here.
