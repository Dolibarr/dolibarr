<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
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
 *      \file       htdocs/fourn/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture fournisseur
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->loadLangs(array("bills", "companies"));

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);
$object->fetch($id, $ref);

$permissionnote = $user->rights->fournisseur->facture->creer; // Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once

// Set label
if ($action == 'setlabel' && $user->rights->fournisseur->facture->creer)
{
	$object->label = $_POST['label'];
	$result = $object->update($user);
	if ($result < 0) dol_print_error($db);
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('SupplierInvoice')." - ".$langs->trans('Notes');
$helpurl = "EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores";
llxHeader('', $title, $helpurl);

if ($object->id > 0)
{
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
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($user->rights->fournisseur->commande->creer)
		{
			if ($action != 'classify') {
				// $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				$morehtmlref .= ' : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				$morehtmlref .= $proj->ref;
				$morehtmlref .= '</a>';
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	$object->totalpaye = $alreadypaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Type
	print '<tr><td class="titlefield">'.$langs->trans('Type').'</td><td>';
	print $object->getLibType();
	if ($object->type == FactureFournisseur::TYPE_REPLACEMENT)
	{
		$facreplaced = new FactureFournisseur($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
	}
	if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE)
	{
		$facusing = new FactureFournisseur($db);
		$facusing->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
	}

	$facidavoir = $object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0)
	{
		print ' ('.$langs->transnoentities("InvoiceHasAvoir");
		$i = 0;
		foreach ($facidavoir as $fid)
		{
			if ($i == 0) print ' ';
			else print ',';
			$facavoir = new FactureFournisseur($db);
			$facavoir->fetch($fid);
			print $facavoir->getNomUrl(1);
		}
		print ')';
	}
	if ($facidnext > 0)
	{
		$facthatreplace = new FactureFournisseur($db);
		$facthatreplace->fetch($facidnext);
		print ' ('.$langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)).')';
	}
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
	if ($societe->localtax1_assuj == "1") //Localtax1
	{
		print '<tr><td>'.$langs->transcountry("AmountLT1", $societe->country_code).'</td>';
		print '<td>'.price($object->total_localtax1, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
		print '</tr>';
	}
	if ($societe->localtax2_assuj == "1") //Localtax2
	{
		print '<tr><td>'.$langs->transcountry("AmountLT2", $societe->country_code).'</td>';
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
