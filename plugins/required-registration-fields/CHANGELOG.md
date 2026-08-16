# Changelog

## 2.0.0

First Shopclass release, rewritten from the Osclass plugin (1.0.6, 2013).

### Fixed

- **The plugin could not run at all.** Saving a registration pulled in
  `LIB_PATH . 'osclass/UserActions.php'`, a path that has not existed for several major
  versions, so completing a registration with the plugin active was a fatal error.
- **Nothing was enforced on the server.** The rules were jQuery-validate calls emitted
  into the page, so a field was mandatory only for a browser that ran them and chose to
  comply; anything posting the form directly ignored them. Validation now runs during
  account creation and rejects the registration, keeping what was typed.
- **The save wrote back whatever `prepareData()` produced from the request**, which hands
  the request control over every column that method touches. Only the configured fields
  are written now, read one at a time.
- The client-side rules depended on jQuery and jQuery-validate. Neither ships any more, so
  on a current install the rules silently did nothing even in the browser.
- Labels were translated under the `modern` text domain, borrowed from a theme that may
  not be installed.

### Changed

- **It is configured, not edited.** Which fields appeared was decided by uncommenting
  lines in `form.php`, and which were mandatory by uncommenting matching lines of
  JavaScript — two lists to keep in agreement by hand, both lost on update. Each field is
  now a setting: not shown, shown and optional, or shown and required.
- Installing the plugin no longer changes the registration form until a field is turned on.

### New

- Settings screen listing every available field with its three states.
- Error messages are translatable, under this plugin's own text domain.
