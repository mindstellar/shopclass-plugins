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
 * The trigger and the dialog it opens. The symbol itself is drawn in the browser, so
 * nothing here depends on the listing beyond its address and title.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$qrcUrl  = osc_item_url();
$qrcBase = osc_plugin_url(dirname(__DIR__) . '/index.php');
?>
<div class="qrc"
     data-url="<?php echo osc_esc_html($qrcUrl); ?>"
     data-title="<?php echo osc_esc_html(osc_item_title()); ?>"
     data-ecc="<?php echo osc_esc_html(qrc_ecc()); ?>"
     data-base="<?php echo osc_esc_html($qrcBase); ?>">

    <button type="button" class="qrc-open">
        <?php echo osc_esc_html(__('Show QR code', 'qr-code')); ?>
    </button>

    <dialog class="qrc-dialog" aria-label="<?php echo osc_esc_html(__('QR code for this listing', 'qr-code')); ?>">
        <div class="qrc-body">
            <div class="qrc-out"
                 data-alt="<?php echo osc_esc_html(__('QR code linking to this listing', 'qr-code')); ?>"
                 data-error="<?php echo osc_esc_html(__('The code could not be generated.', 'qr-code')); ?>">
                <p class="qrc-wait"><?php echo osc_esc_html(__('Generating…', 'qr-code')); ?></p>
            </div>

            <p class="qrc-url"><?php echo osc_esc_html($qrcUrl); ?></p>

            <div class="qrc-actions">
                <?php /* Revealed by script only where the browser has the API behind them. */ ?>
                <button type="button" class="qrc-share" hidden>
                    <?php echo osc_esc_html(__('Share', 'qr-code')); ?>
                </button>
                <button type="button" class="qrc-copy" hidden
                        data-done="<?php echo osc_esc_html(__('Copied', 'qr-code')); ?>">
                    <?php echo osc_esc_html(__('Copy link', 'qr-code')); ?>
                </button>
                <button type="button" class="qrc-print">
                    <?php echo osc_esc_html(__('Print', 'qr-code')); ?>
                </button>
                <button type="button" class="qrc-close">
                    <?php echo osc_esc_html(__('Close', 'qr-code')); ?>
                </button>
            </div>
        </div>
    </dialog>
</div>
