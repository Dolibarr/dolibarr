<?php
/* Copyright (C) 2009-2012 Regis Houssin <regis.houssin@capnetworks.com>
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
// CSS forced by modules (relative url starting with /)
if (isset($conf->modules_parts['css']))
{
	$arraycss=(array) $conf->modules_parts['css'];
	foreach($arraycss as $modcss => $filescss)
	{
		$filescss=(array) $filescss;	// To be sure filecss is an array
		foreach($filescss as $cssfile)
		{
			// cssfile is a relative path
			print '<link rel="stylesheet" type="text/css" title="default" href="'.dol_buildpath($cssfile,1);
			// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters, so browser cache is not used.
			if (!preg_match('/\.css$/i',$cssfile)) print $themeparam;
			print '"><!-- Added by module '.$modcss. '-->'."\n";
		}
	}
}
// JQuery. Must be before other includes
$ext='.js';
print '<!-- Includes JS for JQuery -->'."\n";
if (constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min'.$ext.'"></script>'."\n";
else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-latest.min'.$ext.'"></script>'."\n";
print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/core/js/dst.js"></script>'."\n";
print '<link rel="stylesheet" type="text/css" href="'.dol_escape_htmltag($conf_css).'" />
<style type="text/css">
<!--
#login {
	margin-top: 70px;
	margin-bottom: 30px;
}
.login_table {
	width: 512px;
	border: 1px solid #C0C0C0;
	background: #F0F0F0 url('.$login_background.') repeat-x;
}
-->
</style>'."\n";
if (! empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER;
print '<!-- HTTP_USER_AGENT = '.$_SERVER['HTTP_USER_AGENT'].' -->
</head>';

?>

<body class="body">

<!-- Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second -->
<script type="text/javascript">
$(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) { ?>$('#<?php echo $focus_element; ?>').focus(); <?php } ?>		// Warning to use this only on visible element
});
</script>

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
<input type="hidden" name="dol_hide_topmenu" id="dol_hide_topmenu" value="" />
<input type="hidden" name="dol_hide_leftmenu" id="dol_hide_leftmenu" value="" />

<div id="infoVersion"><?php echo $title; ?></div>

<div id="logoBox">
  <img alt="Logo" title="" src="<?php echo $urllogo; ?>" />
</div>

<div id="parameterBox">

<div id="logBox"><strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong><input type="text" id="username" name="username" class="flat" size="15" maxlength="40" value="<?php echo dol_escape_htmltag($login); ?>" tabindex="1" /></div>
<div id="passBox"><strong><label for="password"><?php echo $langs->trans('Password'); ?></label></strong><input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="<?php echo dol_escape_htmltag($password); ?>" tabindex="2" /></div>

	<?php
	if (! empty($hookmanager->resArray['options'])) {
		foreach ($hookmanager->resArray['options'] as $format => $option)
		{
			if ($format == 'div') {
				echo '<!-- Option by hook -->';
				echo $option;
			}
		}
	}
	?>

	<?php if ($captcha) { ?>
		<div class="captchaBox">
        	<strong><label><?php echo $langs->trans('SecurityCode'); ?></label></strong>
			<input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4" />
        </div>
        <div class="captchaImg">
			<img src="<?php echo DOL_URL_ROOT ?>/core/antispamimage.php" border="0" width="80" height="32" id="captcha" />
			<a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a>
		</div>
	<?php } ?>

<div id="connectionLine">
  <input type="submit" class="button" value="&nbsp; <?php echo $langs->trans('Connection'); ?> &nbsp;" tabindex="5" />
</div>

<?php
	if ($forgetpasslink || $helpcenterlink) {
		echo '<div class="other">';
		if ($forgetpasslink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php">(';
			echo $langs->trans('PasswordForgotten');
			if (! $helpcenterlink) {
				echo ')';
			}
			echo '</a>';
		}

		if ($helpcenterlink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/support/index.php" target="_blank">';
			if ($forgetpasslink) {
				echo '&nbsp;-&nbsp;';
			} else {
				echo '(';
			}
			echo $langs->trans('NeedHelpCenter').')</a>';
		}
		echo '</div>';
	}
?>

</div>

<?php if ($main_home) { ?>
	<div id="infoLogin">
	<?php echo $main_home; ?>
	</div>
<?php } ?>

<?php if ($_SESSION['dol_loginmesg']) { ?>
	<div class="error">
	<?php echo $_SESSION['dol_loginmesg']; ?>
	</div>
<?php } ?>

	<?php
	if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
	{
	?>
		<div align="center">
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
	<?php } ?>
</form>




<!-- authentication mode = <?php echo $main_authentication ?> -->
<!-- cookie name used for this session = <?php echo $session_name ?> -->
<!-- urlfrom in this session = <?php echo $_SESSION["urlfrom"] ?> -->

<?php if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER; ?>

</body>
</html>

<!-- END PHP TEMPLATE -->
