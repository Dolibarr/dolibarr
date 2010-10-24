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
$smartphone->smartheader();
?>

<!-- START LOGIN SMARTPHONE TEMPLATE -->

<div data-role="page" id="dol-home" data-theme="b">

	<div data-role="header" data-nobackbtn="true" data-theme="b">
		<div id="dol-homeheader">
			<img src="<?php echo $dol_url_root.'/theme/phones/smartphone/theme/'.$smartphone->theme.'/img/dolibarr.png'; ?>">
		</div>
	</div>

	<div data-role="content">
	
		<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<input type="hidden" name="loginfunction" value="loginfunction" />
		
		<div data-role="fieldcontain">
			<label for="username"><?php echo $langs->trans('Login'); ?></label>
			<input type="text" name="username" id="username" value="<?php echo $login; ?>"  />
			
			<br><label for="password"><?php echo $langs->trans('Password'); ?></label>
			<input type="password" name="password" id="password" value="<?php echo $password; ?>" />
			
			<?php if ($select_entity) { ?>
			<br><label for="entity" class="select"><?php echo $langs->trans('Entity'); ?></label>
			<?php echo $select_entity; ?>
			<?php } ?>
			
			<?php if ($captcha) { ?>
			<label for="securitycode"><?php echo $langs->trans('SecurityCode'); ?></label>
			<input type="text" id="securitycode" name="securitycode" />
			<div align="center"><img src="<?php echo $dol_url_root.'/lib/antispamimage.php'; ?>" border="0" width="128" height="36" /></div>
			<?php } ?>
		</div>
		
		<input type="submit" data-theme="b" value="<?php echo $langs->trans('Connection'); ?>" />
		
		</form><br>

	</div><!-- /content -->
	
	<?php if ($forgetpasslink || $helpcenterlink) { ?>
	<div data-role="footer" data-id="foo1" data-theme="b">
		<div data-role="navbar">
			<ul>

		<?php if ($forgetpasslink) { ?>
			<li><a href="<?php echo 'user/passwordforgotten.php'; ?>" data-icon="gear"><?php echo $langs->trans('PasswordForgotten'); ?></a></li>
		<?php } ?>
		
		<?php if ($helpcenterlink) { ?>
			<li><a href="<?php echo 'support/index.php'; ?>" data-icon="info"><?php echo $langs->trans('NeedHelpCenter'); ?></a></li>
		<?php } ?>
			</ul>
		</div><!-- /navbar -->
	</div><!-- /footer -->
	<?php } ?>

</div><!-- /page -->

<?php if ($dol_loginmesg) { ?>
	<script type="text/javascript" language="javascript">
		alert('<?php echo $dol_loginmesg; ?>');
	</script>
<?php } ?>

<!-- END LOGIN SMARTPHONE TEMPLATE -->

<?php $smartphone->smartfooter(); ?>