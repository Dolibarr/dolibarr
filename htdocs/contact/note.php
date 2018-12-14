<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013           Florian Henry		 <florian.henry@open-concept.pro>
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
 *   \file       htdocs/contact/note.php
 *   \brief      Tab for notes on contact
 *   \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$action = GETPOST('action','aZ09');

// Load translation files required by the page
$langs->load("companies");

// Security check
$id = GETPOST('id','int');
if ($user->societe_id) $id=$user->societe_id;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

$object = new Contact($db);
if ($id > 0) $object->fetch($id);

$permissionnote=$user->rights->societe->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once


/*
 *	View
 */

$now=dol_now();

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($id > 0)
{
    /*
     * Affichage onglets
     */
    if (! empty($conf->notification->enabled)) $langs->load("mails");

    $head = contact_prepare_head($object);

    dol_fiche_head($head, 'note', $title, -1, 'contact');

    $linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref='<div class="refidno">';
    if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
    {
        $objsoc=new Societe($db);
        $objsoc->fetch($object->socid);
        // Thirdparty
        $morehtmlref.=$langs->trans('ThirdParty') . ' : ';
        if ($objsoc->id > 0) $morehtmlref.=$objsoc->getNomUrl(1);
        else $morehtmlref.=$langs->trans("ContactNotLinkedToCompany");
    }
    $morehtmlref.='</div>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

    $cssclass='titlefield';
    //if ($action == 'editnote_public') $cssclass='titlefieldcreate';
    //if ($action == 'editnote_private') $cssclass='titlefieldcreate';

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

    // Civility
    print '<tr><td class="'.$cssclass.'">'.$langs->trans("UserTitle").'</td><td>';
    print $object->getCivilityLabel();
    print '</td></tr>';

    print "</table>";


	$cssclass="titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

    dol_fiche_end();
}

llxFooter();
$db->close();
