<?php
/*
Plugin Name: Required Registration Fields
Plugin URI: https://github.com/mindstellar/shopclass-plugins
Description: Ask for more than a name and an email when someone registers, and require the fields you pick.
Version: 2.0.0
Author: Mindstellar Community
Author URI: https://mindstellar.com
Short Name: required-registration-fields
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
 * Extra profile fields on the registration form, with the choice of which are mandatory
 * made in the admin rather than in the source.
 *
 * Three things were wrong with the original and each is worth naming, because they are
 * the reason this is a rewrite rather than a port:
 *
 * - **It was a file to edit, not a plugin.** Which fields appeared was decided by
 *   uncommenting lines in form.php, and which were mandatory by uncommenting matching
 *   lines of JavaScript. Every edit was lost on update, and the two lists had to be kept
 *   in agreement by hand. Both are now settings.
 *
 * - **Nothing was enforced on the server.** The rules were jQuery-validate calls in the
 *   page, so a field was mandatory only for a browser that ran them and chose to comply.
 *   Validation now runs in the `user_add_flash_error` filter, which rejects the
 *   registration; the markup carries `required` as a convenience, not as the check.
 *
 * - **It could not run at all on a current install.** Saving pulled in
 *   `LIB_PATH . 'osclass/UserActions.php'`, a path that has not existed for several major
 *   versions, so registering with the plugin active was a fatal error. It also wrote back
 *   whatever `prepareData()` produced from the request, which hands the request control
 *   over every column that method touches. Only the fields configured here are written now.
 */

define('RRF_PREF_SECTION', 'required_registration_fields');

/**
 * The fields this plugin can add, in the order they are drawn.
 *
 * `column` is the user table column the value lands in, `param` the request field
 * UserForm renders. Both are fixed by core, never by configuration, which is what keeps
 * the write below bounded to columns chosen here.
 *
 * `clean` names the Sanitize method for the field's type, matching what
 * UserActions::prepareData() applies to the same column when core writes it. A single
 * general-purpose cleaner is wrong here: the string sanitiser is a slug-maker, and
 * running a phone number or a URL through it turns "+44 7700 900123" into
 * "44-7700-900123" and loses the scheme off an address.
 *
 * @return array<string,array<string,string>>
 */
function rrf_fields()
{
    return array(
        'phone_mobile' => array(
            'label'  => __('Mobile phone', 'required-registration-fields'),
            'column' => 's_phone_mobile',
            'param'  => 's_phone_mobile',
            'render' => 'mobile_text',
            'clean'  => 'phone',
        ),
        'phone_land' => array(
            'label'  => __('Landline', 'required-registration-fields'),
            'column' => 's_phone_land',
            'param'  => 's_phone_land',
            'render' => 'phone_land_text',
            'clean'  => 'phone',
        ),
        'website' => array(
            'label'  => __('Website', 'required-registration-fields'),
            'column' => 's_website',
            'param'  => 's_website',
            'render' => 'website_text',
            'clean'  => 'websiteUrl',
        ),
        'address' => array(
            'label'  => __('Address', 'required-registration-fields'),
            'column' => 's_address',
            'param'  => 's_address',
            'render' => 'address_text',
            'clean'  => 'string',
        ),
        'company' => array(
            'label'  => __('Account type (person or company)', 'required-registration-fields'),
            'column' => 'b_company',
            'param'  => 'b_company',
            'render' => 'is_company_select',
            'clean'  => 'flag',
        ),
    );
}

/**
 * How one field is configured: 'off', 'optional' or 'required'.
 *
 * @param string $key
 *
 * @return string
 */
function rrf_mode($key)
{
    $value = osc_get_preference($key, RRF_PREF_SECTION);

    return in_array($value, array('optional', 'required'), true) ? $value : 'off';
}

/** Fields that are drawn on the form at all. */
function rrf_active_fields()
{
    return array_filter(rrf_fields(), static fn ($_, $k) => rrf_mode($k) !== 'off', ARRAY_FILTER_USE_BOTH);
}

