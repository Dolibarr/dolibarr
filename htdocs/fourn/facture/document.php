<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/fourn/facture/document.php
 *       \ingroup    facture, fournisseur
 *       \brief      Page de gestion des documents attaches a une facture fournisseur
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load('bills');
$langs->load('other');
$langs->load("companies");

$id = GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$ref = GETPOST('ref','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new FactureFournisseur($db);
if ($object->fetch($id, $ref))
{
	$object->fetch_thirdparty();
	$ref=dol_sanitizeFileName($object->ref);
	$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref;
}


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

llxHeader();

if ($object->id > 0)
{
	$head = facturefourn_prepare_head($object);
	dol_fiche_head($head, 'documents', $langs->trans('SupplierInvoice'), 0, 'bill');

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

	/*
	 * Confirm delete file
	 */
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);

	}

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%" class="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td>';
	print "</tr>\n";

	// Ref supplier
	print '<tr><td class="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$object->ref_supplier.'</td>';
	print "</tr>\n";

	// Thirdparty
	print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1,'supplier').'</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
	print $object->getLibType();
	if ($object->type == FactureFournisseur::TYPE_REPLACEMENT)
	{
		$facreplaced=new FactureFournisseur($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
	}
	if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE)
	{
		$facusing=new FactureFournisseur($db);
		$facusing->fetch($object->fk_facture_source);
		print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
	}

	$facidavoir=$object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0)
	{
		print ' ('.$langs->transnoentities("InvoiceHasAvoir");
		$i=0;
		foreach($facidavoir as $id)
		{
			if ($i==0) print ' ';
			else print ',';
			$facavoir=new FactureFournisseur($db);
			$facavoir->fetch($id);
			print $facavoir->getNomUrl(1);
		}
		print ')';
	}
	/*
	if ($facidnext > 0)
	{
		$facthatreplace=new FactureFournisseur($db);
		$facthatreplace->fetch($facidnext);
		print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
	}
	*/
	print '</td></tr>';

	// Label
	print '<tr><td>'.$form->editfieldkey("Label",'label',$object->label,$object,0).'</td><td colspan="3">';
	print $form->editfieldval("Label",'label',$object->label,$object,0);
	print '</td>';

	// Status
	$alreadypaid=$object->getSommePaiement();
	print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4,$alreadypaid).'</td></tr>';

	// Amount
	print '<tr><td>'.$langs->trans('AmountHT').'</td><td colspan="3">'.price($object->total_ht,1,$langs,0,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td>'.$langs->trans('AmountVAT').'</td><td colspan="3">'.price($object->total_tva,1,$langs,0,-1,-1,$conf->currency).'</td></tr>';

	// Amount Local Taxes
	//TODO: Place into a function to control showing by country or study better option
	if ($societe->localtax1_assuj=="1") //Localtax1
	{
		print '<tr><td>'.$langs->transcountry("AmountLT1",$societe->country_code).'</td>';
		print '<td colspan="3">'.price($object->total_localtax1,1,$langs,0,-1,-1,$conf->currency).'</td>';
		print '</tr>';
	}
	if ($societe->localtax2_assuj=="1") //Localtax2
	{
		print '<tr><td>'.$langs->transcountry("AmountLT2",$societe->country_code).'</td>';
		print '<td colspan="3">'.price($object->total_localtax2,1,$langs,0,-1,-1,$conf->currency).'</td>';
		print '</tr>';
	}
	print '<tr><td>'.$langs->trans('AmountTTC').'</td><td colspan="3">'.price($object->total_ttc,1,$langs,0,-1,-1,$conf->currency).'</td></tr>';

	print '</table><br>';

	print '<table class="border" width="100%">';

	// Nb of files
	print '<tr><td width="30%" class="nowrap">'.$langs->trans('NbOfAttachedFiles').'</td><td colspan="3">'.count($filearray).'</td></tr>';

	print '<tr><td>'.$langs->trans('TotalSizeOfAttachedFiles').'</td><td colspan="3">'.$totalsize.' '.$langs->trans('bytes').'</td></tr>';

	print '</table>';
	print '</div>';

	$modulepart = 'facture_fournisseur';
	$permission = $user->rights->fournisseur->facture->creer;
	$param = '&facid=' . $object->id;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    print $langs->trans('ErrorUnknown');
}


llxFooter();
$db->close();
