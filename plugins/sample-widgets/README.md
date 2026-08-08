# Sample Widgets

A reference plugin for the widget and page-template registries. It does not add a feature you
would enable on a production site — it exists so a plugin author has working, functioning examples
to copy from. Read `index.php` and `widgets/*.php`; each is commented with what it demonstrates.

## What it demonstrates

Four widget types, registered through `osc_register_widget()`:

1. **`sample_widgets.notice`** — a static text box. Proves the registry plus a declarative
   `fields` array (a single textarea, rendered as an admin form control automatically).
2. **`sample_widgets.recent_listings`** — lists the newest published listings. Proves a functional
   widget backed by live data and no configuration; see `widgets/recent-listings.php`.
3. **`sample_widgets.category_listings`** — the same list, filtered to a chosen category with a
   configurable count. Proves the `s_config` JSON round-trip: what you save in the widget's admin
   form is what `render` receives back; see `widgets/category-list.php`.
4. **`sample_widgets.embed_code`** — a raw HTML/JavaScript embed field, gated to
   `capability: 'super_admin'`. Proves that a plugin can ship an unfiltered-output widget type
   using the same capability gate as core's own embed-code widget — deliberately dangerous, and
   deliberately restricted to the one role that can already do anything.

It also registers one page-template type through `osc_register_page_template()`:
`sample_widgets.plain`, a minimal full-width layout a site owner can pick per static page. It
reuses the active theme's header/footer and the standard `osc_static_page_*()` helpers, so it is a
working example of "a plugin owns page rendering" rather than only widget rendering.

## Configuration

None as a plugin-level setting. Once enabled, the four widget types are available anywhere widgets
are placed (admin: **Appearance → Widgets**), and the page template is selectable when editing a
static page. Each widget instance's own fields (message text, listing count, category) are
configured per-placement in the widget editor, not in a plugin settings screen.

## Using this as a starting point

Copy the relevant `osc_register_widget()` or `osc_register_page_template()` block, rename the
type id and the `sample_widgets_*` functions, and replace `render` with real markup. Every registry
call is guarded with `function_exists()` so dropping this plugin onto a core version that predates
these registries degrades to a no-op instead of a fatal error.
