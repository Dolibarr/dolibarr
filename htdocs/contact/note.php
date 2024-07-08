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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   \file       htdocs/contact/note.php
 *   \brief      Tab for notes on contact
 *   \ingroup    societe
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$action = GETPOST('action', 'aZ09');

// Load translation files required by the page
$langs->load("companies");

$id = GETPOSTINT('id');

$object = new Contact($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
if ($user->socid > 0) {
	if ($object->fk_soc > 0 && $object->fk_soc != $user->socid) {
		accessforbidden();
	}
}
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');


$permissionnote = $user->hasRight('societe', 'creer'); // Used by the include of actions_setnotes.inc.php

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
// $hookmanager->initHooks(array('contactcard')); -> Name conflict with product/card.php
$hookmanager->initHooks(array('contactnote'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}


/*
 *	View
 */

$now = dol_now();

$title = $langs->trans("ContactNotes");

$form = new Form($db);

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($id > 0) {
	/*
	 * Affichage onglets
	 */
	if (isModEnabled('notification')) {
		$langs->load("mails");
	}

	$head = contact_prepare_head($object);

	print dol_get_fiche_head($head, 'note', $title, -1, 'contact');

	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$morehtmlref .= '<div class="refidno">';
	if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
		$objsoc = new Societe($db);
		$objsoc->fetch($object->socid);
		// Thirdparty
		if ($objsoc->id > 0) {
			$morehtmlref .= $objsoc->getNomUrl(1);
		} else {
			$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
		}
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
