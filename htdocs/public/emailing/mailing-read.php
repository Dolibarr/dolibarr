<?php
/**
 * Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Florian Henry  <florian.henry@open-concept.pro>
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
 */


/**
 *      \file       public/emailing/mailing-read.php
 *      \ingroup    mailing
 *      \brief      Script use to update mail status if destinaries read it (if images during mail read are display)
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require("../../main.inc.php");

$id=GETPOST('tag');

if (empty($conf->global->MAILING_EMAIL_UNSUBSCRIBE)) accessforbidden('Option not enabled');


/*
 * Actions
 */

if ($id!='')
{
	$statut='2';
	$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles SET statut=".$statut." WHERE tag='".$id."'";
	dol_syslog("public/emailing/mailing-read.php : Mail read : ".$sql, LOG_DEBUG);

	$resql=$db->query($sql);

	//Update status communication of thirdparty prospect
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=3 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE tag='".$id."' AND source_type='thirdparty' AND source_id is not null)";
	dol_syslog("public/emailing/mailing-read.php : Mail read thirdparty : ".$sql, LOG_DEBUG);

	$resql=$db->query($sql);

    //Update status communication of contact prospect
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=3 WHERE rowid IN (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."socpeople AS sc INNER JOIN ".MAIN_DB_PREFIX."mailing_cibles AS mc ON mc.tag = '".$id."' AND mc.source_type = 'contact' AND mc.source_id = sc.rowid)";
	dol_syslog("public/emailing/mailing-read.php : Mail read contact : ".$sql, LOG_DEBUG);

	$resql=$db->query($sql);

}

$db->close();
?>
