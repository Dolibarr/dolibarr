<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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


$result=$contact->fetch($id);
if ($result <= 0)
{
	dol_print_error($contact->error);
	exit;
}

$physicalperson=1;

$company = new Societe($db);
if ($contact->socid)
{
	$result=$company->fetch($contact->socid);
}

// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-CONTACTID-'.$contact->id);
$v->setName($contact->lastname, $contact->firstname, "", "", "");
$v->setFormattedName($contact->getFullName($langs));

// By default, all informations are for work (except phone_perso and phone_mobile)
$v->setPhoneNumber($contact->phone_pro, "PREF;WORK;VOICE");
$v->setPhoneNumber($contact->phone_mobile, "CELL;VOICE");
$v->setPhoneNumber($contact->fax, "WORK;FAX");

$v->setAddress("", "", $contact->address, $contact->town, "", $contact->zip, ($contact->country_code?$contact->country:''), "WORK;POSTAL");
$v->setLabel("", "", $contact->address, $contact->town, "", $contact->zip, ($contact->country_code?$contact->country:''), "WORK");
$v->setEmail($contact->email,'internet,pref');
$v->setNote($contact->note);

$v->setTitle($contact->poste);

// Data from linked company
if ($company->id)
{
	$v->setURL($company->url, "WORK");
	if (! $contact->phone_pro) $v->setPhoneNumber($company->phone, "WORK;VOICE");
	if (! $contact->fax)       $v->setPhoneNumber($company->fax, "WORK;FAX");
	if (! $contact->zip)        $v->setAddress("", "", $company->address, $company->town, "", $company->zip, $company->country, "WORK;POSTAL");
	if ($company->email != $contact->email) $v->setEmail($company->email,'internet');
	// Si contact lie a un tiers non de type "particulier"
	if ($contact->typent_code != 'TE_PRIVATE') $v->setOrg($company->name);
}

// Personal informations
$v->setPhoneNumber($contact->phone_perso, "HOME;VOICE");
if ($contact->birthday) $v->setBirthday($contact->birthday);

$db->close();


// Renvoi la VCard au navigateur

$output = $v->getVCard();

$filename =trim(urldecode($v->getFileName()));      // "Nom prenom.vcf"
$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
//$filename = dol_sanitizeFileName($filename);


header("Content-Disposition: attachment; filename=\"".$filename."\"");
header("Content-Length: ".dol_strlen($output));
header("Connection: close");
header("Content-Type: text/x-vcard; name=\"".$filename."\"");

print $output;
