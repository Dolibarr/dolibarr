<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       resource/card.php
 *		\ingroup    resource
 *		\brief      Page to manage resource object
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('resource', 'companies', 'other', 'main'));

// Get parameters
$id						= GETPOST('id', 'int');
$action					= GETPOST('action', 'aZ09');
$cancel					= GETPOST('cancel', 'alpha');
$ref					= GETPOST('ref', 'alpha');
$description			= GETPOST('description', 'restricthtml');
$confirm				= GETPOST('confirm', 'aZ09');
$fk_code_type_resource = GETPOST('fk_code_type_resource', 'alpha');
$country_id				= GETPOST('country_id', 'int');

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

$object = new Dolresource($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$result = restrictedArea($user, 'resource', $object->id, 'resource');

$permissiontoadd = $user->rights->resource->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->resource->delete;


/*
 * Actions
 */

$hookmanager->initHooks(array('resource', 'resource_card', 'globalcard'));
$parameters = array('resource_id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		if ($action == 'add') {
			header("Location: ".DOL_URL_ROOT.'/resource/list.php');
			exit;
		}
		$action = '';
	}

	if ($action == 'add' && $user->hasRight('resource', 'write')) {
		if (!$cancel) {
			$error = '';

			if (empty($ref)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
				$action = 'create';
			} else {
				$object->ref                    = $ref;
				$object->description            = $description;
				$object->fk_code_type_resource  = $fk_code_type_resource;
				$object->country_id             = $country_id;

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
				}

				$result = $object->create($user);
				if ($result > 0) {
					// Creation OK
					setEventMessages($langs->trans('ResourceCreatedWithSuccess'), null, 'mesgs');
					Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					// Creation KO
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'create';
				}
			}
		} else {
			Header("Location: list.php");
			exit;
		}
	}

	if ($action == 'update' && !$cancel && $user->hasRight('resource', 'write')) {
		$error = 0;

		if (empty($ref)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$res = $object->fetch($id);
			if ($res > 0) {
				$object->ref          			= $ref;
				$object->description  			= $description;
				$object->fk_code_type_resource  = $fk_code_type_resource;
				$object->country_id             = $country_id;

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
				if ($ret < 0) {
					$error++;
				}

				$result = $object->update($user);
				if ($result > 0) {
					Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit';
		}
	}

	if ($action == 'confirm_delete_resource' && $user->hasRight('resource', 'delete') && $confirm === 'yes') {
		$res = $object->fetch($id);
		if ($res > 0) {
			$result = $object->delete($id);

			if ($result >= 0) {
				setEventMessages($langs->trans('RessourceSuccessfullyDeleted'), null, 'mesgs');
				header('Location: '.DOL_URL_ROOT.'/resource/list.php');
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 * View
 */

$title = $langs->trans($action == 'create' ? 'AddResource' : 'ResourceSingular');
llxHeader('', $title, '');

$form = new Form($db);
$formresource = new FormResource($db);

if ($action == 'create' || $object->fetch($id, $ref) > 0) {
	if ($action == 'create') {
		print load_fiche_titre($title, '', 'object_resource');
		print dol_get_fiche_head('');
	} else {
		$head = resource_prepare_head($object);
		print dol_get_fiche_head($head, 'resource', $title, -1, 'resource');
	}

	if ($action == 'create' || $action == 'edit') {
		if (!$user->hasRight('resource', 'write')) {
			accessforbidden('', 0, 1);
		}

		// Create/Edit object

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.($action == "create" ? "add" : "update").'">';

		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("ResourceFormLabel_ref").'</td>';
		print '<td><input class="minwidth200" name="ref" value="'.($ref ? $ref : $object->ref).'" autofocus="autofocus"></td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("ResourceType").'</td>';
		print '<td>';
		$formresource->select_types_resource($object->fk_code_type_resource, 'fk_code_type_resource', '', 2);
		print '</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
		print '<td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('description', ($description ? $description : $object->description), '', '200', 'dolibarr_notes', false);
		$doleditor->Create();
		print '</td></tr>';

		// Origin country
		print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td>';
		print $form->select_country($object->country_id, 'country_id');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit');
		}

		print '</table>';

		print dol_get_fiche_end();

		$button_label = ($action == "create" ? "Create" : "Modify");
		print $form->buttonsSaveCancel($button_label);

		print '</div>';

		print '</form>';
	} else {
		$formconfirm = '';

		// Confirm deleting resource line
		if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteResource"), $langs->trans("ConfirmDeleteResource"), "confirm_delete_resource", '', 0, "action-delete");
		}

		// Print form confirm
		print $formconfirm;


		$linkback = '<a href="'.DOL_URL_ROOT.'/resource/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&id='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= '</div>';


		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		/*---------------------------------------
		 * View object
		 */
		print '<table class="border tableforfield centpercent">';

		// Resource type
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("ResourceType").'</td>';
		print '<td>';
		print $object->type_label;
		print '</td>';
		print '</tr>';

		// Description
		print '<tr>';
		print '<td>'.$langs->trans("ResourceFormLabel_description").'</td>';
		print '<td>';
		print $object->description;
		print '</td>';
		print '</tr>';

		// Origin country code
		print '<tr>';
		print '<td>'.$langs->trans("CountryOrigin").'</td>';
		print '<td>';
		print getCountry($object->country_id, 0, $db);
		print '</td>';


		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</tr>';

		print '</table>';

		print '</div>';

		print '<div class="clearboth"></div><br>';

		print dol_get_fiche_end();
	}


	/*
	 * Boutons actions
	 */
	print '<div class="tabsAction">';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
	// modified by hook
	if (empty($reshook)) {
		if ($action != "create" && $action != "edit") {
			// Edit resource
			if ($user->hasRight('resource', 'write')) {
				print '<div class="inline-block divButAction">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=edit&token='.newToken().'" class="butAction">'.$langs->trans('Modify').'</a>';
				print '</div>';
			}
		}
		if ($action != "create" && $action != "edit") {
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can't use preloaded confirm form with jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete);
		}
	}
	print '</div>';
} else {
	dol_print_error();
}

// End of page
llxFooter();
$db->close();
