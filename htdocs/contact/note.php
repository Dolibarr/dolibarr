<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013           Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2020           Bailly Benjamin      <benjamin@netlogic.fr>
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
 *   \file       htdocs/contact/note.php
 *   \brief      Tab for notes on contact
 *   \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$action = GETPOST('action', 'aZ09');

// Load translation files required by the page
$langs->load("companies");

// Security check
$id = GETPOST('id', 'int');
if ($user->socid) $id = $user->socid;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

$object = new Contact($db);
if ($id > 0) $object->fetch($id);

$permissionnote = $user->rights->societe->creer; // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once


/*
 *	View
 */

$now = dol_now();

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

$form = new Form($db);

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($id > 0)
{
	/*
     * Affichage onglets
     */
	if (!empty($conf->notification->enabled)) $langs->load("mails");

	$head = contact_prepare_head($object);

	print dol_get_fiche_head($head, 'note', $title, -1, 'contact');

	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';

    // Code added here, resolving issue when users who have no access rights to see for fournisseurs can access to them anyway by passing trough contacts  //
    $sql = "SELECT fournisseur, client FROM " . MAIN_DB_PREFIX . "societe WHERE rowid = " . (int) $object->socid ;
    $resql = $db->query($sql);
	if ($resql) {
		$result = $resql->fetch_object();
	}
	if ($result->client == 0 && $result->fournisseur > 0) {
		if (!empty($user->rights->fournisseur->lire)) {
			if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
				$objsoc = new Societe($db);
				$objsoc->fetch($object->socid);
				// Thirdparty
				$morehtmlref .= $langs->trans('ThirdParty') . ' : ';
				if ($objsoc->id > 0) $morehtmlref .= $objsoc->getNomUrl(1, 'contact');
				else $morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
			}
		}
	}

	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS) && $result->client > 0)
        {
		$objsoc = new Societe($db);
		$objsoc->fetch($object->socid);
		// Thirdparty
		$morehtmlref .= $langs->trans('ThirdParty').' : ';
		if ($objsoc->id > 0) $morehtmlref .= $objsoc->getNomUrl(1, 'contact');
		else $morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
	}

    $morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	$cssclass = 'titlefield';
	//if ($action == 'editnote_public') $cssclass='titlefieldcreate';
	//if ($action == 'editnote_private') $cssclass='titlefieldcreate';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Civility
	print '<tr><td class="'.$cssclass.'">'.$langs->trans("UserTitle").'</td><td>';
	print $object->getCivilityLabel();
	print '</td></tr>';

	print "</table>";


	$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	print dol_get_fiche_end();
}

llxFooter();
$db->close();
