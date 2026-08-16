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
 * The tag.
 *
 * Two orderings matter here and neither is obvious.
 *
 * **Consent before the library.** Consent Mode has to be set before gtag.js initialises,
 * because the library reads the current state as it starts. A default written afterwards
 * arrives too late and the first page view is already recorded with storage allowed. So
 * the dataLayer and the denied default are written inline first, and the library is only
 * requested after.
 *
 * **The opt-out is checked in the browser, not on the server.** Leaving the tag out of the
 * response for some visitors would make the page differ between them, and a shared cache
 * has one copy per URL — it would serve the untagged page to everyone, or the tagged one
 * to someone who opted out. So every visitor gets the same bytes and the script decides
 * whether to go on. This is also the only way it can work at all: an admin session is not
 * visible from the front end, so the server genuinely cannot tell who is staff.
 */

if (!defined('ABS_PATH')) {
    exit('Direct access is not allowed.');
}

$gaId      = ga_measurement_id();
$gaConsent = ga_setting('require_consent') === '1';
$gaAnon    = ga_setting('anonymize_ip') === '1';
?>
<script id="ga-tag">
(function () {
    // Anyone carrying the opt-out cookie is not counted, and nothing is loaded for them.
    // Matched by name so a cookie whose *value* happens to contain the name cannot
    // switch tracking off for someone who never asked.
    var optedOut = document.cookie.split('; ').some(function (pair) {
        return pair.slice(0, pair.indexOf('=')) === <?php echo json_encode(GA_OPTOUT_COOKIE); ?>;
    });
    if (optedOut) { return; }

    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    window.gtag = gtag;

<?php if ($gaConsent) { ?>
    // Denied until the visitor says otherwise. With this in place gtag.js sets no cookie
    // and no identifier; it queues what it would have sent and delivers it only if
    // consent is granted later in the same page view.
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        wait_for_update: 500
    });

    /**
     * Called by whatever asks the visitor — a banner plugin, the theme, a link in a page:
     *
     *   oscGoogleAnalytics.grant();   // they agreed
     *   oscGoogleAnalytics.deny();    // they refused, or changed their mind
     *
     * Remembering the answer belongs to the banner, which is the thing that knows what was
     * asked and under which policy.
     */
    window.oscGoogleAnalytics = {
        grant: function () {
            gtag('consent', 'update', {
                ad_storage: 'granted',
                ad_user_data: 'granted',
                ad_personalization: 'granted',
                analytics_storage: 'granted'
            });
        },
        deny: function () {
            gtag('consent', 'update', {
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
                analytics_storage: 'denied'
            });
        }
    };
<?php } ?>

    gtag('js', new Date());
    gtag('config', <?php echo json_encode($gaId); ?><?php echo $gaAnon ? ', {anonymize_ip: true}' : ''; ?>);

    // Injected here rather than as a plain tag, so that an opted-out browser never
    // requests it at all.
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(<?php echo json_encode($gaId); ?>);
    document.head.appendChild(s);
})();
</script>
