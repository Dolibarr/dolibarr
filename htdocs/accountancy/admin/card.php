<?PHP
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
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
 * \file htdocs/accountancy/admin/card.php
 * \ingroup Accounting Expert
 * \brief Card accounting account
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';

$error = 0;

// Langs
$langs->load("bills");
$langs->load("accountancy");

$mesg = '';
$action = GETPOST('action');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel');

// Security check
if (! $user->admin)
	accessforbidden();

$accounting = new AccountingAccount($db);

// Action
if ($action == 'add') {
	if (! $cancel) {
		$sql = 'SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $conf->global->CHARTOFACCOUNTS;
		
		dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
		$result = $db->query($sql);
		$obj = $db->fetch_object($result);
		
		$accounting->fk_pcg_version = $obj->pcg_version;
		$accounting->pcg_type = GETPOST('pcg_type');
		$accounting->pcg_subtype = GETPOST('pcg_subtype');
		$accounting->account_number = GETPOST('account_number');
		$accounting->account_parent = GETPOST('account_parent', 'int');
		$accounting->label = GETPOST('label', 'alpha');
		$accounting->active = 1;
		
		$res = $accounting->create($user);
		
		if ($res == 0) {
		} else {
			if ($res == - 3) {
				$error = 1;
				$action = "create";
			}
			if ($res == - 4) {
				$error = 2;
				$action = "create";
			}
		}
	}
	Header("Location: account.php");
} else if ($action == 'edit') {
	if (! GETPOST('cancel', 'alpha')) {
		$result = $accounting->fetch($id);
		
		$sql = 'SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $conf->global->CHARTOFACCOUNTS;
		
		dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
		$result2 = $db->query($sql);
		$obj = $db->fetch_object($result2);
		
		$accounting->fk_pcg_version = $obj->pcg_version;
		$accounting->pcg_type = GETPOST('pcg_type');
		$accounting->pcg_subtype = GETPOST('pcg_subtype');
		$accounting->account_number = GETPOST('account_number');
		$accounting->account_parent = GETPOST('account_parent', 'int');
		$accounting->label = GETPOST('label', 'alpha');
		
		$result = $accounting->update($user);
		
		if ($result > 0) {
			header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
			exit();
		} else {
			$mesg = $object->error;
		}
	} else {
		header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
		exit();
	}
} else if ($action == 'delete') {
	$result = $accounting->fetch($id);
	
	if (! empty($accounting->id)) {
		$result = $accounting->delete($user);
		
		if ($result > 0) {
			Header("Location: account.php");
		}
	}
	
	if ($result < 0) {
		setEventMessages($accounting->error, $accounting->errors, 'errors');
	}
}

/*
 * View
 */
llxheader('', $langs->trans('AccountAccounting'));

$form = new Form($db);
$htmlacc = new FormVentilation($db);

