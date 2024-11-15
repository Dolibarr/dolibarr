<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2013  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *  \file       htdocs/societe/contact.php
 *  \ingroup    societe
 *  \brief      Page of contacts of thirdparties
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (isModEnabled('member')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users"));

if (isModEnabled('category')) {
	$langs->load("categories");
}
if (isModEnabled('incoterm')) {
	$langs->load("incoterm");
}
if (isModEnabled('notification')) {
	$langs->load("mails");
}

$error = 0;
$errors = array();


// Get parameters
$action			= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel 		= GETPOST('cancel', 'alpha');
$backtopage 	= GETPOST('backtopage', 'alpha');
$confirm 		= GETPOST('confirm');
$socid 			= GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id');
$selectedfields = GETPOST('selectedfields', 'alpha');

if ($user->socid) {
	$socid = $user->socid;
}

if (empty($socid) && $action == 'view') {
	$action = 'create';
}

// Initialize objects
$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartycontact', 'globalcard'));

if ($object->fetch($socid) <= 0 && $action == 'view') {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);
if (!$user->hasRight('societe', 'contact', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = '';
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}

if ($action == 'confirm_delete' && $user->hasRight('societe', 'contact', 'delete')) {
	$id = GETPOST('id', 'int');
	if (!empty($id) && $socid > 0) {
		$contact = new Contact($db);
		$ret = $contact->fetch($id);
		if ($ret > 0) {
			if ($contact->priv == 0  || ($contact->user_modification_id == ((int) $user->id) && $contact->priv == 1)) {
				$contact->oldcopy = clone $contact; // @phan-suppress-current-line PhanTypeMismatchProperty
				$result = $contact->delete($user);
				if ($result > 0) {
					setEventMessages('RecordDeleted', null, 'mesgs');
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$socid);
					exit();
				} else {
					setEventMessages($contact->error, $contact->errors, 'errors');
				}
			}
		} else {
			setEventMessages($contact->error, $contact->errors, 'errors');
		}
	}
}

/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

if ($socid > 0 && empty($object->id)) {
	$result = $object->fetch($socid);
	if ($result <= 0) {
		dol_print_error(null, $object->error);
	}
}

$title = $langs->trans("ThirdParty");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name." - ".$langs->trans('ContactsAddresses');
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if (!empty($object->id)) {
	$res = $object->fetch_optionals();
}
//if ($res < 0) { dol_print_error($db); exit; }


$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'contact', $langs->trans("ThirdParty"), 0, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 1);

print dol_get_fiche_end();

print '<br>';

if ($action != 'presend') {
	// Contacts list
	if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
		$showuserlogin = in_array('u.user', explode(',', $selectedfields)) ? 1 : 0;
		$result = show_contacts($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id, $showuserlogin);
	}
}
if ($action == 'delete') {
	$formconfirm = $form->formconfirm(
		$_SERVER["PHP_SELF"].'?id='.GETPOST('id').'&socid='.$object->id,
		$langs->trans('Delete'),
		$langs->trans('ConfirmDeleteContact', GETPOST('id', 'alpha')),
		'confirm_delete',
		'',
		0,
		1
	);
	print $formconfirm;
}

// End of page
llxFooter();
$db->close();
