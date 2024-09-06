<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2023-2024	William Mead		<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
$id						= GETPOSTINT('id');
$action					= GETPOST('action', 'aZ09');
$cancel					= GETPOST('cancel', 'alpha');
$ref					= GETPOST('ref', 'alpha');
$address				= GETPOST('address', 'alpha');
$zip					= GETPOST('zipcode', 'alpha');
$town					= GETPOST('town', 'alpha');
$country_id				= GETPOSTINT('country_id');
$state_id				= GETPOSTINT('state_id');
$description			= GETPOST('description', 'restricthtml');
$phone					= GETPOST('phone', 'alpha');
$email					= GETPOST('email', 'alpha');
$max_users				= GETPOSTINT('max_users');
$url					= GETPOST('url', 'alpha');
$confirm				= GETPOST('confirm', 'aZ09');
$fk_code_type_resource	= GETPOST('fk_code_type_resource', 'aZ09');

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

$object = new Dolresource($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

$hookmanager->initHooks(array('resource', 'resource_card', 'globalcard'));

$result = restrictedArea($user, 'resource', $object->id, 'resource');

$permissiontoadd = $user->hasRight('resource', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('resource', 'delete');


/*
 * Actions
 */

$parameters = array('resource_id' => $id);
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
		if ($action == 'add') {	// Test on permission not required here
			header("Location: ".DOL_URL_ROOT.'/resource/list.php');
			exit;
		}
		$action = '';
	}

	if ($action == 'add' && $permissiontoadd) {
		if (!$cancel) {
			$error = '';

			if (empty($ref)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
				$action = 'create';
			} else {
				$object->ref                    = $ref;
				$object->address				= $address;
				$object->zip					= $zip;
				$object->town					= $town;
				$object->country_id				= $country_id;
				$object->state_id				= $state_id;
				$object->description			= $description;
				$object->phone					= $phone;
				$object->email					= $email;
				$object->max_users				= $max_users;
				$object->url					= $url;
				$object->fk_code_type_resource	= $fk_code_type_resource;

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
				}

				$result = $object->create($user);
				if ($result > 0) {
					// Creation OK
					setEventMessages($langs->trans('ResourceCreatedWithSuccess'), null);
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					// Creation KO
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'create';
				}
			}
		} else {
			header("Location: list.php");
			exit;
		}
	}

	if ($action == 'update' && !$cancel && $permissiontoadd) {
		$error = 0;

		if (empty($ref)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$res = $object->fetch($id);
			if ($res > 0) {
				$object->ref          			= $ref;
				$object->address				= $address;
				$object->zip					= $zip;
				$object->town					= $town;
				$object->country_id             = $country_id;
				$object->state_id				= $state_id;
				$object->description  			= $description;
				$object->phone					= $phone;
				$object->email					= $email;
				$object->max_users				= $max_users;
				$object->url					= $url;
				$object->fk_code_type_resource  = $fk_code_type_resource;

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
				if ($ret < 0) {
					$error++;
				}

				$result = $object->update($user);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
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

	if ($action == 'confirm_delete_resource' && $permissiontodelete && $confirm === 'yes') {
		$res = $object->fetch($id);
		if ($res > 0) {
			$result = $object->delete($user);

			if ($result >= 0) {
				setEventMessages($langs->trans('RessourceSuccessfullyDeleted'), null);
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
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-resource page-card');

$form = new Form($db);
$formresource = new FormResource($db);

if ($action == 'create' || $object->fetch($id, $ref) > 0) {
	if ($action == 'create') {
		print load_fiche_titre($title, '', 'object_resource');
		print dol_get_fiche_head();
	} else {
		$head = resource_prepare_head($object);
		print dol_get_fiche_head($head, 'resource', $title, -1, 'resource');
	}

	if ($action == 'create' || $action == 'edit') {
		if (!$user->hasRight('resource', 'write')) {
			accessforbidden('', 0);
		}

		// Create/Edit object

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.($action == "create" ? "add" : "update").'">';

		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("ResourceFormLabel_ref").'</td>';
		print '<td><input class="minwidth200" name="ref" value="'.($ref ?: $object->ref).'" autofocus="autofocus"></td></tr>';

		// Address
		print '<tr><td class="tdtop">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
		print '<td colspan="3"><textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
		print dol_escape_htmltag($object->address, 0, 1);
		print '</textarea>';
		print $form->widgetForTranslation("address", $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>';
		print $formresource->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
		print '</td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>';
		print $formresource->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print $form->widgetForTranslation("town", $object, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
		print '</td></tr>';

		// Origin country
		print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td>';
		print $form->select_country($object->country_id);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// State
		if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
			if ((getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 || getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 2)) {
				print '<tr><td>'.$form->editfieldkey('Region-State', 'state_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			} else {
				print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			}

			if ($object->country_id) {
				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formresource->select_state($object->state_id, $object->country_code);
			} else {
				print $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';
			}
			print '</td></tr>';
		}

		// Type
		print '<tr><td>'.$langs->trans("ResourceType").'</td>';
		print '<td>';
		$formresource->select_types_resource($object->fk_code_type_resource, 'fk_code_type_resource', '', 2);
		print '</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
		print '<td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('description', ($description ?: $object->description), '', 200, 'dolibarr_notes', false);
		$doleditor->Create();
		print '</td></tr>';

		// Phone
		print '<td>'.$form->editfieldkey('Phone', 'phone', '', $object, 0).'</td>';
		print '<td>';
		print img_picto('', 'object_phoning', 'class="pictofixedwidth"');
		print '<input type="tel" name="phone" id="phone" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $object->phone).'"></td>';
		print '</tr>';

		// Email
		print '<tr><td>'.$form->editfieldkey('EMail', 'email', '', $object, 0).'</td>';
		print '<td>';
		print img_picto('', 'object_email', 'class="pictofixedwidth"');
		print '<input type="email" name="email" id="email" value="'.(GETPOSTISSET('email') ? GETPOST('email', 'alpha') : $object->email).'"></td>';
		print '</tr>';

		// Max users
		print '<tr><td>'.$form->editfieldkey('MaxUsers', 'max_users', '', $object, 0).'</td>';
		print '<td>';
		print img_picto('', 'object_user', 'class="pictofixedwidth"');
		print '<input type="number" name="max_users" id="max_users" value="'.(GETPOSTISSET('max_users') ? GETPOSTINT('max_users') : $object->max_users).'"></td>';
		print '</tr>';

		// URL
		print '<tr><td>'.$form->editfieldkey('URL', 'url', '', $object, 0).'</td>';
		print '<td>';
		print img_picto('', 'object_url', 'class="pictofixedwidth"');
		print '<input type="url" name="url" id="url" value="'.(GETPOSTISSET('url') ? GETPOST('url', 'alpha') : $object->url).'"></td>';
		print '</tr>';

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

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref');


		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

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

		// Max users
		print '<tr>';
		print '<td>'.$langs->trans("MaxUsers").'</td>';
		print '<td>';
		print $object->max_users;
		print '</td>';
		print '</tr>';

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
