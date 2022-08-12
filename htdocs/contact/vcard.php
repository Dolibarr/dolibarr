<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
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
 *	    \file       htdocs/contact/vcard.php
 *      \ingroup    societe
 *		\brief      Onglet vcard d'un contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

$contact = new Contact($db);


$id = GETPOST('id', 'int');

// Security check
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');


$result = $contact->fetch($id);
if ($result <= 0) {
	dol_print_error($contact->error);
	exit;
}


$company = new Societe($db);
if ($contact->socid) {
	$result = $company->fetch($contact->socid);
}

// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-CONTACTID-'.$contact->id);
$v->setName($contact->lastname, $contact->firstname, "", $contact->civility, "");
$v->setFormattedName($contact->getFullName($langs, 1));

$v->setPhoneNumber($contact->phone_pro, "TYPE=WORK;VOICE");
//$v->setPhoneNumber($contact->phone_perso,"TYPE=HOME;VOICE");
$v->setPhoneNumber($contact->phone_mobile, "TYPE=CELL;VOICE");
$v->setPhoneNumber($contact->fax, "TYPE=WORK;FAX");

$country = $contact->country_code ? $contact->country : '';

$v->setAddress("", "", $contact->address, $contact->town, $contact->state, $contact->zip, $country, "TYPE=WORK;POSTAL");
$v->setLabel("", "", $contact->address, $contact->town, $contact->state, $contact->zip, $country, "TYPE=WORK");

$v->setEmail($contact->email);
$v->setNote($contact->note);
$v->setTitle($contact->poste);

// Data from linked company
if ($company->id) {
	$v->setURL($company->url, "TYPE=WORK");
	if (!$contact->phone_pro) {
		$v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
	}
	if (!$contact->fax) {
		$v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
	}
	if (!$contact->zip) {
		$v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
	}

	// when company e-mail is empty, use only contact e-mail
	if (empty(trim($company->email))) {
		// was set before, don't set twice
	} elseif (empty(trim($contact->email))) {
		// when contact e-mail is empty, use only company e-mail
		$v->setEmail($company->email);
	} elseif (strtolower(end(explode("@", $contact->email))) == strtolower(end(explode("@", $company->email)))) {
		// when e-mail domain of contact and company are the same, use contact e-mail at first (and company e-mail at second)
		$v->setEmail($contact->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($company->email, 'INTERNET');
	} else {
		// when e-mail of contact and company complete different use company e-mail at first (and contact e-mail at second)
		$v->setEmail($company->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($contact->email, 'INTERNET');
	}

	// Si contact lie a un tiers non de type "particulier"
	if ($company->typent_code != 'TE_PRIVATE') {
		$v->setOrg($company->name);
	}
}

// Personal informations
$v->setPhoneNumber($contact->phone_perso, "TYPE=HOME;VOICE");
if ($contact->birthday) {
	$v->setBirthday($contact->birthday);
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
