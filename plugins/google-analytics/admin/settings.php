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
 * This file only draws. The POST is handled on init_admin in index.php.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$gaStored = trim(ga_setting('measurement_id'));
$gaValid  = ga_measurement_id();
$gaFlags  = array(
    'require_consent' => array(
        __('Wait for consent before tracking', 'google-analytics'),
        __('Storage starts denied, so no cookie or identifier is set until something calls oscGoogleAnalytics.grant(). Leave this on unless another tool is already managing consent.', 'google-analytics'),
    ),
    'anonymize_ip' => array(
        __('Ask Google to truncate visitor IP addresses', 'google-analytics'),
        __('Sent as a config flag; GA4 truncates by default, so this mainly documents the intent.', 'google-analytics'),
    ),
);
?>
<h2 class="render-title"><?php echo osc_esc_html(__('Google Analytics', 'google-analytics')); ?></h2>

<form action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" method="post" class="form-horizontal">
    <?php echo osc_csrf_token_form(); ?>
    <input type="hidden" name="page" value="plugins"/>
    <input type="hidden" name="action" value="renderplugin"/>
    <input type="hidden" name="file" value="<?php echo osc_esc_html(ga_settings_file()); ?>"/>
    <input type="hidden" name="ga_action" value="save"/>

    <div class="form-row">
        <label class="form-label" for="ga-id"><?php echo osc_esc_html(__('Measurement ID', 'google-analytics')); ?></label>
        <div class="form-controls">
            <input type="text" id="ga-id" name="measurement_id" class="input-large"
                   placeholder="G-XXXXXXXXXX" value="<?php echo osc_esc_html($gaStored); ?>"/>
            <?php if ($gaStored !== '' && $gaValid === '') { ?>
                <span class="help-block"><?php echo osc_esc_html(__(
                    'That does not look like a measurement ID, so nothing is being tracked. They start G-, GT-, UA- or AW-.',
                    'google-analytics'
                )); ?></span>
            <?php } elseif ($gaStored === '') { ?>
                <span class="help-block"><?php echo osc_esc_html(__(
                    'Nothing is tracked until this is set.',
                    'google-analytics'
                )); ?></span>
            <?php } ?>
        </div>
    </div>

    <?php foreach ($gaFlags as $gaKey => $gaRow) { ?>
        <div class="form-row">
            <div class="form-controls">
                <label for="ga-<?php echo osc_esc_html($gaKey); ?>">
                    <input type="checkbox" id="ga-<?php echo osc_esc_html($gaKey); ?>"
                           name="<?php echo osc_esc_html($gaKey); ?>" value="1"
                        <?php echo ga_setting($gaKey) === '1' ? 'checked="checked"' : ''; ?> />
                    <?php echo osc_esc_html($gaRow[0]); ?>
                </label>
                <span class="help-block"><?php echo osc_esc_html($gaRow[1]); ?></span>
            </div>
        </div>
    <?php } ?>

    <div class="form-row">
        <label class="form-label"><?php echo osc_esc_html(__('Your own visits', 'google-analytics')); ?></label>
        <div class="form-controls">
            <button type="button" class="btn" id="ga-optout"
                    data-cookie="<?php echo osc_esc_html(GA_OPTOUT_COOKIE); ?>"
                    data-on="<?php echo osc_esc_html(__('Stop counting this browser', 'google-analytics')); ?>"
                    data-off="<?php echo osc_esc_html(__('Count this browser again', 'google-analytics')); ?>"></button>
            <span class="help-block"><?php echo osc_esc_html(__(
                'Sets a cookie in this browser so your own visits are not counted. It applies to this browser only — a signed-in admin cannot be recognised from the public side of the site, so there is nothing server-side to key it on.',
                'google-analytics'
            )); ?></span>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-submit"><?php echo osc_esc_html(__('Save', 'google-analytics')); ?></button>
    </div>
</form>

<script>
(function () {
    var btn = document.getElementById('ga-optout');
    if (!btn) { return; }
    var name = btn.dataset.cookie;
    var has = function () {
        return document.cookie.split('; ').some(function (p) { return p.slice(0, p.indexOf('=')) === name; });
    };
    var paint = function () { btn.textContent = has() ? btn.dataset.off : btn.dataset.on; };
    btn.addEventListener('click', function () {
        if (has()) {
            document.cookie = name + '=; path=/; max-age=0';
        } else {
            // Long-lived: this is a preference, not a session.
            document.cookie = name + '=1; path=/; max-age=' + (5 * 365 * 86400) + '; SameSite=Lax';
        }
        paint();
    });
    paint();
})();
</script>
