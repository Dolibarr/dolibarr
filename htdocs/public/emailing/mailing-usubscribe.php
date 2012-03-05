<?php
/*
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
 *      \file       scripts/emailings/mailing-read.php
 *      \ingroup    mailing
 *      \brief      Script use to update unsubcribe contact to prospect mailing list
 */
 
define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

global $user, $conf, $langs;

$langs->load("main");
$langs->load("mails");

$id=GETPOST('tag');
$unsuscrib=GETPOST('unsuscrib');


if (($id!='') && ($unsuscrib=='1'))
{
	//Udate status of mail in Destinaries maling list
	$statut='3';
	$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles SET statut=".$statut." WHERE tag='".$id."'";
	dol_syslog("public/emailing/mailing-read.php : Mail unsubcribe : ".$sql, LOG_DEBUG);
	
	$resql=$db->query($sql);
	
	//Update status communication of prospect
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=-1 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE tag='".$id."' AND source_type='thirdparty')";
	dol_syslog("public/emailing/mailing-read.php : Mail unsubcribe : ".$sql, LOG_DEBUG);
	
	$resql=$db->query($sql);

	$sql = "SELECT mc.email";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.tag='".$id."'";

	$resql=$db->query($sql);
	
	$obj = $db->fetch_object($resql);
	
	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	print "\n";
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";
	print '<meta name="keywords" content="dolibarr,mailing">'."\n";
	print '<meta name="description" content="Welcome on Dolibarr Mailing unsubcribe">'."\n";
	print "<title>".$langs->trans("MailUnsubcribe")."</title>\n";
	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";
	print '<style type="text/css">';
	print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '</style>';

	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
	print '<table><tr><td style="text_align:center;">';
	print $langs->trans("YourMailUnsubcribeOK",$obj->email)."<br>\n";
	print '</td></tr></table>';
	print "</body>\n";
	print "</html>\n";
	

}

$db->close();
?>
