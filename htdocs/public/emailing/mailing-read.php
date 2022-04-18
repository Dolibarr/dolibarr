<?php
/**
 * Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Florian Henry  <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


/**
 *      \file       public/emailing/mailing-read.php
 *      \ingroup    mailing
 *      \brief      Script use to update mail status if destinaries read it (if images during mail read are display)
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not check anti POST attack test
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}

/**
 * Header empty
 *
 * @return	void
 */
function llxHeader()
{
}
/**
 * Footer empty
 *
 * @return	void
 */
function llxFooter()
{
}


require '../../main.inc.php';

$mtid = GETPOST('mtid');
$email = GETPOST('email');
$tag = GETPOST('tag');
$securitykey = GETPOST('securitykey');


/*
 * Actions
 */

dol_syslog("public/emailing/mailing-read.php : tag=".$tag." securitykey=".$securitykey, LOG_DEBUG);

if ($securitykey != $conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) {
	print 'Bad security key value.';
	exit;
}

if (!empty($tag)) {
	dol_syslog("public/emailing/mailing-read.php : Update status of email target and thirdparty for tag ".$tag, LOG_DEBUG);

	$sql = "SELECT mc.rowid, mc.email, mc.statut, mc.source_type, mc.source_id, m.entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
	$sql .= " WHERE mc.fk_mailing = m.rowid AND mc.tag='".$db->escape($tag)."'";

	$resql = $db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);

	if (empty($obj)) {
		print 'Email target not valid. Operation canceled.';
		exit;
	}
	if (empty($obj->email)) {
		print 'Email target not valid. Operation canceled.';
		exit;
	}
	if ($obj->statut == 2 || $obj->statut == 3) {
		print 'Email target already set to read or unsubscribe. Operation canceled.';
		exit;
	}
	// TODO Test that mtid and email match also with the one found from $tag
	/*
	 if ($obj->email != $email)
	 {
	 print 'Email does not match tagnot found. No need to unsubscribe.';
	 exit;
	 }
	 */

	//Update status of target
	$statut = '2';
	$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles SET statut=".((int) $statut)." WHERE rowid = ".((int) $obj->rowid);
	$resql = $db->query($sql);
	if (!$resql) dol_print_error($db);

	//Update status communication of thirdparty prospect
	if ($obj->source_id > 0 && $obj->source_type == 'thirdparty' && $obj->entity) {
		$sql = "UPDATE ".MAIN_DB_PREFIX.'societe SET fk_stcomm = 3 WHERE fk_stcomm <> -1 AND entity = '.((int) $obj->entity).' AND rowid = '.((int) $obj->source_id);
		$resql = $db->query($sql);
	}

	//Update status communication of contact prospect
	if ($obj->source_id > 0 && $obj->source_type == 'contact' && $obj->entity) {
		$sql = "UPDATE ".MAIN_DB_PREFIX.'societe SET fk_stcomm = 3 WHERE fk_stcomm <> -1 AND entity = '.((int) $obj->entity).' AND rowid IN (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'socpeople AS sc WHERE sc.rowid = '.((int) $obj->source_id).')';
		$resql = $db->query($sql);
	}
}

$db->close();
