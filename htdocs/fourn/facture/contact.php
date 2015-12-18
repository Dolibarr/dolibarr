<?php
/* Copyright (C) 2005		Patrick Rouillon	<patrick@rouillon.net>
 * Copyright (C) 2005-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/fourn/facture/contact.php
 *      \ingroup    facture, fournisseur
 *      \brief      Onglet de gestion des contacts des factures
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("bills");
$langs->load('other');
$langs->load("companies");

$id		= (GETPOST('id','int') ? GETPOST('id','int') : GETPOST('facid','int'));
$ref	= GETPOST('ref','alpha');
$action	= GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);


/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->fournisseur->facture->creer)
{
	$result = $object->fetch($id, $ref);

    if ($result > 0 && $id > 0)
    {
    	$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
  		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->fournisseur->facture->creer)
{
	if ($object->fetch($id))
	{
	    $result=$object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->fournisseur->facture->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Bill"), "Facture");

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		$object->fetch_thirdparty();

		$head = facturefourn_prepare_head($object);

		dol_fiche_head($head, 'contact', $langs->trans('SupplierInvoice'), 0, 'bill');

		/*
		 *   Facture synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Reference du facture
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
		print "</td></tr>";

        // Ref supplier
        print '<tr><td class="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$object->ref_supplier.'</td>';
        print "</tr>\n";

		// Third party
		print "<tr><td>".$langs->trans("Supplier")."</td>";
		print '<td colspan="3">'.$object->thirdparty->getNomUrl(1,'supplier').'</td></tr>';

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
			foreach($facidavoir as $fid)
			{
				if ($i==0) print ' ';
				else print ',';
				$facavoir=new FactureFournisseur($db);
				$facavoir->fetch($fid);
				print $facavoir->getNomUrl(1);
			}
			print ')';
		}
		if ($facidnext > 0)
		{
			$facthatreplace=new FactureFournisseur($db);
			$facthatreplace->fetch($facidnext);
			print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
		}
		print '</td></tr>';

		// Label
		print '<tr><td>'.$form->editfieldkey("Label",'label',$object->label,$object,0).'</td><td colspan="3">';
		print $form->editfieldval("Label",'label',$object->label,$object,0);
		print '</td></tr>';

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

		print "</table>";

		dol_fiche_end();

		print '<br>';

		// Contacts lines
		include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';

	}
	else
	{
		print "ErrorRecordNotFound";
	}
}


llxFooter();
$db->close();
