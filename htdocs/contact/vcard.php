<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/contact/vcard.php
 *      \ingroup    societe
 *		\brief      Onglet vcard d'un contact
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/vcard.class.php");


$contact = new Contact($db);
$result=$contact->fetch($_GET["id"]);

$physicalperson=1;

$company = new Societe($db);
if ($contact->socid)
{
	$result=$company->fetch($contact->socid);
	//print "ee";
}

// We create VCard
$v = new vCard();
$v->setProdId('Dolibarr '.DOL_VERSION);

$v->setUid('DOLIBARR-CONTACTID-'.$contact->id);
$v->setName($contact->name, $contact->firstname, "", "", "");
$v->setFormattedName($contact->getFullName($langs));

// By default, all informations are for work (except phone_perso and phone_mobile)
$v->setPhoneNumber($contact->phone_pro, "PREF;WORK;VOICE");
$v->setPhoneNumber($contact->phone_mobile, "CELL;VOICE");
$v->setPhoneNumber($contact->fax, "WORK;FAX");

$v->setAddress("", "", $contact->address, $contact->ville, "", $contact->cp, ($contact->pays_code?$contact->pays:''), "WORK;POSTAL");
$v->setLabel("", "", $contact->address, $contact->ville, "", $contact->cp, ($contact->pays_code?$contact->pays:''), "WORK");
$v->setEmail($contact->email,'internet,pref');
$v->setNote($contact->note);

$v->setTitle($contact->poste);

// Data from linked company
if ($company->id)
{
	$v->setURL($company->url, "WORK");
	if (! $contact->phone_pro) $v->setPhoneNumber($company->tel, "WORK;VOICE");
	if (! $contact->fax)       $v->setPhoneNumber($company->fax, "WORK;FAX");
	if (! $contact->cp)        $v->setAddress("", "", $company->address, $company->ville, "", $company->cp, $company->pays_code, "WORK;POSTAL");
	if ($company->email != $contact->email) $v->setEmail($company->email,'internet');
	// Si contact lie a un tiers non de type "particulier"
	if ($contact->typent_code != 'TE_PRIVATE') $v->setOrg($company->nom);
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


Header("Content-Disposition: attachment; filename=\"".$filename."\"");
Header("Content-Length: ".dol_strlen($output));
Header("Connection: close");
Header("Content-Type: text/x-vcard; name=\"".$filename."\"");

print $output;

?>
