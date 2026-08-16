# Registered Users Only

Closes the site to visitors who are not signed in, and lets you choose what stays public.

## What it does

A signed-out visitor asking for a closed page is sent to the registration page with a
message explaining why. Which pages are closed is up to you.

## Always public

These cannot be closed, because closing them locks out the people the plugin exists to
let in — or simply breaks the route:

- signing in, registering, recovering a password, activating an account, the error page
- the sitemap, the feeds, the ajax endpoints and the cron entry point

## Your choice

| Area | Default |
|---|---|
| Static pages — terms, privacy notice, imprint | open |
| Contact form | open |
| Browsing and search | closed |
| Individual listings | closed |

Static pages default to open because that is where the terms and the privacy notice live,
and those usually have to be readable without an account.

## A note on search engines

Closing browsing and listings hides them from search engines too — a crawler is a
signed-out visitor. That is normally the point of a members-only site, but it is worth
knowing before switching it on for a site that relies on organic traffic.

## Requirements

Shopclass 6.1.0 or newer, PHP 8.0 or newer.

## History

Derived from the Osclass "Registered users only" plugin (0.9.3, 2013), which let through
only the login and registration pages — so password recovery and account activation were
unreachable and a locked-out user could never get back in. See CHANGELOG.md.

## Licence

GPL-3.0-or-later. Derived from Osclass, originally Apache-2.0; both notices are retained
in the source headers.
