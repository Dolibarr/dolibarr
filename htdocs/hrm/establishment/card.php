<?php
/* Copyright (C) 2015      Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 *  \file       	htdocs/hrm/establishment/card.php
 *  \brief      	Page to show an establishment
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

$error = 0;

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');

// List of status
static $tmpstatus2label = array(
		'0'=>'CloseEtablishment',
		'1'=>'OpenEtablishment'
);
$status2label = array('');
foreach ($tmpstatus2label as $key => $val) {
	$status2label[$key] = $langs->trans($val);
}

$object = new Establishment($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

$permissiontoread = $user->admin;
$permissiontoadd = $user->admin; // Used by the include of actions_addupdatedelete.inc.php
$permissiontodelete = $user->admin;
$upload_dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, '', '', 'fk_soc', 'rowid', 0);
if (!isModEnabled('hrm')) {
	accessforbidden();
}
if (empty($permissiontoread)) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'confirm_delete' && $confirm == "yes") {
	$result = $object->delete($id);
	if ($result >= 0) {
		header("Location: ../admin/admin_establishment.php");
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
} elseif ($action == 'add') {
	if (!$cancel) {
		$error = 0;

		$object->label = GETPOST('label', 'alpha');
		if (empty($object->label)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			$error++;
		}

		if (empty($error)) {
			$object->address = GETPOST('address', 'alpha');
			$object->zip = GETPOST('zipcode', 'alpha');
			$object->town = GETPOST('town', 'alpha');
			$object->country_id = GETPOST("country_id", 'int');
			$object->status = GETPOST('status', 'int');
			$object->fk_user_author	= $user->id;
			$object->datec = dol_now();
			$object->entity = GETPOST('entity', 'int') > 0 ? GETPOST('entity', 'int') : $conf->entity;

			$id = $object->create($user);

			if ($id > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$action = 'create';
		}
	} else {
		header("Location: ../admin/admin_establishment.php");
		exit;
	}
} elseif ($action == 'update') {
	// Update record
	$error = 0;

	if (!$cancel) {
		$name = GETPOST('label', 'alpha');
		if (empty($name)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label')), null, 'errors');
			$error++;
		}

		if (empty($error)) {
			$object->label = GETPOST('label', 'alphanohtml');
			$object->address = GETPOST('address', 'alpha');
			$object->zip 			= GETPOST('zipcode', 'alpha');
			$object->town			= GETPOST('town', 'alpha');
			$object->country_id     = GETPOST('country_id', 'int');
			$object->fk_user_mod = $user->id;
			$object->status         = GETPOST('status', 'int');
			$object->entity         = GETPOST('entity', 'int') > 0 ? GETPOST('entity', 'int') : $conf->entity;

			$result = $object->update($user);

			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".GETPOST('id', 'int'));
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} else {
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".GETPOST('id', 'int'));
		exit;
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
$formcompany = new FormCompany($db);

/*
 * Action create
 */
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewEstablishment"));

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Name
	print '<tr>';
	print '<td>'.$form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td>';
	print '<td><input name="label" id="label" value="'.GETPOST("label", "alphanohtml").'" autofocus></td>';
	print '</tr>';

	// Entity
	/*
	if (isModEnabled('multicompany')) {
		print '<tr>';
		print '<td>'.$form->editfieldkey('Parent', 'entity', '', $object, 0, 'string', '', 1).'</td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->selectEstablishments(GETPOST('entity', 'int') > 0 ?GETPOST('entity', 'int') : $conf->entity, 'entity', 1);
		print '</td>';
		print '</tr>';
	} */

	// Address
	print '<tr>';
	print '<td>'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
	print '<td>';
	print '<input name="address" id="address" class="qutrevingtpercent" value="'.GETPOST('address', 'alphanohtml').'">';
	print '</td>';
	print '</tr>';

	// Zipcode
	print '<tr>';
	print '<td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td>';
	print '<td>';
	print $formcompany->select_ziptown(
		GETPOST('zipcode', 'alpha'),
		'zipcode',
		array(
			'town',
			'selectcountry_id'
		),
		6
	);
	print '</td>';
	print '</tr>';

	// Town
	print '<tr>';
	print '<td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td>';
	print '<td>';
	print $formcompany->select_ziptown(GETPOSTISSET('town') ? GETPOST('town', 'alpha') : $object->town, 'town', array(
			'zipcode',
			'selectcountry_id'
	));
	print '</td>';
	print '</tr>';

	// Country
	print '<tr>';
	print '<td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td>';
	print '<td class="maxwidthonsmartphone">';
	print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'int') : ($object->country_id ? $object->country_id : $mysoc->country_id), 'country_id');
	if ($user->admin) {
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	}
	print '</td>';
	print '</tr>';

	// Status
	print '<tr>';
	print '<td>'.$form->editfieldkey('Status', 'status', '', $object, 0, 'string', '', 1).'</td>';
	print '<td>';
	print $form->selectarray('status', $status2label, GETPOSTISSET('status') ? GETPOST('status', 'alpha') : 1);
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to edit record
if ((!empty($id) || !empty($ref)) && $action == 'edit') {
	$result = $object->fetch($id);
	if ($result > 0) {
		$head = establishment_prepare_head($object);

		if ($action == 'edit') {
			print dol_get_fiche_head($head, 'card', $langs->trans("Establishment"), 0, $object->picto);

			print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print '<table class="border centpercent">';

			// Ref
			print "<tr>";
			print '<td class="titlefield">'.$langs->trans("Ref").'</td><td>';
			print $object->id;
			print '</td></tr>';

			// Name
			print '<tr><td>'.$form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
			print '<input name="label" id="label" class="flat" value="'.$object->label.'">';
			print '</td></tr>';

			// Entity
			/*
			if (isModEnabled('multicompany')) {
				print '<tr><td>'.$form->editfieldkey('Parent', 'entity', '', $object, 0, 'string', '', 1).'</td>';
				print '<td class="maxwidthonsmartphone">';
				print $object->entity > 0 ? $object->entity : $conf->entity;
				print '</td></tr>';
			}*/

			// Address
			print '<tr><td>'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
			print '<td>';
			print '<input name="address" id="address" value="'.$object->address.'">';
			print '</td></tr>';

			// Zipcode / Town
			print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array(
					'town',
					'selectcountry_id'
			), 6).'</tr>';
			print '<tr><td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
			print $formcompany->select_ziptown($object->town, 'town', array(
					'zipcode',
					'selectcountry_id'
			)).'</td></tr>';

			// Country
			print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print $form->select_country($object->country_id, 'country_id');
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print '</td>';
			print '</tr>';

			// Status
			print '<tr><td>'.$form->editfieldkey('Status', 'status', '', $object, 0, 'string', '', 1).'</td><td>';
			print $form->selectarray('status', $status2label, $object->status);
			print '</td></tr>';

			print '</table>';

			print dol_get_fiche_end();

			print $form->buttonsSaveCancel();

			print '</form>';
		}
	} else {
		dol_print_error($db);
	}
}

