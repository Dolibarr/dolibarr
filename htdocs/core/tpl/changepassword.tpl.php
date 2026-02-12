<?php
/* Copyright (C) 2026		Eric Seigne			<eric.seigne@cap-rel.fr>
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

// Template for change password page (logged user)

if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dol_url_root
 * @var string $title
 * @var string $urllogo
 */
'
@phan-var-force string $dol_url_root
@phan-var-force string $title
@phan-var-force string $urllogo
@phan-var-force User $user
';

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

$titleofpage = $langs->trans('ChangePassword');

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
<!-- BEGIN PHP TEMPLATE CHANGEPASSWORD.TPL.PHP -->

<body class="body bodylogin"<?php print !getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background-image: url(\''.DOL_URL_ROOT.'/viewimage.php?cache=1&noalt=1&modulepart=mycompany&file='.urlencode('logos/' . getDolGlobalString('MAIN_LOGIN_BACKGROUND')).'\')"'; ?>>

<?php if (empty($conf->dol_use_jmobile)) { ?>
<script>
$(document).ready(function () {
	$('#oldpassword').focus();
});
</script>
<?php } ?>

<div class="login_center center"<?php
if (!getDolGlobalString('ADD_UNSPLASH_LOGIN_BACKGROUND')) {
	$backstyle = 'background: linear-gradient('.($conf->browser->layout == 'phone' ? '0deg' : '4deg').', var(--colorbackbody) 52%, rgb('.$colorbackhmenu1.') 52.1%);';
	$backstyle = getDolGlobalString('MAIN_LOGIN_BACKGROUND_STYLE', $backstyle);
	print !getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? ' style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; '.$backstyle.'"' : '';
}
?>>
<div class="login_vertical_align">

<form id="changepassword" name="changepassword" method="POST" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="update">


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

<div class="tagtable centpercent" title="<?php echo dol_escape_htmltag($langs->trans('ChangePassword')); ?>">

<!-- Login (info) -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<span class="fa fa-user"></span>
<input type="text" id="login" name="login" class="flat input-icon-user minwidth150" value="<?php echo dol_escape_htmltag($user->login); ?>" disabled />
</div>
</div>

<!-- Old password -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<span class="fa fa-key"></span>
<input type="password" maxlength="128" placeholder="<?php echo $langs->trans("OldPassword"); ?>" id="oldpassword" name="oldpassword" class="flat input-icon-password minwidth150" value="" tabindex="1" autocomplete="current-password" />
</div>
</div>

<!-- New password -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<span class="fa fa-lock"></span>
<input type="password" maxlength="128" placeholder="<?php echo $langs->trans("NewPassword"); ?>" id="newpassword" name="newpassword" class="flat input-icon-password minwidth150" value="" tabindex="2" autocomplete="new-password" />
</div>
</div>

<!-- Confirm password -->
<div class="trinputlogin">
<div class="tagtd nowraponall center valignmiddle tdinputlogin">
<span class="fa fa-lock"></span>
<input type="password" maxlength="128" placeholder="<?php echo $langs->trans("ConfirmPassword"); ?>" id="confirmpassword" name="confirmpassword" class="flat input-icon-password minwidth150" value="" tabindex="3" autocomplete="new-password" />
</div>
</div>

</div>

</div> <!-- end div login_right -->

</div> <!-- end div login_line1 -->


<div id="login_line2" style="clear: both">

<!-- Button "Change password" -->
<br><input type="submit" class="butAction butActionLogin noborderfocus small" id="button_changepassword" name="button_changepassword" value="<?php echo $langs->trans('ChangePassword'); ?>" tabindex="4" />

<?php
if (empty($user->force_pass_change)) {
	?>
<br>
<div class="center" style="margin-top: 15px;">
	<?php
	print '<a class="alogin" href="'.$dol_url_root.'/index.php">'.$langs->trans('BackToApplication').'</a>';
	?>
</div>
	<?php
}
?>

</div>

</div>

</form>


<?php
print '<div class="center login_main_home divpasswordmessagedesc paddingtopbottom'.(!getDolGlobalString('MAIN_LOGIN_BACKGROUND') ? '' : ' backgroundsemitransparent boxshadow').'" style="max-width: 70%">';
print '<span class="passwordmessagedesc opacitymedium">';
print $langs->trans('ChangePasswordDesc');
print '</span>';
print '</div>';

print "\n".'<br>'."\n";


// Show messages
if (!empty($conf->use_javascript_ajax)) {
	print '<script>
		$(document).ready(function() {
			$(".jnotify-container").addClass("jnotify-container-login");
		});
	</script>';
}
?>


<!-- Common footer is not used for changepassword page, this is same than footer but inside changepassword tpl -->

<?php

print getDolGlobalString('MAIN_HTML_FOOTER');

?>


</div>
</div>	<!-- end of center -->


</body>
</html>
<!-- END PHP TEMPLATE -->
