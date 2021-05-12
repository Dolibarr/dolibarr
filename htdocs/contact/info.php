<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *	    \file       htdocs/contact/info.php
 *      \ingroup    societe
 *		\brief      Onglet info d'un contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

// Load translation files required by the page
$langs->load("companies");


// Security check
<<<<<<< HEAD
$id = GETPOST("id",'int');
=======
$id = GETPOST("id", 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

$object = new Contact($db);



/*
 * 	View
 */

$form=new Form($db);

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

<<<<<<< HEAD
llxHeader('',$title,'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');
=======
llxHeader('', $title, 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if ($id > 0)
{
	$result = $object->fetch($id, $user);

	$object->info($id);


	$head = contact_prepare_head($object);

	dol_fiche_head($head, 'info', $title, -1, 'contact');

	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');


	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	print '<br>';

	dol_print_object_info($object);

	print '</div>';

	dol_fiche_end();
}

llxFooter();

$db->close();
