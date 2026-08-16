<?php
/*
Plugin Name: QR Code
Plugin URI: https://github.com/mindstellar/shopclass-plugins
Description: Show a QR code for a listing so it can be scanned, shared or printed.
Version: 2.0.0
Author: Mindstellar Community
Author URI: https://mindstellar.com
Short Name: qr-code
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
 * The code is drawn in the browser, and nothing is written to disk.
 *
 * The original carried a PHP encoder — 427 files, most of them a pre-computed mask cache —
 * rendered each listing's symbol to a PNG under oc-content/uploads/qrcode/, and served
 * that file. Which meant: a GD dependency, a file per listing accumulating in the uploads
 * directory, filenames keyed on a hash of the URL so every permalink change orphaned one,
 * and a cleanup pass on delete and uninstall to chase them. None of it earned its keep for
 * an image that is a pure function of a URL.
 *
 * Now the symbol is produced by an encoder the browser fetches only when a visitor asks
 * for the code, and drawn as inline SVG. There is nothing to store, nothing to clean up,
 * no GD, and the result is sharp at any size — which matters for the one thing this is
 * for, holding a phone up to a screen or to a printed page.
 *
 * The original also never registered a display hook: it defined show_qrcode() and left it
 * to the theme to call, so on a theme that did not know about the plugin it did nothing at
 * all. It renders through `item_detail` now, which both bundled themes emit.
 */

define('QRC_PREF_SECTION', 'qr_code');

/** Error-correction levels the encoder accepts, lowest to highest redundancy. */
define('QRC_LEVELS', 'L,M,Q,H');

/**
 * Configured error-correction level.
 *
 * M is the default the format itself is usually generated at: it survives a scuffed print
 * or an awkward angle while keeping the symbol sparse enough to scan quickly. H costs
 * roughly a third more modules for damage tolerance most listings never need.
 *
 * @return string one of L, M, Q, H
 */
function qrc_ecc()
{
    $value = strtoupper((string)osc_get_preference('ecc', QRC_PREF_SECTION));

    return in_array($value, explode(',', QRC_LEVELS), true) ? $value : 'M';
}

/**
 * @return void
 */
function qrc_install()
{
    osc_set_preference('ecc', 'M', QRC_PREF_SECTION, 'STRING');
    osc_reset_preferences();
}

/**
 * Remove every preference this plugin created. There are no files to remove — that is the
 * point of drawing the symbol in the browser.
 *
 * @return void
 */
function qrc_uninstall()
{
    Preference::newInstance()->delete(
        array('s_section' => QRC_PREF_SECTION, 's_name' => 'ecc')
    );
    osc_reset_preferences();
}

/** Path of the settings screen, relative to the plugins directory. */
function qrc_settings_file()
{
    return osc_plugin_folder(__FILE__) . 'admin/settings.php';
}

/**
 * @return void
 */
function qrc_configure()
{
    osc_redirect_to(osc_admin_render_plugin_url(qrc_settings_file()));
}

/**
 * Styles and the module, emitted once in the theme's head.
 *
 * The module is deferred and the encoder behind it is not fetched at all until the code
 * is asked for, so a listing page that nobody scans pays for neither.
 *
 * @return void
 */
function qrc_head()
{
    if ($GLOBALS['qrc_head_done'] ?? false) {
        return;
    }
    $GLOBALS['qrc_head_done'] = true;

    require __DIR__ . '/public/head.php';
}

/**
 * Draw the trigger and its dialog on a listing.
 *
 * @return void
 */
function qrc_item_detail()
{
    if (!function_exists('osc_item_id') || !osc_item_id()) {
        return;
    }

    require __DIR__ . '/public/qr.php';
}

/**
 * Persist the setting, before the panel has rendered anything — the settings screen is
 * required after the admin header is printed, so a redirect from there would be discarded.
 *
 * @return void
 */
function qrc_admin_post()
{
    if (Params::getParamString('qrc_action') !== 'save') {
        return;
    }

    // State-changing admin action: PACKAGE-SPEC §9.
    osc_csrf_check();

    $ecc = strtoupper(Params::getParamString('ecc'));
    if (!in_array($ecc, explode(',', QRC_LEVELS), true)) {
        $ecc = 'M';
    }
    osc_set_preference('ecc', $ecc, QRC_PREF_SECTION, 'STRING');
    osc_reset_preferences();

    osc_add_flash_ok_message(__('Settings saved', 'qr-code'), 'admin');
    osc_redirect_to(osc_admin_render_plugin_url(qrc_settings_file()));
}

osc_register_plugin(osc_plugin_path(__FILE__), 'qrc_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'qrc_uninstall');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'qrc_configure');

osc_add_hook('init_admin', 'qrc_admin_post');
osc_add_hook('header', 'qrc_head');
osc_add_hook('item_detail', 'qrc_item_detail');
