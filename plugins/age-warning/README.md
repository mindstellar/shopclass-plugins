# Age Warning

Shows an age-confirmation notice before a visitor sees the site, and remembers the answer
so it is asked once rather than on every page.

Intended for sites whose listings carry adult content and which are obliged to put a
notice in front of them.

## What it does

- Draws a full-screen notice over the page until the visitor confirms.
- Remembers the confirmation in a cookie, for a period you choose (1–365 days).
- Sends anyone who declines to an address you choose.
- Every string on the notice is editable from **Plugins → Age Warning → Configure**, and
  translatable.

## What it is not

It is not access control. The page is still delivered to the browser, and anyone who
wants to get past the notice can clear a cookie or read the source — which is true of
every age gate on the web. It is a good-faith notice, which is what the regulations
asking for one describe.

## How it works, and why it is built this way

The notice is drawn on **every** page and dismissed in the browser. The server does not
decide who sees it, and the HTML is byte-for-byte identical for every visitor.

That matters on any site running a response cache. If the server varied the page — by
redirecting to an interstitial, or by omitting the notice once confirmed — a shared cache
would have two different pages under one URL, and would eventually serve the interstitial
to everyone or the page to someone who never confirmed. Keeping the response invariant
means the notice costs nothing in cacheability: no `Vary` header, no extra cookie for the
cache to bypass on, no reverse-proxy configuration to keep in step.

It also fails closed. The notice is plain HTML and CSS and is visible by default; script
is what *hides* it, once it finds the confirmation cookie. A visitor with JavaScript
turned off keeps the notice rather than slipping past it.

## Settings

| Setting | Default |
|---|---|
| Notice text | "This site contains adult content. Confirm that you are of legal age…" |
| Confirm button | "I am of legal age" |
| Decline button | "Leave this site" |
| Send decliners to | `https://www.google.com/` |
| Remember for | 30 days |

Only `http://` and `https://` addresses are accepted for the decline link; anything else
falls back to the default, so a mistyped value cannot put another scheme into a link that
appears on every public page.

## Requirements

Shopclass 6.1.0 or newer, PHP 8.0 or newer.

## Theme compatibility

The notice is emitted through the `header` and `footer` theme hooks, which a public theme
is required to run — the styles go in the head, the overlay at the end of the body. A
theme that omits either one shows no notice, and that is a fault in the theme rather than
something this plugin works around.

## History

Derived from the Osclass "Age warning" plugin (1.0.2, 2013). Rewritten for Shopclass: the
session-and-redirect gate became the cache-safe overlay described above, the hardcoded
message and exit link became settings, and a debug `error_log()` call that ran on every
page load was removed. See CHANGELOG.md.

## Licence

GPL-3.0-or-later. Derived from Osclass, originally Apache-2.0; both notices are retained
in the source headers.
