# Changelog

## 2.0.0

First Shopclass release, rewritten from the Osclass plugin (1.0.2, 2013).

### Breaking

- The confirmation is no longer kept in the PHP session, so a visitor who confirmed under
  1.0.2 is asked once more. Nothing else carries over; there were no settings to migrate.

### Changed

- **The gate no longer redirects.** It was a server-side redirect to an interstitial page,
  with the answer in the session. Both are incompatible with a response cache: the HTML
  differed between a confirmed and an unconfirmed visitor, so a shared cache could serve
  the interstitial to everyone or the page to someone who never confirmed, and touching
  the session marked the response private, which stopped the page being cached at all.
  The notice is now drawn on every page and dismissed in the browser, leaving the response
  identical for every visitor.
- The notice fails closed: it is visible as plain HTML and CSS, and script only *hides* it
  once the confirmation cookie is found. Previously a visitor without JavaScript was
  unaffected either way, because the gate was a redirect.
- The confirmation cookie is set with `SameSite=Lax`, and with `Secure` over HTTPS.

### New

- Settings screen under Plugins → Configure: notice text, both button labels, where
  decliners are sent, and how long the confirmation is remembered.
- Every string is translatable under the `age-warning` domain.
- The notice is a labelled dialog (`role="dialog"`, `aria-modal`), respects the visitor's
  dark-mode preference, and its buttons are real buttons and links rather than styled text.

### Fixed

- **Removed an open redirect.** The post-confirmation location came from the request URI
  by way of the session and was passed to `header('Location: …')` unchecked, so a request
  path beginning `//` sent the visitor to another host.
- **Removed two `error_log()` calls that ran on every page load**, one of them logging the
  requested file. On a busy site they filled the error log.
- The decline link is validated: only `http://` and `https://` are used, so a settings
  value cannot introduce another scheme into a link rendered on every public page.
- The decline link no longer points at a hardcoded third-party address for every site.
- Settings are removed on uninstall, so a reinstall does not silently inherit them.
- Dropped `confirm.php`, which contained nothing but an editor's template comment and
  existed only as a marker in the redirect flow.
