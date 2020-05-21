<?php
/* Copyright (C) 2005		Patrick Rouillon	<patrick@rouillon.net>
 * Copyright (C) 2005-2009	Destailleur Laurent	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 *      \file       htdocs/contrat/contact.php
 *      \ingroup    contrat
 *      \brief      Onglet de gestion des contacts des contrats
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('contracts', 'companies'));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'contrat', $id);

$object = new Contrat($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('contractcard', 'globalcard'));


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->contrat->creer)
{
	$result = $object->fetch($id);

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
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			$msg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $object->error;
		}

		setEventMessages($mesg, null, 'errors');
	}
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->rights->contrat->creer)
{
	if ($object->fetch($id))
	{
	    $result = $object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db, $object->error);
	}
}

// Delete contact
if ($action == 'deletecontact' && $user->rights->contrat->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Contract"), "");

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref))
{
	if ($object->fetch($id, $ref) > 0)
	{
		$object->fetch_thirdparty();

	    $head = contract_prepare_head($object);

		$hselected = 1;

		dol_fiche_head($head, $hselected, $langs->trans("Contract"), -1, 'contract');

		// Contract card

        $linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


        $morehtmlref = '';
        //if (! empty($modCodeContract->code_auto)) {
            $morehtmlref .= $object->ref;
        /*} else {
            $morehtmlref.=$form->editfieldkey("",'ref',$object->ref,0,'string','',0,3);
            $morehtmlref.=$form->editfieldval("",'ref',$object->ref,0,'string','',0,2);
        }*/

		$morehtmlref .= '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, 0, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');
		// Ref supplier
		$morehtmlref .= '<br>';
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
		// Thirdparty
	    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
        // Project
        if (!empty($conf->projet->enabled)) {
            $langs->load("projects");
            $morehtmlref .= '<br>'.$langs->trans('Project').' ';
            if ($user->rights->contrat->creer) {
                if ($action != 'classify') {
                	//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
                    $morehtmlref .= ' : ';
                }
                if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	                $morehtmlref .= $formproject->select_projects($object->thirdparty->id, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                $morehtmlref .= '</form>';
	            } else {
	                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->thirdparty->id, $object->fk_project, 'none', 0, 0, 0, 1);
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


	    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'none', $morehtmlref);


	    print '<div class="fichecenter">';
	    print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield" width="100%">';


        // Ligne info remises tiers
        print '<tr><td class="titlefield">'.$langs->trans('Discount').'</td><td colspan="3">';
        if ($object->thirdparty->remise_percent) {
            print $langs->trans("CompanyHasRelativeDiscount", $object->thirdparty->remise_percent);
        } else {
            print $langs->trans("CompanyHasNoRelativeDiscount");
        }
        $absolute_discount = $object->thirdparty->getAvailableDiscounts();
        print '. ';
        if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->trans("Currency".$conf->currency));
        else print $langs->trans("CompanyHasNoAbsoluteDiscount");
        print '.';
        print '</td></tr>';

        // Date
        print '<tr>';
		print '<td class="titlefield">';
		print $form->editfieldkey("Date", 'date_contrat', $object->date_contrat, $object, 0);
		print '</td><td>';
		print $form->editfieldval("Date", 'date_contrat', $object->date_contrat, $object, 0, 'datehourpicker');
		print '</td>';
		print '</tr>';

		print "</table>";

		print '</div>';

		dol_fiche_end();

		print '<br>';

		// Contacts lines
		include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';
	} else {
		print "ErrorRecordNotFound";
	}
}


llxFooter();
$db->close();
