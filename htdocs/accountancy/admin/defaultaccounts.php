<?php
/* Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2020  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015  Ari Elbaz (elarifr)     <github@accedinfo.com>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
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
 *
 */

/**
 * \file		htdocs/accountancy/admin/defaultaccounts.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Setup page to configure accounting expert module
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "salaries", "loan"));

// Security check
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


$list_account_main = array(
    'ACCOUNTING_ACCOUNT_CUSTOMER',
    'ACCOUNTING_ACCOUNT_SUPPLIER',
    'SALARIES_ACCOUNTING_ACCOUNT_PAYMENT',
);

$list_account = array();
$list_account[] = '---Product---';
$list_account[] = 'ACCOUNTING_PRODUCT_SOLD_ACCOUNT';
if ($mysoc->isInEEC()) {
	$list_account[] = 'ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT';
}
$list_account[] = 'ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT';
$list_account[] = 'ACCOUNTING_PRODUCT_BUY_ACCOUNT';
if ($mysoc->isInEEC()) {
	$list_account[] = 'ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT';
}
$list_account[] = 'ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT';
$list_account[] = '---Service---';
$list_account[] = 'ACCOUNTING_SERVICE_SOLD_ACCOUNT';
if ($mysoc->isInEEC()) {
	$list_account[] = 'ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT';
}
$list_account[] = 'ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT';
$list_account[] = 'ACCOUNTING_SERVICE_BUY_ACCOUNT';
if ($mysoc->isInEEC()) {
	$list_account[] = 'ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT';
}
$list_account[] = 'ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT';
$list_account[] = '---Other---';
$list_account[] = 'ACCOUNTING_VAT_BUY_ACCOUNT';
$list_account[] = 'ACCOUNTING_VAT_SOLD_ACCOUNT';
$list_account[] = 'ACCOUNTING_VAT_PAY_ACCOUNT';
$list_account[] = 'ACCOUNTING_ACCOUNT_SUSPENSE';
if ($conf->banque->enabled) {
	$list_account[] = 'ACCOUNTING_ACCOUNT_TRANSFER_CASH';
}
if ($conf->don->enabled) {
	$list_account[] = 'DONATION_ACCOUNTINGACCOUNT';
}
if ($conf->adherent->enabled) {
	$list_account[] = 'ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT';
}
if ($conf->loan->enabled) {
	$list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_CAPITAL';
	$list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_INTEREST';
	$list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_INSURANCE';
}

/*
 * Actions
 */

$accounting_mode = empty($conf->global->ACCOUNTING_MODE) ? 'RECETTES-DEPENSES' : $conf->global->ACCOUNTING_MODE;

if (GETPOST('change_chart', 'alpha'))
{
    $chartofaccounts = GETPOST('chartofaccounts', 'int');

    if (!empty($chartofaccounts)) {
        if (!dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
            $error++;
        }
    } else {
        $error++;
    }
}

if ($action == 'update') {
	$error = 0;

	foreach ($list_account_main as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	}

	foreach ($list_account as $constname) {
		$reg = array();
		if (preg_match('/---(.*)---/', $constname, $reg)) {	// This is a separator
			continue;
		}

		$constvalue = GETPOST($constname, 'alpha');

	    if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
	        $error++;
	    }
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

llxHeader();

$linkback = '';
print load_fiche_titre($langs->trans('MenuDefaultAccounts'), $linkback, 'title_accountancy');

print '<span class="opacitymedium">'.$langs->trans("DefaultBindingDesc").'</span><br>';
print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';


// Define main accounts for thirdparty

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("ThirdParties").' | '.$langs->trans("Users").'</td><td></td></tr>';

foreach ($list_account_main as $key) {
    print '<tr class="oddeven value">';
    // Param
    $label = $langs->trans($key);
    $keydesc = $key.'_Desc';

    $htmltext = $langs->trans($keydesc);
    print '<td class="fieldrequired" width="50%">';
    print $form->textwithpicto($label, $htmltext);
    print '</td>';
    // Value
    print '<td>'; // Do not force class=right, or it align also the content of the select box
    print $formaccounting->select_account($conf->global->$key, $key, 1, '', 1, 1);
    print '</td>';
    print '</tr>';
}


foreach ($list_account as $key) {
	$reg = array();
	if (preg_match('/---(.*)---/', $key, $reg)) {
		print '<tr class="liste_titre"><td>'.$langs->trans($reg[1]).'</td><td></td></tr>';
	}
	else {
		print '<tr class="oddeven value">';
		// Param
		$label = $langs->trans($key);
		print '<td width="50%">'.$label.'</td>';
		// Value
		print '<td>'; // Do not force class=right, or it align also the content of the select box
		print $formaccounting->select_account($conf->global->$key, $key, 1, '', 1, 1);
		print '</td>';
		print '</tr>';
	}
}


print "</table>\n";


print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
