<?php
/* Copyright (C) 2021		VIAL-GOUTEYRON Quentin		<quentin.vial-gouteyron@atm-consulting.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *  \file       htdocs/contact/project.php
 *  \ingroup    contact
 *  \brief      Page of third party projects
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->loadLangs(array("contacts", "companies", "projects"));

// Security check
$id = GETPOSTINT('id');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projectcontact'));

$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

/*
 *	Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 *	View
 */

$form = new Form($db);

if ($id) {
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

	$object = new Contact($db);

	$result = $object->fetch($id);
	if (empty($object->thirdparty)) {
		$object->fetch_thirdparty();
	}
	$socid = !empty($object->thirdparty->id) ? $object->thirdparty->id : null;
	$title = $langs->trans("Projects");
	if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
		$title = $object->name." - ".$title;
	}
	$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-contact-card_project');

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = contact_prepare_head($object);

	print dol_get_fiche_head($head, 'project', $langs->trans("Contact"), -1, 'contact');

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
			$morehtmlref .= $objsoc->getNomUrl(1, 'contact');
		} else {
			$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Civility
	print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td>';
	print $object->getCivilityLabel();
	print '</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();
	print '<br>';

	// Projects list
	$result = show_contacts_projects($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?id='.$object->id, 1);
}

// End of page
llxFooter();
$db->close();
