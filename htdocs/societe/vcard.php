<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *	    \file       htdocs/societe/vcard.php
 *      \ingroup    societe
 *		\brief      Third party vcard download
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$company = new Societe($db);


$socid = GETPOSTINT('id');

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe');


$result = $company->fetch($socid);
if ($result <= 0) {
	dol_print_error($db, $company->error);
	exit;
}


// Compute VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-THIRDPARTYID-'.$company->id);


// Data from company
if (!empty($company->url)) {
	$v->setURL($company->url, "TYPE=WORK");
}
if (!empty($company->phone)) {
	$v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
}
if (!empty($company->phone_mobile)) {
	$v->setPhoneNumber($company->phone_mobile, "TYPE=CELL;VOICE");
}
if (!empty($company->fax)) {
	$v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
}
$v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
if (!empty(trim($company->email))) {
	$v->setEmail($company->email);
}

// If the company is not a private person
if ($company->typent_code != 'TE_PRIVATE') {
	$v->setOrg($company->name);
	$v->filename = $company->name.'.vcf';

	$v->setFormattedName($company->name.(!empty($company->name_alias) ? ' ('.$company->name_alias.')' : ''));
} else {
	$civility = (string) $company->civility_code;
	if (!empty($civility)) {
		$transKey = "Civility".$civility;
		$trans = $langs->transnoentitiesnoconv($transKey);
		if ($trans !== $transKey) {
			$civility = $trans;
		}
	}
	$v->setName($company->lastname, $company->firstname, "", $civility, "");
	$v->setFormattedName($company->getFullName($langs));
}


$db->close();


// Send the vCard to the web client

$output = $v->getVCard();

$filename = trim(urldecode($v->getFileName())); // "Nom prenom.vcf"
$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
//$filename = dol_sanitizeFileName($filename);


header("Content-Disposition: attachment; filename=\"".$filename."\"");
header("Content-Length: ".dol_strlen($output));
header("Connection: close");
header("Content-Type: text/x-vcard; name=\"".$filename."\"");

print $output;
