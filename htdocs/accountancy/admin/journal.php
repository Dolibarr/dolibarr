<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2014 	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016      Laurent Destailleur 	<eldy@users.sourceforge.net>
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
 *
 */

/**
 * \file		htdocs/accountancy/admin/journal.php
* \ingroup		Advanced accountancy
* \brief		Setup page to configure accounting expert module
*/
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");
$langs->load("salaries");

// Security check
if (empty($user->admin) || ! empty($user->rights->accountancy->chartofaccount))
{
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Other parameters ACCOUNTING_*
$list = array (
		'ACCOUNTING_SELL_JOURNAL',
		'ACCOUNTING_PURCHASE_JOURNAL',
		'ACCOUNTING_SOCIAL_JOURNAL',
		'ACCOUNTING_MISCELLANEOUS_JOURNAL',
		'ACCOUNTING_EXPENSEREPORT_JOURNAL'
);

/*
 * Actions
*/

if ($action == 'update') {
	$error = 0;

	// Save vars
	foreach ($list as $constname)
	{
		$constvalue = GETPOST($constname, 'alpha');

		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}

	// Save bank account journals
	$arrayofbankaccount = GETPOST('bank_account', 'array');
	foreach($arrayofbankaccount as $key => $code)
	{
		$bankaccount = new Account($db);
		$res = $bankaccount->fetch($key);
		if ($res > 0)
		{
			$bankaccount->accountancy_journal = $code;
			$bankaccount->update($user);
		}
		else
		{
			$error++;
			break;
		}
	}

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
*/

llxHeader();

$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');

$head = admin_accounting_prepare_head(null);

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

dol_fiche_head($head, 'journal', $langs->trans("Configuration"), 0, 'cron');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('Journaux') . '</td>';
print "</tr>\n";

foreach ( $list as $key ) {
	$var = ! $var;

	print '<tr ' . $bc[$var] . ' class="value">';

	// Param
	$label = $langs->trans($key);
	print '<td width="50%"><label for="' . $key . '">' . $label . '</label></td>';

	// Value
	print '<td>';
	print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . $conf->global->$key . '">';
	print '</td></tr>';
}

print "</table>\n";

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('JournalFinancial') . ' ('.$langs->trans('Opened').')</td>';
print "</tr>\n";

// Bank account
$sql = "SELECT rowid, ref, label, number, account_number, accountancy_journal";
$sql .= " FROM " . MAIN_DB_PREFIX . "bank_account";
$sql .= " WHERE entity = " . $conf->entity;
$sql .= " AND clos = 0";
$sql .= " ORDER BY label";

$resql = $db->query($sql);
if ($resql) {
	$numr = $db->num_rows($resql);
	$i = 0;

	if ($numr > 0)

		$bankaccountstatic = new Account($db);

	while ( $i < $numr ) {
		$objp = $db->fetch_object($resql);

		$var = ! $var;

		$bankaccountstatic->rowid = $objp->rowid;
		$bankaccountstatic->id = $objp->rowid;
		$bankaccountstatic->ref = $objp->ref;
		$bankaccountstatic->label = $objp->label;
		$bankaccountstatic->number = $objp->number;
		$bankaccountstatic->account_number = $objp->account_number;
		$bankaccountstatic->accountancy_journal = $objp->accountancy_journal;

		print '<tr ' . $bc[$var] . ' class="value">';

		// Param
		print '<td width="50%"><label for="' . $objp->rowid . '">' . $langs->trans("Journal");
		print ' - '.$bankaccountstatic->getNomUrl(1);
		print '</label></td>';

		// Value
		print '<td>';
		print '<input type="text" size="20" id="' . $objp->rowid . '" name="bank_account['.$objp->rowid.']" value="' . $objp->accountancy_journal . '">';
		print '</td></tr>';

		$i ++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print "</table>\n";

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '</form>';

llxFooter();
$db->close();