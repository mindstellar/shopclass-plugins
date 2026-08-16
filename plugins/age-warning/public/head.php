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
 * Styles, plus the script that hides the notice for a visitor who already confirmed.
 *
 * The overlay is visible by default and script removes it, never the reverse — see the
 * note in index.php. The check runs here, in the head, so a returning visitor never sees
 * it flash before it goes.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}
?>
<style id="agew-style">
.agew-overlay{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;
justify-content:center;padding:1.25rem;background:rgba(12,14,18,.92);
font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.agew-panel{max-width:34rem;width:100%;background:#fff;color:#14181f;border-radius:.75rem;
padding:1.75rem;box-shadow:0 1.5rem 3rem rgba(0,0,0,.35);text-align:center}
.agew-text{margin:0 0 1.5rem;font-size:1.0625rem;line-height:1.55}
.agew-actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
.agew-btn{appearance:none;border:0;border-radius:.5rem;padding:.7rem 1.35rem;font:inherit;
font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.agew-accept{background:#0b7269;color:#fff}
.agew-decline{background:#eef1f5;color:#14181f}
.agew-passed .agew-overlay{display:none}
@media (prefers-color-scheme:dark){
.agew-panel{background:#181c22;color:#f2f5f8}
.agew-decline{background:#2a3038;color:#f2f5f8}}
</style>
<script id="agew-early">
(function () {
    var name = <?php echo json_encode(AGE_WARNING_COOKIE); ?>;
    // Match the cookie by name only. A substring test would let any cookie whose value
    // happens to contain this name dismiss the notice.
    var found = document.cookie.split('; ').some(function (pair) {
        return pair.slice(0, pair.indexOf('=')) === name;
    });
    if (found) {
        document.documentElement.className += ' agew-passed';
    }
})();
</script>
