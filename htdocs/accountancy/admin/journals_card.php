<?php
/* Copyright (C) 2017		Alexandre Spangaro	<aspangaro@zendsi.com>
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
 * \file        htdocs/accountancy/admin/journals_card.php
 * \ingroup     Advanced accountancy
 * \brief       Page to show an accounting journal
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

$langs->load("admin");
$langs->load("compta");
$langs->load("accountancy");

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (empty($user->rights->accounting->fiscalyear))
	accessforbidden();

$error = 0;

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');

// List of status
static $tmptype2label = array (
		'0' => 'AccountingJournalTypeVariousOperation',
		'1' => 'AccountingJournalTypeSale',
		'2' => 'AccountingJournalTypePurchase',
		'3' => 'AccountingJournalTypeBank',
		'9' => 'AccountingJournalTypeHasNew'
);
$type2label = array (
		'' 
);
foreach ( $tmptype2label as $key => $val )
	$type2label[$key] = $langs->trans($val);

$object = new AccountingJournal($db);

/*
 * Actions
 */

if ($action == 'confirm_delete' && $confirm == "yes") {
	$result = $object->delete($id);
	if ($result >= 0) {
		header("Location: journals.php");
		exit();
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
} 

else if ($action == 'add') {
	if (! GETPOST('cancel', 'alpha')) {
		$error = 0;

		$object->code = GETPOST('code', 'alpha');
		$object->label = GETPOST('label', 'alpha');
		$object->nature = GETPOST('nature', 'int');

		if (empty($object->code)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Code")), null, 'errors');
			$error ++;
		}
		if (empty($object->label)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			$error ++;
		}

		if (! $error) {
			$db->begin();

			$id = $object->create($user);

			if ($id > 0) {
				$db->commit();

				header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
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
		header("Location: ./journals.php");
		exit();
	}
} 

// Update record
else if ($action == 'update') {
	if (! GETPOST('cancel', 'alpha')) {
		$result = $object->fetch($id);

		$object->code = GETPOST('code', 'alpha');
		$object->label = GETPOST('label', 'alpha');
		$object->nature = GETPOST('nature', 'int');

		if (empty($object->code)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Code")), null, 'errors');
			$error ++;
		}
		if (empty($object->label)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
			$error ++;
		}

		$result = $object->update($user);
		
		if ($result > 0) {
			header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
		exit();
	}
}



/*
 * View
 */

$title = $langs->trans("Journal") . " - " . $langs->trans("Card");
$helpurl = "";
llxHeader("",$title,$helpurl);

$form = new Form($db);

if ($action == 'create') 
{
	print load_fiche_titre($langs->trans("NewAccountingJournal"));

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Code
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Code") . '</td><td><input name="code" size="10" value="' . GETPOST("code") . '"></td></tr>';


	// Label
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td><input name="label" size="32" value="' . GETPOST("label") . '"></td></tr>';

	// Nature
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Type") . '</td>';
	print '<td class="valeur">';
	print $form->selectarray('nature', $type2label, GETPOST('nature'));
	print '</td></tr>';
	
	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
} else if ($id) {
	$result = $object->fetch($id);
	if ($result > 0) {
		$head = accounting_journal_prepare_head($object);

		if ($action == 'edit') {
			dol_fiche_head($head, 'card', $langs->trans("AccountingJournal"), 0, 'cron');

			print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="' . $id . '">';

			print '<table class="border" width="100%">';

			// Code
			print "<tr>";
			print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("Code") . '</td><td>';
			print '<input name="code" class="flat" size="8" value="' . $object->code . '">';
			print '</td></tr>';

			// Label
			print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
			print '<input name="label" class="flat" size="32" value="' . $object->label . '">';
			print '</td></tr>';

			// Nature
			print '<tr><td>' . $langs->trans("Type") . '</td><td>';
			print $form->selectarray('nature', $type2label, $object->nature);
			print '</td></tr>';

			print '</table>';

			print '<br><div class="center">';
			print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" name="cancel" class="button" value="' . $langs->trans("Cancel") . '">';
			print '</div>';

			print '</form>';

			dol_fiche_end();
		} else {
			/*
			 * Confirm delete
			 */
			if ($action == 'delete') {
				print $form->formconfirm($_SERVER["PHP_SELF"] . "?id=" . $id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete");
			}

			dol_fiche_head($head, 'card', $langs->trans("AccountingJournal"), 0, 'cron');

			print '<table class="border" width="100%">';

			$linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/journals.php">' . $langs->trans("BackToList") . '</a>';

			// Ref
			print '<tr><td class="titlefield">' . $langs->trans("Code") . '</td><td width="50%">';
			print $object->code;
			print '</td><td>';
			print $linkback;
			print '</td></tr>';

			// Label
			print '<tr><td class="tdtop">';
			print $form->editfieldkey("Label", 'label', $object->label, $object, $conf->global->MAIN_EDIT_ALSO_INLINE, 'alpha:32');
			print '</td><td colspan="2">';
			print $form->editfieldval("Label", 'label', $object->label, $object, $conf->global->MAIN_EDIT_ALSO_INLINE, 'alpha:32');
			print "</td></tr>";

			// Nature
			print '<tr><td>' . $langs->trans("Type") . '</td><td colspan="2">' . $object->getLibType(0) . '</td></tr>';

			print "</table>";

			dol_fiche_end();

			if (! empty($user->rights->accounting->fiscalyear))
			{
    			/*
    			 * Barre d'actions
    			 */
    			print '<div class="tabsAction">';

    			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';

    			print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';

    			print '</div>';
			}
		}
	} else {
		dol_print_error($db);
	}
}

llxFooter();
$db->close();
