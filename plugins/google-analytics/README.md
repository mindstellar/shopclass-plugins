# Google Analytics

Adds Google Analytics 4 to your site. Core carried this as a built-in field until 6.2.0,
when it was removed — analytics is one vendor's product among many, and every site was
shipping the code for a service most of them do not use. This is where it lives now.

## Upgrading from 6.1.0 or earlier

Installing the plugin picks up the tracking ID core already had, so a site that had
analytics configured keeps working without retyping anything.

## Consent

Consent Mode is initialised with storage **denied**, before the library loads. Until
something grants consent, gtag.js sets no cookie and no identifier — it queues what it
would have sent and delivers it only if consent arrives during the same page view.

Whatever asks the visitor calls:

```js
oscGoogleAnalytics.grant();  // they agreed
oscGoogleAnalytics.deny();   // they refused, or changed their mind
```

Remembering the answer is the banner's job, not this plugin's — it is the thing that knows
what was asked and under which policy. Turn the setting off if another tool already manages
consent for the whole page.

## Keeping your own visits out

The settings screen has a button that marks **this browser** as not counted, and the tag
then does nothing there.

It is per-browser rather than per-account on purpose: an admin session is not visible from
the public side of the site, so the server has no way to recognise staff there. Deciding it
in the browser is also what keeps the page cacheable — omitting the tag from some responses
would give a shared cache two versions of one URL.

## Where it loads

The public side of the site only. Nothing is emitted anywhere in the admin panel.

## Caching

What is emitted is byte-identical for every visitor who is counted, so a page still has one
version per URL and the response cache is unaffected.

## Settings

Plugins → Google Analytics → Configure: the measurement ID, the two switches above, and the opt-out button.
An ID that is not a shape Google issues (`G-`, `GT-`, `UA-`, `AW-`) is refused and the
screen says so, rather than writing it into a script tag on every page.

## Extending

`google_analytics_should_track` — return false to suppress the tag for a particular
request.

## Requirements

Shopclass 6.1.0 or newer, PHP 8.0 or newer.

## Licence

GPL-3.0-or-later.
