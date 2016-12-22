<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
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
 *      \file       htdocs/compta/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("donations");

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'don',$id,'');

$object = new Don($db);
$object->fetch($id);

$permissionnote=$user->rights->don->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/*
 * View
 */

$title = $langs->trans('Donation') . " - " . $langs->trans('Notes');
$helpurl = "";
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new Don($db);
	$object->fetch($id,$ref);

    $head = donation_prepare_head($object);

    dol_fiche_head($head, 'note', $langs->trans("Donation"), 0, 'generic');
    
    $linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php">'.$langs->trans("BackToList").'</a>';
    
    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
    print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
    print '</td></tr>';

    print "</table>";

    print '<br>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}


llxFooter();

$db->close();
