<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014-2015 Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * \file htdocs/accountancy/admin/index.php
 * \ingroup Accounting Expert
 * \brief Setup page to configure accounting expert module
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");

// Security check
if (! $user->admin)
	accessforbidden();

$action = GETPOST('action', 'alpha');

// Other parameters ACCOUNTING_*
$list = array (
		'ACCOUNTING_LIMIT_LIST_VENTILATION',
		'ACCOUNTING_LENGTH_DESCRIPTION', // adjust size displayed for lines description for dol_trunc
		'ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', // adjust size displayed for select account description for dol_trunc
		'ACCOUNTING_LENGTH_GACCOUNT',
		'ACCOUNTING_LENGTH_AACCOUNT',
		'ACCOUNTING_ACCOUNT_CUSTOMER',
		'ACCOUNTING_ACCOUNT_SUPPLIER',
		'ACCOUNTING_PRODUCT_BUY_ACCOUNT',
		'ACCOUNTING_PRODUCT_SOLD_ACCOUNT',
		'ACCOUNTING_SERVICE_BUY_ACCOUNT',
		'ACCOUNTING_SERVICE_SOLD_ACCOUNT',
		'ACCOUNTING_VAT_BUY_ACCOUNT',
		'ACCOUNTING_VAT_SOLD_ACCOUNT',
		'ACCOUNTING_ACCOUNT_SUSPENSE',
		'ACCOUNTING_ACCOUNT_TRANSFER_CASH' 
);

/*
 * Actions
 */

$accounting_mode = defined('ACCOUNTING_MODE') ? ACCOUNTING_MODE : 'RECETTES-DEPENSES';

if ($action == 'update') {
	$error = 0;
	
	$accounting_modes = array (
			'RECETTES-DEPENSES',
			'CREANCES-DETTES' 
	);
	
	$accounting_mode = GETPOST('accounting_mode', 'alpha');
	
	if (in_array($accounting_mode, $accounting_modes)) {
		
		if (! dolibarr_set_const($db, 'ACCOUNTING_MODE', $accounting_mode, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	} else {
		$error ++;
	}
	
	$chartofaccounts = GETPOST('chartofaccounts', 'int');
	
	if (! empty($chartofaccounts)) {
		
		if (! dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	} else {
		$error ++;
	}
	
	foreach ( $list as $constname ) {
		$constvalue = GETPOST($constname, 'alpha');
		
		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}
	
	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setlistsorttodo') {
	$setlistsorttodo = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $setlistsorttodo, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;
	
	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setlistsortdone') {
	$setlistsortdone = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_DONE", $setlistsortdone, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;
	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');

$head = admin_accounting_prepare_head($accounting);

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

dol_fiche_head($head, 'general', $langs->trans("Configuration"), 0, 'cron');

print '<table class="noborder" width="100%">';

// Cas du parametre ACCOUNTING_MODE

print '<tr class="liste_titre">';
print '<td>' . $langs->trans('OptionMode') . '</td><td>' . $langs->trans('Description') . '</td>';
print "</tr>\n";
print '<tr ' . $bc[false] . '><td width="200"><input type="radio" name="accounting_mode" value="RECETTES-DEPENSES"' . ($accounting_mode != 'CREANCES-DETTES' ? ' checked' : '') . '> ' . $langs->trans('OptionModeTrue') . '</td>';
print '<td colspan="2">' . nl2br($langs->trans('OptionModeTrueDesc'));
// Write info on way to count VAT
// if (! empty($conf->global->MAIN_MODULE_COMPTABILITE))
// {
// // print "<br>\n";
// // print nl2br($langs->trans('OptionModeTrueInfoModuleComptabilite'));
// }
// else
// {
// // print "<br>\n";
// // print nl2br($langs->trans('OptionModeTrueInfoExpert'));
// }
print "</td></tr>\n";
print '<tr ' . $bc[true] . '><td width="200"><input type="radio" name="accounting_mode" value="CREANCES-DETTES"' . ($accounting_mode == 'CREANCES-DETTES' ? ' checked' : '') . '> ' . $langs->trans('OptionModeVirtual') . '</td>';
print '<td colspan="2">' . nl2br($langs->trans('OptionModeVirtualDesc')) . "</td></tr>\n";

print "</table>\n";

print "<br>\n";

/*
 *  Define Chart of accounts
 */
print '<table class="noborder" width="100%">';
$var = true;

print '<tr class="liste_titre">';
print '<td colspan="3">';
print $langs->trans("Chartofaccounts") . '</td>';
print "</tr>\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print "<td>" . $langs->trans("Selectchartofaccounts") . "</td>";
print "<td>";
print '<select class="flat" name="chartofaccounts" id="chartofaccounts">';

$sql = "SELECT rowid, pcg_version, fk_pays, label, active";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_system";
$sql .= " WHERE active = 1";
$sql .= " AND fk_pays = " . $mysoc->country_id;

dol_syslog('accountancy/admin/index.php:: $sql=' . $sql);
$resql = $db->query($sql);

$var = true;

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ( $i < $num ) {
		$var = ! $var;
		$row = $db->fetch_row($resql);
		
		print '<option value="' . $row[0] . '"';
		print $conf->global->CHARTOFACCOUNTS == $row[0] ? ' selected' : '';
		print '>' . $row[1] . ' - ' . $row[3] . '</option>';
		
		$i ++;
	}
}
print "</select>";
print "</td></tr>";
print "</table>";

print "<br>\n";

/*
 *  Others params
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
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

$var = ! $var;
print "<tr " . $bc[$var] . ">";
print '<td width="80%">' . $langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_TODO") . '</td>';
if (! empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO)) {
	print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsorttodo&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsorttodo&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

$var = ! $var;
print "<tr " . $bc[$var] . ">";
print '<td width="80%">' . $langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_DONE") . '</td>';
if (! empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE)) {
	print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsortdone&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsortdone&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print "</table>\n";

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '</form>';

llxFooter();
$db->close();
