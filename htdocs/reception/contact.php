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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *     \file       htdocs/reception/contact.php
 *     \ingroup    reception
 *     \brief      Onglet de gestion des contacts de reception
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->loadLangs(array("orders", "receptions", "companies"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$object = new Reception($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	if (!empty($object->origin)) {
		$origin = $object->origin;

		$object->fetch_origin();
		$typeobject = $object->origin;
	}

	// Linked documents
	if ($origin == 'order_supplier' && $object->$typeobject->id && (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled))) {
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->$typeobject->id);
	}
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
if ($origin == 'reception') {
	$result = restrictedArea($user, $origin, $object->id);
} else {
	if ($origin == 'supplierorder' || $origin == 'order_supplier') {
		$result = restrictedArea($user, 'fournisseur', $origin_id, 'commande_fournisseur', 'commande');
	} elseif (empty($user->rights->{$origin}->lire) && empty($user->rights->{$origin}->read)) {
		accessforbidden();
	}
}


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->reception->creer) {
	if ($result > 0 && $id > 0) {
		$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
		$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$result = $objectsrc->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($objectsrc->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $objectsrc->error;
			$mesgs = $objectsrc->errors;
		}
		setEventMessages($mesg, $mesgs, 'errors');
	}
} elseif ($action == 'swapstatut' && $user->rights->reception->creer) {
	// bascule du statut d'un contact
	$result = $objectsrc->swapContactStatus(GETPOST('ligne', 'int'));
} elseif ($action == 'deletecontact' && $user->rights->reception->creer) {
	// Efface un contact
	$result = $objectsrc->delete_contact(GETPOST("lineid", 'int'));

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans('Reception'), 'EN:Customers_Orders|FR:receptions_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


// View mode

if ($id > 0 || !empty($ref)) {
	$langs->trans("OrderCard");

	$head = reception_prepare_head($object);
	print dol_get_fiche_head($head, 'contact', $langs->trans("Reception"), -1, 'dollyrevert');


	// Reception card
	$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer reception
	$morehtmlref .= $form->editfieldkey("RefSupplier", '', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", '', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
	// Project
	if (!empty($conf->project->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if (0) {    // Do not change on reception
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				// $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			// We don't have project on reception, so we will use the project or source object instead
			// TODO Add project on reception
			$morehtmlref .= ' : ';
			if (!empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= ' : '.$proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= ' - '.$proj->title;
				}
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	//print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';
	// Linked documents
	if ($origin == 'order_supplier' && $object->$typeobject->id && (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled))) {
		print '<tr><td class="titlefield">';
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->$typeobject->id);
		print $langs->trans("RefOrder").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'commande');
		print "</td>\n";
		print '</tr>';
	}
	if ($typeobject == 'propal' && $object->$typeobject->id && !empty($conf->propal->enabled)) {
		print '<tr><td class="titlefield">';
		$objectsrc = new Propal($db);
		$objectsrc->fetch($object->$typeobject->id);
		print $langs->trans("RefProposal").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'reception');
		print "</td>\n";
		print '</tr>';
	}

	print "</table>";


	//print '</div>';
	//print '<div class="fichehalfright">';
	//print '<div class="underbanner clearboth"></div>';


	//print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	print dol_get_fiche_end();

	// Lines of contacts
	echo '<br>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) {
			break;
		}
	}
}

llxFooter();

$db->close();
