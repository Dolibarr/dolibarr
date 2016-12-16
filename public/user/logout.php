<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit         <doli@sydesy.com>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/user/logout.php
 *      \brief      Page called to disconnect a user
 */

//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Uncomment creates pb to relogon after a disconnect
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');	// We need company to get correct logo onto home page
if (! defined('EVEN_IF_ONLY_LOGIN_ALLOWED'))  define('EVEN_IF_ONLY_LOGIN_ALLOWED','1');

require_once '../main.inc.php';

// This can happen only with a bookmark or forged url call.
if (!empty($_SESSION["dol_authmode"]) && ($_SESSION["dol_authmode"] == 'forceuser' || $_SESSION["dol_authmode"] == 'http'))
{
   die("Disconnection does not work when connection was made in mode ".$_SESSION["dol_authmode"]);
}

global $conf, $langs, $user;

// Appel des triggers
include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
$interface=new Interfaces($db);
$result=$interface->run_triggers('USER_LOGOUT',$user,$user,$langs,$conf);
if ($result < 0) { $error++; }
// Fin appel triggers

// Define url to go after disconnect
$urlfrom=empty($_SESSION["urlfrom"])?'':$_SESSION["urlfrom"];

// Destroy some cookies
// TODO external module
if (! empty($conf->phenix->enabled) && ! empty($conf->phenix->cookie))
{
	setcookie($conf->phenix->cookie, '', 1, "/");
}

// Define url to go
$url=DOL_URL_ROOT."/index.php";		// By default go to login page
if ($urlfrom) $url=DOL_URL_ROOT.$urlfrom;
if (! empty($conf->global->MAIN_LOGOUT_GOTO_URL)) $url=$conf->global->MAIN_LOGOUT_GOTO_URL;

if (GETPOST('dol_hide_topmenu'))         $url.=(preg_match('/\?/',$url)?'&':'?').'dol_hide_topmenu=1';
if (GETPOST('dol_hide_leftmenu'))        $url.=(preg_match('/\?/',$url)?'&':'?').'dol_hide_leftmenu=1';
if (GETPOST('dol_optimize_smallscreen')) $url.=(preg_match('/\?/',$url)?'&':'?').'dol_optimize_smallscreen=1';
if (GETPOST('dol_no_mouse_hover'))       $url.=(preg_match('/\?/',$url)?'&':'?').'dol_no_mouse_hover=1';
if (GETPOST('dol_use_jmobile'))          $url.=(preg_match('/\?/',$url)?'&':'?').'dol_use_jmobile=1';

// Destroy session
$prefix=dol_getprefix();
$sessionname='DOLSESSID_'.$prefix;
$sessiontimeout='DOLSESSTIMEOUT_'.$prefix;
if (! empty($_COOKIE[$sessiontimeout])) ini_set('session.gc_maxlifetime',$_COOKIE[$sessiontimeout]);
session_name($sessionname);
session_destroy();
dol_syslog("End of session ".$sessionname);

// Not sure this is required
unset($_SESSION['dol_login']);
unset($_SESSION['dol_entity']);

if (GETPOST('noredirect')) return;
header("Location: ".$url);		// Default behaviour is redirect to index.php page
