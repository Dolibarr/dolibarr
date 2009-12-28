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
<title>{$title}</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport"content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" />
<link type="text/css" rel="stylesheet" href="{$dol_url_root}/theme/phones/iphone/theme/{$theme}/{$theme}.css.php" />
<script type="text/javascript" src="{$dol_url_root}/includes/iphone/iwebkit/Framework/javascript/functions.js"></script>
</head>

<body>

<div id="topbar">
	<div id="title">{$title}</div>
</div>

<div id="content">
	<form id="login" name="login" method="post" action="{$php_self}">
	<input type="hidden" name="token" value="{$smarty.session.newtoken}" />
	<input type="hidden" name="loginfunction" value="loginfunction" />
	
	<div align="center">
		<img src="{$logo}">
	</div>
	
	<ul class="pageitem">
		<li class="form">
			<input placeholder="{$langs->trans('Login')}" type="text" id="username" name="username" value="{$login}" />
		</li>
		
		<li class="form">
			<input placeholder="{$langs->trans('Password')}" type="password" id="password" name="password" value="{$password}" />
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
			<input name="input Button" type="submit" value="{$langs->trans('Connection')}" />
		</li>
	</ul>
	
</form>
</div>

{if $dol_loginmesg}
	<script type="text/javascript" language="javascript">
		alert('{$dol_loginmesg}');
	</script>
{/if}

</body>
</html>

<!-- END SMARTY TEMPLATE -->