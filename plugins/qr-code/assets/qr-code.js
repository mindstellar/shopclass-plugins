/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2021-2026 Mindstellar Community
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Draws the QR code for a listing, on demand.
 *
 * Nothing here runs, and the encoder is not fetched, until the visitor asks for the code.
 * The encoder is the larger part of this plugin by some distance, so it is pulled in with
 * a dynamic import at that point rather than shipped to everyone who opens a listing.
 */

const QUIET_ZONE = 4; // modules of margin the QR spec requires around the symbol

let encoderPromise = null;

/**
 * Load the encoder once, and only when a code is actually wanted.
 *
 * The UTF-8 byte encoder is not optional. Left on its default the library encodes text
 * one byte per code unit, and a URL carrying anything outside ASCII — an accented slug,
 * a non-Latin title — produces a symbol that either fails to scan or decodes to the
 * wrong string. Verified in tests/qr-roundtrip.mjs against a separate decoder.
 */
function loadEncoder(base) {
    if (encoderPromise === null) {
        encoderPromise = Promise.all([
            import(`${base}vendor/qrcode.js`),
            import(`${base}vendor/qrcode-utf8.js`),
        ]).then(([lib, utf8]) => {
            const qrcode = lib.default;
            qrcode.stringToBytes = utf8.stringToBytes;
            return qrcode;
        });
    }

    return encoderPromise;
}

/**
 * Build the symbol as one SVG path.
 *
 * SVG rather than a canvas so it stays sharp at whatever size it is shown or printed at,
 * and one path rather than a rect per module so the markup stays small — a dense symbol
 * is well over a thousand modules.
 *
 * @param {object} qr  a made qrcode instance
 * @returns {SVGElement}
 */
function toSvg(qr) {
    const count = qr.getModuleCount();
    const span = count + QUIET_ZONE * 2;

    let d = '';
    for (let row = 0; row < count; row++) {
        for (let col = 0; col < count; col++) {
            if (qr.isDark(row, col)) {
                d += `M${col + QUIET_ZONE} ${row + QUIET_ZONE}h1v1h-1z`;
            }
        }
    }

    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', `0 0 ${span} ${span}`);
    svg.setAttribute('role', 'img');
    svg.setAttribute('shape-rendering', 'crispEdges');

    const bg = document.createElementNS(ns, 'rect');
    bg.setAttribute('width', String(span));
    bg.setAttribute('height', String(span));
    bg.setAttribute('fill', '#fff');
    svg.appendChild(bg);

    const path = document.createElementNS(ns, 'path');
    path.setAttribute('d', d);
    path.setAttribute('fill', '#000');
    svg.appendChild(path);

    return svg;
}

/**
 * Wire one trigger and its dialog.
 *
 * @param {HTMLElement} root the .qrc element carrying the configuration
 */
function setup(root) {
    const button = root.querySelector('.qrc-open');
    const dialog = root.querySelector('.qrc-dialog');
    const canvas = root.querySelector('.qrc-out');
    if (!button || !dialog || !canvas) {
        return;
    }

    const url = root.dataset.url || window.location.href;
    const title = root.dataset.title || document.title;
    const ecc = root.dataset.ecc || 'M';
    const base = root.dataset.base || '';

    button.addEventListener('click', async () => {
        // Native dialog: the browser handles the backdrop, focus trapping and Escape.
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', 'open');
        }

        if (canvas.dataset.rendered === '1') {
            return;
        }

        try {
            const qrcode = await loadEncoder(base);
            const qr = qrcode(0, ecc); // 0 picks the smallest version the payload fits in
            qr.addData(url, 'Byte');
            qr.make();

            const svg = toSvg(qr);
            svg.setAttribute('aria-label', canvas.dataset.alt || 'QR code');
            canvas.replaceChildren(svg);
            canvas.dataset.rendered = '1';
        } catch (err) {
            canvas.textContent = canvas.dataset.error || 'The code could not be generated.';
        }
    });

    root.querySelector('.qrc-close')?.addEventListener('click', () => dialog.close());

    // Printing copies the symbol onto a sheet appended to <body> and prints that. The
    // dialog itself cannot be used: it sits inside the theme's markup, so hiding the
    // page's top-level children to isolate it would hide its own ancestors too.
    root.querySelector('.qrc-print')?.addEventListener('click', () => {
        const svg = canvas.querySelector('svg');
        if (!svg) {
            return;
        }

        const sheet = document.createElement('div');
        sheet.className = 'qrc-sheet';
        sheet.appendChild(svg.cloneNode(true));

        const caption = document.createElement('p');
        caption.textContent = url;
        sheet.appendChild(caption);

        document.body.appendChild(sheet);
        const cleanup = () => sheet.remove();
        window.addEventListener('afterprint', cleanup, { once: true });
        window.print();
        // Safari never fires afterprint in some versions; this is the belt to that braces.
        setTimeout(cleanup, 10000);
    });

    // Both of the following exist only where the browser supports them, so the buttons
    // are revealed rather than hidden — a control that does nothing is worse than absent.
    const share = root.querySelector('.qrc-share');
    if (share && typeof navigator.share === 'function') {
        share.hidden = false;
        share.addEventListener('click', async () => {
            try {
                await navigator.share({ title, url });
            } catch (err) {
                // A cancelled share rejects; that is the visitor's choice, not a failure.
            }
        });
    }

    const copy = root.querySelector('.qrc-copy');
    if (copy && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        copy.hidden = false;
        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(url);
                const said = copy.dataset.done;
                if (said) {
                    const was = copy.textContent;
                    copy.textContent = said;
                    setTimeout(() => { copy.textContent = was; }, 1600);
                }
            } catch (err) {
                // Clipboard access can be refused; nothing useful to say about it.
            }
        });
    }
}

document.querySelectorAll('.qrc').forEach(setup);
