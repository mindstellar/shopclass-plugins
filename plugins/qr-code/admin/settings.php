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
 * This file only draws. The POST is handled on init_admin in index.php, before the panel
 * has printed anything — see the note there.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$levels = array(
    'L' => __('L — about 7% recoverable, smallest symbol', 'qr-code'),
    'M' => __('M — about 15% recoverable (recommended)', 'qr-code'),
    'Q' => __('Q — about 25% recoverable', 'qr-code'),
    'H' => __('H — about 30% recoverable, densest symbol', 'qr-code'),
);
$current = qrc_ecc();
?>
<h2 class="render-title"><?php echo osc_esc_html(__('QR Code', 'qr-code')); ?></h2>

<p class="text"><?php echo osc_esc_html(__(
    'A higher level survives a scuffed print or an awkward scanning angle, at the cost of a denser symbol that takes longer to read.',
    'qr-code'
)); ?></p>

<form action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" method="post" class="form-horizontal">
    <?php echo osc_csrf_token_form(); ?>
    <input type="hidden" name="page" value="plugins"/>
    <input type="hidden" name="action" value="renderplugin"/>
    <input type="hidden" name="file" value="<?php echo osc_esc_html(qrc_settings_file()); ?>"/>
    <input type="hidden" name="qrc_action" value="save"/>

    <div class="form-row">
        <label class="form-label" for="qrc-ecc"><?php echo osc_esc_html(__('Error correction', 'qr-code')); ?></label>
        <div class="form-controls">
            <select id="qrc-ecc" name="ecc">
                <?php foreach ($levels as $value => $label) { ?>
                    <option value="<?php echo osc_esc_html($value); ?>"
                        <?php echo $current === $value ? 'selected="selected"' : ''; ?>>
                        <?php echo osc_esc_html($label); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-submit"><?php echo osc_esc_html(__('Save', 'qr-code')); ?></button>
    </div>
</form>
