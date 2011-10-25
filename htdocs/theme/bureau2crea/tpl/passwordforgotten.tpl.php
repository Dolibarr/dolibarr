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

<!-- BEGIN PHP TEMPLATE -->

<html>
<head>
<meta name="robots" content="noindex,nofollow">
<title>Dolibarr Authentification</title>

<link rel="stylesheet" type="text/css" href="<?php echo $conf_css; ?>">


<script type="text/javascript">
function donnefocus() {
	document.getElementById('<?php echo $focus_element; ?>').focus();
}
</script>

</head>

<body class="body" onLoad="donnefocus();">
<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="buildnewpassword">

<div id="infoVersion"><?php echo $title; ?></div>

<div id="logoBox">
  <img alt="Logo" title="" src="<?php echo $urllogo; ?>" />
</div>

<div id="parameterBox">
	<div id="logBox"><strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong><input type="text" <?php echo $disabled; ?> id="username" name="username" class="flat" size="15" maxlength="25" value="<?php echo $login; ?>" tabindex="1" /></div>

	<?php if ($select_entity) { ?>
        <div><?php echo $langs->trans('Entity'); ?> &nbsp;
        <?php echo $select_entity; ?>
        </div>
    <?php } ?>

    <?php if ($captcha) { ?>
        <div class="captchaBox">
            <strong><label><?php echo $langs->trans('SecurityCode'); ?></label></strong>
            <input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="3">
        </div>
        <div class="captchaImg">
            <img src="<?php echo $dol_url_root.'/core/antispamimage.php'; ?>" border="0" width="128" height="36" id="captcha">
            <a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a>
        </div>

    <?php } ?>


    <div id="connectionLine">
        <input id="password" type="submit" <?php echo $disabled; ?> class="button" name="password" value="<?php echo $langs->trans('SendNewPassword'); ?>" tabindex="4" />
    </div>
</div>

</form>

<div class="other">
<?php if ($mode == 'dolibarr' || ! $disabled) { ?>
	<font style="font-size: 12px;">
	<?php echo $langs->trans('SendNewPasswordDesc'); ?>
	</font>
<?php }else{ ?>
	<div class="warning" align="center">
	<?php echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode); ?>
	</div>
<?php } ?>
</div>

<br>

<?php if ($message) { ?>
	<div class="other">
	<?php echo $message; ?>
	</div>
<?php } ?>

<div class="other">
<a href="<?php echo $dol_url_root; ?>/">
	<?php echo $langs->trans('BackToLoginPage'); ?>
</a>
</div>

</body>
</html>

<!-- END PHP TEMPLATE -->