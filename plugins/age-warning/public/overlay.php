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
 * The notice itself. Identical bytes for every visitor, so the page it sits on stays
 * cacheable; the browser alone decides whether it is on screen.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}
?>
<div class="agew-overlay" role="dialog" aria-modal="true" aria-labelledby="agew-text">
    <div class="agew-panel">
        <p class="agew-text" id="agew-text"><?php echo osc_esc_html(age_warning_setting('message')); ?></p>
        <div class="agew-actions">
            <button type="button" class="agew-btn agew-accept" id="agew-accept">
                <?php echo osc_esc_html(age_warning_setting('accept_label')); ?>
            </button>
            <a class="agew-btn agew-decline" rel="nofollow noopener"
               href="<?php echo osc_esc_html(age_warning_decline_url()); ?>">
                <?php echo osc_esc_html(age_warning_setting('decline_label')); ?>
            </a>
        </div>
    </div>
</div>
<script id="agew-accept-script">
(function () {
    var btn = document.getElementById('agew-accept');
    if (!btn) { return; }
    btn.addEventListener('click', function () {
        var days = <?php echo (int)age_warning_remember_days(); ?>;
        var expires = new Date(Date.now() + days * 864e5).toUTCString();
        // Lax rather than Strict: the visitor may well have arrived from a search engine
        // or a shared link, and Strict would drop the cookie on that first cross-site
        // navigation and show the notice again.
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = <?php echo json_encode(AGE_WARNING_COOKIE); ?>
            + '=1; path=/; max-age=' + (days * 86400)
            + '; expires=' + expires + '; SameSite=Lax' + secure;
        document.documentElement.className += ' agew-passed';
    });
})();
</script>
