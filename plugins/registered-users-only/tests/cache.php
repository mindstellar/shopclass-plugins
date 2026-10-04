<?php
/*
 * This file is part of Shopclass (Mindstellar).
 * Copyright (c) 2021-2026 Navjot Tomer (Mindstellar) and contributors
 *
 * Distributed under the GNU General Public License v3.0 or later. See LICENSE.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Standalone: `php tests/cache.php`. Core is stubbed, so this checks what the plugin decides
 * about access and shared caching, not core itself.
 */

define('ABS_PATH', __DIR__ . '/');

$state = array();

function ruo_test_reset()
{
    $GLOBALS['state'] = array(
        'logged_in'   => false,
        'location'    => '',
        'params'      => array(),
        'prefs'       => array(),
        'core_ok'     => true,
        'marked'      => false,
        'filters'     => array(),
        'hooks'       => array(),
        'purged'      => 0,
        'redirect'    => null,
        'flash'       => array(),
    );
}
ruo_test_reset();

class Rewrite
{
    public static function newInstance()
    {
        return new self();
    }

    public function get_location()
    {
        return $GLOBALS['state']['location'];
    }
}

class Params
{
    public static function getParamString($name)
    {
        return (string)($GLOBALS['state']['params'][$name] ?? '');
    }

    public static function getParam($name)
    {
        return $GLOBALS['state']['params'][$name] ?? '';
    }
}

class RuoRedirect extends Exception
{
}

function osc_plugin_path($file)
{
    return 'registered-users-only/index.php';
}
function osc_plugin_folder($file)
{
    return 'registered-users-only/';
}
function osc_register_plugin($path, $fn)
{
}
function osc_add_hook($hook, $fn)
{
    $GLOBALS['state']['hooks'][$hook][] = $fn;
}
function osc_add_filter($hook, $fn)
{
    $GLOBALS['state']['filters'][$hook][] = $fn;
}
function osc_apply_filter($hook, $value)
{
    return $value;
}
function osc_get_preference($key, $section)
{
    return $GLOBALS['state']['prefs'][$key] ?? null;
}
function osc_is_web_user_logged_in()
{
    return $GLOBALS['state']['logged_in'];
}
function osc_register_account_url()
{
    return 'http://example.test/user/register';
}
function osc_add_flash_info_message($msg)
{
    $GLOBALS['state']['flash'][] = $msg;
}
function osc_redirect_to($url)
{
    $GLOBALS['state']['redirect'] = $url;
    throw new RuoRedirect($url);
}
function __($s, $d = '')
{
    return $s;
}
function osc_mark_response_cacheable($on = true)
{
    $GLOBALS['state']['marked'] = (bool)$on;
}
// Core's rule, reduced to the parts the plugin depends on: opted in, core happy, and every
// response_is_cacheable filter agreeing.
function osc_response_is_cacheable()
{
    if (!$GLOBALS['state']['marked'] || !$GLOBALS['state']['core_ok']) {
        return false;
    }
    $ok = true;
    foreach ($GLOBALS['state']['filters']['response_is_cacheable'] ?? array() as $fn) {
        $ok = (bool)$fn($ok);
    }

    return $ok;
}
function osc_purge_page_cache()
{
    $GLOBALS['state']['purged']++;
}

require __DIR__ . '/../index.php';

$ok   = 0;
$fail = 0;
function check($label, $cond)
{
    global $ok, $fail;
    if ($cond) {
        $ok++;
    } else {
        $fail++;
        echo "FAIL: $label\n";
    }
}

function guard($location, array $params = array())
{
    $GLOBALS['state']['location'] = $location;
    $GLOBALS['state']['params']   = $params;
    $GLOBALS['state']['redirect'] = null;
    try {
        ruo_guard();
    } catch (RuoRedirect $e) {
    }

    return $GLOBALS['state']['redirect'];
}

$filters = $state['filters'];
$hooks   = $state['hooks'];

