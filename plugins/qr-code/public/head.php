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
 * Styles, and the module that wires the trigger.
 *
 * The module is a `type="module"` script, so it is deferred by definition and its own
 * import of the encoder happens later still — only once a visitor asks for a code.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$qrcBase = osc_plugin_url(dirname(__DIR__) . '/index.php');
?>
<style id="qrc-style">
.qrc-dialog{border:0;border-radius:.75rem;padding:0;max-width:26rem;width:calc(100% - 2rem);
box-shadow:0 1.5rem 3rem rgba(0,0,0,.35)}
.qrc-dialog::backdrop{background:rgba(12,14,18,.72)}
.qrc-body{padding:1.5rem;background:#fff;color:#14181f;text-align:center;
font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
.qrc-out{display:block;margin:0 auto .9rem;max-width:15rem}
.qrc-out svg{display:block;width:100%;height:auto}
.qrc-wait{margin:2rem 0;color:#5f6b7a}
.qrc-url{margin:0 0 1.1rem;font-size:.8125rem;color:#5f6b7a;word-break:break-all}
.qrc-actions{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}
.qrc-actions button{appearance:none;border:0;border-radius:.5rem;padding:.55rem 1rem;
font:inherit;font-weight:600;cursor:pointer;background:#eef1f5;color:#14181f}
.qrc-actions .qrc-share{background:#0b7269;color:#fff}
.qrc-open{appearance:none;border:1px solid currentColor;background:none;color:inherit;
border-radius:.5rem;padding:.5rem 1rem;font:inherit;cursor:pointer}
@media (prefers-color-scheme:dark){
.qrc-body{background:#181c22;color:#f2f5f8}
.qrc-actions button{background:#2a3038;color:#f2f5f8}}
/* Printing works off a sheet appended straight to <body>, not off the dialog. The dialog
   sits deep inside the theme's markup, so hiding the rest of the page by its top-level
   children would hide the dialog's own ancestors along with it. */
.qrc-sheet{display:none}
@media print{
  body>*{display:none!important}
  body>.qrc-sheet{display:block!important;text-align:center}
  .qrc-sheet svg{width:60mm;height:60mm;margin:0 auto}
  .qrc-sheet p{font:12pt/1.4 system-ui,sans-serif;color:#000;word-break:break-all;margin:6mm 2mm 0}
}
</style>
<script type="module" src="<?php echo osc_esc_html($qrcBase . 'assets/qr-code.js'); ?>"></script>
