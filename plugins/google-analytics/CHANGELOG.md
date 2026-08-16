# Changelog

## 1.0.0

First release. Restores, as a plugin, the analytics core carried until 6.2.0 — and adds
what a plain snippet does not.

### New

- Google Analytics 4 through `gtag.js`, configured from the admin.
- **Consent Mode**, initialised denied before the library loads, with
  `oscGoogleAnalytics.grant()` / `.deny()` for a banner to call. Ordering matters: a
  default written after gtag.js has started arrives too late, and the first page view is
  already recorded with storage allowed.
- A per-browser opt-out, set from the settings screen, so your own visits stay out of the
  numbers. Per-browser because an admin session cannot be seen from the public side of the
  site — and because deciding it in the browser leaves the page cacheable.
- The tracking ID core used to hold is adopted on install, so an upgraded site keeps
  working without retyping it.
- `google_analytics_should_track` filter for suppressing the tag per request.

### Notes

- The measurement ID is checked against the shapes Google issues before it is written into
  a page.
- Output does not vary between tracked visitors, so the response cache is unaffected.
