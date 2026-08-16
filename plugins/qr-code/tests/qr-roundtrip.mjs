/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2021-2026 Mindstellar Community
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Encodes a set of addresses with the vendored encoder and reads them back with a
 * different library, so a symbol that does not actually scan cannot pass.
 *
 * The case that matters most is the non-ASCII one. Left on its default the encoder writes
 * one byte per code unit, and an address with an accented or non-Latin slug produces a
 * symbol that decodes to the wrong text — silently, because it still looks like a QR code.
 * The UTF-8 companion module is what fixes it, and this is what proves it.
 *
 *   npm install jsqr
 *   node tests/qr-roundtrip.mjs
 *
 * Not shipped in the release zip; see .distignore.
 */

import qrcode from '../vendor/qrcode.js';
import { stringToBytes } from '../vendor/qrcode-utf8.js';
import jsQR from 'jsqr';

qrcode.stringToBytes = stringToBytes;

const QUIET = 4;

/** Encode text, then draw the modules into an RGBA bitmap a decoder can read. */
function render(text, ecc, scale = 6) {
    const qr = qrcode(0, ecc);
    qr.addData(text, 'Byte');
    qr.make();

    const count = qr.getModuleCount();
    const size = (count + QUIET * 2) * scale;
    const data = new Uint8ClampedArray(size * size * 4).fill(255);

    for (let row = 0; row < count; row++) {
        for (let col = 0; col < count; col++) {
            if (!qr.isDark(row, col)) {
                continue;
            }
            for (let dy = 0; dy < scale; dy++) {
                for (let dx = 0; dx < scale; dx++) {
                    const px = ((row + QUIET) * scale + dy) * size + ((col + QUIET) * scale + dx);
                    data[px * 4] = 0;
                    data[px * 4 + 1] = 0;
                    data[px * 4 + 2] = 0;
                }
            }
        }
    }

    return { data, width: size, height: size, modules: count };
}

const cases = [
    ['a short listing address', 'https://example.com/listing/123', 'M'],
    ['a realistic permalink', 'https://shop.example.co.uk/vehicles/cars/2016-honda-accord-ex-l-one-owner_i139', 'M'],
    ['an accented slug', 'https://example.com/ünïcode-titel-mit-umlauten_i7', 'M'],
    ['a non-Latin slug', 'https://example.com/листинг-объявление_i42', 'M'],
    ['a query string and fragment', 'https://example.com/x?a=1&b=2#frag', 'L'],
    ['a long address at the highest level', 'https://example.com/' + 'p'.repeat(300), 'H'],
];

let failed = 0;
for (const [label, text, ecc] of cases) {
    const img = render(text, ecc);
    const got = jsQR(img.data, img.width, img.height);
    const ok = got !== null && got.data === text;
    if (!ok) {
        failed++;
    }
    console.log(
        `  ${ok ? 'PASS' : 'FAIL'}  ${label} (ecc ${ecc}, ${img.modules} modules)`
        + (ok ? '' : `\n        expected ${JSON.stringify(text)}\n        decoded  ${JSON.stringify(got && got.data)}`)
    );
}

console.log(`\n  ${cases.length - failed}/${cases.length} round-tripped through an independent decoder`);
process.exit(failed === 0 ? 0 : 1);
