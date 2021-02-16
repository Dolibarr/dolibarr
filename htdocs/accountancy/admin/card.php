<?php
/* Copyright (C) 2013-2014  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2013-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Florian Henry       <florian.henry@open-concept.pro>
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
 *  \file       htdocs/accountancy/admin/card.php
 *  \ingroup    Accountancy (Double entries)
 *  \brief      Card of accounting account
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancysystem.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

$error = 0;

// Load translation files required by the page
$langs->loadLangs(array("bills", "accountancy", "compta"));

$mesg = '';
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel', 'alpha');

$account_number = GETPOST('account_number', 'string');
$label = GETPOST('label', 'alpha');

// Security check
if ($user->socid > 0) accessforbidden();
if (!$user->rights->accounting->chartofaccount) accessforbidden();


$object = new AccountingAccount($db);


/*
 * Action
 */

if (GETPOST('cancel', 'alpha'))
{
	$urltogo = $backtopage ? $backtopage : dol_buildpath('/accountancy/admin/account.php', 1);
	header("Location: ".$urltogo);
	exit;
}

if ($action == 'add' && $user->rights->accounting->chartofaccount)
{
	if (!$cancel) {
		if (!$account_number)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountNumber")), null, 'errors');
			$action = 'create';
		} elseif (!$label)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
			$action = 'create';
		} else {
			$sql = 'SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid='.((int) $conf->global->CHARTOFACCOUNTS);

			dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
			$result = $db->query($sql);
			$obj = $db->fetch_object($result);

			// Clean code

			// To manage zero or not at the end of the accounting account
			if ($conf->global->ACCOUNTING_MANAGE_ZERO == 1) {
				$account_number = $account_number;
			} else {
				$account_number = clean_account($account_number);
			}

			if (GETPOST('account_parent', 'int') <= 0) {
				$account_parent = 0;
			} else {
				$account_parent = GETPOST('account_parent', 'int');
			}

			$object->fk_pcg_version = $obj->pcg_version;
			$object->pcg_type = GETPOST('pcg_type', 'alpha');
			$object->account_number = $account_number;
			$object->account_parent = $account_parent;
			$object->account_category = GETPOST('account_category', 'alpha');
			$object->label = $label;
			$object->labelshort = GETPOST('labelshort', 'alpha');
			$object->active = 1;

			$res = $object->create($user);
			if ($res == -3) {
				$error = 1;
				$action = "create";
				setEventMessages($object->error, $object->errors, 'errors');
			} elseif ($res == -4) {
				$error = 2;
				$action = "create";
				setEventMessages($object->error, $object->errors, 'errors');
			} elseif ($res < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";
			}
			if (!$error) {
				setEventMessages("RecordCreatedSuccessfully", null, 'mesgs');
				$urltogo = $backtopage ? $backtopage : dol_buildpath('/accountancy/admin/account.php', 1);
				header("Location: " . $urltogo);
				exit;
			}
		}
	}
} elseif ($action == 'edit' && $user->rights->accounting->chartofaccount) {
	if (!$cancel) {
		if (!$account_number)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountNumber")), null, 'errors');
			$action = 'update';
		} elseif (!$label)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
			$action = 'update';
		} else {
			$result = $object->fetch($id);

			$sql = 'SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $conf->global->CHARTOFACCOUNTS;

			dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
			$result2 = $db->query($sql);
			$obj = $db->fetch_object($result2);

			// Clean code

			// To manage zero or not at the end of the accounting account
			if ($conf->global->ACCOUNTING_MANAGE_ZERO == 1) {
				$account_number = $account_number;
			} else {
				$account_number = clean_account($account_number);
			}

			if (GETPOST('account_parent', 'int') <= 0) {
				$account_parent = 0;
			} else {
				$account_parent = GETPOST('account_parent', 'int');
			}

			$object->fk_pcg_version = $obj->pcg_version;
			$object->pcg_type = GETPOST('pcg_type', 'alpha');
			$object->account_number = $account_number;
			$object->account_parent = $account_parent;
			$object->account_category = GETPOST('account_category', 'alpha');
			$object->label = $label;
			$object->labelshort = GETPOST('labelshort', 'alpha');

			$result = $object->update($user);

			if ($result > 0) {
				$urltogo = $backtopage ? $backtopage : ($_SERVER["PHP_SELF"] . "?id=" . $id);
				header("Location: " . $urltogo);
				exit();
			} else {
				$mesg = $object->error;
			}
		}
	} else {
		$urltogo = $backtopage ? $backtopage : ($_SERVER["PHP_SELF"]."?id=".$id);
		header("Location: ".$urltogo);
		exit();
	}
} elseif ($action == 'delete' && $user->rights->accounting->chartofaccount) {
	$result = $object->fetch($id);

	if (!empty($object->id)) {
		$result = $object->delete($user);

		if ($result > 0) {
			header("Location: account.php");
			exit;
		}
	}

	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$accountsystem = new AccountancySystem($db);
$accountsystem->fetch($conf->global->CHARTOFACCOUNTS);

$title = $langs->trans('AccountAccounting')." - ".$langs->trans('Card');
$helpurl = '';
llxheader('', $title, $helpurl);


// Create mode
if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewAccountingAccount'));

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Chart of account
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Chartofaccounts").'</span></td>';
	print '<td>';
	print $accountsystem->ref;
	print '</td></tr>';

	// Account number
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("AccountNumber").'</span></td>';
	print '<td><input name="account_number" size="30" value="'.$account_number.'"></td></tr>';

	// Label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td>';
	print '<td><input name="label" size="70" value="'.$object->label.'"></td></tr>';

	// Label short
	print '<tr><td>'.$langs->trans("LabelToShow").'</td>';
	print '<td><input name="labelshort" size="70" value="'.$object->labelshort.'"></td></tr>';

	// Account parent
	print '<tr><td>'.$langs->trans("Accountparent").'</td>';
	print '<td>';
	print $formaccounting->select_account($object->account_parent, 'account_parent', 1, null, 0, 0, 'minwidth200');
	print '</td></tr>';

	// Chart of accounts type
	print '<tr><td>'.$langs->trans("Pcgtype").'</td>';
	print '<td>';
	print '<input type="text" name="pcg_type" value="'.dol_escape_htmltag(GETPOSTISSET('pcg_type') ? GETPOST('pcg_type', 'alpha') : $object->pcg_type).'">';
	print '</td></tr>';

	// Category
	print '<tr><td>'.$langs->trans("AccountingCategory").'</td>';
	print '<td>';
	$formaccounting->select_accounting_category($object->account_category, 'account_category', 1, 0, 1);
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
} elseif ($id > 0 || $ref) {
	$result = $object->fetch($id, $ref, 1);

	if ($result > 0) {
		dol_htmloutput_mesg($mesg);

		$head = accounting_prepare_head($object);

		// Edit mode
		if ($action == 'update')
		{
			print dol_get_fiche_head($head, 'card', $langs->trans('AccountAccounting'), 0, 'billr');

			print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="edit">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

			print '<table class="border centpercent">';

			// Account number
			print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("AccountNumber").'</span></td>';
			print '<td><input name="account_number" size="30" value="'.$object->account_number.'"</td></tr>';

			// Label
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td>';
			print '<td><input name="label" size="70" value="'.$object->label.'"</td></tr>';

			// Label short
			print '<tr><td>'.$langs->trans("LabelToShow").'</td>';
			print '<td><input name="labelshort" size="70" value="'.$object->labelshort.'"</td></tr>';

			// Account parent
			print '<tr><td>'.$langs->trans("Accountparent").'</td>';
			print '<td>';
			print $formaccounting->select_account($object->account_parent, 'account_parent', 1);
			print '</td></tr>';

			// Chart of accounts type
			print '<tr><td>'.$langs->trans("Pcgtype").'</td>';
			print '<td>';
			print '<input type="text" name="pcg_type" value="'.dol_escape_htmltag(GETPOSTISSET('pcg_type') ? GETPOST('pcg_type', 'alpha') : $object->pcg_type).'">';
			print '</td></tr>';

			// Category
			print '<tr><td>'.$langs->trans("AccountingCategory").'</td>';
			print '<td>';
			$formaccounting->select_accounting_category($object->account_category, 'account_category', 1);
			print '</td></tr>';

			print '</table>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		} else {
			// View mode
			$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/account.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			print dol_get_fiche_head($head, 'card', $langs->trans('AccountAccounting'), -1, 'billr');

			dol_banner_tab($object, 'ref', $linkback, 1, 'account_number', 'ref');


			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent">';

			// Label
			print '<tr><td class="titlefield">'.$langs->trans("Label").'</td>';
			print '<td colspan="2">'.$object->label.'</td></tr>';

			// Label to show
			print '<tr><td class="titlefield">'.$langs->trans("LabelToShow").'</td>';
			print '<td colspan="2">'.$object->labelshort.'</td></tr>';

			// Account parent
			$accp = new AccountingAccount($db);
			if (!empty($object->account_parent)) {
				$accp->fetch($object->account_parent, '');
			}
			print '<tr><td>'.$langs->trans("Accountparent").'</td>';
			print '<td colspan="2">'.$accp->account_number.' - '.$accp->label.'</td></tr>';

			// Category
			print "<tr><td>".$langs->trans("AccountingCategory")."</td><td colspan='2'>".$object->account_category_label."</td>";

			// Chart of accounts type
			print '<tr><td>'.$langs->trans("Pcgtype").'</td>';
			print '<td colspan="2">'.$object->pcg_type.'</td></tr>';

			print '</table>';

			print '</div>';

			print dol_get_fiche_end();

			/*
			 * Actions buttons
			 */
			print '<div class="tabsAction">';

			if (!empty($user->rights->accounting->chartofaccount)) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=update&token='.newToken().'&id='.$id.'">'.$langs->trans('Modify').'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
			}

			if (!empty($user->rights->accounting->chartofaccount)) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$id.'">'.$langs->trans('Delete').'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
			}

			print '</div>';
		}
	} else {
		dol_print_error($db, $object->error, $object->errors);
	}
}

// End of page
llxFooter();
$db->close();
