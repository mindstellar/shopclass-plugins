<?php
/*
Plugin Name: Age Warning
Plugin URI: https://github.com/mindstellar/shopclass-plugins
Description: Show an age-confirmation notice before a visitor sees adult content, and remember the answer.
Version: 2.0.0
Author: Mindstellar Community
Author URI: https://mindstellar.com
Short Name: age-warning
Requires Shopclass: 6.1.0
Tested up to: 6.2
Requires PHP: 8.0
Support URI: https://github.com/mindstellar/shopclass-plugins/issues
*/

/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2013 Osclass (original work, licensed under the Apache License 2.0)
 * Copyright (c) 2021-2026 Mindstellar Community
 *
 * Distributed under the GNU General Public License v3.0 or later. The original
 * Osclass code it derives from was licensed under the Apache License 2.0.
 * See LICENSE (GPL-3.0) and LICENSE-APACHE (Apache-2.0).
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * The gate is drawn on every page and dismissed in the browser, rather than being a
 * redirect the server decides on.
 *
 * The original plugin redirected an unconfirmed visitor to an interstitial and kept the
 * answer in the session. On a site behind the response cache that is unworkable twice
 * over: a public page's HTML would differ between a confirmed and an unconfirmed visitor,
 * so a shared cache could serve the interstitial to everyone or the page to someone who
 * never confirmed; and touching the session marks the response private, which stops the
 * page being cached at all. Neither is visible in development, where nothing is cached.
 *
 * So the markup is identical for every visitor, and only the browser decides whether to
 * show it. The response never varies, no cookie needs adding to
 * osc_cache_relevant_cookies(), and no reverse-proxy configuration changes.
 *
 * It fails closed: the overlay is visible as plain HTML and CSS, and script is what
 * *hides* it once the cookie is found. A visitor with no JavaScript keeps the notice.
 *
 * What this is not: access control. Anyone can clear a cookie or read the HTML, exactly
 * as with every age gate on the web. It is a good-faith notice, which is what the law it
 * exists for asks for.
 */

define('AGE_WARNING_PREF_SECTION', 'age_warning');

/** Cookie the browser sets once the visitor confirms. Deliberately not a session. */
define('AGE_WARNING_COOKIE', 'agew_ok');

/**
 * Defaults, applied when the plugin has never been configured. Kept in one place so the
 * settings screen, the renderer and the installer cannot disagree.
 *
 * @return array<string,string>
 */
function age_warning_defaults()
{
    return array(
        'message'      => __('This site contains adult content. Confirm that you are of legal age in your country to continue.', 'age-warning'),
        'accept_label' => __('I am of legal age', 'age-warning'),
        'decline_label' => __('Leave this site', 'age-warning'),
        'decline_url'  => 'https://www.google.com/',
        'remember_days' => '30',
    );
}

/**
 * Read one setting, falling back to its default when unset.
 *
 * @param string $key
 *
 * @return string
 */
function age_warning_setting($key)
{
    $defaults = age_warning_defaults();
    $value    = osc_get_preference($key, AGE_WARNING_PREF_SECTION);

    if ($value === null || $value === '') {
        return isset($defaults[$key]) ? $defaults[$key] : '';
    }

    return (string)$value;
}

/**
 * Seed the defaults so the settings screen opens populated rather than empty.
 *
 * @return void
 */
function age_warning_install()
{
    foreach (age_warning_defaults() as $key => $value) {
        osc_set_preference($key, $value, AGE_WARNING_PREF_SECTION, 'STRING');
    }
    osc_reset_preferences();
}

/**
 * Remove every preference this plugin created. An uninstall that leaves rows behind is
 * how a reinstall inherits settings the admin thought they had cleared.
 *
 * @return void
 */
function age_warning_uninstall()
{
    foreach (array_keys(age_warning_defaults()) as $key) {
        Preference::newInstance()->delete(
            array('s_section' => AGE_WARNING_PREF_SECTION, 's_name' => $key)
        );
    }
    osc_reset_preferences();
}

/** Path of the settings screen, relative to the plugins directory. */
function age_warning_settings_file()
{
    return osc_plugin_folder(__FILE__) . 'admin/settings.php';
}

