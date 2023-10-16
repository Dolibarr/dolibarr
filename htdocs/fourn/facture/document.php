<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2016		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Ferran Marcet       	<fmarcet@2byte.es>
 * Copyright (C) 2021		Frédéric France			<frederic.france@netlogic.fr>
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
 *       \file       htdocs/fourn/facture/document.php
 *       \ingroup    facture, fournisseur
 *       \brief      Page to manage documents joined to vendor invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->loadLangs(array('bills', 'other', 'companies'));

$id = GETPOST('facid', 'int') ?GETPOST('facid', 'int') : GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');
$hookmanager->initHooks(array('invoicesuppliercarddocument'));

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

$object = new FactureFournisseur($db);
if ($object->fetch($id, $ref)) {
	$object->fetch_thirdparty();
	$ref = dol_sanitizeFileName($object->ref);
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$ref;
}

$permissiontoadd = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")); // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $object->ref." - ".$langs->trans('Documents');
$helpurl = "EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores";
llxHeader('', $title, $helpurl);

if ($object->id > 0) {
	$head = facturefourn_prepare_head($object);
	print dol_get_fiche_head($head, 'documents', $langs->trans('SupplierInvoice'), -1, 'supplier_invoice');

	$totalpaid = $object->getSommePaiement();

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
		$morehtmlref .= ' <div class="inline-block valignmiddle">(<a class="valignmiddle" href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)</div>';
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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

	$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	/*
	 * Confirm delete file
	 */
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	print '<table class="border tableforfield centpercent">';

	// Type
	print '<tr><td class="titlefield">'.$langs->trans('Type').'</td><td>';
	print '<span class="badgeneutral">';
	print $object->getLibType();
	print '</span>';
	if ($object->type == FactureFournisseur::TYPE_REPLACEMENT) {
		$facreplaced = new FactureFournisseur($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
	}
	if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
		$facusing = new FactureFournisseur($db);
		$facusing->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
	}

	$facidavoir = $object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0) {
		$invoicecredits = array();
		foreach ($facidavoir as $facid) {
			$facavoir = new FactureFournisseur($db);
			$facavoir->fetch($facid);
			$invoicecredits[] = $facavoir->getNomUrl(1);
		}
		print ' ('.$langs->transnoentities("InvoiceHasAvoir") . (count($invoicecredits) ? ' ' : '') . implode(',', $invoicecredits) . ')';
	}
	/*
	if ($facidnext > 0) {
		$facthatreplace = new FactureFournisseur($db);
		$facthatreplace->fetch($facidnext);
		print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
	}
	*/
	print '</td></tr>';

	// Label
	print '<tr><td>'.$form->editfieldkey("Label", 'label', $object->label, $object, 0).'</td><td>';
	print $form->editfieldval("Label", 'label', $object->label, $object, 0);
	print '</td>';

	// Amount
	print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($object->total_ht, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';
	print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';

	// Amount Local Taxes
	//TODO: Place into a function to control showing by country or study better option
	if ($mysoc->localtax1_assuj == "1") { //Localtax1
		print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
		print '<td>'.price($object->total_localtax1, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
		print '</tr>';
	}
	if ($mysoc->localtax2_assuj == "1") { //Localtax2
		print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
		print '<td>'.price($object->total_localtax2, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
		print '</tr>';
	}
	print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($object->total_ttc, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';

	print '</table><br>';

	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Nb of files
	print '<tr><td class="titlefield nowrap">'.$langs->trans('NbOfAttachedFiles').'</td><td>'.count($filearray).'</td></tr>';

	print '<tr><td>'.$langs->trans('TotalSizeOfAttachedFiles').'</td><td>'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	$modulepart = 'facture_fournisseur';
	$permission = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
	$permtoedit = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
	$param = '&facid='.$object->id;

	$defaulttpldir = '/core/tpl';
	$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
	foreach ($dirtpls as $module => $reldir) {
		if (!empty($module)) {
			$tpl = dol_buildpath($reldir.'/document_actions_post_headers.tpl.php');
		} else {
			$tpl = DOL_DOCUMENT_ROOT.$reldir.'/document_actions_post_headers.tpl.php';
		}

		if (empty($conf->file->strict_mode)) {
			$res = @include $tpl;
		} else {
			$res = include $tpl; // for debug
		}
		if ($res) {
			break;
		}
	}
} else {
	print $langs->trans('ErrorUnknown');
}

// End of page
llxFooter();
$db->close();
