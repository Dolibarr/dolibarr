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

if (GETPOST('dol_hide_topmenu')) $conf->dol_hide_topmenu=1;
if (GETPOST('dol_hide_leftmenu')) $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_optimize_smallscreen')) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover')) $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile')) $conf->dol_use_jmobile=1;

$arrayofjs=array('/core/js/dst.js');	// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
print top_htmlhead('',$langs->trans('Login').' '.$title,0,0,$arrayofjs);
?>
<!-- BEGIN PHP TEMPLATE LOGIN.TPL.PHP -->

<body class="body">

<script type="text/javascript">
$(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) { ?>$('#<?php echo $focus_element; ?>').focus(); <?php } ?>		// Warning to use this only on visible element
});
</script>

<center>

<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="loginfunction" value="loginfunction" />
<!-- Add fields to send local user information -->
<input type="hidden" name="tz" id="tz" value="" />
<input type="hidden" name="tz_string" id="tz_string" value="" />
<input type="hidden" name="dst_observed" id="dst_observed" value="" />
<input type="hidden" name="dst_first" id="dst_first" value="" />
<input type="hidden" name="dst_second" id="dst_second" value="" />
<input type="hidden" name="screenwidth" id="screenwidth" value="" />
<input type="hidden" name="screenheight" id="screenheight" value="" />
<input type="hidden" name="dol_hide_topmenu" id="dol_hide_topmenu" value="<?php echo $dol_hide_topmenu; ?>" />
<input type="hidden" name="dol_hide_leftmenu" id="dol_hide_leftmenu" value="<?php echo $dol_hide_leftmenu; ?>" />
<input type="hidden" name="dol_optimize_smallscreen" id="dol_optimize_smallscreen" value="<?php echo $dol_optimize_smallscreen; ?>" />
<input type="hidden" name="dol_no_mouse_hover" id="dol_no_mouse_hover" value="<?php echo $dol_no_mouse_hover; ?>" />
<input type="hidden" name="dol_use_jmobile" id="dol_use_jmobile" value="<?php echo $dol_use_jmobile; ?>" />

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
<td valign="bottom" class="loginfield"><strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong></td>
<td valign="bottom" class="nowrap">
<input type="text" id="username" name="username" class="flat" size="15" maxlength="40" value="<?php echo dol_escape_htmltag($login); ?>" tabindex="1" />
</td>
</tr>
<!-- Password -->
<tr><td valign="top" class="loginfield nowrap"><strong><label for="password"><?php echo $langs->trans('Password'); ?></label></strong></td>
<td valign="top" class="nowrap">
<input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="<?php echo dol_escape_htmltag($password); ?>" tabindex="2" autocomplete="off" />
</td></tr>
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
	<tr><td valign="middle" class="loginfield nowrap"><b><?php echo $langs->trans('SecurityCode'); ?></b></td>
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

<!-- Button Connection -->
<br><input type="submit" class="button" value="&nbsp; <?php echo $langs->trans('Connection'); ?> &nbsp;" tabindex="5" />

<?php
if ($forgetpasslink || $helpcenterlink)
{
	$moreparam='';
	if ($dol_hide_topmenu)   $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_hide_topmenu='.$dol_hide_topmenu;
	if ($dol_hide_leftmenu)  $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_hide_leftmenu='.$dol_hide_leftmenu;
	if ($dol_no_mouse_hover) $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_no_mouse_hover='.$dol_no_mouse_hover;
	if ($dol_use_jmobile)    $moreparam.=(strpos($moreparam,'?')===false?'?':'&').'dol_use_jmobile='.$dol_use_jmobile;

	echo '<br>';
	echo '<div align="center" style="margin-top: 4px;">';
	if ($forgetpasslink) {
		echo '<a class="alogin" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php'.$moreparam.'">(';
		echo $langs->trans('PasswordForgotten');
		if (! $helpcenterlink) echo ')';
		echo '</a>';
	}

	if ($forgetpasslink && $helpcenterlink) echo '&nbsp;-&nbsp;';

	if ($helpcenterlink) {
		$url=DOL_URL_ROOT.'/support/index.php'.$moreparam;
		if (! empty($conf->global->MAIN_HELPCENTER_LINKTOUSE)) $url=$conf->global->MAIN_HELPCENTER_LINKTOUSE;
		echo '<a class="alogin" href="'.dol_escape_htmltag($url).'" target="_blank">';
		if (! $forgetpasslink) echo '(';
		echo $langs->trans('NeedHelpCenter');
		echo ')</a>';
	}
	echo '</div>';
}
?>

</div>

</div>

</form>




<?php if (! empty($_SESSION['dol_loginmesg']))
{
?>
	<center><div align="center" style="max-width: 500px; margin-left: 10px; margin-right: 10px;"><div class="error">
	<?php echo $_SESSION['dol_loginmesg']; ?>
	</div></div></center>
<?php
}
?>

<?php if ($main_home)
{
?>
	<center><div align="center" class="login_main_home" style="max-width: 80%">
	<?php echo $main_home; ?>
	</div></center><br>
<?php
}
?>

<?php
if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
{
?>
	<div align="center"><br>
		<script type="text/javascript"><!--
			google_ad_client = "<?php echo $conf->global->MAIN_GOOGLE_AD_CLIENT ?>";
			google_ad_slot = "<?php echo $conf->global->MAIN_GOOGLE_AD_SLOT ?>";
			google_ad_width = <?php echo $conf->global->MAIN_GOOGLE_AD_WIDTH ?>;
			google_ad_height = <?php echo $conf->global->MAIN_GOOGLE_AD_HEIGHT ?>;
			//-->
		</script>
		<script type="text/javascript"
			src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
		</script>
	</div>
<?php
}
?>

<!-- authentication mode = <?php echo $main_authentication ?> -->
<!-- cookie name used for this session = <?php echo $session_name ?> -->
<!-- urlfrom in this session = <?php echo $_SESSION["urlfrom"] ?> -->

<?php if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER; ?>

</center>	<!-- end of center -->

</body>
</html>
<!-- END PHP TEMPLATE -->
