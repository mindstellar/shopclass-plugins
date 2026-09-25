# Age Warning

Shows an age-confirmation notice before a visitor sees the site, and remembers the answer
so it is asked once rather than on every page.

Intended for sites whose listings carry adult content and which are obliged to put a
notice in front of them.

## What it does

- Draws a full-screen notice over the page until the visitor confirms.
- Remembers the confirmation in a cookie, for a period you choose (1–365 days).
- Sends anyone who declines to an address you choose.
- Every string on the notice is editable, and translatable: **Plugins → Manage plugins**,
  then **Settings** next to Age Warning.

## What it is not

It is not access control. The page is still delivered to the browser, and anyone who
wants to get past the notice can clear a cookie or read the source — which is true of
every age gate on the web. It is a good-faith notice, which is what the regulations
asking for one describe.

## How it works

The notice is on every page and is hidden in the browser once confirmed, so it works with
a page cache. With JavaScript off, the notice stays.

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

Needs the theme's `header` and `footer` hooks. A theme missing either shows no notice.

## History

Derived from the Osclass "Age warning" plugin (1.0.2, 2013). See CHANGELOG.md.

## Licence

GPL-3.0-or-later. Derived from Osclass, originally Apache-2.0; both notices are retained
in the source headers.
