{* Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>
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
 *}
{php}
	header('Cache-Control: Public, must-revalidate');
	header("Content-type: text/html; charset=".$conf->file->character_set_client);
{/php}

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!-- BEGIN SMARTY TEMPLATE -->

<html>
<head>
<meta name="robots" content="noindex,nofollow">
<title>Dolibarr Authentification</title>

<link rel="stylesheet" type="text/css" href="{$conf_css}">

<style type="text/css">
<!--
#login {ldelim}
	margin-top: 70px;
	margin-bottom: 30px;
	text-align: center;
	font: 12px arial,helvetica;
{rdelim}
#login table {ldelim}
	width: 498px;
	border: 1px solid #C0C0C0;
	background: #F0F0F0 url({$login_background}) repeat-x;
	font-size: 12px;
{rdelim}
-->
</style>

<script type="text/javascript">
function donnefocus() {ldelim}
	document.getElementById('{$focus_element}').focus();
	{rdelim}
</script>

</head>

<body class="body" onload="donnefocus();">
<form id="login" name="login" method="post" action="{$php_self}">
<input type="hidden" name="token" value="{$smarty.session.newtoken}">
<input type="hidden" name="action" value="buildnewpassword">

<table class="login" summary="{$title}" cellpadding="0" cellspacing="0" border="0" align="center">
<tr class="vmenu"><td align="center">{$title}</td></tr>
</table>
<br>

<table class="login" summary="Login area" cellpadding="2" align="center">

<tr><td colspan="3">&nbsp;</td></tr>

<tr>

<td valign="bottom"> &nbsp; <b>{$langs->trans('Login')}</b> &nbsp; </td>
<td valign="bottom" nowrap="nowrap">
<input type="text" {$disabled} id="username" name="username" class="flat" size="15" maxlength="25" value="{$login}" tabindex="1" /></td>

<td rowspan="{$logo_rowspan}" align="center" valign="top">
<img alt="Logo" title="" src="{$logo}" />

</td>
</tr>

	{if $select_entity}
		<tr><td valign="top" nowrap="nowrap"> &nbsp; <b>{$langs->trans('Entity')}</b> &nbsp; </td>
		<td valign="top" nowrap="nowrap">
		{$select_entity}
		</td></tr>
	{/if}

	{if $captcha}
		<tr><td valign="middle" nowrap="nowrap"> &nbsp; <b>{$langs->trans('SecurityCode')}</b></td>
		<td valign="top" nowrap="nowrap" align="left" class="none">

		<table style="width: 100px;"><tr>
		<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="3"></td>
		<td><img src="{$dol_url_root}/lib/antispamimage.php" border="0" width="128" height="36"></td>
		<td><a href="{$php_self}">{$captcha_refresh}</a></td>
		</tr></table>

		</td></tr>
	{/if}

<tr><td colspan="3">&nbsp;</td></tr>

<tr><td colspan="3" style="text-align:center;"><br>
<input id="password" type="submit" {$disabled} class="button" name="password" value="{$langs->trans('SendNewPassword')}" tabindex="4" />
</td></tr>

</table>

</form>

<center>
<table width="90%"><tr><td align="center">

	{if ($mode == 'dolibarr' || $mode == 'dolibarr_mdb2') || (! $disabled)}
		<font style="font-size: 12px;">
			{$langs->trans('SendNewPasswordDesc')}
		</font>
	{else}
		<div class="warning" align="center">
			{$langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode)}
		</div>
	{/if}
	
</td></tr>
</table>

<br>

	{if $error_message}
		<table width="90%"><tr><td align="center" style="font-size: 12px;>
		{$error_message}
		</td></tr></table><br>
	{/if}

<br>
<a href="{$dol_url_root}/">
	{$langs->trans('BackToLoginPage')}
</a>
</center>

<br>
<br>

</body>
</html>

<!-- END SMARTY TEMPLATE -->