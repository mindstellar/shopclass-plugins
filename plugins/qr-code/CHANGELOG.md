# Changelog

## 2.0.0

First Shopclass release, rewritten from the Osclass plugin (1.0.3, 2013).

### Breaking

- Codes are no longer PNG files under `oc-content/uploads/qrcode/`, and the `upload_path`
  and `upload_url` settings are gone with them. Any PNGs the old version left behind can
  be deleted; nothing reads them.
- The theme-callable `show_qrcode()` is gone. The code renders through the `item_detail`
  hook instead, so no theme edit is needed.

### Changed

- **The symbol is generated in the browser and drawn as SVG.** It was rendered server-side
  to a PNG per listing by a bundled PHP encoder of 427 files — 400 of them a pre-computed
  mask cache — which required GD, accumulated a file per listing, keyed those filenames on
  a hash of the URL so every permalink change orphaned one, and needed a cleanup pass on
  item delete and on uninstall to chase them. A QR code is a pure function of a URL; none
  of that earned its keep.
- The encoder is fetched only when a visitor asks for a code, so a listing page that
  nobody scans downloads none of it.
- Printing prints the code and the address on their own, at 60 mm.

### New

- **Share** via the Web Share API and **Copy link** via the Clipboard API, each shown only
  where the browser supports it.
- The code opens in a native `<dialog>`, so the backdrop, focus handling and Escape are
  the browser's job.
- Error-correction level is a setting; it was fixed before.

### Fixed

- **Non-ASCII addresses produced an unscannable code.** Encoding wrote one byte per code
  unit, so a URL with an accented or non-Latin slug came out wrong. UTF-8 encoding is now
  used, verified against an independent decoder.
- The plugin rendered nothing at all on a theme that did not call `show_qrcode()` itself.
- Dropped the `osc_version() < 320` branch and the `getConnection()`/`commit()` calls in
  install and uninstall, none of which had meant anything for years.