// Wiring.
check('the cacheability filter is registered', in_array('ruo_response_is_cacheable', $filters['response_is_cacheable'] ?? array(), true));
check('activating purges the page cache', in_array('ruo_purge_page_cache', $hooks['registered-users-only/index.php_enable'] ?? array(), true));
check('deactivating purges the page cache', in_array('ruo_purge_page_cache', $hooks['registered-users-only/index.php_disable'] ?? array(), true));

// Access.
ruo_test_reset();
$GLOBALS['state']['filters'] = $filters;
check('the front page redirects a signed-out visitor', guard('') === 'http://example.test/user/register?ruo=1');
check('a listing redirects by default', guard('item') !== null);
check('the login page stays open', guard('login') === null);
check('the registration page stays open', guard('register') === null);
check('password recovery stays open', guard('recover') === null);
check('a signed-in visitor is never redirected', (function () {
    $GLOBALS['state']['logged_in'] = true;
    $r = guard('');
    $GLOBALS['state']['logged_in'] = false;

    return $r === null;
})());
check('a public profile is closed while browsing is closed', guard('user', array('action' => 'pub_profile')) !== null);
$GLOBALS['state']['prefs']['allow_search'] = '1';
check('a public profile opens with browsing', guard('user', array('action' => 'pub_profile')) === null);
unset($GLOBALS['state']['prefs']['allow_search']);
check('account recovery under user stays open', guard('user', array('action' => 'forgot')) === null);

// The notice comes from the marker, not a cookie.
$GLOBALS['state']['flash'] = array();
guard('register', array('ruo' => '1'));
check('the registration page explains the redirect', count($GLOBALS['state']['flash']) === 1);
$GLOBALS['state']['flash'] = array();
guard('');
check('the redirect itself queues no flash cookie', $GLOBALS['state']['flash'] === array());
check('a register URL with a query keeps it', (function () {
    return strpos(ruo_register_url(), '?ruo=1') !== false;
})());

// Shared caching.
check(
    'the redirect is cacheable when core would cache an anonymous page',
    ruo_redirect_cache_control() === 'public, s-maxage=30, max-age=0, must-revalidate'
);
check('the redirect leaves the opt-in off afterwards', $GLOBALS['state']['marked'] === false);
$GLOBALS['state']['core_ok'] = false;
check('the redirect is private when core says the request is personal', ruo_redirect_cache_control() === 'private, no-store');
$GLOBALS['state']['core_ok'] = true;

$GLOBALS['state']['location'] = '';
$GLOBALS['state']['params']   = array();
check('a closed page is never cacheable', ruo_response_is_cacheable(true) === false);
$GLOBALS['state']['location'] = 'page';
check('an open static page stays cacheable', ruo_response_is_cacheable(true) === true);
$GLOBALS['state']['location'] = 'user';
$GLOBALS['state']['params']   = array('action' => 'pub_profile');
check('a closed public profile is never cacheable', ruo_response_is_cacheable(true) === false);
$GLOBALS['state']['logged_in'] = true;
$GLOBALS['state']['location']  = '';
check('the filter leaves a signed-in response to core', ruo_response_is_cacheable(true) === true);
$GLOBALS['state']['logged_in'] = false;
check('the filter never turns a private response public', ruo_response_is_cacheable(false) === false);

check(
    'a cookie on the way out makes a public redirect private',
    ruo_cache_control_fix(array('Cache-Control: public, s-maxage=30', 'Set-Cookie: oc_flash=x')) === 'private, no-store'
);
check(
    'a public redirect with no cookie is left alone',
    ruo_cache_control_fix(array('Cache-Control: public, s-maxage=30', 'Location: /')) === null
);

// Purging.
$GLOBALS['state']['purged'] = 0;
ruo_purge_page_cache();
check('purging goes through core when core offers it', $GLOBALS['state']['purged'] === 1);

echo "$ok passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
