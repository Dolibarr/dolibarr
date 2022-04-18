<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2020-2021	Frédéric France		<frederic.france@netlogic.fr>
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
 *	    \file       htdocs/adherents/vcard.php
 *      \ingroup    societe
 *		\brief      Vcard tab of a member
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alphanohtml');

$object = new adherent($db);

// Fetch object
if ($id > 0 || !empty($ref)) {
	// Load member
	$result = $object->fetch($id, $ref);

	// Define variables to know what current user can do on users
	$canadduser = ($user->admin || $user->rights->user->user->creer);
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id) {
		// $User is the user who edits, $object->user_id is the id of the related user in the edited member
		$caneditfielduser = ((($user->id == $object->user_id) && $user->rights->user->self->creer)
			|| (($user->id != $object->user_id) && $user->rights->user->user->creer));
		$caneditpassworduser = ((($user->id == $object->user_id) && $user->rights->user->self->password)
			|| (($user->id != $object->user_id) && $user->rights->user->user->password));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember = $user->rights->adherent->creer;
// Define variables to determine what the current user can do on the properties of a member
if ($id) {
	$caneditfieldmember = $user->rights->adherent->creer;
}

// Security check
$result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


/*
 * Actions
 */

// None


/*
 * View
 */

$company = new Societe($db);
if ($object->socid) {
	$result = $company->fetch($object->socid);
}



// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-ADHERENTID-'.$object->id);
$v->setName($object->lastname, $object->firstname, "", $object->civility, "");
$v->setFormattedName($object->getFullName($langs, 1));

$v->setPhoneNumber($object->phone_pro, "TYPE=WORK;VOICE");
//$v->setPhoneNumber($object->phone_perso,"TYPE=HOME;VOICE");
$v->setPhoneNumber($object->phone_mobile, "TYPE=CELL;VOICE");
$v->setPhoneNumber($object->fax, "TYPE=WORK;FAX");

$country = $object->country_code ? $object->country : '';

$v->setAddress("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK;POSTAL");
$v->setLabel("", "", $object->address, $object->town, $object->state, $object->zip, $country, "TYPE=WORK");

$v->setEmail($object->email);
$v->setNote($object->note_public);
$v->setTitle($object->poste);

// Data from linked company
if ($company->id) {
	$v->setURL($company->url, "TYPE=WORK");
	if (!$object->phone_pro) {
		$v->setPhoneNumber($company->phone, "TYPE=WORK;VOICE");
	}
	if (!$object->fax) {
		$v->setPhoneNumber($company->fax, "TYPE=WORK;FAX");
	}
	if (!$object->zip) {
		$v->setAddress("", "", $company->address, $company->town, $company->state, $company->zip, $company->country, "TYPE=WORK;POSTAL");
	}
	// when company e-mail is empty, use only adherent e-mail
	if (empty(trim($company->email))) {
		// was set before, don't set twice
	} elseif (empty(trim($object->email))) {
		// when adherent e-mail is empty, use only company e-mail
		$v->setEmail($company->email);
	} elseif (strtolower(end(explode("@", $object->email))) == strtolower(end(explode("@", $company->email)))) {
		// when e-mail domain of adherent and company are the same, use adherent e-mail at first (and company e-mail at second)
		$v->setEmail($object->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($company->email, 'INTERNET');
	} else {
		// when e-mail of adherent and company complete different use company e-mail at first (and adherent e-mail at second)
		$v->setEmail($company->email);

		// support by Microsoft Outlook (2019 and possible earlier)
		$v->setEmail($object->email, 'INTERNET');
	}

	// Si adherent lie a un tiers non de type "particulier"
	if ($company->typent_code != 'TE_PRIVATE') {
		$v->setOrg($company->name);
	}
}

// Personal informations
$v->setPhoneNumber($object->phone_perso, "TYPE=HOME;VOICE");
if ($object->birth) {
	$v->setBirthday($object->birth);
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
