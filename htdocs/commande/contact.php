<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2022 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2021 		Frédéric France		<frederic.france@netlogic.fr>
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
 *     \file       htdocs/commande/contact.php
 *     \ingroup    order
 *     \brief      Onglet de gestion des contacts de commande
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'companies', 'bills'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ordercontact', 'globalcard'));

$result = restrictedArea($user, 'commande', $id, '');
$hookmanager->initHooks(array('ordercontactcard', 'globalcard'));

$usercancreate  =  $user->hasRight("commande", "creer");

$object = new Commande($db);

/*
 * Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Add new contact
	if ($action == 'addcontact' && $user->hasRight('commande', 'creer')) {
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0) {
			$contactid = (GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
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
	} elseif ($action == 'swapstatut' && $user->hasRight('commande', 'creer')) {
		// Toggle the status of a contact
		if ($object->fetch($id)) {
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} else {
			dol_print_error($db);
		}
	} elseif ($action == 'deletecontact' && $user->hasRight('commande', 'creer')) {
		// Delete contact
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
$formother = new FormOther($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Card view and edit mode                                                       */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		$object->fetch_thirdparty();

		$title = $object->ref." - ".$langs->trans('ContactsAddresses');
		$help_url = 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes|DE:Modul_Kundenaufträge';
		llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-order page-card_contact');

		$head = commande_prepare_head($object);
		print dol_get_fiche_head($head, 'contact', $langs->trans("CustomerOrder"), -1, 'order');

		// Order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if (0) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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

		print '<br>';

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
		foreach ($dirtpls as $reldir) {
			$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) {
				break;
			}
		}
	} else {
		// Contact not found
		print "ErrorRecordNotFound";
	}
}


// End of page
llxFooter();
$db->close();
