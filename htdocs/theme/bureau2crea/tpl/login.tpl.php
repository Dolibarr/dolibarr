<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis@dolibarr.fr>
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!-- <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> -->
<!-- <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> -->
<!-- <!DOCTYPE html> -->
<!-- Ce DTD est KO car inhibe document.body.scrollTop -->
<!-- <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> -->

<!-- BEGIN PHP TEMPLATE -->

<html>
<!-- <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr"> -->
<head>
<meta name="robots" content="noindex,nofollow" />
<title><?php echo $langs->trans('Login'); ?></title>
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/includes/jquery/js/jquery-latest.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $conf_css; ?>" />
<?php echo $conf->global->MAIN_HTML_HEADER ?>
<?php
$favicon=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/favicon.ico';
?>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $favicon; ?>" />
<!-- HTTP_USER_AGENT = <?php echo $_SERVER['HTTP_USER_AGENT']; ?> -->
</head>

<body class="body">

<script type="text/javascript">
jQuery(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) { ?>jQuery('#<?php echo $focus_element; ?>').focus(); <?php } ?>		// Warning to use this only on visible element
	// Detect and save TZ and DST
	var rightNow = new Date();
	var jan1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
	var temp = jan1.toGMTString();
	var jan2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
	var june1 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
	temp = june1.toGMTString();
	var june2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var daylight_time_offset = (june1 - june2) / (1000 * 60 * 60);
	var dst;
	if (std_time_offset == daylight_time_offset) {
	    dst = "0"; // daylight savings time is NOT observed
	} else {
	    dst = "1"; // daylight savings time is observed
	}
	jQuery('#tz').val(std_time_offset);   				  // returns TZ
	jQuery('#dst').val(dst);   							  // returns DST
	// Detect and save screen resolution
	jQuery('#screenwidth').val(jQuery(window).width());   // returns width of browser viewport
	jQuery('#screenheight').val(jQuery(window).height());   // returns width of browser viewport
});
</script>

<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="loginfunction" value="loginfunction" />
<!-- Add fields to send local user information -->
<input type="hidden" name="tz" id="tz" value="" />
<input type="hidden" name="dst" id="dst" value="" />
<input type="hidden" name="screenwidth" id="screenwidth" value="" />
<input type="hidden" name="screenheight" id="screenheight" value="" />

<div id="infoVersion"><?php echo $title; ?></div>

<div id="logoBox">
  <img alt="Logo" title="" src="<?php echo $urllogo; ?>" />
</div>

<div id="parameterBox">

<div id="logBox"><strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong><input type="text" id="username" name="username" class="flat" size="15" maxlength="40" value="<?php echo $login; ?>" tabindex="1" /></div>
<div id="passBox"><strong><label for="password"><?php echo $langs->trans('Password'); ?></label></strong><input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="<?php echo $password; ?>" tabindex="2" /></div>

	<?php if ($select_entity) { ?>
		<div><strong><?php echo $langs->trans('Entity'); ?></strong><?php echo $select_entity; ?></div>
	<?php } ?>

	<?php if ($captcha) { ?>
		<div class="captchaBox">
        	<strong><label><?php echo $langs->trans('SecurityCode'); ?></label></strong>
			<input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4" />
        </div>
        <div class="captchaImg">
			<img src="<?php echo DOL_URL_ROOT ?>/core/antispamimage.php" border="0" width="128" height="36" id="captcha" />
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

<?php echo $conf->global->MAIN_HTML_FOOTER; ?>

</body>
</html>

<!-- END PHP TEMPLATE -->