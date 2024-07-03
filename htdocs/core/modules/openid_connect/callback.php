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
 *      \brief      OpenID Connect: Authorization Code flow authentication
 */



define('NOLOGIN', '1');
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
$arrayofjs = array(
	'/includes/jstz/jstz.min.js'.(empty($conf->dol_use_jmobile) ? '' : '?version='.urlencode(DOL_VERSION)),
	'/core/js/dst.js'.(empty($conf->dol_use_jmobile) ? '' : '?version='.urlencode(DOL_VERSION))
);

top_htmlhead('', '', 0, 0, $arrayofjs);

$prefix = dol_getprefix('');
$rollback_url = $_COOKIE["DOL_rollback_url_$prefix"];
if (empty($rollback_url) || $rollback_url === '/') {
	$action = $dolibarr_main_url_root . '/index.php?mainmenu=home&leftmenu=';
} else {
	$action = $rollback_url;
    setcookie('DOL_rollback_url_' . dol_getprefix(''), "", time() + 1, '/');
}
?>

<form id="login" name="login" method="post" action="<?= $action ?>">
	<!-- Add fields to send OpenID information -->
	<input type="hidden" name="openid_mode" value="true" />
	<input type="hidden" name="state" value="<?php echo GETPOST('state'); ?>" />
	<input type="hidden" name="session_state" value="<?php echo GETPOST('session_state'); ?>" />
	<input type="hidden" name="code" value="<?php echo GETPOST('code'); ?>" />
	<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
	<!-- Add fields to send local user information -->
	<input type="hidden" name="tz" id="tz" value="" />
	<input type="hidden" name="tz_string" id="tz_string" value="" />
	<input type="hidden" name="dst_observed" id="dst_observed" value="" />
	<input type="hidden" name="dst_first" id="dst_first" value="" />
	<input type="hidden" name="dst_second" id="dst_second" value="" />
	<input type="hidden" name="screenwidth" id="screenwidth" value="" />
	<input type="hidden" name="screenheight" id="screenheight" value="" />
</form>
<script type="text/javascript">
	$(document).ready(function () {
		document.forms['login'].submit();
	});
</script>
