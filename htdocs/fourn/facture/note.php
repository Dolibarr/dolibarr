<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2017      	Ferran Marcet       	<fmarcet@2byte.es>
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
 *      \file       htdocs/fourn/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture fournisseur
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->loadLangs(array("bills", "companies"));

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('invoicesuppliernote'));

$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);
$object->fetch($id, $ref);

$usercancreate = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));
$permissiontoadd = $usercancreate;
$permissionnote = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer")); // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once
}

// Set label
if ($action == 'setlabel' && ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"))) {
	$object->label = GETPOST('label');
	$result = $object->update($user);
	if ($result < 0) {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $object->ref." - ".$langs->trans('Notes');
$helpurl = "EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores";
llxHeader('', $title, $helpurl);

if ($object->id > 0) {
	$object->fetch_thirdparty();

	$alreadypaid = $object->getSommePaiement();

	$head = facturefourn_prepare_head($object);
	$titre = $langs->trans('SupplierInvoice');
	print dol_get_fiche_head($head, 'note', $titre, -1, 'supplier_invoice');


	// Supplier invoice card
	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
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

	$object->totalpaid = $alreadypaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

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
		print ' ('.$langs->transnoentities("InvoiceHasAvoir") . implode(',', $invoicecredits) . ')';
	}
	/*
	if ($facidnext > 0) {
		$facthatreplace = new FactureFournisseur($db);
		$facthatreplace->fetch($facidnext);
		print ' ('.$langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)).')';
	}
	*/
	print '</td></tr>';

	// Label
	print '<tr><td>'.$form->editfieldkey("Label", 'label', $object->label, $object, 0).'</td><td>';
	print $form->editfieldval("Label", 'label', $object->label, $object, 0);
	print '</td></tr>';

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

	print "</table>";

	print '<br>';

	print '<div class="underbanner clearboth"></div>';
	$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