if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewAccount'));
	
	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	
	dol_fiche_head();
	
	print '<table class="border" width="100%">';
	
	print '<tr><td width="25%"><span class="fieldrequired">' . $langs->trans("AccountNumber") . '</span></td>';
	print '<td><input name="account_number" size="30" value="' . $accounting->account_number . '"</td></tr>';
	print '<tr><td><span class="fieldrequired">' . $langs->trans("Label") . '</span></td>';
	print '<td><input name="label" size="70" value="' . $accounting->label . '"</td></tr>';
	print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
	print '<td>';
	print $htmlacc->select_account($accounting->account_parent, 'account_parent', 1);
	print '</td></tr>';
	print '<tr><td>' . $langs->trans("Pcgtype") . '</td>';
	print '<td>';
	print $htmlacc->select_pcgtype($accounting->pcg_type, 'pcg_type');
	print '</td></tr>';
	print '<tr><td>' . $langs->trans("Pcgsubtype") . '</td>';
	print '<td>';
	print $htmlacc->select_pcgsubtype($accounting->pcg_subtype, 'pcg_subtype');
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
	$rowid = $id;
	$account = $accounting->fetch($rowid);
	
	if ($account > 0) {
		dol_htmloutput_mesg($mesg);
		
		$head = accounting_prepare_head($accounting);
		
		if ($action == 'update') {
			$soc = new Societe($db);
			if ($object->socid) {
				$soc->fetch($object->socid);
			}
			
			dol_fiche_head($head, 'card', $langs->trans('AccountAccounting'), 0, 'billr');
			
			print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="edit">';
			print '<input type="hidden" name="id" value="' . $id . '">';
			
			print '<table class="border" width="100%">';
			
			print '<tr><td width="25%"><span class="fieldrequired">' . $langs->trans("AccountNumber") . '</span></td>';
			print '<td><input name="account_number" size="30" value="' . $accounting->account_number . '"</td></tr>';
			print '<tr><td><span class="fieldrequired">' . $langs->trans("Label") . '</span></td>';
			print '<td><input name="label" size="70" value="' . $accounting->label . '"</td></tr>';
			print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
			print '<td>';
			print $htmlacc->select_account($accounting->account_parent, 'account_parent', 1);
			print '</td></tr>';
			print '<tr><td>' . $langs->trans("Pcgtype") . '</td>';
			print '<td>';
			print $htmlacc->select_pcgtype($accounting->pcg_type, 'pcg_type');
			print '</td></tr>';
			print '<tr><td>' . $langs->trans("Pcgsubtype") . '</td>';
			print '<td>';
			print $htmlacc->select_pcgsubtype($accounting->pcg_subtype, 'pcg_subtype');
			print '</td></tr>';
			
			print '</table>';
			
			dol_fiche_end();
			
			print '<div class="center">';
			print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" name="cancel" class="button" value="' . $langs->trans("Cancel") . '">';
			print '</div>';
			
			print '</form>';
		} else {
			$linkback = '<a href="../admin/account.php">' . $langs->trans("BackToChartofaccounts") . '</a>';
			
			dol_fiche_head($head, 'card', $langs->trans('AccountAccounting'), 0, 'billr');
			
			print '<table class="border" width="100%">';
			
			// Account number
			print '<tr><td width="25%">' . $langs->trans("AccountNumber") . '</td>';
			print '<td>' . $accounting->account_number . '</td>';
			print '<td align="right" width="25%">' . $linkback . '</td></tr>';
			
			print '<tr><td>' . $langs->trans("Label") . '</td>';
			print '<td colspan="2">' . $accounting->label . '</td></tr>';
			
			$accp = new AccountingAccount($db);
			if (! empty($accounting->account_parent)) {
				$accp->fetch($accounting->account_parent, '');
			}
			print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
			print '<td colspan="2">' . $accp->account_number . ' - ' . $accp->label . '</td></tr>';
			
			print '<tr><td>' . $langs->trans("Pcgtype") . '</td>';
			print '<td colspan="2">' . $accounting->pcg_type . '</td></tr>';
			
			print '<tr><td>' . $langs->trans("Pcgsubtype") . '</td>';
			print '<td colspan="2">' . $accounting->pcg_subtype . '</td></tr>';
			
			print '<tr><td>' . $langs->trans("Activated") . '</td>';
			print '<td colspan="2">';
			
			if (empty($accounting->active)) {
				print img_picto($langs->trans("Disabled"), 'switch_off');
			} else {
				print img_picto($langs->trans("Activated"), 'switch_on');
			}
			
			print '</td></tr>';
			
			print '</table>';
			
			dol_fiche_end();
			
			/*
			 * Barre d'actions
			 */
			
			print '<div class="tabsAction">';
			
			if ($user->admin) {
				print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=update&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
			} else {
				print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
			}
			
			if ($user->admin) {
				print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
			} else {
				print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
			}
			
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}
}

llxFooter();

$db->close();