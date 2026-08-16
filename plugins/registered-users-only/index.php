<?php
/*
Plugin Name: Registered Users Only
Plugin URI: https://github.com/mindstellar/shopclass-plugins
Description: Require a visitor to sign in before they can see the site, with control over what stays public.
Version: 2.0.0
Author: Mindstellar Community
Author URI: https://mindstellar.com
Short Name: registered-users-only
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
 * A signed-out visitor is sent to the registration page, except on the pages that have to
 * stay reachable for signing in to be possible at all.
 *
 * The original let through exactly two: login and register. Everything else redirected,
 * which took the password-reset flow, the email-activation link and the error page with
 * it — so a visitor who forgot their password was bounced from the recovery form to the
 * registration form and could never get back in. Those pages are now in a set that cannot
 * be switched off, because closing them locks out the very people the plugin is meant to
 * let in.
 *
 * The machine-facing routes are exempt for a different reason: redirecting the sitemap,
 * the feeds or an ajax endpoint does not gate anything, it just breaks them.
 */

define('RUO_PREF_SECTION', 'registered_users_only');

/**
 * Pages that stay public no matter how the plugin is configured.
 *
 * Two groups: everything the sign-in, registration and account-recovery flow needs, and
 * the routes that are consumed by software rather than read by a person.
 *
 * @return string[]
 */
function ruo_always_public()
{
    return osc_apply_filter('registered_users_only_always_public', array(
        // getting in
        'login', 'register', 'recover', 'forgot', 'change', 'user', 'error',
        // consumed by software; a redirect here breaks rather than gates
        'ajax', 'sitemap', 'feed', 'cron',
    ));
}

/**
 * Pages the site owner chooses to leave open, with the default for each.
 *
 * Static pages default to open because that is where the terms, the privacy notice and
 * the imprint live, and those are usually required to be readable without an account.
 *
 * @return array<string,int>
 */
function ruo_optional_defaults()
{
    return array(
        'allow_page'    => 1, // static pages: terms, privacy, imprint
        'allow_contact' => 1, // the contact form
        'allow_search'  => 0, // browsing and search results
        'allow_item'    => 0, // individual listings
    );
}

/**
 * Map an optional setting to the page it governs.
 *
 * @return array<string,string>
 */
function ruo_optional_pages()
{
    return array(
        'allow_page'    => 'page',
        'allow_contact' => 'contact',
        'allow_search'  => 'search',
        'allow_item'    => 'item',
    );
}

/**
 * Whether one optional area is open.
 *
 * @param string $key
 *
 * @return bool
 */
function ruo_setting($key)
{
    $defaults = ruo_optional_defaults();
    $value    = osc_get_preference($key, RUO_PREF_SECTION);

    if ($value === null || $value === '') {
        return !empty($defaults[$key]);
    }

    return (int)$value === 1;
}

/**
 * Seed the defaults so the settings screen opens populated.
 *
 * @return void
 */
function ruo_install()
{
    foreach (ruo_optional_defaults() as $key => $value) {
        osc_set_preference($key, (string)$value, RUO_PREF_SECTION, 'INTEGER');
    }
    osc_reset_preferences();
}

/**
 * Remove every preference this plugin created.
 *
 * @return void
 */
function ruo_uninstall()
{
    foreach (array_keys(ruo_optional_defaults()) as $key) {
        Preference::newInstance()->delete(
            array('s_section' => RUO_PREF_SECTION, 's_name' => $key)
        );
    }
    osc_reset_preferences();
}

/** Path of the settings screen, relative to the plugins directory. */
function ruo_settings_file()
{
    return osc_plugin_folder(__FILE__) . 'admin/settings.php';
}

/**
 * Send the plugin list's Configure link to the settings screen. Redirecting rather than
 * requiring the file is what wraps it in the panel's own header and footer.
 *
 * @return void
 */
function ruo_configure()
{
    osc_redirect_to(osc_admin_render_plugin_url(ruo_settings_file()));
}

/**
 * Whether the page being requested is readable without an account.
 *
 * @param string $page the resolved `page` parameter
 *
 * @return bool
 */
function ruo_is_public($page)
{
    // An empty page is the front page, which is the main thing being closed — so it is
    // not a special case, and an unrecognised one is closed too. For a plugin whose whole
    // job is to shut the door, the safe direction when in doubt is shut.
    if ($page !== '' && in_array($page, ruo_always_public(), true)) {
        return true;
    }

    foreach (ruo_optional_pages() as $key => $governed) {
        if ($page === $governed) {
            return ruo_setting($key);
        }
    }

    return false;
}

/**
 * Send a signed-out visitor to the registration page.
 *
 * Runs on before_html, which fires before the theme has produced any output, so the
 * redirect header can still be sent.
 *
 * @return void
 */
function ruo_guard()
{
    if (osc_is_web_user_logged_in()) {
        return;
    }

    $page = (string)Rewrite::newInstance()->get_location();
    if ($page === '') {
        $page = Params::getParamString('page');
    }

    if (ruo_is_public($page)) {
        return;
    }

    osc_add_flash_info_message(
        __('Only registered users can browse this site. Please sign in or create an account.', 'registered-users-only')
    );
    osc_redirect_to(osc_register_account_url());
}

/**
 * Persist the settings, before the panel has rendered anything — the settings screen is
 * required after the admin header is printed, so a redirect from there would be discarded.
 *
 * @return void
 */
function ruo_admin_post()
{
    if (Params::getParamString('ruo_action') !== 'save') {
        return;
    }

    // State-changing admin action: PACKAGE-SPEC §9.
    osc_csrf_check();

    foreach (array_keys(ruo_optional_defaults()) as $key) {
        // An unticked checkbox is absent from the request rather than sent as 0.
        osc_set_preference($key, Params::getParam($key) !== '' ? '1' : '0', RUO_PREF_SECTION, 'INTEGER');
    }
    osc_reset_preferences();

    osc_add_flash_ok_message(__('Settings saved', 'registered-users-only'), 'admin');
    osc_redirect_to(osc_admin_render_plugin_url(ruo_settings_file()));
}

osc_register_plugin(osc_plugin_path(__FILE__), 'ruo_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'ruo_uninstall');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'ruo_configure');

osc_add_hook('init_admin', 'ruo_admin_post');
osc_add_hook('before_html', 'ruo_guard');
