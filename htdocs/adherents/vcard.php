<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2020		Frédéric France		<frederic.france@netlogic.fr>
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
 *	    \file       htdocs/adherent/vcard.php
 *      \ingroup    societe
 *		\brief      Onglet vcard d'un adherent
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

$adherent = new adherent($db);


$id = GETPOST('id', 'int');

// Security check
$result = restrictedArea($user, 'adherent', $id, '', '', 'socid', 'rowid', $objcanvas);


$result = $adherent->fetch($id);
if ($result <= 0) {
	dol_print_error($adherent->error);
	exit;
}

$physicalperson = 1;

$company = new Societe($db);
if ($adherent->socid) {
	$result = $company->fetch($adherent->socid);
}

// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-ADHERENTID-'.$adherent->id);
$v->setName($adherent->lastname, $adherent->firstname, "", $adherent->civility, "");
$v->setFormattedName($adherent->getFullName($langs, 1));

$v->setPhoneNumber($adherent->phone_pro, "TYPE=WORK;VOICE");
//$v->setPhoneNumber($adherent->phone_perso,"TYPE=HOME;VOICE");
$v->setPhoneNumber($adherent->phone_mobile, "TYPE=CELL;VOICE");
$v->setPhoneNumber($adherent->fax, "TYPE=WORK;FAX");

$country = $adherent->country_code ? $adherent->country : '';

$v->setAddress("", "", $adherent->address, $adherent->town, $adherent->state, $adherent->zip, $country, "TYPE=WORK;POSTAL");
$v->setLabel("", "", $adherent->address, $adherent->town, $adherent->state, $adherent->zip, $country, "TYPE=WORK");

$v->setEmail($adherent->email);
$v->setNote($adherent->note_public);
$v->setTitle($adherent->poste);

// Data from linked company
if ($company->id) {
	$v->setURL($company->url, "TYPE=WORK");
	if (!$adherent->phone_pro) {
		$v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
	}
	if (!$adherent->fax) {
		$v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
	}
	if (!$adherent->zip) {
		$v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
	}
	// when company e-mail is empty, use only adherent e-mail
	if (empty(trim($company->email))) {
		// was set before, don't set twice
	} elseif (empty(trim($adherent->email))) {
		// when adherent e-mail is empty, use only company e-mail
		$v->setEmail($company->email);
	} elseif (strtolower(end(explode("@", $adherent->email))) == strtolower(end(explode("@", $company->email)))) {
		// when e-mail domain of adherent and company are the same, use adherent e-mail at first (and company e-mail at second)
		$v->setEmail($adherent->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($company->email, 'INTERNET');
	} else {
		// when e-mail of adherent and company complete different use company e-mail at first (and adherent e-mail at second)
		$v->setEmail($company->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($adherent->email, 'INTERNET');
	}

	// Si adherent lie a un tiers non de type "particulier"
	if ($company->typent_code != 'TE_PRIVATE') {
		$v->setOrg($company->name);
	}
}

// Personal informations
$v->setPhoneNumber($adherent->phone_perso, "TYPE=HOME;VOICE");
if ($adherent->birth) {
	$v->setBirthday($adherent->birth);
}

$db->close();


// Renvoi la VCard au navigateur

$output = $v->getVCard();

$filename = trim(urldecode($v->getFileName())); // "Nom prenom.vcf"
$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
//$filename = dol_sanitizeFileName($filename);


header("Content-Disposition: attachment; filename=\"".$filename."\"");
header("Content-Length: ".dol_strlen($output));
header("Connection: close");
header("Content-Type: text/x-vcard; name=\"".$filename."\"");

print $output;
