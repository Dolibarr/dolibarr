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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!-- BEGIN PHP TEMPLATE -->

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $langs->trans('Password'); ?></title>
<meta name="robots" content="noindex,nofollow" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport"content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" />
<link type="text/css" rel="stylesheet" href="<?php echo $dol_url_root.'/theme/phones/iphone/theme/'.$theme.'/'.$theme.'.css.php'; ?>" />
<script type="text/javascript" src="<?php echo $dol_url_root.'/includes/iphone/iwebkit/Framework/javascript/functions.js'; ?>"></script>
</head>

<body>

<div id="topbar">
	<div id="title"><?php echo $langs->trans('Password'); ?></div>
	<div id="leftnav">
		<a href="<?php echo $dol_url_root; ?>/">
			<img alt="home" src="<?php echo $dol_url_root.'/theme/phones/iphone/theme/'.$theme.'/img/home.png'; ?>"/>
		</a>
	</div>
</div>

<div id="content">
	<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="action" value="buildnewpassword">
	
	<div align="center">
		<img src="<?php echo $dol_url_root.'/theme/phones/iphone/theme/'.$theme.'/thumbs/dolibarr.png'; ?>">
	</div>
	
	<br>
	
	<span class="graytitle"><?php echo $langs->trans('Identification'); ?></span>
	<ul class="pageitem">
		<li class="bigfield">
			<input placeholder="<?php echo $langs->trans('Login'); ?>" type="text" <?php echo $disabled; ?> id="username" name="username" value="<?php echo $login; ?>" />
		</li>
	</ul>
	
	<?php if ($select_entity) { ?>
	<span class="graytitle"><?php echo $langs->trans('Entity'); ?></span>
	<ul class="pageitem">
		<li class="select">
			<?php echo $select_entity; ?>
			<span class="arrow"></span>
        </li>
	</ul>
	<?php } ?>
	
	<?php if ($captcha) { ?>
	<span class="graytitle"><?php echo $langs->trans('SecurityCode'); ?></span>
	<ul class="pageitem">
		<li class="smallfield">
			<input placeholder="<?php echo $langs->trans('SecurityCode'); ?>" type="text" id="securitycode" name="code" />
			<img src="<?php echo $dol_url_root.'/lib/antispamimage.php'; ?>" border="0" width="128" height="36" />
		</li>
	</ul>
	<?php } ?>
	
	<ul class="pageitem">
		<li class="button">
			<input name="input Button" <?php echo $disabled; ?> type="submit" value="<?php echo $langs->trans('SendByMail'); ?>" />
		</li>
	</ul>
	
	</form>
</div>

<ul class="pageitem">
	<li class="textbox">
	<span class="header"><?php echo $langs->trans('Infos'); ?></span>
	<?php if ($mode == 'dolibarr' || ! $disabled) {
			echo $langs->trans('SendNewPasswordDesc');
		}else{
			echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode);
		} ?>
	</li>
</ul>

<?php if ($message) { ?>
	<script type="text/javascript" language="javascript">
		alert('<?php echo $message; ?>');
	</script>
<?php } ?>

</body>
</html>

<!-- END PHP TEMPLATE -->