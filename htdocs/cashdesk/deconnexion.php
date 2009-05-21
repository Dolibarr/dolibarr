<?php
/* Copyright (C) 2007-2008 J�r�mie Ollivier <jeremie.o@laposte.net>
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
include('../master.inc.php');

// Init session
// Init session. Name of session is specific to Dolibarr instance.
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) ini_set('session.gc_maxlifetime',$conf->global->MAIN_SESSION_TIMEOUT);
session_name($sessionname);
session_start();
dol_syslog("Start session name=".$sessionname." Session id()=".session_id().", _SESSION['dol_login']=".$_SESSION["dol_login"].", ".ini_get("session.gc_maxlifetime"));

// Destroy session
$sessionname='DOLSESSID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) ini_set('session.gc_maxlifetime',$conf->global->MAIN_SESSION_TIMEOUT);
session_name($sessionname);
session_destroy();
dol_syslog("End of session ".$sessionname);


header ('Location: index.php');
?>