/** Fields that must be filled in. */
function rrf_required_fields()
{
    return array_filter(rrf_fields(), static fn ($_, $k) => rrf_mode($k) === 'required', ARRAY_FILTER_USE_BOTH);
}

/**
 * Nothing is added to the form until the admin asks for it, so installing the plugin
 * changes nothing on its own.
 *
 * @return void
 */
function rrf_install()
{
    foreach (array_keys(rrf_fields()) as $key) {
        osc_set_preference($key, 'off', RRF_PREF_SECTION, 'STRING');
    }
    osc_reset_preferences();
}

/**
 * Remove every preference this plugin created.
 *
 * @return void
 */
function rrf_uninstall()
{
    foreach (array_keys(rrf_fields()) as $key) {
        Preference::newInstance()->delete(
            array('s_section' => RRF_PREF_SECTION, 's_name' => $key)
        );
    }
    osc_reset_preferences();
}

/** Path of the settings screen, relative to the plugins directory. */
function rrf_settings_file()
{
    return osc_plugin_folder(__FILE__) . 'admin/settings.php';
}

/**
 * Send the plugin list's Configure link to the settings screen.
 *
 * @return void
 */
function rrf_configure()
{
    osc_redirect_to(osc_admin_render_plugin_url(rrf_settings_file()));
}

/**
 * Draw the configured fields on the registration form.
 *
 * @return void
 */
function rrf_register_form()
{
    require __DIR__ . '/public/form.php';
}

/**
 * Reject a registration that leaves a required field empty.
 *
 * Appending to the filtered error string is what stops the registration: a non-empty
 * value makes UserActions::add() return before it writes anything, having preserved what
 * was typed. This is the check — the `required` attribute on the input is only there to
 * save a round trip.
 *
 * @param string $flashError
 *
 * @return string
 */
function rrf_validate($flashError)
{
    foreach (rrf_required_fields() as $field) {
        if (trim(Params::getParamString($field['param'])) === '') {
            $flashError .= sprintf(
                __('%s is required', 'required-registration-fields'),
                $field['label']
            ) . PHP_EOL;
        }
    }

    return $flashError;
}

/**
 * Store the configured fields against the new account.
 *
 * Writes only the columns named in rrf_fields(), read one at a time from the request, so
 * the update cannot reach a column the admin did not turn on.
 *
 * @param int $userId
 *
 * @return void
 */
function rrf_save($userId)
{
    $userId = (int)$userId;
    if ($userId <= 0) {
        return;
    }

    $sanitize = new \mindstellar\utility\Sanitize();
    $set      = array();

    foreach (rrf_active_fields() as $field) {
        $value = trim(Params::getParamString($field['param']));
        if ($value === '') {
            continue;
        }
        $set[$field['column']] = $field['clean'] === 'flag'
            ? ($value === '1' ? 1 : 0)
            : $sanitize->{$field['clean']}($value);
    }

    if ($set !== array()) {
        User::newInstance()->update($set, array('pk_i_id' => $userId));
    }
}

/**
 * Persist the settings before the panel has rendered anything.
 *
 * @return void
 */
function rrf_admin_post()
{
    if (Params::getParamString('rrf_action') !== 'save') {
        return;
    }

    // State-changing admin action: PACKAGE-SPEC §9.
    osc_csrf_check();

    foreach (array_keys(rrf_fields()) as $key) {
        $mode = Params::getParamString($key);
        if (!in_array($mode, array('off', 'optional', 'required'), true)) {
            $mode = 'off';
        }
        osc_set_preference($key, $mode, RRF_PREF_SECTION, 'STRING');
    }
    osc_reset_preferences();

    osc_add_flash_ok_message(__('Settings saved', 'required-registration-fields'), 'admin');
    osc_redirect_to(osc_admin_render_plugin_url(rrf_settings_file()));
}

osc_register_plugin(osc_plugin_path(__FILE__), 'rrf_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'rrf_uninstall');
osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'rrf_configure');

osc_add_hook('init_admin', 'rrf_admin_post');
osc_add_hook('user_register_form', 'rrf_register_form');
osc_add_filter('user_add_flash_error', 'rrf_validate');
osc_add_hook('user_register_completed', 'rrf_save');
