<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/reception/document.php
 *	\ingroup    reception
 *	\brief      Management page of documents attached to an reception
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

// Load translation files required by the page
$langs->loadLangs(array('receptions', 'companies', 'other'));

$action		= GETPOST('action', 'aZ09');
$confirm	= GETPOST('confirm');
$id			= GETPOSTINT('id');
$ref		= GETPOST('ref');

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$object = new Reception($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	if (!empty($object->origin)) {
		$origin = $object->origin;
		$typeobject = $object->origin;

		$object->fetch_origin();
	}

	// Linked documents
	if ($origin == 'order_supplier' && $object->origin_object->id && isModEnabled("supplier_order")) {
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->origin_object->id);
	}

	$upload_dir = $conf->reception->dir_output."/".dol_sanitizeFileName($object->ref);
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('receptiondocument'));

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'reception', $object->id, '');

if (isModEnabled("reception")) {
	$permissiontoread = $user->hasRight('reception', 'lire');
	$permissiontoadd = $user->hasRight('reception', 'creer');
	$permissiondellink = $user->hasRight('reception', 'creer'); // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'reception_advance', 'validate')));
	$permissiontodelete = $user->hasRight('reception', 'supprimer');
} else {
	$permissiontoread = $user->hasRight('fournisseur', 'commande', 'receptionner');
	$permissiontoadd = $user->hasRight('fournisseur', 'commande', 'receptionner');
	$permissiondellink = $user->hasRight('fournisseur', 'commande', 'receptionner'); // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande', 'receptionner')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande_advance', 'check')));
	$permissiontodelete = $user->hasRight('fournisseur', 'commande', 'receptionner');
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

llxHeader('', $langs->trans('Reception'), '', '', 0, 0, '', '', '', 'mod-reception page-card_documents');

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref)) {
		$object->fetch_thirdparty();

		$upload_dir = $conf->reception->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = reception_prepare_head($object);
		print dol_get_fiche_head($head, 'documents', $langs->trans("Shipment"), -1, $object->picto);


		// Build file list
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
		$totalsize = 0;
		foreach ($filearray as $key => $file) {
			$totalsize += $file['size'];
		}

		// Shipment card
		$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


		$morehtmlref = '<div class="refidno">';
		// Ref customer reception
		$morehtmlref .= $form->editfieldkey("RefSupplier", '', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", '', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (0) {    // Do not change on reception
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify' && $permissiontoadd) {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($objectsrc->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
		$morehtmlref .= '</div>';

		// Order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';

		print "</table>\n";

		print "</div>\n";

		print dol_get_fiche_end();

		$modulepart = 'reception';
		$permissiontoadd = $user->hasRight('reception', 'creer');
		$permtoedit = $user->hasRight('reception', 'creer');
		$param = '&id='.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	} else {
		dol_print_error($db);
	}
} else {
	header('Location: index.php');
	exit;
}

// End of page
llxFooter();
$db->close();
