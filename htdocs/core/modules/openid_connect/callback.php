<?php
/* Copyright (C) 2023   Maximilien Rozniecki    <mrozniecki@easya.solutions>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/modules/openid_connect/public/callback.php
 *      \ingroup    openid_connect
 *      \brief      OpenID Connect: Authorization Code flow callback
 *
 *      This page receives the authorization code from the OIDC provider and
 *      stores the OIDC parameters (code, state) in $_SESSION, then redirects
 *      to index.php via a same-site JS redirect (with tz detection from dst.js).
 *
 *      The same-site redirect ensures the session cookie IS sent (SameSite=Lax
 *      allows same-site navigations). The OIDC code and state are transported
 *      via $_SESSION instead of GET parameters to avoid exposing them in
 *      web server access logs.
 */


define('NOLOGIN', '1');
define('NOTOKENRENEWAL', '1');
define('NOCSRFCHECK', 1);

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

/**
 * @var string $dolibarr_main_url_root
 * @var string $dolibarr_main_force_https
 */

// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
$arrayofjs = array(
	'/core/js/dst.js'.(empty($conf->dol_use_jmobile) ? '' : '?version='.urlencode(DOL_VERSION))
);

top_htmlhead('', '', 0, 0, $arrayofjs);

$prefix = dol_getprefix('');

$callbackUrl = isset($_COOKIE["DOL_rollback_url_".$prefix]) ? $_COOKIE["DOL_rollback_url_".$prefix] : '';

if (empty($callbackUrl) || !preg_match('/^\/[a-z0-9]/i', $callbackUrl)) {
	// We accept only value that is an internal relative URL. URL starting with http are not allowed.
	$callbackUrl = '/';
}
if ($callbackUrl === '/') {
	$callbackUrl = $dolibarr_main_url_root . '/index.php?mainmenu=home&leftmenu=';
} else {
	dolSetCookie('DOL_rollback_url_'.dol_getprefix(''), "", time() + 1);
}

// Get OIDC parameters from the provider's redirect (GET) and store in session
$oidcState = GETPOST('state', 'aZ09');
$oidcCode = GETPOST('code', 'password');

// Store in $_SESSION so they are not exposed as GET params on the redirect to index.php
$_SESSION['oidc_code'] = $oidcCode;
$_SESSION['oidc_state'] = $oidcState;
?>

<!-- Hidden fields for dst.js to populate with timezone detection -->
<input type="hidden" id="tz" value="" />
<input type="hidden" id="tz_string" value="" />
<input type="hidden" id="dst_observed" value="" />
<input type="hidden" id="dst_first" value="" />
<input type="hidden" id="dst_second" value="" />
<input type="hidden" id="screenwidth" value="" />
<input type="hidden" id="screenheight" value="" />

<script type="text/javascript">
	$(document).ready(function () {
		// dst.js has already populated the hidden fields above.
		// Build a GET redirect URL with openid_mode flag + timezone info.
		var baseUrl = '<?php echo dol_escape_js($callbackUrl); ?>';
		var sep = baseUrl.indexOf('?') === -1 ? '?' : '&';

		var url = baseUrl + sep + 'openid_mode=true';
		url += '&tz=' + encodeURIComponent($('#tz').val());
		url += '&tz_string=' + encodeURIComponent($('#tz_string').val());
		url += '&dst_observed=' + encodeURIComponent($('#dst_observed').val());
		url += '&dst_first=' + encodeURIComponent($('#dst_first').val());
		url += '&dst_second=' + encodeURIComponent($('#dst_second').val());
		url += '&screenwidth=' + encodeURIComponent($('#screenwidth').val());
		url += '&screenheight=' + encodeURIComponent($('#screenheight').val());

		window.location.href = url;
	});
</script>
