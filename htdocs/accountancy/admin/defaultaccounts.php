<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro@zendsi.com>
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
 * \file		htdocs/accountancy/admin/index.php
 * \ingroup		Advanced accountancy
 * \brief		Setup page to configure accounting expert module
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("admin");
$langs->load("accountancy");
$langs->load("salaries");
$langs->load("loan");

// Security check
if (! empty($user->rights->accountancy->chartofaccount))
{
	accessforbidden();
}

$action = GETPOST('action', 'alpha');


$list_account = array (
		'ACCOUNTING_ACCOUNT_SUPPLIER',
		'ACCOUNTING_ACCOUNT_CUSTOMER',
		'SALARIES_ACCOUNTING_ACCOUNT_PAYMENT',
		'ACCOUNTING_PRODUCT_BUY_ACCOUNT',
		'ACCOUNTING_PRODUCT_SOLD_ACCOUNT',
		'ACCOUNTING_SERVICE_BUY_ACCOUNT',
		'ACCOUNTING_SERVICE_SOLD_ACCOUNT',
		'ACCOUNTING_VAT_BUY_ACCOUNT',
		'ACCOUNTING_VAT_SOLD_ACCOUNT',
		'ACCOUNTING_VAT_PAY_ACCOUNT',
		'ACCOUNTING_ACCOUNT_SUSPENSE',
		'ACCOUNTING_ACCOUNT_TRANSFER_CASH',
		'DONATION_ACCOUNTINGACCOUNT',
		'LOAN_ACCOUNTING_ACCOUNT_CAPITAL',
		'LOAN_ACCOUNTING_ACCOUNT_INTEREST',
		'LOAN_ACCOUNTING_ACCOUNT_INSURANCE'
);


/*
 * Actions
 */

$accounting_mode = defined('ACCOUNTING_MODE') ? ACCOUNTING_MODE : 'RECETTES-DEPENSES';


if (GETPOST('change_chart'))
{
    $chartofaccounts = GETPOST('chartofaccounts', 'int');

    if (! empty($chartofaccounts)) {

        if (! dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
            $error ++;
        }
    } else {
        $error ++;
    }
}

if ($action == 'update') {
	$error = 0;
	
	foreach ( $list_account as $constname ) {
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


/*
 * View
 */

llxHeader();

$form = new Form($db);
$formaccountancy = new FormVentilation($db);

$linkback = '';
print load_fiche_titre($langs->trans('MenuDefaultAccounts'), $linkback, 'title_accountancy');

print '<br>';
print $langs->trans("DefaultBindingDesc").'<br>';
print '<br>';

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

// Define Chart of accounts

print '<table class="noborder" width="100%">';

foreach ( $list_account as $key ) {
	$var = ! $var;
	
	print '<tr ' . $bc[$var] . ' class="value">';
	// Param
	$label = $langs->trans($key);
	print '<td>' . $label . '</td>';
	// Value
	print '<td>';  // Do not force align=right, or it align also the content of the select box 
	print $formaccountancy->select_account($conf->global->$key, $key, 1, '', 1, 1);
	print '</td>';
	print '</tr>';
}


print "</table>\n";


print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '</form>';

llxFooter();
$db->close();
