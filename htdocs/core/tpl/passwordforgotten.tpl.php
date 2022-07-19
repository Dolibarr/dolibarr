<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

// DDOS protection
$size = (int) $_SERVER['CONTENT_LENGTH'];
if ($size > 10000) {
	http_response_code(413);
	$langs->loadLangs(array("errors", "install"));
	accessforbidden('<center>'.$langs->trans("ErrorRequestTooLarge").'<br><a href="'.DOL_URL_ROOT.'">'.$langs->trans("ClickHereToGoToApp").'</a></center>', 0, 0, 1);
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);

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

$titleofpage = $langs->trans('SendNewPassword');

// Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second
$arrayofjs = array();

$disablenofollow = 1;
if (!preg_match('/'.constant('DOL_APPLICATION_TITLE').'/', $title)) {
	$disablenofollow = 0;
}
if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	$disablenofollow = 0;
}

print top_htmlhead('', $titleofpage, 0, 0, $arrayofjs, array(), 1, $disablenofollow);


$colorbackhmenu1 = '60,70,100'; // topmenu
if (!isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
	$conf->global->THEME_ELDY_TOPMENU_BACK1 = $colorbackhmenu1;
}
$colorbackhmenu1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'

?>
<!-- BEGIN PHP TEMPLATE PASSWORDFORGOTTEN.TPL.PHP -->

<body class="body bodylogin"<?php print empty($conf->global->MAIN_LOGIN_BACKGROUND) ? '' : ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: url(\''.DOL_URL_ROOT.'/viewimage.php?cache=1&noalt=1&modulepart=mycompany&file='.urlencode('logos/'.$conf->global->MAIN_LOGIN_BACKGROUND).'\')"'; ?>>

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


<div class="login_center center"<?php print empty($conf->global->MAIN_LOGIN_BACKGROUND) ? ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: linear-gradient(rgb('.$colorbackhmenu1.',0.3), rgb(240,240,240));"' : '' ?>>
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

<!-- Login -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<!-- <span class="span-icon-user">-->
<span class="fa fa-user"></span>
<input type="text" maxlength="255" placeholder="<?php echo $langs->trans("Login"); ?>" <?php echo $disabled; ?> id="username" name="username" class="flat input-icon-user minwidth150" value="<?php echo dol_escape_htmltag($username); ?>" tabindex="1" />
</div>
</div>

<?php
if (!empty($captcha)) {
	// Add a variable param to force not using cache (jmobile)
	$php_self = preg_replace('/[&\?]time=(\d+)/', '', $php_self); // Remove param time
	if (preg_match('/\?/', $php_self)) {
		$php_self .= '&time='.dol_print_date(dol_now(), 'dayhourlog');
	} else {
		$php_self .= '?time='.dol_print_date(dol_now(), 'dayhourlog');
	}
	// TODO: provide accessible captcha variants
	?>
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
<br><input type="submit" <?php echo $disabled; ?> class="button small" name="button_password" value="<?php echo $langs->trans('SendNewPassword'); ?>" tabindex="4" />

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


<div class="center login_main_home divpasswordmessagedesc paddingtopbottom<?php echo empty($conf->global->MAIN_LOGIN_BACKGROUND) ? '' : ' backgroundsemitransparent boxshadow'; ?>" style="max-width: 70%">
<?php if ($mode == 'dolibarr' || !$disabled) { ?>
	<span class="passwordmessagedesc">
	<?php echo $langs->trans('SendNewPasswordDesc'); ?>
	</span>
<?php } else { ?>
	<div class="warning center">
	<?php echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode); ?>
	</div>
<?php } ?>
</div>


<br>

<?php if (!empty($message)) { ?>
	<div class="center login_main_message">
	<?php echo dol_htmloutput_mesg($message, '', '', 1); ?>
	</div>
<?php } ?>


<!-- Common footer is not used for passwordforgotten page, this is same than footer but inside passwordforgotten tpl -->

<?php
if (!empty($conf->global->MAIN_HTML_FOOTER)) {
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
// TODO Add a hook here
if (!empty($conf->google->enabled) && !empty($conf->global->MAIN_GOOGLE_AN_ID)) {
	$tmptagarray = explode(',', $conf->global->MAIN_GOOGLE_AN_ID);
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
if (!empty($conf->google->enabled) && !empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && !empty($conf->global->MAIN_GOOGLE_AD_SLOT)) {
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
