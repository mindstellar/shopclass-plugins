<?php
/*
Plugin Name: Google Analytics
Plugin URI: https://github.com/mindstellar/shopclass-plugins
Description: Add Google Analytics 4 to your site, with consent gating and a per-browser opt-out.
Version: 1.0.0
Author: Mindstellar Community
Author URI: https://mindstellar.com
Short Name: google-analytics
Requires Shopclass: 6.1.0
Tested up to: 6.2
Requires PHP: 8.0
Support URI: https://github.com/mindstellar/shopclass-plugins/issues
*/

/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2021-2026 Mindstellar Community
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Google Analytics, as a plugin rather than a field in core.
 *
 * Core carried a tracking-id setting and emitted the script for everyone until 6.2.0,
 * when it was taken out: analytics is one vendor's product among many, and every site
 * carried the code for a service most of them do not use. This is where it lives now.
 *
 * Three things it does that a pasted snippet does not:
 *
 *  - **Consent first.** Consent Mode is initialised with storage denied, before the
 *    library loads, so nothing is written and no identifier is set until the visitor
 *    agrees. Whatever asks them — a banner plugin, a theme — calls one function to grant.
 *  - **You can keep your own visits out.** One click in the settings marks that browser,
 *    and it is then never counted — which is the only way this can work, because an admin
 *    session is not visible from the public side of the site.
 *  - **It does not break the response cache.** Every visitor gets identical bytes and the
 *    script decides what to do, so a shared cache still has one page per URL.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

define('GA_PREF_SECTION', 'google_analytics');

/** Cookie a browser carries to stay out of the numbers. */
define('GA_OPTOUT_COOKIE', 'ga_optout');

/**
 * Defaults, in one place so install and the settings screen cannot disagree.
 *
 * @return array<string,string>
 */
function ga_defaults()
{
    return array(
        'measurement_id'  => '',
        'require_consent' => '1',
        'anonymize_ip'    => '1',
    );
}

/**
 * @param string $key
 *
 * @return string
 */
function ga_setting($key)
{
    $value = osc_get_preference($key, GA_PREF_SECTION);
    if ($value === null || $value === '') {
        $defaults = ga_defaults();

        return $defaults[$key] ?? '';
    }

    return (string)$value;
}

/**
 * The configured measurement id, or '' when it is unset or not a shape Google issues.
 *
 * Validated rather than trusted: the value is written into a script on every page, and
 * an id is a short token from a known set of prefixes — nothing else belongs there.
 *
 * @return string
 */
function ga_measurement_id()
{
    $id = strtoupper(trim(ga_setting('measurement_id')));

    return preg_match('/^(G|GT|UA|AW)-[A-Z0-9-]{4,24}$/', $id) ? $id : '';
}

/**
 * Whether this particular response should carry the tag.
 *
 * @return bool
 */
function ga_should_track()
{
    if (ga_measurement_id() === '') {
        return false;
    }

    // Deliberately no check for staff here. An admin session is not visible from the
    // front end, so the server cannot tell — and even if it could, leaving the tag out for
    // some visitors would make the page differ between them and a shared cache would serve
    // the wrong copy. Excluding a browser is the opt-out cookie's job, in public/head.php.
    return (bool)osc_apply_filter('google_analytics_should_track', true);
}

/**
 * Adopt the tracking id core used to hold, so an upgraded site keeps working.
 *
 * Core's `ga_tracking_id` preference outlived the feature — `osc_google_analytics_id()`
 * still reads it, deprecated — so a site that had analytics configured before 6.2.0 has
 * the value sitting there. Taking it over means installing this plugin restores what was
 * lost rather than asking for it again.
 *
 * @return void
 */
function ga_install()
{
    foreach (ga_defaults() as $key => $value) {
        osc_set_preference($key, $value, GA_PREF_SECTION, 'STRING');
    }

    $inherited = trim((string)osc_get_preference('ga_tracking_id'));
    if ($inherited !== '') {
        osc_set_preference('measurement_id', $inherited, GA_PREF_SECTION, 'STRING');
    }

    osc_reset_preferences();
}

/**
 * @return void
 */
function ga_uninstall()
{
    // The whole section, not a list of the keys this version happens to know about: a
    // setting dropped by some later version would otherwise be left behind for good,
    // with nothing that ever looks at it again.
    Preference::newInstance()->delete(array('s_section' => GA_PREF_SECTION));
    osc_reset_preferences();
}

/** Path of the settings screen, relative to the plugins directory. */
function ga_settings_file()
{
    return osc_plugin_folder(__FILE__) . 'admin/settings.php';
}

/**
 * @return void
 */
function ga_configure()
{
    osc_redirect_to(osc_admin_render_plugin_url(ga_settings_file()));
}

/**
 * Emit the tag, once, in the theme's head.
 *
 * @return void
 */
function ga_head()
{
    if ($GLOBALS['ga_head_done'] ?? false) {
        return;
    }
    $GLOBALS['ga_head_done'] = true;

    if (!ga_should_track()) {
        return;
    }

    require __DIR__ . '/public/head.php';
}

/**
 * Persist the settings, before the panel has rendered anything.
 *
 * @return void
 */
function ga_admin_post()
{
    if (Params::getParamString('ga_action') !== 'save') {
        return;
    }

    // State-changing admin action: PACKAGE-SPEC §9.
    osc_csrf_check();

    osc_set_preference('measurement_id', trim(Params::getParamString('measurement_id')), GA_PREF_SECTION, 'STRING');
    foreach (array('require_consent', 'anonymize_ip') as $flag) {
        osc_set_preference($flag, Params::getParam($flag) !== '' ? '1' : '0', GA_PREF_SECTION, 'STRING');
    }
    osc_reset_preferences();

    osc_add_flash_ok_message(__('Settings saved', 'google-analytics'), 'admin');
    osc_redirect_to(osc_admin_render_plugin_url(ga_settings_file()));
}

osc_register_plugin(osc_plugin_path(__FILE__), 'ga_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'ga_uninstall');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'ga_configure');

osc_add_hook('init_admin', 'ga_admin_post');
osc_add_hook('header', 'ga_head');
