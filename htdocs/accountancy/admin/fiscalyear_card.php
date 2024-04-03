<?php
/* Copyright (C) 2014-2024  Alexandre Spangaro  <aspangaro@easya.solutions>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 * \file        htdocs/accountancy/admin/fiscalyear_card.php
 * \ingroup     Accountancy (Double entries)
 * \brief       Page to show a fiscal year
 */

// Load Dolibarr environment
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

$error = 0;

// Initialize technical objects
$object = new Fiscalyear($db);
$extrafields = new ExtraFields($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// List of status
static $tmpstatus2label = array(
		'0' => 'OpenFiscalYear',
		'1' => 'CloseFiscalYear'
);
$status2label = array(
		''
);
foreach ($tmpstatus2label as $key => $val) {
	$status2label[$key] = $langs->trans($val);
}

$date_start = dol_mktime(0, 0, 0, GETPOST('fiscalyearmonth', 'int'), GETPOST('fiscalyearday', 'int'), GETPOST('fiscalyearyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('fiscalyearendmonth', 'int'), GETPOST('fiscalyearendday', 'int'), GETPOST('fiscalyearendyear', 'int'));

$permissiontoadd = $user->hasRight('accounting', 'fiscalyear', 'write');

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$permissiontoadd) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'confirm_delete' && $confirm == "yes") {
	$result = $object->delete($id);
	if ($result >= 0) {
		header("Location: fiscalyear.php");
		exit();
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
} elseif ($action == 'add') {
	if (!GETPOST('cancel', 'alpha')) {
		$error = 0;

		$object->date_start = $date_start;
		$object->date_end = $date_end;
		$object->label = GETPOST('label', 'alpha');
		$object->status = GETPOST('status', 'int');
		$object->datec = dol_now();

		if (empty($object->date_start) && empty($object->date_end)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$error++;
		}
		if (empty($object->label)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->begin();

			$id = $object->create($user);

			if ($id > 0) {
				$db->commit();

				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit();
			} else {
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	} else {
		header("Location: ./fiscalyear.php");
		exit();
	}
} elseif ($action == 'update') {
	// Update record
	if (!GETPOST('cancel', 'alpha')) {
		$result = $object->fetch($id);

		$object->date_start = GETPOST("fiscalyear") ? $date_start : '';
		$object->date_end = GETPOST("fiscalyearend") ? $date_end : '';
		$object->label = GETPOST('label', 'alpha');
		$object->status = GETPOST('status', 'int');

		$result = $object->update($user);

		if ($result > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
		exit();
	}
}



/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Fiscalyear")." - ".$langs->trans("Card");
if ($action == 'create') {
	$title = $langs->trans("NewFiscalYear");
}

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

if ($action == 'create') {
	print load_fiche_titre($title, '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" size="32" value="'.GETPOST('label', 'alpha').'"></td></tr>';

	// Date start
	print '<tr><td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate(($date_start ? $date_start : ''), 'fiscalyear');
	print '</td></tr>';

	// Date end
	print '<tr><td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate(($date_end ? $date_end : - 1), 'fiscalyearend');
	print '</td></tr>';

	/*
	// Status
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Status") . '</td>';
	print '<td class="valeur">';
	print $form->selectarray('status', $status2label, GETPOST('status', 'int'));
	print '</td></tr>';
	*/

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';
}


// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Fiscalyear"), '', 'object_'.$object->picto);

	print '<form method="POST" name="update" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Ref
	print "<tr>";
	print '<td class="titlefieldcreate titlefield">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input name="label" class="flat" size="32" value="'.$object->label.'">';
	print '</td></tr>';

	// Date start
	print '<tr><td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate($object->date_start ? $object->date_start : - 1, 'fiscalyear');
	print '</td></tr>';

	// Date end
	print '<tr><td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate($object->date_end ? $object->date_end : - 1, 'fiscalyearend');
	print '</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	print $object->getLibStatut(4);
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = fiscalyear_prepare_head($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), 0, 'calendar');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete", '', 0, 1);
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php">'.$langs->trans("BackToList").'</a>';

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td width="50%">';
	print $object->ref;
	print '</td><td>';
	print $linkback;
	print '</td></tr>';

	// Label
	print '<tr><td class="tdtop">';
	print $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'alpha:32');
	print '</td><td colspan="2">';
	print $form->editfieldval("Label", 'label', $object->label, $object, 0, 'alpha:32');
	print "</td></tr>";

	// Date start
	print '<tr><td>';
	print $form->editfieldkey("DateStart", 'date_start', $object->date_start, $object, 0, 'datepicker');
	print '</td><td colspan="2">';
	print $form->editfieldval("DateStart", 'date_start', $object->date_start, $object, 0, 'datepicker');
	print '</td></tr>';

	// Date end
	print '<tr><td>';
	print $form->editfieldkey("DateEnd", 'date_end', $object->date_end, $object, 0, 'datepicker');
	print '</td><td colspan="2">';
	print $form->editfieldval("DateEnd", 'date_end', $object->date_end, $object, 0, 'datepicker');
	print '</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$object->getLibStatut(4).'</td></tr>';

	print "</table>";

	print dol_get_fiche_end();

	/*
	 * Action bar
	 */
	if ($user->hasRight('accounting', 'fiscalyear', 'write')) {
		print '<div class="tabsAction">';

		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$id.'">'.$langs->trans('Modify').'</a>';

		//print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);

		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
