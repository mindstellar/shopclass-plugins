# Changelog

## 2.0.0

First Shopclass release, rewritten from the Osclass plugin (0.9.3, 2013).

### Fixed

- **Password recovery and account activation were unreachable.** Only the login and
  registration pages were let through, so a signed-out visitor following a reset link or
  an activation email was redirected to the registration form instead, with no way back
  into their account. The whole sign-in flow is now permanently open.
- **The sitemap, the feeds and the ajax endpoints were redirected too.** Sending a crawler
  or an XHR to the registration page does not gate anything, it breaks the route. They are
  now exempt.
- The guard function was called `login_necessary()`, an unprefixed name in the global
  namespace. Everything this plugin defines is now prefixed.

### New

- Settings screen: static pages, the contact form, browsing and search, and individual
  listings can each be left open. Static pages and the contact form default to open,
  because the terms and privacy notice usually have to be readable without an account.
- The always-public set is filterable through `registered_users_only_always_public`.
