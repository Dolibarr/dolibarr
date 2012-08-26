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
 *	    \file       htdocs/contact/info.php
 *      \ingroup    societe
 *		\brief      Onglet info d'un contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

$langs->load("companies");

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, 'socpeople&societe');



/*
* 	View
*/

llxHeader('',$langs->trans("ContactsAddresses"),'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');


$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);
$contact->info($_GET["id"]);


$head = contact_prepare_head($contact);

dol_fiche_head($head, 'info', $langs->trans("ContactsAddresses"), 0, 'contact');


print '<table width="100%"><tr><td>';
print '</td></tr></table>';

dol_print_object_info($contact);

print "</div>";

llxFooter();

$db->close();
?>
