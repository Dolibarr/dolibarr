<?php
/* Copyright (C) 2022 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

// Page called to validate a password change
// To show this page, we need parameters: setnewpassword=1&username=...&passworduidhash=...

if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

// DDOS protection
$size = (int) $_SERVER['CONTENT_LENGTH'];
if ($size > 10000) {
	$langs->loadLangs(array("errors", "install"));
	httponly_accessforbidden('<center>'.$langs->trans("ErrorRequestTooLarge").'<br><a href="'.DOL_URL_ROOT.'">'.$langs->trans("ClickHereToGoToApp").'</a></center>', 413, 1);
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

header('Cache-Control: Public, must-revalidate');

if (GETPOST('dol_hide_topmenu')) {
	$conf->dol_hide_topmenu = 1;
}
if (GETPOST('dol_hide_leftmenu')) {
	$conf->dol_hide_leftmenu = 1;
}
if (GETPOST('dol_optimize_smallscreen')) {
	$conf->dol_optimize_smallscreen = 1;
}
if (GETPOST('dol_no_mouse_hover')) {
	$conf->dol_no_mouse_hover = 1;
}
if (GETPOST('dol_use_jmobile')) {
	$conf->dol_use_jmobile = 1;
}

// If we force to use jmobile, then we reenable javascript
if (!empty($conf->dol_use_jmobile)) {
	$conf->use_javascript_ajax = 1;
}

$php_self = $_SERVER['PHP_SELF'];
$php_self .= dol_escape_htmltag($_SERVER["QUERY_STRING"]) ? '?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]) : '';
$php_self = str_replace('action=validatenewpassword', '', $php_self);

$titleofpage = $langs->trans('ResetPassword');

// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
$arrayofjs = array();

$disablenofollow = 1;
if (!preg_match('/'.constant('DOL_APPLICATION_TITLE').'/', $title)) {
	$disablenofollow = 0;
}
if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	$disablenofollow = 0;
}

top_htmlhead('', $titleofpage, 0, 0, $arrayofjs, array(), 1, $disablenofollow);


$colorbackhmenu1 = '60,70,100'; // topmenu
if (!isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
	$conf->global->THEME_ELDY_TOPMENU_BACK1 = $colorbackhmenu1;
}
$colorbackhmenu1 = getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED') ? getDolUserString('THEME_ELDY_TOPMENU_BACK1', $colorbackhmenu1) : getDolGlobalString('THEME_ELDY_TOPMENU_BACK1', $colorbackhmenu1);
$colorbackhmenu1 = implode(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'


$edituser = new User($db);


// Validate parameters
if ($setnewpassword && $username && $passworduidhash) {
	$result = $edituser->fetch(0, $username);
	if ($result < 0) {
		$message = '<div class="error">'.dol_escape_htmltag($langs->trans("ErrorTechnicalError")).'</div>';
	} else {
		global $conf;

		//print $edituser->pass_temp.'-'.$edituser->id.'-'.$conf->file->instance_unique_id.' '.$passworduidhash;
		if ($edituser->pass_temp && dol_verifyHash($edituser->pass_temp.'-'.$edituser->id.'-'.$conf->file->instance_unique_id, $passworduidhash)) {
			// Clear session
			unset($_SESSION['dol_login']);

			// Parameters to reset the user are validated
		} else {
			$langs->load("errors");
			$message = '<div class="error">'.$langs->trans("ErrorFailedToValidatePasswordReset").'</div>';
		}
	}
} else {
	$langs->load("errors");
	$message = '<div class="error">'.$langs->trans("ErrorFailedToValidatePasswordReset").'</div>';
}


?>
<!-- BEGIN PHP TEMPLATE PASSWORDRESET.TPL.PHP -->

<body class="body bodylogin"<?php print !getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: url(\''.DOL_URL_ROOT.'/viewimage.php?cache=1&noalt=1&modulepart=mycompany&file='.urlencode('logos/' . getDolGlobalString('MAIN_LOGIN_BACKGROUND')).'\')"'; ?>>

<?php if (empty($conf->dol_use_jmobile)) { ?>
<script>
$(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) {
		?>$('#<?php echo $focus_element; ?>').focus(); <?php
	} ?>		// Warning to use this only on visible element
});
</script>
<?php } ?>


<div class="login_center center"<?php
if (!getDolGlobalString('ADD_UNSPLASH_LOGIN_BACKGROUND')) {
		$backstyle = 'background: linear-gradient('.($conf->browser->layout == 'phone' ? '0deg' : '4deg').', rgb(240,240,240) 52%, rgb('.$colorbackhmenu1.') 52.1%);';
		// old style:  $backstyle = 'background-image: linear-gradient(rgb('.$colorbackhmenu1.',0.3), rgb(240,240,240));';
		$backstyle = getDolGlobalString('MAIN_LOGIN_BACKGROUND_STYLE', $backstyle);
		print !getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; '.$backstyle.'"' : '';
}
?>>
<div class="login_vertical_align">

<form id="login" name="login" method="POST" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="buildnewpassword">


<!-- Title with version -->
<div class="login_table_title center" title="<?php echo dol_escape_htmltag($title); ?>">
<?php
if (!empty($disablenofollow)) {
	echo '<a class="login_table_title" href="https://www.dolibarr.org" target="_blank" rel="noopener noreferrer external">';
}
echo dol_escape_htmltag($title);
if (!empty($disablenofollow)) {
	echo '</a>';
}
?>
</div>



<div class="login_table">

<div id="login_line1">

<div id="login_left">
<img alt="" title="" src="<?php echo $urllogo; ?>" id="img_logo" />
</div>

<br>

<div id="login_right">

<div class="tagtable centpercent" title="Login pass" >

<!-- New pass 1 -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<!-- <span class="span-icon-user">-->
<span class="fa fa-user"></span>
<input type="text" maxlength="255" placeholder="<?php echo $langs->trans("NewPassword"); ?>" <?php echo $disabled; ?> id="newpass1" name="newpass1" class="flat input-icon-user minwidth150" value="<?php echo dol_escape_htmltag($newpass1); ?>" tabindex="1" autofocus />
</div>
</div>
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<!-- <span class="span-icon-user">-->
<span class="fa fa-user"></span>
<input type="text" maxlength="255" placeholder="<?php echo $langs->trans("PasswordRetype"); ?>" <?php echo $disabled; ?> id="newpass2" name="newpass2" class="flat input-icon-user minwidth150" value="<?php echo dol_escape_htmltag($newpass2); ?>" tabindex="1" />
</div>
</div>


<?php
$captcha = 0;
if (!empty($captcha)) {
	// Add a variable param to force not using cache (jmobile)
	$php_self = preg_replace('/[&\?]time=(\d+)/', '', $php_self); // Remove param time
	if (preg_match('/\?/', $php_self)) {
		$php_self .= '&time='.dol_print_date(dol_now(), 'dayhourlog');
	} else {
		$php_self .= '?time='.dol_print_date(dol_now(), 'dayhourlog');
	}
	// TODO: provide accessible captcha variants?>
	<!-- Captcha -->
	<div class="trinputlogin">
	<div class="tagtd tdinputlogin nowrap none valignmiddle">

	<span class="fa fa-unlock"></span>
	<span class="nofa inline-block">
	<input id="securitycode" placeholder="<?php echo $langs->trans("SecurityCode"); ?>" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" autocomplete="off" />
	</span>
	<span class="nowrap inline-block">
	<img class="inline-block valignmiddle" src="<?php echo DOL_URL_ROOT ?>/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />
	<a class="inline-block valignmiddle" href="<?php echo $php_self; ?>" tabindex="4"><?php echo $captcha_refresh; ?></a>
	</span>

	</div></div>
	<?php
}

if (!empty($morelogincontent)) {
	if (is_array($morelogincontent)) {
		foreach ($morelogincontent as $format => $option) {
			if ($format == 'table') {
				echo '<!-- Option by hook -->';
				echo $option;
			}
		}
	} else {
		echo '<!-- Option by hook -->';
		echo $morelogincontent;
	}
}
?>

</div>

</div> <!-- end div login_right -->

</div> <!-- end div login_line1 -->


<div id="login_line2" style="clear: both">

<!-- Button "Regenerate and Send password" -->
<br><input type="submit" <?php echo $disabled; ?> class="button small" name="button_password" value="<?php echo $langs->trans('Save'); ?>" tabindex="4" />

<br>
<div class="center" style="margin-top: 15px;">
	<?php
	$moreparam = '';
	if (!empty($conf->dol_hide_topmenu)) {
		$moreparam .= (strpos($moreparam, '?') === false ? '?' : '&').'dol_hide_topmenu='.$conf->dol_hide_topmenu;
	}
	if (!empty($conf->dol_hide_leftmenu)) {
		$moreparam .= (strpos($moreparam, '?') === false ? '?' : '&').'dol_hide_leftmenu='.$conf->dol_hide_leftmenu;
	}
	if (!empty($conf->dol_no_mouse_hover)) {
		$moreparam .= (strpos($moreparam, '?') === false ? '?' : '&').'dol_no_mouse_hover='.$conf->dol_no_mouse_hover;
	}
	if (!empty($conf->dol_use_jmobile)) {
		$moreparam .= (strpos($moreparam, '?') === false ? '?' : '&').'dol_use_jmobile='.$conf->dol_use_jmobile;
	}

	print '<a class="alogin" href="'.$dol_url_root.'/index.php'.$moreparam.'">'.$langs->trans('BackToLoginPage').'</a>';
	?>
</div>

</div>

</div>

</form>


<?php
if ($mode == 'dolibarr' || !$disabled) {
	if (empty($message)) {
		print '<div class="center login_main_home divpasswordmessagedesc paddingtopbottom'.(!getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' backgroundsemitransparent boxshadow').'" style="max-width: 70%">';
		print '<span class="passwordmessagedesc opacitymedium">';
		print $langs->trans('EnterNewPasswordHere');
		print '</span>';
		print '</div>';
	}
} else {
	print '<div class="center login_main_home divpasswordmessagedesc paddingtopbottom'.(!getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' backgroundsemitransparent boxshadow').'" style="max-width: 70%">';
	print '<div class="warning center">';
	print $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode);
	print '</div>';
	print '</div>';
}
?>


<br>

<?php if (!empty($message)) { ?>
	<div class="center login_main_message">
	<?php dol_htmloutput_mesg($message, [], '', 1); ?>
	</div>
<?php } ?>


<!-- Common footer is not used for passwordforgotten page, this is same than footer but inside passwordforgotten tpl -->

<?php
if (getDolGlobalString('MAIN_HTML_FOOTER')) {
	print $conf->global->MAIN_HTML_FOOTER;
}

if (!empty($morelogincontent) && is_array($morelogincontent)) {
	foreach ($morelogincontent as $format => $option) {
		if ($format == 'js') {
			echo "\n".'<!-- Javascript by hook -->';
			echo $option."\n";
		}
	}
} elseif (!empty($moreloginextracontent)) {
	echo '<!-- Javascript by hook -->';
	echo $moreloginextracontent;
}

// Google Analytics
// TODO Remove this, and add content into hook getPasswordForgottenPageExtraOptions() instead
if (isModEnabled('google') && getDolGlobalString('MAIN_GOOGLE_AN_ID')) {
	$tmptagarray = explode(',', getDolGlobalString('MAIN_GOOGLE_AN_ID'));
	foreach ($tmptagarray as $tmptag) {
		print "\n";
		print "<!-- JS CODE TO ENABLE for google analtics tag -->\n";
		print "
					<!-- Global site tag (gtag.js) - Google Analytics -->
					<script async src=\"https://www.googletagmanager.com/gtag/js?id=".trim($tmptag)."\"></script>
					<script>
					window.dataLayer = window.dataLayer || [];
					function gtag(){dataLayer.push(arguments);}
					gtag('js', new Date());

					gtag('config', '".trim($tmptag)."');
					</script>";
		print "\n";
	}
}

// TODO Replace this with a hook
// Google Adsense (need Google module)
if (isModEnabled('google') && getDolGlobalString('MAIN_GOOGLE_AD_CLIENT') && getDolGlobalString('MAIN_GOOGLE_AD_SLOT')) {
	if (empty($conf->dol_use_jmobile)) {
		?>
	<div class="center"><br>
		<script><!--
			google_ad_client = "<?php echo $conf->global->MAIN_GOOGLE_AD_CLIENT ?>";
			google_ad_slot = "<?php echo $conf->global->MAIN_GOOGLE_AD_SLOT ?>";
			google_ad_width = <?php echo $conf->global->MAIN_GOOGLE_AD_WIDTH ?>;
			google_ad_height = <?php echo $conf->global->MAIN_GOOGLE_AD_HEIGHT ?>;
			//-->
		</script>
		<script	src="//pagead2.googlesyndication.com/pagead/show_ads.js"></script>
	</div>
		<?php
	}
}
?>


</div>
</div>	<!-- end of center -->


</body>
</html>
<!-- END PHP TEMPLATE -->
