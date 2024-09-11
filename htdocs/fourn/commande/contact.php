<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2009 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 * Copyright (C) 2023      Christian Foellmann  <christian@foellmann.de>
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
 *       \ingroup    order
 *       \brief      Onglet de gestion des contacts de commande
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("facture", "orders", "sendings", "companies"));

$id		= GETPOSTINT('id');
$ref	= GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$hookmanager->initHooks(array('ordersuppliercardcontact', 'ordersuppliercontactcard', 'globalcard'));
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

$object = new CommandeFournisseur($db);

$usercancreate	= ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
$permissiontoadd	= $usercancreate; // Used by the include of actions_addupdatedelete.inc.php

/*
 * Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 * Add a new contact
 */

if (empty($reshook)) {
	if ($action == 'addcontact' && ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"))) {
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0) {
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$typeid    = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
			$result    = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
		}

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'swapstatut' && ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"))) {
		// Toggle the status of a contact
		if ($object->fetch($id)) {
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'deletecontact' && ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"))) {
		// Deleting a contact
		$object->fetch($id);
		$result = $object->delete_contact(GETPOSTINT("lineid"));

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Card view and edit mode                                                       */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref)) {
	$langs->trans("OrderCard");

	if ($object->fetch($id, $ref) > 0) {
		$object->fetch_thirdparty();

		$title = $object->ref." - ".$langs->trans('ContactsAddresses');
		$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
		llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-supplier-order page-card_contact');

		$head = ordersupplier_prepare_head($object);
		print dol_get_fiche_head($head, 'contact', $langs->trans("SupplierOrder"), -1, 'order');

		// Supplier order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref supplier
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (0) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify' && $caneditproject) {
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


		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

		print dol_get_fiche_end();

		// Contacts lines
		include DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php';
	} else {
		// Contact not found
		print "ErrorRecordNotFound";
	}
}

// End of page
llxFooter();
$db->close();
