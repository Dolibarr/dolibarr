<?php
/* Copyright (C) 2014-2016  Alexandre Spangaro  <aspangaro@open-dsi.fr>
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

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta"));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (empty($user->rights->accounting->fiscalyear->write)) {
	accessforbidden();
}

$error = 0;

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');

// List of statut
static $tmpstatut2label = array(
		'0' => 'OpenFiscalYear',
		'1' => 'CloseFiscalYear'
);
$statut2label = array(
		''
);
foreach ($tmpstatut2label as $key => $val) {
	$statut2label[$key] = $langs->trans($val);
}

$object = new Fiscalyear($db);

$date_start = dol_mktime(0, 0, 0, GETPOST('fiscalyearmonth', 'int'), GETPOST('fiscalyearday', 'int'), GETPOST('fiscalyearyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('fiscalyearendmonth', 'int'), GETPOST('fiscalyearendday', 'int'), GETPOST('fiscalyearendyear', 'int'));


/*
 * Actions
 */

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
		$object->statut = GETPOST('statut', 'int');
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
		$object->statut = GETPOST('statut', 'int');

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

$help_url = "EN:Module_Double_Entry_Accounting";

llxHeader('', $title, $help_url);

if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewFiscalYear"));

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
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
	// Statut
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Status") . '</td>';
	print '<td class="valeur">';
	print $form->selectarray('statut', $statut2label, GETPOST('statut', 'int'));
	print '</td></tr>';
	*/

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
} elseif ($id) {
	$result = $object->fetch($id);
	if ($result > 0) {
		$head = fiscalyear_prepare_head($object);

		if ($action == 'edit') {
			print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), 0, 'cron');

			print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print '<table class="border centpercent">';

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

			// Statut
			print '<tr><td>'.$langs->trans("Statut").'</td><td>';
			// print $form->selectarray('statut', $statut2label, $object->statut);
			print $object->getLibStatut(4);
			print '</td></tr>';

			print '</table>';

			print '<br><div class="center">';
			print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';

			print dol_get_fiche_end();
		} else {
			/*
			 * Confirm delete
			 */
			if ($action == 'delete') {
				print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete");
			}

			print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), 0, 'cron');

			print '<table class="border centpercent">';

			$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php">'.$langs->trans("BackToList").'</a>';

			// Ref
			print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td width="50%">';
			print $object->ref;
			print '</td><td>';
			print $linkback;
			print '</td></tr>';

			// Label
			print '<tr><td class="tdtop">';
			print $form->editfieldkey("Label", 'label', $object->label, $object, 1, 'alpha:32');
			print '</td><td colspan="2">';
			print $form->editfieldval("Label", 'label', $object->label, $object, 1, 'alpha:32');
			print "</td></tr>";

			// Date start
			print '<tr><td>';
			print $form->editfieldkey("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
			print '</td><td colspan="2">';
			print $form->editfieldval("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
			print '</td></tr>';

			// Date end
			print '<tr><td>';
			print $form->editfieldkey("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
			print '</td><td colspan="2">';
			print $form->editfieldval("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
			print '</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$object->getLibStatut(4).'</td></tr>';

			print "</table>";

			print dol_get_fiche_end();

			/*
			 * Action bar
			 */
			if (!empty($user->rights->accounting->fiscalyear->write)) {
				print '<div class="tabsAction">';

				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$id.'">'.$langs->trans('Modify').'</a>';

				// print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=delete&token='.newToken().'&id=' . $id . '">' . $langs->trans('Delete') . '</a>';

				print '</div>';
			}
		}
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
