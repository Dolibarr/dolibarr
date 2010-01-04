<?php
/* Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
<meta name="robots" content="noindex,nofollow">
<title><?php echo $langs->trans('Login'); ?></title>

<link rel="stylesheet" type="text/css" href="<?php echo $conf->css; ?>">

<style type="text/css">
<!--
#login {
	margin-top: 70px;
	margin-bottom: 30px;
	text-align: center;
	font: 12px arial,helvetica;
}
#login table {
	width: 498px;
	border: 1px solid #C0C0C0;
	background: #F0F0F0 url(<?php echo $login_background; ?>) repeat-x;
	font-size: 12px;
}
-->
</style>

<script type="text/javascript">
function donnefocus() {
	document.getElementById('<?php echo $focus_element; ?>').focus();
	}
</script>

<?php
	if ($main_html_header)
		echo $main_html_header;
?>

<!-- HTTP_USER_AGENT = <?php echo $_SERVER['HTTP_USER_AGENT']; ?> -->
</head>

<body class="body" onload="donnefocus();">
<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="loginfunction" value="loginfunction" />

<table class="login" summary="<?php echo $title; ?>" cellpadding="0" cellspacing="0" border="0" align="center">
<tr class="vmenu"><td align="center"><?php echo $title; ?></td></tr>
</table>
<br>

<table class="login" summary="Login area" cellpadding="2" align="center">

<tr><td colspan="3">&nbsp;</td></tr>

<tr>

<td valign="bottom"> &nbsp; <b><?php echo $langs->trans('Login'); ?></b> &nbsp; </td>
<td valign="bottom" nowrap="nowrap">
<input type="text" id="username" name="username" class="flat" size="15" maxlength="25" value="<?php echo $login; ?>" tabindex="1" /></td>

<td rowspan="<?php echo $rowspan; ?>" align="center" valign="top">
<img alt="Logo" title="" src="<?php echo $urllogo; ?>" />

</td>
</tr>

<tr><td valign="top" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('Password'); ?></b> &nbsp; </td>
<td valign="top" nowrap="nowrap">
<input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="<?php echo $password; ?>" tabindex="2">
</td></tr>

	<?php if ($select_entity) { ?>
		<tr><td valign="top" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('Entity'); ?></b> &nbsp; </td>
		<td valign="top" nowrap="nowrap">
		<?php echo $select_entity; ?>
		</td></tr>
	<?php } ?>

	<?php if ($captcha) { ?>
		<tr><td valign="middle" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('SecurityCode'); ?></b></td>
		<td valign="top" nowrap="nowrap" align="left" class="none">

		<table style="width: 100px;"><tr>
		<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4"></td>
		<td><img src="<?php echo $dol_url_root; ?>/lib/antispamimage.php" border="0" width="128" height="36"></td>
		<td><a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a></td>
		</tr></table>

		</td></tr>
	<?php } ?>

<tr><td colspan="3">&nbsp;</td></tr>

<tr><td colspan="3" style="text-align:center;"><br>
<input type="submit" class="button" value="&nbsp; <?php echo $langs->trans('Connection'); ?> &nbsp;" tabindex="5" />
</td></tr>

<?php
	if ($forgetpasslink || $helpcenterlink) {
		echo '<tr><td colspan="3" align="center">';
		if ($forgetpasslink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.$dol_url_root.'/user/passwordforgotten.php">(';
			echo $langs->trans('PasswordForgotten');
			if (! $helpcenterlink) {
				echo ')';
			}
			echo '</a>';
		}

		if ($helpcenterlink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.$dol_url_root.'/support/index.php" target="_blank">';
			if ($forgetpasslink) {
				echo '&nbsp;-&nbsp;';
			} else {
				echo '(';
			}
			echo $langs->trans('NeedHelpCenter').')</a>';
		}
		echo '</td></tr>';
	}
?>

</table>

</form>

	<?php if ($_SESSION['dol_loginmesg']) { ?>
		<center><table width="60%"><tr><td align="center"><div class="error">
		<?php echo $_SESSION['dol_loginmesg']; ?>
		</div></td></tr></table></center>
	<?php } ?>

	<?php if ($main_home) { ?>
		<center><table summary="info" cellpadding="0" cellspacing="0" border="0" align="center" width="750">
		<tr><td align="center">
		<?php echo $main_home; ?>
		</td></tr></table></center><br>
	<?php } ?>

	<?php if ($main_google_ad_client) { ?>
		<div align="center">
		<?php include('google_ad.tpl.php'); ?>
		</div>
	<?php } ?>

<!-- authentication mode = {$main_authentication} -->
<!-- cookie name used for this session = {$session_name} -->
<!-- urlfrom in this session = {$smarty.session.urlfrom} -->

<?php
	if ($main_html_footer)
		echo $main_html_footer;
?>

</body>
</html>

<!-- END SMARTY TEMPLATE -->