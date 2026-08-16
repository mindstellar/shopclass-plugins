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
 * Which extra fields appear on the registration form, and which must be filled in.
 *
 * This file only draws. The POST is handled on init_admin in index.php, before the panel
 * has printed anything — see the note there.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$modes = array(
    'off'      => __('Not shown', 'required-registration-fields'),
    'optional' => __('Shown, optional', 'required-registration-fields'),
    'required' => __('Shown, required', 'required-registration-fields'),
);
?>
<h2 class="render-title"><?php echo osc_esc_html(__('Required Registration Fields', 'required-registration-fields')); ?></h2>

<p class="text"><?php echo osc_esc_html(__(
    'Fields set to required are checked when the account is created, so a registration that leaves one empty is rejected whatever the browser does.',
    'required-registration-fields'
)); ?></p>

<form action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" method="post" class="form-horizontal">
    <?php echo osc_csrf_token_form(); ?>
    <input type="hidden" name="page" value="plugins"/>
    <input type="hidden" name="action" value="renderplugin"/>
    <input type="hidden" name="file" value="<?php echo osc_esc_html(rrf_settings_file()); ?>"/>
    <input type="hidden" name="rrf_action" value="save"/>

    <?php foreach (rrf_fields() as $key => $field) {
        $current = rrf_mode($key); ?>
        <div class="form-row">
            <label class="form-label" for="rrf-<?php echo osc_esc_html($key); ?>">
                <?php echo osc_esc_html($field['label']); ?>
            </label>
            <div class="form-controls">
                <select id="rrf-<?php echo osc_esc_html($key); ?>" name="<?php echo osc_esc_html($key); ?>">
                    <?php foreach ($modes as $value => $label) { ?>
                        <option value="<?php echo osc_esc_html($value); ?>"
                            <?php echo $current === $value ? 'selected="selected"' : ''; ?>>
                            <?php echo osc_esc_html($label); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php } ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-submit">
            <?php echo osc_esc_html(__('Save', 'required-registration-fields')); ?>
        </button>
    </div>
</form>
