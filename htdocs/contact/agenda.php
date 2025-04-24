<?php
/* Copyright (C) 2004-2005	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke		<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013		Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2014		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *       \file       htdocs/contact/agenda.php
 *       \ingroup    societe
 *       \brief      Card agenda of a contact
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'other', 'commercial'));

$mesg = '';
$error = 0;
$errors = array();

// Get parameters
$action		= (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm	= GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$id = GETPOSTINT('id');
$socid		= GETPOSTINT('socid');

// Initialize objects
$object = new Contact($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$objcanvas = null;
$canvas = (!empty($object->canvas) ? $object->canvas : GETPOST("canvas"));
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('contact', 'contactcard', $canvas);
}

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}
$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('contactagenda', 'globalcard'));

$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', 0); // If we create a contact with no company (shared contacts), no check on write permission

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep, a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}


/*
 *	Actions
 */

$parameters = array('id'=>$id, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$actioncode = '';
		$search_agenda_label = '';
	}
}


/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans("ContactEvents");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/contactnameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->lastname) {
	$title = $object->lastname;
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas|DE:Modul_Partner';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-contact-card_agenda');


if ($socid > 0) {
	$objsoc = new Societe($db);
	$objsoc->fetch($socid);
}

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id) {
		$object = new Contact($db);
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error(null, $object->error);
		}
	}
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	// Confirm deleting contact
	if ($user->hasRight('societe', 'contact', 'supprimer')) {
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id.($backtopage ? '&backtopage='.$backtopage : ''), $langs->trans("DeleteContact"), $langs->trans("ConfirmDeleteContact"), "confirm_delete", '', 0, 1);
		}
	}

	/*
	 * Onglets
	 */
	$head = array();
	if ($id > 0) {
		// Si edition contact deja existent
		$object = new Contact($db);
		$res = $object->fetch($id, $user);
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}

		// Show tabs
		$head = contact_prepare_head($object);

		$title = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
	}

	if (!empty($id) && $action != 'edit' && $action != 'create') {
		$objsoc = new Societe($db);

		/*
		 * Card in view mode
		 */

		dol_htmloutput_errors((string) $error, $errors);

		print dol_get_fiche_head($head, 'agenda', $title, -1, 'contact');

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

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';

		$object->info($id);
		dol_print_object_info($object, 1);

		print '</div>';

		print dol_get_fiche_end();


		// Actions buttons

		$objcon = $object;
		$object->fetch_thirdparty();
		$objthirdparty = $object->thirdparty;

		$out = '';
		$newcardbutton = '';
		$messagingUrl = DOL_URL_ROOT.'/contact/messaging.php?id='.$object->id;
		$newcardbutton .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 1);
		$messagingUrl = DOL_URL_ROOT.'/contact/agenda.php?id='.$object->id;
		$newcardbutton .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 2);

		if (isModEnabled('agenda')) {
			$permok = $user->hasRight('agenda', 'myactions', 'create');
			if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
				if (is_object($objthirdparty) && get_class($objthirdparty) == 'Societe') {
					$out .= '&amp;originid='.$objthirdparty->id.($objthirdparty->id > 0 ? '&amp;socid='.$objthirdparty->id : '');
				}
				$out .= (!empty($objcon->id) ? '&amp;contactid='.$objcon->id : '').'&amp;origin=contact&amp;originid='.$object->id.'&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].($objcon->id > 0 ? '?id='.$objcon->id : ''));
				$out .= '&amp;datep='.urlencode(dol_print_date(dol_now(), 'dayhourlog'));
			}

			if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
				$newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
			}
		}

		if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
			print '<br>';

			$param = '&id='.$id;
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
				$param .= '&contextpage='.$contextpage;
			}
			if ($limit > 0 && $limit != $conf->liste_limit) {
				$param .= '&limit='.$limit;
			}

			print load_fiche_titre($langs->trans("ActionsOnContact"), $newcardbutton, '');
			//print_barre_liste($langs->trans("ActionsOnCompany"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $morehtmlcenter, 0, -1, '', '', '', '', 0, 1, 1);

			// List of all actions
			$filters = array();
			$filters['search_agenda_label'] = $search_agenda_label;
			$filters['search_rowid'] = $search_rowid;

			show_actions_done($conf, $langs, $db, $objthirdparty, $object, 0, $actioncode, '', $filters, $sortfield, $sortorder);
		}
	}
}


llxFooter();

$db->close();
