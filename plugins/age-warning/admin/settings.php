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
 * Settings screen for the notice.
 *
 * The original plugin had none: the wording, the decline link and the retention period
 * were literals in the source, so adapting any of them meant editing a file that the next
 * update overwrote — and the decline link pointed at google.com for everybody.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

// This file only draws. The POST is handled on init_admin in index.php, before the panel
// has printed anything — see the note there.

// The stored decline URL is shown back as typed so a rejected value can be corrected;
// age_warning_decline_url() is what the public page uses, and it substitutes the default
// for anything that is not http(s).
$declineStored = age_warning_setting('decline_url');
$declineLive   = age_warning_decline_url();
?>
<h2 class="render-title"><?php echo osc_esc_html(__('Age Warning', 'age-warning')); ?></h2>

<form action="<?php echo osc_esc_html(osc_admin_base_url(true)); ?>" method="post" class="form-horizontal">
    <?php echo osc_csrf_token_form(); ?>
    <input type="hidden" name="page" value="plugins"/>
    <input type="hidden" name="action" value="renderplugin"/>
    <input type="hidden" name="file" value="<?php echo osc_esc_html(age_warning_settings_file()); ?>"/>
    <input type="hidden" name="agew_action" value="save"/>

    <div class="form-row">
        <label class="form-label" for="agew-message"><?php echo osc_esc_html(__('Notice text', 'age-warning')); ?></label>
        <div class="form-controls">
            <textarea id="agew-message" name="message" rows="3" class="input-large"
            ><?php echo osc_esc_html(age_warning_setting('message')); ?></textarea>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="agew-accept"><?php echo osc_esc_html(__('Confirm button', 'age-warning')); ?></label>
        <div class="form-controls">
            <input id="agew-accept" type="text" name="accept_label" class="input-large"
                   value="<?php echo osc_esc_html(age_warning_setting('accept_label')); ?>"/>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="agew-decline"><?php echo osc_esc_html(__('Decline button', 'age-warning')); ?></label>
        <div class="form-controls">
            <input id="agew-decline" type="text" name="decline_label" class="input-large"
                   value="<?php echo osc_esc_html(age_warning_setting('decline_label')); ?>"/>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="agew-url"><?php echo osc_esc_html(__('Send decliners to', 'age-warning')); ?></label>
        <div class="form-controls">
            <input id="agew-url" type="url" name="decline_url" class="input-large"
                   value="<?php echo osc_esc_html($declineStored); ?>"/>
            <?php if ($declineStored !== '' && $declineStored !== $declineLive) { ?>
                <span class="help-block"><?php
                    echo osc_esc_html(__('Only http:// and https:// addresses are used. The default applies until this is corrected.', 'age-warning'));
                ?></span>
            <?php } ?>
        </div>
    </div>

    <div class="form-row">
        <label class="form-label" for="agew-days"><?php echo osc_esc_html(__('Remember for (days)', 'age-warning')); ?></label>
        <div class="form-controls">
            <input id="agew-days" type="number" name="remember_days" min="1" max="365" class="input-small"
                   value="<?php echo osc_esc_html((string)age_warning_remember_days()); ?>"/>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-submit"><?php echo osc_esc_html(__('Save', 'age-warning')); ?></button>
    </div>
</form>
