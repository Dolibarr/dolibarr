<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2003 Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/user/logout.php
        \brief      Fichier de deconnexion
		\version	$Id$
*/

if ($_SESSION["dol_authmode"] == 'forceuser'
  	 && $_SESSION["dol_authmode"] == 'http')
{
   die("Disconnection does not work when connection was made in mode ".$_SESSION["dol_authmode"]);
}

include_once("../conf/conf.php");
require_once("../main.inc.php");

// Module Phenix
if ($conf->phenix->enabled && $conf->phenix->cookie)
{
	// Destruction du cookie
	setcookie($conf->phenix->cookie, '', 1, "/");
}

dolibarr_syslog("End session in DOLSESSID_".$dolibarr_main_db_name);

session_destroy();
session_name("DOLSESSID_".$dolibarr_main_db_name);
session_start();
session_unregister("dol_login");

header("Location: ".DOL_URL_ROOT."/index.php");
?>
