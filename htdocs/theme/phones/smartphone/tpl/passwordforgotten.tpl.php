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
top_httphead();
?>
<!DOCTYPE html>
<html>
<?php 
include('header.tpl.php');
?>
<body>
<!-- BEGIN SMARTPHONE TEMPLATE -->

<div data-role="page" id="dol-home" data-theme="b">

	<div data-role="header" data-theme="b" data-position="inline">
			<div id="dol-homeheader">
			<?php echo $langs->trans('Identification'); ?>
			</div>
	</div>

	<div data-role="content">

		<form id="login" class="loginform" name="login" method="post" action="<?php echo $php_self; ?>">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<input type="hidden" name="loginfunction" value="buildnewpassword" />

		<div data-role="fieldcontain">
			<label for="username"><?php echo $langs->trans('Login'); ?></label>
			<input type="text" name="username" id="username" value="<?php echo $login; ?>"  />

			<?php if ($select_entity) { ?>
			<label for="entity" class="select"><?php echo $langs->trans('Entity'); ?></label>
			<?php echo $select_entity; ?>
			<?php } ?>

			<?php if ($captcha) { ?>
			<label for="securitycode"><?php echo $langs->trans('SecurityCode'); ?></label>
			<input type="text" id="securitycode" name="securitycode" />
			<div align="center"><img src="<?php echo $dol_url_root.'/core/antispamimage.php'; ?>" border="0" width="256" height="48" /></div>
			<?php } ?>
		</div>

		<input type="submit" data-theme="b" value="<?php echo $langs->trans('SendByMail'); ?>" />

		</form>

	</div><!-- /content -->

	<div data-role="footer" data-theme="b">
		<?php if ($mode == 'dolibarr' || ! $disabled) {
			echo $langs->trans('SendNewPasswordDesc');
		}else{
			echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode);
		} ?>
	</div>

</div><!-- /page -->

<?php if ($message) { ?>
	<script type="text/javascript">
		alert('<?php echo $message; ?>');
	</script>
<?php } ?>

<!-- END SMARTPHONE TEMPLATE -->
</body>
</html>
