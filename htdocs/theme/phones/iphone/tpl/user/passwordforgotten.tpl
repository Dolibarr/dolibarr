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

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!-- BEGIN SMARTY TEMPLATE -->

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>{$langs->trans('Password')}</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport"content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" />
<link type="text/css" rel="stylesheet" href="{$dol_url_root}/theme/phones/iphone/theme/{$theme}/{$theme}.css.php" />
<script type="text/javascript" src="{$dol_url_root}/includes/iphone/iwebkit/Framework/javascript/functions.js"></script>
</head>

<body>

<div id="topbar">
	<div id="title">{$langs->trans('Password')}</div>
	<div id="leftnav">
		<a href="{$dol_url_root}/">
			<img alt="home" src="{$dol_url_root}/theme/phones/iphone/theme/{$theme}/img/home.png"/>
		</a>
	</div>
</div>

<div id="content">
	<form id="login" name="login" method="post" action="{$php_self}">
	<input type="hidden" name="token" value="{$smarty.session.newtoken}" />
	<input type="hidden" name="action" value="buildnewpassword">
	
	<div align="center">
		<img src="{$dol_url_root}/theme/phones/iphone/theme/{$theme}/thumbs/dolibarr.png">
	</div>
	
	<br>
	
	<span class="graytitle">{$langs->trans('Identification')}</span>
	<ul class="pageitem">
		<li class="form">
			<input placeholder="{$langs->trans('Login')}" type="text" {$disabled} id="username" name="username" value="{$login}" />
		</li>
	</ul>
	
	{if $select_entity}
	<span class="graytitle">{$langs->trans('Entity')}</span>
	<ul class="pageitem">
		<li class="form">
			{$select_entity}
			<span class="arrow"></span>
        </li>
	</ul>
	{/if}
	
	{if $captcha}
	<span class="graytitle">{$langs->trans('SecurityCode')}</span>
	<ul class="pageitem">
		<li class="form">
			<span class="narrow">
				<input type="text" id="securitycode" name="code" />
				<img src="{$dol_url_root}/lib/antispamimage.php" border="0" width="128" height="36" />
			</span>
		</li>
	</ul>
	{/if}
	
	<ul class="pageitem">
		<li class="form">
			<input name="input Button" {$disabled} type="submit" value="{$langs->trans('SendByMail')}" />
		</li>
	</ul>
	
	</form>
</div>

<ul class="pageitem">
	<li class="textbox">
	<span class="header">{$langs->trans('Infos')}</span>
		{if ($mode == 'dolibarr' || $mode == 'dolibarr_mdb2') || (! $disabled)}
			{$langs->trans('SendNewPasswordDesc')}
		{else}
			{$langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode)}
		{/if}
	</li>
</ul>

{if $error_message}
	<script type="text/javascript" language="javascript">
		alert('{$error_message}');
	</script>
{/if}

</body>
</html>

<!-- END SMARTY TEMPLATE -->