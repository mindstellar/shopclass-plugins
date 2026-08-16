<?php
/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2021-2026 Mindstellar Community
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Which parts of the site stay readable without an account.
 *
 * This file only draws. The POST is handled on init_admin in index.php, before the panel
 * has printed anything — see the note there.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$rows = array(
    'allow_page'    => array(
        __('Static pages', 'registered-users-only'),
        __('Terms, privacy notice and imprint. Usually has to stay readable.', 'registered-users-only'),
    ),
    'allow_contact' => array(
        __('Contact form', 'registered-users-only'),
        __('Lets someone reach you without an account.', 'registered-users-only'),
    ),
    'allow_search'  => array(
        __('Browsing and search', 'registered-users-only'),
        __('Category listings and search results.', 'registered-users-only'),
    ),
    'allow_item'    => array(
        __('Individual listings', 'registered-users-only'),
        __('Opening one listing directly, including from a search engine.', 'registered-users-only'),
    ),
);
?>
<h2 class="render-title"><?php echo osc_esc_html(__('Registered Users Only', 'registered-users-only')); ?></h2>

<p class="text"><?php echo osc_esc_html(__(
    'Everything not ticked below sends a signed-out visitor to the registration page.',
    'registered-users-only'
)); ?></p>

<form action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" method="post" class="form-horizontal">
    <?php echo osc_csrf_token_form(); ?>
    <input type="hidden" name="page" value="plugins"/>
    <input type="hidden" name="action" value="renderplugin"/>
    <input type="hidden" name="file" value="<?php echo osc_esc_html(ruo_settings_file()); ?>"/>
    <input type="hidden" name="ruo_action" value="save"/>

    <?php foreach ($rows as $key => $row) { ?>
        <div class="form-row">
            <div class="form-controls">
                <label for="ruo-<?php echo osc_esc_html($key); ?>">
                    <input type="checkbox" id="ruo-<?php echo osc_esc_html($key); ?>"
                           name="<?php echo osc_esc_html($key); ?>" value="1"
                        <?php echo ruo_setting($key) ? 'checked="checked"' : ''; ?> />
                    <?php echo osc_esc_html($row[0]); ?>
                </label>
                <span class="help-block"><?php echo osc_esc_html($row[1]); ?></span>
            </div>
        </div>
    <?php } ?>

    <div class="form-row">
        <div class="form-controls">
            <span class="help-block"><?php echo osc_esc_html(__(
                'Signing in, registering, recovering a password and activating an account always stay open, as do the sitemap, the feeds and the ajax endpoints — closing those would lock out the people the plugin exists to let in, or simply break the route.',
                'registered-users-only'
            )); ?></span>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-submit"><?php echo osc_esc_html(__('Save', 'registered-users-only')); ?></button>
    </div>
</form>
