<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *	\file       htdocs/fourn/commande/document.php
 *	\ingroup    supplier
 *	\brief      Management page of attached documents to a supplier order
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("bills", "orders", "sendings", "companies", "deliveries", "products", "stocks", "other"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

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
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$hookmanager->initHooks(array('ordersuppliercarddocument'));

$object = new CommandeFournisseur($db);
if ($object->fetch($id, $ref) < 0) {
	dol_print_error($db);
	exit;
}

$upload_dir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($object->ref);
$object->fetch_thirdparty();

// Security check
$socid = 0;
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

$permissiontoadd = ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer")); // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new	Form($db);

$title = $object->ref." - ".$langs->trans('Documents');
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $title, $help_url);

if ($object->id > 0) {
	$object->fetch_thirdparty();

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans('SupplierOrder'), -1, 'order');


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify' && $caneditproject) {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print "</table>\n";

	print "</div>\n";

	print dol_get_fiche_end();


	$modulepart = 'commande_fournisseur';
	$permissiontoadd = ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
	$permtoedit = ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	header('Location: index.php');
	exit;
}

// End of page
llxFooter();
$db->close();
