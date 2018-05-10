<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *     \file       htdocs/expedition/contact.php
 *     \ingroup    expedition
 *     \brief      Onglet de gestion des contacts de expedition
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition', $id,'');

$object = new Expedition($db);
if ($id > 0 || ! empty($ref))
{
    $object->fetch($id, $ref);
    $object->fetch_thirdparty();

    if (!empty($object->origin))
    {
        $typeobject = $object->origin;
        $origin = $object->origin;
        $object->fetch_origin();
    }

    // Linked documents
    if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
    {
        $objectsrc=new Commande($db);
        $objectsrc->fetch($object->$typeobject->id);
    }
    if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
    {
        $objectsrc=new Propal($db);
        $objectsrc->fetch($object->$typeobject->id);
    }
}


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->expedition->creer)
{
    if ($result > 0 && $id > 0)
    {
  		$result = $objectsrc->add_contact(GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'), $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($objectsrc->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $objectsrc->error;
			$mesgs = $objectsrc->errors;
		}
		setEventMessages($mesg, $mesgs, 'errors');
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->expedition->creer)
{
    $result=$objectsrc->swapContactStatus(GETPOST('ligne'));
}

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->expedition->creer)
{
	$result = $objectsrc->delete_contact(GETPOST("lineid"));

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
else if ($action == 'setaddress' && $user->rights->expedition->creer)
{
	$object->fetch($id);
	$result=$object->setDeliveryAddress($_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}*/


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:expeditions_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	$langs->trans("OrderCard");

	$head = shipping_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Shipment"), -1, 'sending');


	// Shipment card
	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	// Ref customer shipment
	$morehtmlref.=$form->editfieldkey("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", '', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', null, null, '', 1);
	// Thirdparty
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    // Project
    if (! empty($conf->projet->enabled)) {
        $langs->load("projects");
        $morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
        if (0) {    // Do not change on shipment
            if ($action != 'classify') {
                $morehtmlref .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            }
            if ($action == 'classify') {
                // $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            // We don't have project on shipment, so we will use the project or source object instead
            // TODO Add project on shipment
            $morehtmlref .= ' : ';
            if (! empty($objectsrc->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($objectsrc->fk_project);
                $morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $objectsrc->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                $morehtmlref .= $proj->ref;
                $morehtmlref .= '</a>';
            } else {
                $morehtmlref .= '';
            }
        }
    }
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
    //print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">';

    // Linked documents
	if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
	{
	    print '<tr><td class="titlefield">';
	    $objectsrc=new Commande($db);
	    $objectsrc->fetch($object->$typeobject->id);
	    print $langs->trans("RefOrder").'</td>';
	    print '<td colspan="3">';
	    print $objectsrc->getNomUrl(1,'commande');
	    print "</td>\n";
	    print '</tr>';
	}
	if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
	{
	    print '<tr><td class="titlefield">';
	    $objectsrc=new Propal($db);
	    $objectsrc->fetch($object->$typeobject->id);
	    print $langs->trans("RefProposal").'</td>';
	    print '<td colspan="3">';
	    print $objectsrc->getNomUrl(1,'expedition');
	    print "</td>\n";
	    print '</tr>';
	}

	print "</table>";


	//print '</div>';
	//print '<div class="fichehalfright">';
	//print '<div class="ficheaddleft">';
	//print '<div class="underbanner clearboth"></div>';


	//print '</div>';
	//print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	dol_fiche_end();

	// Lignes de contacts
	echo '<br>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
	foreach($dirtpls as $reldir)
	{
	    $res=@include dol_buildpath($reldir.'/contacts.tpl.php');
	    if ($res) break;
	}

}

llxFooter();

$db->close();
