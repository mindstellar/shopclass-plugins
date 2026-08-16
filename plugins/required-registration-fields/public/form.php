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
 * The configured fields, drawn into the theme's registration form.
 *
 * Each input comes from UserForm so it carries the name, id and any value the theme and
 * core already expect; the only thing added here is the label, the required marker and —
 * for a required field — the `required` attribute, set afterwards from script because
 * UserForm renders the element itself.
 *
 * That attribute is a convenience. The check that decides whether a registration is
 * accepted runs on the server, in rrf_validate().
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$rrfActive = rrf_active_fields();
if ($rrfActive === array()) {
    return;
}

$rrfRequiredIds = array();
?>
<div class="rrf-fields">
    <?php foreach ($rrfActive as $rrfKey => $rrfField) {
        $rrfIsRequired = rrf_mode($rrfKey) === 'required';
        if ($rrfIsRequired) {
            $rrfRequiredIds[] = $rrfField['param'];
        } ?>
        <div class="rrf-field">
            <label for="<?php echo osc_esc_html($rrfField['param']); ?>">
                <?php echo osc_esc_html($rrfField['label']); ?><?php echo $rrfIsRequired ? ' *' : ''; ?>
            </label>
            <?php UserForm::{$rrfField['render']}(); ?>
        </div>
    <?php } ?>
</div>
<?php if ($rrfRequiredIds !== array()) { ?>
<script>
(function () {
    // UserForm renders the control, so the attribute is set here rather than inline.
    var ids = <?php echo json_encode($rrfRequiredIds); ?>;
    ids.forEach(function (id) {
        var el = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
        if (el) { el.setAttribute('required', 'required'); }
    });
})();
</script>
<?php } ?>
