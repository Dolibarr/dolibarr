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
 *      \brief      Script use to update unsubcribe status of an email
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

global $user, $conf, $langs;

$langs->loadLangs(array("main", "mails"));

$mtid = GETPOST('mtid');
$email = GETPOST('email');
$tag = GETPOST('tag');
$unsuscrib = GETPOST('unsuscrib');
$securitykey = GETPOST('securitykey');


/*
 * Actions
 */

dol_syslog("public/emailing/mailing-read.php : tag=".$tag." securitykey=".$securitykey, LOG_DEBUG);

if ($securitykey != $conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) {
	print 'Bad security key value.';
	exit;
}


if (!empty($tag) && ($unsuscrib == '1')) {
	dol_syslog("public/emailing/mailing-unsubscribe.php : Launch unsubscribe requests", LOG_DEBUG);

	$sql = "SELECT mc.rowid, mc.email, mc.statut, m.entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
	$sql .= " WHERE mc.fk_mailing = m.rowid AND mc.tag='".$db->escape($tag)."'";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}

	$obj = $db->fetch_object($resql);

	if (empty($obj)) {
		print 'Email target not valid. Operation canceled.';
		exit;
	}
	if (empty($obj->email)) {
		print 'Email target not valid. Operation canceled.';
		exit;
	}
	if ($obj->statut == 3) {
		print 'Email target already set to unsubscribe. Operation canceled.';
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


	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	// Security options
	header("X-Content-Type-Options: nosniff"); // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)
	header("X-Frame-Options: SAMEORIGIN"); // Frames allowed only if on same domain (stop some XSS attacks)

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	print "\n";
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";
	print '<meta name="keywords" content="dolibarr,emailing">'."\n";
	print '<meta name="description" content="Dolibarr EMailing unsubcribe page">'."\n";
	print "<title>".$langs->trans("MailUnsubcribe")."</title>\n";
	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";
	print '<style type="text/css">';
	print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '</style>';

	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
	print '<table><tr><td style="text_align:center;">';
	print $langs->trans("YourMailUnsubcribeOK", $obj->email)."<br>\n";
	print '</td></tr></table>';
	print "</body>\n";
	print "</html>\n";
}

$db->close();
