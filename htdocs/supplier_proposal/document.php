<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo		<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2017		Ferran Marcet				<fmarcet@2byte.es>
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
 *       \file       htdocs/comm/propal/document.php
 *       \ingroup    propal
 *       \brief      Management page of documents attached to a business proposal
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
// Load translation files required by the page
$langs->loadLangs(array('compta', 'supplier_proposal', 'other'));

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$id			= GETPOSTINT('id');
$ref		= GETPOST('ref', 'alpha');

// Security check
$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'supplier_proposal', $id);

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

$object = new SupplierProposal($db);
$object->fetch($id, $ref);
if ($object->id > 0) {
	$object->fetch_thirdparty();
	$upload_dir = $conf->supplier_proposal->dir_output.'/'.dol_sanitizeFileName($object->ref);
}

$permissiontoadd = $user->hasRight('supplier_proposal', 'creer');
$usercancreate = $permissiontoadd;

/*
 * Actions
 */

if ($object->id > 0) {
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}


/*
 * View
 */

$title = $object->ref." - ".$langs->trans('Documents');
$help_url = 'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-supplierproposal page-card_documents');

$form = new Form($db);

if ($object->id > 0) {
	$upload_dir = $conf->supplier_proposal->dir_output.'/'.dol_sanitizeFileName($object->ref);

	$head = supplier_proposal_prepare_head($object);
	print dol_get_fiche_head($head, 'document', $langs->trans('CommRequest'), -1, 'supplier_proposal');

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	// Supplier proposal card
	$linkback = '<a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	//$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', 0, 1);
	//$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->fournisseur->commande->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= $object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= ' - '.$proj->title;
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'supplier_proposal';
	$permissiontoadd = $user->hasRight('supplier_proposal', 'creer');
	$permtoedit = $user->hasRight('supplier_proposal', 'creer');
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
