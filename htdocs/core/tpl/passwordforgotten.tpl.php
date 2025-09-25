<?php
/* Copyright (C) 2009-2010  Regis Houssin 			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2024  Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

// Page to ask email for password forgotten

if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 *
 * @var string $action
 * @var string $captcha
 * @var string $disabled
 * @var string $dol_url_root
 * @var string $focus_element
 * @var string $mode
 * @var string $message
 * @var string $title
 * @var string $urllogo
 * @var string $user
 * @var string $username
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

// DDOS protection
$size = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($size > 10000) {
	$langs->loadLangs(array("errors", "install"));
	httponly_accessforbidden('<center>'.$langs->trans("ErrorRequestTooLarge").'<br><a href="'.DOL_URL_ROOT.'">'.$langs->trans("ClickHereToGoToApp").'</a></center>', 413, 1);
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/*
 * View
 */

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

$titleofpage = $langs->trans('SendNewPassword');

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

?>
<!-- BEGIN PHP TEMPLATE PASSWORDFORGOTTEN.TPL.PHP -->

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

<!-- Login -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<!-- <span class="span-icon-user">-->
<span class="fa fa-user"></span>
<input type="text" maxlength="255" placeholder="<?php echo $langs->trans("Login"); ?>" <?php echo $disabled; ?> id="username" name="username" class="flat input-icon-user minwidth150" value="<?php echo dol_escape_htmltag($username); ?>" tabindex="1" autocapitalize="off" autocomplete="on" spellcheck="false" autocorrect="off" />
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

	// List of directories where we can find captcha handlers
	$dirModCaptcha = array_merge(array('main' => '/core/modules/security/captcha/'), is_array($conf->modules_parts['captcha']) ? $conf->modules_parts['captcha'] : array());
	$fullpathclassfile = '';
	foreach ($dirModCaptcha as $dir) {
		$fullpathclassfile = dol_buildpath($dir."modCaptcha".ucfirst($captcha).'.class.php', 0, 2);
		if ($fullpathclassfile) {
			break;
		}
	}

	if ($fullpathclassfile) {
		include_once $fullpathclassfile;
		$captchaobj = null;

		// Charging the numbering class
		$classname = "modCaptcha".ucfirst($captcha);
		if (class_exists($classname)) {
			/** @var ModeleCaptcha $captchaobj */
			$captchaobj = new $classname($db, $conf, $langs, $user);
			'@phan-var-force ModeleCaptcha $captchaobj';

			if (is_object($captchaobj) && method_exists($captchaobj, 'getCaptchaCodeForForm')) {
				print $captchaobj->getCaptchaCodeForForm($php_self);  // @phan-suppress-current-line PhanUndeclaredMethod
			} else {
				print 'Error, the captcha handler '.get_class($captchaobj).' does not have any method getCaptchaCodeForForm()';
			}
		} else {
			print 'Error, the captcha handler class '.$classname.' was not found after the include';
		}
	} else {
		print 'Error, the captcha handler '.$captcha.' has no class file found modCaptcha'.ucfirst($captcha);
	}
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


<?php
if ($mode == 'dolibarr' || !$disabled) {
	if ($action != 'validatenewpassword') {
		print '<div class="center login_main_home divpasswordmessagedesc paddingtopbottom'.(!getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' backgroundsemitransparent boxshadow').'" style="max-width: 70%">';
		print '<span class="passwordmessagedesc opacitymedium">';
		print $langs->trans('SendNewPasswordDesc');
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

print "\n".'<br>'."\n";


//$conf->use_javascript_ajax = 0;

// Show error message if defined
if ($message) {
	if (!empty($conf->use_javascript_ajax)) {
		if (preg_match('/<!-- warning -->/', $message) || preg_match('/<div class="warning/', $message)) {	// if it contains this comment, this is a warning message
			$message = str_replace('<!-- warning -->', '', $message);
			$message = preg_replace('/<div class="[^"]*">/', '', $message);
			$message = preg_replace('/<\/div>/', '', $message);
			dol_htmloutput_mesg($message, array(), 'warning');
		} else {
			dol_htmloutput_mesg($message, array(), 'error');
		}
		print '<script>
			$(document).ready(function() {
				$(".jnotify-container").addClass("jnotify-container-login");
			});
		</script>';
	} else {
		?>
		<div class="center login_main_message">
		<?php
		dol_htmloutput_mesg($message, [], '', 1);
		?>
		</div>
		<?php
	}
}
?>

<!-- Common footer is not used for passwordforgotten page, this is same than footer but inside passwordforgotten tpl -->

<?php

print getDolGlobalString('MAIN_HTML_FOOTER');

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

// Can add extra content
$parameters = array();
$dummyobject = new stdClass();
$result = $hookmanager->executeHooks('getPasswordForgottenPageExtraContent', $parameters, $dummyobject, $action);
print $hookmanager->resPrint;

?>


</div>
</div>	<!-- end of center -->


</body>
</html>
<!-- END PHP TEMPLATE -->
