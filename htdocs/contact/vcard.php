<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 *
 */

/**
	    \file       htdocs/contact/vcard.php
        \ingroup    societe
		\brief      Onglet vcard d'un contact
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");


$contact = new Contact($db);
$contact->fetch($_GET["id"]);


// On crée car la VCard

$v = new vCard();

$v->setName($contact->name, $contact->firstname, "", "");

$v->setPhoneNumber($contact->phone_perso, "PREF;HOME;VOICE");

if ($contact->birthday) $v->setBirthday($contact->birthday);

// TODO finir le support des adresses dans les contacts
$v->setAddress("", "", $contact->address, $contact->ville, "", $contact->cp, $contact->pays);

$v->setEmail($contact->email);

//$v->setNote("You can take some notes here.\r\nMultiple lines are supported via \\r\\n.");
//$v->setURL("http://www.thomas-mustermann.de", "WORK");


$db->close();


// Renvoi VCard au navigateur

$output = $v->getVCard();

$filename = sanitize_string(ereg_replace('^%20','',$v->getFileName()));
Header("Content-Disposition: attachment; filename=$filename");
Header("Content-Length: ".strlen($output));
Header("Connection: close");
Header("Content-Type: text/x-vCard; name=$filename");

print $output;

?>
