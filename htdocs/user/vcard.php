<?php
/* Copyright (C) 2004      	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2021-2022 	Anthony Berton		<anthony.berton@bb2a.fr>
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
 *	    \file       htdocs/user/vcard.php
 *      \ingroup    societe
 *		\brief      Onglet vcard d'un user
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

$user2 = new User($db);


$id = GETPOST('id', 'int');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';
$result = restrictedArea($user, 'user', $id, 'user', $feature2);


$result = $user2->fetch($id);
if ($result <= 0) {
	dol_print_error($user2->error);
	exit;
}

$physicalperson = 1;

$company = new Societe($db);
if ($user2->socid) {
	$result = $company->fetch($user2->socid);
}

// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-USERID-'.$user2->id);
$v->setName($user2->lastname, $user2->firstname, "", $user2->civility_code, "");
$v->setFormattedName($user2->getFullName($langs, 1));

$v->setPhoneNumber($user2->office_phone, "TYPE=WORK;VOICE");
$v->setPhoneNumber($user2->personal_mobile, "TYPE=HOME;VOICE");
$v->setPhoneNumber($user2->user_mobile, "TYPE=CELL;VOICE");
$v->setPhoneNumber($user2->office_fax, "TYPE=WORK;FAX");

$country = $user2->country_code ? $user2->country : '';

$v->setAddress("", "", $user2->address, $user2->town, $user2->state, $user2->zip, $country, "TYPE=WORK;POSTAL");
$v->setLabel("", "", $user2->address, $user2->town, $user2->state, $user2->zip, $country, "TYPE=WORK");

$v->setEmail($user2->email, "TYPE=WORK");
$v->setNote($user2->note_public);
$v->setTitle($user2->job);

// Data from linked company
if ($company->id) {
	$v->setURL($company->url, "TYPE=WORK");
	if (!$user2->office_phone) {
		$v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
	}
	if (!$user2->office_fax) {
		$v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
	}
	if (!$user2->zip) {
		$v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
	}

	// when company e-mail is empty, use only user e-mail
	if (empty(trim($company->email))) {
		// was set before, don't set twice
	} elseif (empty(trim($user2->email))) {
		// when user e-mail is empty, use only company e-mail
		$v->setEmail($company->email, "TYPE=WORK");
	} elseif (strtolower(end(explode("@", $user2->email))) == strtolower(end(explode("@", $company->email)))) {
		// when e-mail domain of user and company are the same, use user e-mail at first (and company e-mail at second)
		$v->setEmail($user2->email, "TYPE=WORK");

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($company->email, 'INTERNET');
	} else {
		// when e-mail of user and company complete different use company e-mail at first (and user e-mail at second)
		$v->setEmail($company->email, "TYPE=WORK");

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($user2->email, 'INTERNET');
	}

	// Si user lie a un tiers non de type "particulier"
	if ($company->typent_code != 'TE_PRIVATE') {
		$v->setOrg($company->name);
	}
}

// Personal informations
$v->setPhoneNumber($user2->personal_mobile, "TYPE=HOME;VOICE");
if ($user2->birth) {
	$v->setBirthday($user2->birth);
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
