<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);

if (GETPOST('dol_hide_topmenu')) $conf->dol_use_jmobile=1;
if (GETPOST('dol_hide_leftmenu')) $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_optimize_smallscreen')) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover')) $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile')) $conf->dol_use_jmobile=1;

print top_htmlhead('',$langs->trans('Login').' '.$title);
?>
<!-- BEGIN PHP TEMPLATE PASSWORDFORGOTTEN.TPL.PHP -->

<body class="body">

<!-- Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second -->
<script type="text/javascript">
$(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) { ?>$('#<?php echo $focus_element; ?>').focus(); <?php } ?>		// Warning to use this only on visible element
});
</script>

<center>

<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="buildnewpassword">

<table class="login_table_title" summary="<?php echo dol_escape_htmltag($title); ?>" cellpadding="0" cellspacing="0" border="0" align="center">
<tr class="vmenu"><td align="center"><?php echo $title; ?></td></tr>
</table>
<br>

<div class="login_table">

<div id="login_line1">

<div id="login_left">

<table class="left" summary="Login pass" cellpadding="2">

<!-- Login -->
<tr>
<td valign="bottom" class="loginfield nowrap"><strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong></td>
<td valign="bottom" class="nowrap">
<input type="text" <?php echo $disabled; ?> id="username" name="username" class="flat" size="15" maxlength="40" value="<?php echo dol_escape_htmltag($login); ?>" tabindex="1" />
</td>
</tr>

<?php
if (! empty($hookmanager->resArray['options'])) {
	foreach ($hookmanager->resArray['options'] as $format => $option)
	{
		if ($format == 'table') {
			echo '<!-- Option by hook -->';
			echo $option;
		}
	}
}
?>

<?php if ($captcha) { ?>
	<!-- Captcha -->
	<tr><td valign="middle" class="loginfield nowrap"><strong><label for="securitycode"><?php echo $langs->trans('SecurityCode'); ?></label></strong></td>
	<td valign="top" class="nowrap none" align="left">

	<table class="login_table_securitycode" style="width: 100px;"><tr>
	<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4" /></td>
	<td><img src="<?php echo DOL_URL_ROOT ?>/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" /></td>
	<td><a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a></td>
	</tr></table>

	</td></tr>
<?php } ?>

</table>

</div> <!-- end div left -->

<div id="login_right">

<img alt="Logo" title="" src="<?php echo $urllogo; ?>" id="img_logo" />

</div>
</div>

<div id="login_line2" style="clear: both">

<!-- Button Send password -->
<br><input id="password" type="submit" <?php echo $disabled; ?> class="button" name="password" value="<?php echo $langs->trans('SendNewPassword'); ?>" tabindex="4" />

<br>
<div align="center" style="margin-top: 4px;">
	<?php
	$moreparam='';
	if (! empty($conf->dol_hide_topmenu))   $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_hide_topmenu='.$conf->dol_hide_topmenu;
	if (! empty($conf->dol_hide_leftmenu))  $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_hide_leftmenu='.$conf->dol_hide_leftmenu;
	if (! empty($conf->dol_no_mouse_hover)) $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_no_mouse_hover='.$conf->dol_no_mouse_hover;
	if (! empty($conf->dol_use_jmobile))    $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_use_jmobile='.$conf->dol_use_jmobile;

	print '<a style="color: #888888; font-size: 10px" href="'.$dol_url_root.'/index.php'.$moreparam.'">('.$langs->trans('BackToLoginPage').')</a>';
	?>
</div>

</div>

</div>

</form>


<center><div align="center" style="max-width: 680px; margin-left: 10px; margin-right: 10px;">
<?php if ($mode == 'dolibarr' || ! $disabled) { ?>
	<font style="font-size: 12px;">
	<?php echo $langs->trans('SendNewPasswordDesc'); ?>
	</font>
<?php }else{ ?>
	<div class="warning" align="center">
	<?php echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode); ?>
	</div>
<?php } ?>
</div></center>


<br>

<?php if ($message) { ?>
	<center><div align="center" style="max-width: 680px; margin-left: 10px; margin-right: 10px;"><div class="error">
	<?php echo $message; ?>
	</div></div></center>
<?php } ?>

</center>	<!-- end of center -->

<br>

</body>
</html>

<!-- END PHP TEMPLATE -->
