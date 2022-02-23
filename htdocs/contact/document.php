<?php
/* Copyright (C) 2014	    Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2021  Frederic France		<frederic.france@free.fr>
 * Copyright (C) 2017	    Regis Houssin		<regis.houssin@inodbox.com>
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
 *       \file       htdocs/contact/document.php
 *       \ingroup    contact
 *       \brief      Page with attached files on contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array('other', 'companies', 'contact'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

$object = new Contact($db);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$objcanvas = null;
$canvas = (!empty($object->canvas) ? $object->canvas : GETPOST("canvas"));
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('contact', 'contactcard', $canvas);
}

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!empty($conf->global->MAIN_DOC_SORT_FIELD)) {
	$sortfield = $conf->global->MAIN_DOC_SORT_FIELD;
}
if (!empty($conf->global->MAIN_DOC_SORT_ORDER)) {
	$sortorder = $conf->global->MAIN_DOC_SORT_ORDER;
}

if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

if ($id > 0) {
	$object->fetch($id);
}

$upload_dir = $conf->societe->multidir_output[$object->entity].'/contact/'.dol_sanitizeFileName($object->ref);
$modulepart = 'contact';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('contactdocument'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', 0); // If we create a contact with no company (shared contacts), no check on write permission

$permissiontoadd = $user->rights->societe->contact->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->lastname) {
	$title = $object->lastname;
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if ($object->id) {
	$head = contact_prepare_head($object);
	$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

	print dol_get_fiche_head($head, 'documents', $title, -1, 'contact');


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$morehtmlref .= '<div class="refidno">';
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		$objsoc = new Societe($db);
		$objsoc->fetch($object->socid);
		// Thirdparty
		$morehtmlref .= $langs->trans('ThirdParty').' : ';
		if ($objsoc->id > 0) {
			$morehtmlref .= $objsoc->getNomUrl(1);
		} else {
			$morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Company
	/*
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
	{
		if ($object->socid > 0)
		{
			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);

			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
		}

		else
		{
			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
			print $langs->trans("ContactNotLinkedToCompany");
			print '</td></tr>';
		}
	}*/

	// Civility
	print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td colspan="3">';
	print $object->getCivilityLabel();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'contact';
	$permissiontoadd = $user->rights->societe->contact->creer;
	$permtoedit = $user->rights->societe->contact->creer;
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