/**
 * Send the plugin list's Configure link to the settings screen.
 *
 * Redirecting to the render-plugin URL rather than requiring the file here is what gets
 * the admin chrome: that route renders the file inside the panel's own header and footer,
 * whereas this hook fires with no view around it and would print a bare form.
 *
 * @return void
 */
function age_warning_configure()
{
    osc_redirect_to(osc_admin_render_plugin_url(age_warning_settings_file()));
}

/**
 * Where the visitor is sent on decline. Only http(s) is allowed through: the value reaches
 * a browser as a link target, and permitting an arbitrary scheme here would turn a
 * settings field into a javascript: payload rendered on every public page.
 *
 * @return string
 */
function age_warning_decline_url()
{
    $url      = trim(age_warning_setting('decline_url'));
    $defaults = age_warning_defaults();

    if ($url === '') {
        return $defaults['decline_url'];
    }

    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return $defaults['decline_url'];
    }

    return $url;
}

/**
 * How long the confirmation is remembered, in days. Clamped so a typo cannot produce a
 * cookie that expires immediately or effectively never.
 *
 * @return int
 */
function age_warning_remember_days()
{
    $days = (int)age_warning_setting('remember_days');

    return max(1, min(365, $days));
}

/**
 * Emit the styles and the early script that hides the notice for a visitor who has already
 * confirmed. Runs in the theme's head so the check happens before the overlay would paint.
 *
 * Guarded against running twice: a theme that emits the hook from more than one template
 * would otherwise repeat the block, and with it the element ids it carries.
 *
 * @return void
 */
function age_warning_head()
{
    if ($GLOBALS['age_warning_head_done'] ?? false) {
        return;
    }
    $GLOBALS['age_warning_head_done'] = true;

    require __DIR__ . '/public/head.php';
}

/**
 * Emit the overlay itself, at the end of the document. Guarded for the same reason as
 * {@see age_warning_head()}.
 *
 * @return void
 */
function age_warning_body()
{
    if ($GLOBALS['age_warning_body_done'] ?? false) {
        return;
    }
    $GLOBALS['age_warning_body_done'] = true;

    require __DIR__ . '/public/overlay.php';
}

/**
 * Handle the settings POST, before the panel has rendered anything.
 *
 * It cannot be done from the settings screen itself: that file is required by the
 * render-plugin view *after* the admin header has been printed, so by then headers are
 * sent, the Location header is discarded and the exit inside the redirect helper leaves a
 * half-drawn page. Running here — on init_admin, before any output — is what makes the
 * redirect-after-post work, so a refresh cannot save twice.
 *
 * @return void
 */
function age_warning_admin_post()
{
    if (Params::getParamString('agew_action') !== 'save') {
        return;
    }

    // State-changing admin action: PACKAGE-SPEC §9.
    osc_csrf_check();

    $days = Params::getParamInt('remember_days');
    $days = max(1, min(365, $days ?: (int)age_warning_defaults()['remember_days']));

    osc_set_preference('message', Params::getParamString('message'), AGE_WARNING_PREF_SECTION, 'STRING');
    osc_set_preference('accept_label', Params::getParamString('accept_label'), AGE_WARNING_PREF_SECTION, 'STRING');
    osc_set_preference('decline_label', Params::getParamString('decline_label'), AGE_WARNING_PREF_SECTION, 'STRING');
    osc_set_preference('decline_url', Params::getParamString('decline_url'), AGE_WARNING_PREF_SECTION, 'STRING');
    osc_set_preference('remember_days', (string)$days, AGE_WARNING_PREF_SECTION, 'STRING');
    osc_reset_preferences();

    osc_add_flash_ok_message(__('Settings saved', 'age-warning'), 'admin');
    osc_redirect_to(osc_admin_render_plugin_url(age_warning_settings_file()));
}

osc_register_plugin(osc_plugin_path(__FILE__), 'age_warning_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'age_warning_uninstall');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'age_warning_configure');

osc_add_hook('init_admin', 'age_warning_admin_post');

// `header` and `footer` are part of the theme contract, so a public theme emits both.
osc_add_hook('header', 'age_warning_head');
osc_add_hook('footer', 'age_warning_body');
