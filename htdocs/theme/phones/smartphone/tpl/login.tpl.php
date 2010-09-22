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
$smartphone->smartheader($title);
?>

<!-- START LOGIN SMARTPHONE TEMPLATE -->

<div id="topbar">
	<div id="title"><?php echo $title; ?></div>
</div>

<div id="content">
	<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
	<input type="hidden" name="loginfunction" value="loginfunction" />
	
	<div align="center">
		<img src="<?php echo $dol_url_root.'/theme/phones/smartphone/theme/'.$smartphone->theme.'/thumbs/dolibarr.png'; ?>">
	</div>
	
	<br>
	
	<span class="graytitle"><?php echo $langs->trans('Identification'); ?></span>
	<ul class="pageitem">
		<li class="bigfield">
			<input placeholder="<?php echo $langs->trans('Login'); ?>" type="text" id="username" name="username" value="<?php echo $login; ?>" />
		</li>
		
		<li class="bigfield">
			<input placeholder="<?php echo $langs->trans('Password'); ?>" type="password" id="password" name="password" value="<?php echo $password; ?>" />
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
			<input name="input Button" type="submit" value="<?php echo $langs->trans('Connection'); ?>" />
		</li>
	</ul>
	
	</form>
</div>

<?php if ($forgetpasslink || $helpcenterlink) { ?>
	<span class="graytitle"><?php echo $langs->trans('Tools'); ?></span>
	<ul class="pageitem">
	<?php if ($forgetpasslink) { ?>
		<li class="menu">
			<a href="<?php echo $dol_url_root.'/user/passwordforgotten.php'; ?>">
				<img alt="tools" src="<?php echo $dol_url_root.'/theme/phones/smartphone/theme/'.$smartphone->theme.'/thumbs/tools.png'; ?>" />
				<span class="name"><?php echo $langs->trans('PasswordForgotten'); ?></span>
				<span class="arrow"></span>
			</a>
		</li>
	<?php } ?>
	
	<?php if ($helpcenterlink) { ?>
		<li class="menu">
			<a href="<?php echo $dol_url_root.'/support/index.php'; ?>">
				<img alt="support" src="<?php echo $dol_url_root.'/theme/phones/smartphone/theme/'.$smartphone->theme.'/thumbs/support.png'; ?>" />
				<span class="name"><?php echo $langs->trans('NeedHelpCenter'); ?></span>
				<span class="arrow"></span>
			</a>
		</li>
	<?php } ?>
	</ul>
<?php } ?>

<?php if ($dol_loginmesg) { ?>
	<script type="text/javascript" language="javascript">
		alert('<?php echo $dol_loginmesg; ?>');
	</script>
<?php } ?>

<!-- END LOGIN SMARTPHONE TEMPLATE -->

<?php $smartphone->smartfooter(); ?>