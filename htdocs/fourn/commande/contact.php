<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2009 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/fourn/commande/contact.php
 *       \ingroup    commande
 *       \brief      Onglet de gestion des contacts de commande
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("facture", "orders", "sendings", "companies"));

$id		= GETPOST('id', 'int');
$ref	= GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

$object = new CommandeFournisseur($db);


/*
 * Add a new contact
 */

if ($action == 'addcontact' && $user->rights->fournisseur->commande->creer)
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

// Toggle the status of a contact
elseif ($action == 'swapstatut' && $user->rights->fournisseur->commande->creer)
{
	if ($object->fetch($id))
	{
	    $result = $object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Deleting a contact
elseif ($action == 'deletecontact' && $user->rights->fournisseur->commande->creer)
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
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $langs->trans("Order"), $help_url);

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
	$langs->trans("OrderCard");

	if ($object->fetch($id, $ref) > 0)
	{
		$object->fetch_thirdparty();

		$head = ordersupplier_prepare_head($object);
		dol_fiche_head($head, 'contact', $langs->trans("SupplierOrder"), -1, 'order');

		// Supplier order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref supplier
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
		// Project
		if (!empty($conf->projet->enabled))
		{
		    $langs->load("projects");
		    $morehtmlref .= '<br>'.$langs->trans('Project').' ';
		    if ($user->rights->fournisseur->commande->creer)
		    {
		        if ($action != 'classify') {
		        	//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
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


		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

		dol_fiche_end();

		// Contacts lines
		include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';
	}
	else
	{
		// Contact not found
		print "ErrorRecordNotFound";
	}
}

// End of page
llxFooter();
$db->close();