if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = establishment_prepare_head($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Establishment"), -1, $object->picto);

	// Confirmation to delete
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("DeleteEstablishment"), $langs->trans("ConfirmDeleteEstablishment"), "confirm_delete");
	}


	// Object card
	// ------------------------------------------------------------

	$linkback = '<a href="'.DOL_URL_ROOT.'/hrm/admin/admin_establishment.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'id', $morehtmlref);


	print '<div class="fichecenter">';
	//print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Name
	print '<tr>';
	print '<td class="titlefield">'.$langs->trans("Label").'</td>';
	print '<td>'.$object->label.'</td>';
	print '</tr>';

	// Entity
	/*
	if (!isModEnabled('multicompany') {
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("Entity").'</td>';
		print '<td>'.$object->entity.'</td>';
		print '</tr>';
	}*/

	// Address
	print '<tr>';
	print '<td>'.$langs->trans("Address").'</td>';
	print '<td>'.$object->address.'</td>';
	print '</tr>';

	// Zipcode
	print '<tr>';
	print '<td>'.$langs->trans("Zip").'</td>';
	print '<td>'.$object->zip.'</td>';
	print '</tr>';

	// Town
	print '<tr>';
	print '<td>'.$langs->trans("Town").'</td>';
	print '<td>'.$object->town.'</td>';
	print '</tr>';

	// Country
	print '<tr>';
	print '<td>'.$langs->trans("Country").'</td>';
	print '<td>';
	if ($object->country_id > 0) {
		$img = picto_from_langcode($object->country_code);
		print $img ? $img.' ' : '';
		print getCountry($object->getCountryCode(), 0, $db);
	}
	print '</td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	print dol_get_fiche_end();

	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	// Modify
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$id.'">'.$langs->trans('Modify').'</a>';

	// Delete
	print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);

	print '</div>';
}

// End of page
llxFooter();
$db->close();
