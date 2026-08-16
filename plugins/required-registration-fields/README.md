# Required Registration Fields

Adds profile fields to the registration form and lets you decide which are mandatory.

## Fields

Mobile phone, landline, website, address, and account type (person or company). Each is
set to **not shown**, **shown and optional**, or **shown and required**, from
Plugins → Required Registration Fields → Configure.

Nothing is added until you ask for it, so installing the plugin changes nothing on its own.

## Required means required

A field marked required is checked when the account is created. A registration that leaves
one empty is rejected and what was typed is kept, so the visitor can correct it.

The form also carries the browser's own `required` attribute, but that is a convenience to
save a round trip — it is not what enforces the rule. The original plugin enforced its
rules only in the browser, through jQuery validation, which meant anything that posted the
form directly bypassed them entirely.

## What gets stored

Only the fields you turn on, written to the matching column on the new account. The set of
columns is fixed in the plugin, so a request cannot reach a column you did not enable.

## Requirements

Shopclass 6.1.0 or newer, PHP 8.0 or newer.

## History

Derived from the Osclass "Required Fields at Registration" plugin (1.0.6, 2013), which was
a file to edit rather than a plugin to configure, validated only in the browser, and could
not run on a current install. See CHANGELOG.md.

## Licence

GPL-3.0-or-later. Derived from Osclass, originally Apache-2.0; both notices are retained
in the source headers.
