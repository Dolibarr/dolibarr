<?php
/**
 * Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Florian Henry  <florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       public/emailing/mailing-unsubscribe.php
 *      \ingroup    mailing
 *      \brief      Script use to update unsubscribe status of an email
 *                  https://myserver/public/emailing/mailing-unsubscribe.php?unsuscrib=1&securitykey=securitykey&tag=abcdefghijklmn
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
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
}


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

global $user, $conf, $langs;

$langs->loadLangs(array("main", "mails"));

$mtid = GETPOST('mtid');
$email = GETPOST('email');
$tag = GETPOST('tag');	// To retrieve the emailing, and recipient
$unsuscrib = GETPOST('unsuscrib');
$securitykey = GETPOST('securitykey');


/*
 * Actions
 */

dol_syslog("public/emailing/mailing-unsubscribe.php : tag=".$tag." securitykey=".$securitykey, LOG_DEBUG);

if ($securitykey != dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-".$tag."-".$email."-".$mtid, 'md5')) {
	print 'Bad security key value.';
	exit;
}

if (empty($tag) || ($unsuscrib != '1')) {
	print 'Bad parameters';
	exit;
}


/*
 * View
 */

$head = '';
$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';

llxHeader($head, $langs->trans("MailUnsubcribe"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);

dol_syslog("public/emailing/mailing-unsubscribe.php : Launch unsubscribe requests", LOG_DEBUG);

$sql = "SELECT mc.rowid, mc.email, mc.statut, m.entity";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE mc.fk_mailing = m.rowid AND mc.tag = '".$db->escape($tag)."'";

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
}

$obj = $db->fetch_object($resql);

if (empty($obj)) {
	print 'Emailing tag '.$tag.' not found in database. Operation canceled.';
	llxFooter('', 'private');
	exit;
}
if (empty($obj->email)) {
	print 'Email for this tag is not valid. Operation canceled.';
	llxFooter('', 'private');
	exit;
}

if ($obj->statut == 3) {
	print 'Email tag already set to unsubscribe. Operation canceled.';
	llxFooter('', 'private');
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

// Update status of mail in recipient mailing list table
$statut = '3';
$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles SET statut=".((int) $statut)." WHERE tag = '".$db->escape($tag)."'";

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
}

/*
// Update status communication of thirdparty prospect (old usage)
$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=-1 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE tag = '".$db->escape($tag)."' AND source_type='thirdparty' AND source_id is not null)";

$resql=$db->query($sql);
if (! $resql) dol_print_error($db);

// Update status communication of contact prospect (old usage)
$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET no_email=1 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE tag = '".$db->escape($tag)."' AND source_type='contact' AND source_id is not null)";

$resql=$db->query($sql);
if (! $resql) dol_print_error($db);
*/

// Update status communication of email (new usage)
$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_unsubscribe (date_creat, entity, email, unsubscribegroup, ip) VALUES ('".$db->idate(dol_now())."', ".((int) $obj->entity).", '".$db->escape($obj->email)."', '', '".$db->escape(getUserRemoteIP())."')";

$resql = $db->query($sql);
//if (! $resql) dol_print_error($db);	No test on errors, may fail if already unsubscribed


print '<table><tr><td style="text_align:center;">';
print $langs->trans("YourMailUnsubcribeOK", $obj->email)."<br>\n";
print '</td></tr></table>';


llxFooter('', 'public');

$db->close();
