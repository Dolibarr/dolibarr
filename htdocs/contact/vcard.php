<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");
require("../contact.class.php");
require (DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

/*
 *
 *
 */
$contact = new Contact($db);
$contact->fetch($_GET["id"]);

$v = new vCard();

$v->setName($contact->name, $contact->firstname, "", "");

$v->setPhoneNumber($contact->phone_perso, "PREF;HOME;VOICE");

if ($contact->birthday)
  $v->setBirthday($contact->birthday);

// TODO finir le support des adresses dans les contacts

$v->setAddress("", "", $contact->address, $contact->ville, "", $contact->cp, $contact->pays);
$v->setEmail($contact->email);
//$v->setNote("You can take some notes here.\r\nMultiple lines are supported via \\r\\n.");
//$v->setURL("http://www.thomas-mustermann.de", "WORK");

$output = $v->getVCard();
$filename = $v->getFileName();

Header("Content-Disposition: attachment; filename=$filename");
Header("Content-Length: ".strlen($output));
Header("Connection: close");
Header("Content-Type: text/x-vCard; name=$filename");

echo $output;

$db->close();
?>
