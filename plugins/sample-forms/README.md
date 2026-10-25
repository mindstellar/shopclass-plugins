# Sample Forms

A reference plugin for the Forms platform. It adds no feature for a live site. It gives plugin authors a working example to copy.

## Requirements

- Shopclass 6.1.0 or later
- PHP 8.0 or later

## Install

1. Open Plugins → Browse (or upload the zip from the GitHub release).
2. Install.
3. Activate.

There is nothing to set up. It has no admin screen, no preferences and no table.

## How it works

Read `index.php`. Each section is commented. It shows:

1. Registering a placement context. `osc_register_form_context()` gives a form embedded with `osc_render_form($formId, 'sample_forms.embed', $someId)` a readable label in Listings → Form submissions.
2. Filtering a form's fields. The `form_fields` filter runs on render and submit. The example reverses field order, only for a form with the slug `sample-reversed`.
3. Vetoing a submission. `form_submit_veto` rejects a submission by returning a message. The example rejects a few spammy words.
4. Amending validation. `form_validation_errors` adds error messages on top of core's. The example is a no-op.
5. Reacting to a stored submission. `form_submitted` fires after a submission is saved. The example logs the submission id under `OSC_DEBUG`.

## Notes / limits

- To start your own plugin: copy `index.php`, rename the `sample_forms_*` functions and the `Short Name` header, and replace each body.
- The `osc_register_form_context()` call is guarded with `function_exists()`, so it does nothing on a core without the Forms platform.

## Support

https://github.com/mindstellar/shopclass/issues

## License

GPL-3.0-or-later.
