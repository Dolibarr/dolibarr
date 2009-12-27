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
 
<!-- BEGIN SMARTY TEMPLATE -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{$title}</title>
<meta name="robots" content="noindex,nofollow" />
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
<link rel="icon" type="image/png" href="{$dol_url_root}/includes/iphone/iui/iui-favicon.png" />
<link rel="apple-touch-icon" href="{$dol_url_root}/includes/iphone/iui/iui-logo-touch-icon.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="stylesheet" href="{$dol_url_root}/includes/iphone/iui/iui.css" type="text/css" />
<link rel="stylesheet" title="Default" href="{$dol_url_root}/theme/phones/iphone/theme/default/default.css.php"  type="text/css" />
<script type="application/x-javascript" src="{$dol_url_root}/includes/iphone/iui/iui.js"></script>
<script type="application/x-javascript" src="{$dol_url_root}/includes/iphone/iui/js/iui-theme-switcher.js"></script>

</head>

<body>

<div class="toolbar">
	<h1 id="pageTitle"></h1>
</div>

<form title="{$title}" id="login" name="login" class="panel" method="post" action="{$php_self}" selected="true">
	<div align="center">
		<img src="{$logo}">
	</div>
	<fieldset>		
        <input type="hidden" name="token" value="{$smarty.session.newtoken}">
        <input type="hidden" name="loginfunction" value="loginfunction" />    
        <div class="row">
        	<label>{$langs->trans('Login')}</label>
        	<input type="text" id="username" name="username" value="" />
        </div>
        
        <div class="row">
        	<label>{$langs->trans('Password')}</label>
        	<input type="password" id="password" name="password" value="" />
        </div>
        
        {if $entity}
        <div class="row">
        	<label>{$langs->trans('Entity')}</label>
        	{$entity}
        </div>
        {/if}
        
        {if $captcha}
        <div class="row">
        	<label>{$langs->trans('SecurityCode')}</label>
        	<input type="text" id="securitycode" name="code">
        	<div align="center">
        		<img src="{$dol_url_root}/lib/antispamimage.php" border="0" width="128" height="36">
			</div>
		</div>
		{/if}

	</fieldset>
	
	{if $smarty.session.dol_loginmesg}
	<div align="center">
		{$smarty.session.dol_loginmesg}
	</div>
	{/if}
	
	<a class="whiteButton" type="submit">{$langs->trans('Connection')}</a>
	
</form>

</body>
</html>

<!-- END SMARTY TEMPLATE -->