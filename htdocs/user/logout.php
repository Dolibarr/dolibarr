<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit         <doli@sydesy.com>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 */

/**
 *      \file       htdocs/user/logout.php
 *      \brief      Page called to disconnect a user
 * 		\version	$Id$
 */

require_once("../main.inc.php");

if (!empty($_SESSION["dol_authmode"]) && ($_SESSION["dol_authmode"] == 'forceuser'
  	 || $_SESSION["dol_authmode"] == 'http'))
{
   die("Disconnection does not work when connection was made in mode ".$_SESSION["dol_authmode"]);
}

// Define url to go after disconnect
$urlfrom=empty($_SESSION["urlfrom"])?'':$_SESSION["urlfrom"];

// Phenix module
if ($conf->phenix->enabled && $conf->phenix->cookie)
{
	// Destroy cookie
	setcookie($conf->phenix->cookie, '', 1, "/");
}

// Destroy object of session
session_unregister("dol_login");
session_unregister("dol_entity");

// Destroy session
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$sessiontimeout);
session_name($sessionname);
session_destroy();
dol_syslog("End of session ".$sessionname);

// Destroy entity cookie
// TODO MULTICOMP Must fix this. Use session instead of cookie.
if ($conf->multicompany->enabled)
{
	$entityCookieName = "DOLENTITYID_dolibarr";
	setcookie($entityCookieName, '', 1, "/");
}

// Define url to go
$url=DOL_URL_ROOT."/index.php";		// By default go to login page
if ($urlfrom)
{
	$url=DOL_URL_ROOT.$urlfrom;
}

header("Location: ".$url);
?>
