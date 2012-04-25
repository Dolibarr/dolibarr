<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2011-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- BEGIN PHP TEMPLATE -->
<html>

<?php
print '<head>
<meta name="robots" content="noindex,nofollow" />
<meta name="author" content="Dolibarr Development Team">
<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>
<title>'.$langs->trans('Login').' '.$title.'</title>'."\n";
print '<!-- Includes for JQuery (Ajax library) -->'."\n";
if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css" />'."\n";  // JQuery
else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui-latest.custom.css" />'."\n";    // JQuery
// JQuery. Must be before other includes
print '<!-- Includes JS for JQuery -->'."\n";
if (constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min.js"></script>'."\n";
else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-latest.min'.$ext.'"></script>'."\n";
print '<link rel="stylesheet" type="text/css" href="'.$conf_css.'" />'."\n";
if (! empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER;
print '<!-- HTTP_USER_AGENT = '.$_SERVER['HTTP_USER_AGENT'].' -->
</head>';

?>

<body class="body">

<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="buildnewpassword">

<table class="login_table_title" summary="<?php echo $title; ?>" cellpadding="0" cellspacing="0" border="0" align="center">
<tr class="vmenu"><td align="center"><?php echo $title; ?></td></tr>
</table>
<br>

<table class="login_table" summary="Login area" cellpadding="2" align="center">

<tr><td colspan="2" valign="middle">
<table class="none" summary="Login pass" cellpadding="2" align="center">

<!-- Login -->
<tr>
<td valign="bottom"> &nbsp; <b><?php echo $langs->trans('Login'); ?></b> &nbsp; </td>
<td valign="bottom" nowrap="nowrap">
<input type="text" <?php echo $disabled; ?> id="username" name="username" class="flat" size="15" maxlength="25" value="<?php echo $login; ?>" tabindex="1" /></td>
</tr>

<?php if ($select_entity) { ?>
	<tr><td valign="top" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('Entity'); ?></b> &nbsp; </td>
	<td valign="top" nowrap="nowrap">
<?php echo $select_entity; ?>
	</td></tr>
<?php } ?>

<?php if ($captcha) { ?>
	<tr><td valign="middle" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('SecurityCode'); ?></b></td>
	<td valign="middle" nowrap="nowrap" align="left" class="none">

	<table class="login_table_securitycode" style="width: 100px;"><tr>
	<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="3"></td>
	<td><img src="<?php echo $dol_url_root.'/core/antispamimage.php'; ?>" border="0" width="80" height="32" id="img_securitycode"></td>
	<td><a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a></td>
	</tr></table>

	</td></tr>
<?php } ?>

</table>
</td>

<td align="center" valign="top">
<img alt="Logo" title="" src="<?php echo $urllogo; ?>" id="img_logo" />
</td>

</tr>

<!-- Button Send password -->
<tr><td colspan="3" style="text-align:center;"><br>
<input id="password" type="submit" <?php echo $disabled; ?> class="button" name="password" value="<?php echo $langs->trans('SendNewPassword'); ?>" tabindex="4" />
</td></tr>

<tr><td colspan="3" align="center">
<a style="color: #888888; font-size: 10px" href="<?php echo $dol_url_root; ?>/">
	<?php echo '('.$langs->trans('BackToLoginPage').')'; ?>
</a>
</td></tr>

</table>

</form>

<center>
<table width="90%"><tr><td align="center">

<?php if ($mode == 'dolibarr' || ! $disabled) { ?>
	<font style="font-size: 12px;">
	<?php echo $langs->trans('SendNewPasswordDesc'); ?>
	</font>
<?php }else{ ?>
	<div class="warning" align="center">
	<?php echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode); ?>
	</div>
<?php } ?>

</td></tr>
</table>

<br>

<?php if ($message) { ?>
	<table width="90%"><tr><td align="center" style="font-size: 12px;">
	<?php echo $message; ?>
	</td></tr></table><br>
<?php } ?>

</center>

<br>
<br>

</body>
</html>

<!-- END PHP TEMPLATE -->