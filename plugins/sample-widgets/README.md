# Sample Widgets

A reference plugin for the widget and page-template registries. It adds no feature for a live site. It gives plugin authors working examples to copy.

## Requirements

- Shopclass 6.1.0 or later
- PHP 8.0 or later

## Install

1. Open Plugins → Browse (or upload the zip from the GitHub release).
2. Install.
3. Activate.

## Set up

No plugin settings. Once active, the widget types can be placed in Appearance → Widgets. Each widget's own fields are set in the widget editor. The page template can be picked when editing a static page.

## How it works

Read `index.php` and `widgets/*.php`. Four widget types use `osc_register_widget()`:

1. `sample_widgets.notice` ("Sample: Notice box"): a static text box with one textarea field.
2. `sample_widgets.recent_listings` ("Sample: Recent listings"): the newest published listings, no configuration. See `widgets/recent-listings.php`.
3. `sample_widgets.category_listings` ("Sample: Category listings"): the same list for a chosen Category and Number of listings. It shows the `s_config` JSON round-trip. See `widgets/category-list.php`.
4. `sample_widgets.embed_code` ("Sample: Embed code (raw)"): a raw HTML/JavaScript field, limited to `capability: 'super_admin'`.

One page template uses `osc_register_page_template()`: `sample_widgets.plain` ("Sample: Plain page (full width)"). It reuses the theme's header and footer and the `osc_static_page_*()` helpers.

## Notes / limits

- To start your own plugin: copy a registration block, rename the type id and the `sample_widgets_*` functions, and replace `render` with your markup.
- Registry calls are guarded with `function_exists()`, so on an older core the plugin does nothing.
- The embed widget outputs unfiltered code. Keep it limited to super admins.

## Support

https://github.com/mindstellar/shopclass/issues

## License

GPL-3.0-or-later.
