<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2014 	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 * \file		htdocs/accountancy/admin/journaux.php
 * \ingroup		Accounting Expert
 * \brief		Setup page to configure accounting expert module
 */

require '../../main.inc.php';
	
// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("accountancy");

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

// Other parameters ACCOUNTING_*
$list = array (
		'ACCOUNTING_SELL_JOURNAL',
		'ACCOUNTING_PURCHASE_JOURNAL',
		'ACCOUNTING_SOCIAL_JOURNAL',
		'ACCOUNTING_CASH_JOURNAL',
		'ACCOUNTING_MISCELLANEOUS_JOURNAL' 
);

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;
	
	foreach ( $list as $constname ) {
		$constvalue = GETPOST($constname, 'alpha');
		
		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}
	
	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"));
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

print_fiche_titre($langs->trans('ConfigAccountingExpert'));

$head = admin_accounting_prepare_head(null);

dol_fiche_head($head, 'journal', $langs->trans("Configuration"), 0, 'cron');

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Journaux') . '</td>';
print "</tr>\n";

foreach ( $list as $key ) {
	$var = ! $var;
	
	print '<tr ' . $bc[$var] . ' class="value">';
	
	// Param
	$label = $langs->trans($key);
	print '<td><label for="' . $key . '">' . $label . '</label></td>';
	
	// Value
	print '<td>';
	print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . $conf->global->$key . '">';
	print '</td></tr>';
}

print '</form>';
print "</table>\n";

print '<br /><div style="text-align:center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '<br />';

// Bank account
$sql  = "SELECT ba.rowid, ba.ref , ba.label, ba.bank , ba.account_number, ba.code_journal ";
$sql .= " FROM ".MAIN_DB_PREFIX."lx_bank_account as ba";
$sql .= " WHERE ba.clos = 0" ;
$sql .= " ORDER BY label";

dol_syslog('accountancy/admin/journaux.php:: $sql='.$sql);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('JournauxTresorerie') . '</td>';
print "</tr>\n";

$form2 = new Form($db);

$account = new Account($db);
foreach ( $resql as $key ) {
	$var = ! $var;
	
	print '<tr ' . $bc[$var] . ' class="value">';
	
	// Param
	$label = $langs->trans($key);
	print '<td><label for="' . $key . '">' . $label . '</label></td>';
	
	// Value
	print '<td>';
	print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . $conf->global->$key . '">';
	print '</td></tr>';
}

print '</table>';
print '</div>';

print '<br>';

llxFooter();
$db->close();