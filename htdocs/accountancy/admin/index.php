<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
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

// Security check
if (empty($user->admin))
{
	accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Parameters ACCOUNTING_* and others
$list = array (
    'ACCOUNTING_LENGTH_GACCOUNT',
    'ACCOUNTING_LENGTH_AACCOUNT' ,
    'ACCOUNTING_LENGTH_DESCRIPTION', // adjust size displayed for lines description for dol_trunc
    'ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', // adjust size displayed for select account description for dol_trunc
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
	
	if ($error) {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

    foreach ($list as $constname) 
    {
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

// TO DO Mutualize code for yes/no constants
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

if ($action == 'setmanagezero') {
    $setmanagezero = GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "ACCOUNTING_MANAGE_ZERO", $setmanagezero, 'yesno', 0, '', $conf->entity);
    if (! $res > 0)
        $error ++;
        if (! $error) {
            setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("Error"), null, 'mesgs');
        }
}

if ($action == 'setdisabledirectinput') {
    $setdisabledirectinput = GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "BANK_DISABLE_DIRECT_INPUT", $setdisabledirectinput, 'yesno', 0, '', $conf->entity);
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
$formaccountancy = new FormVentilation($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');

$head = admin_accounting_prepare_head($accounting);

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

dol_fiche_head($head, 'general', $langs->trans("Configuration"), 0, 'cron');


// Default mode for calculating turnover (parameter ACCOUNTING_MODE)

print '<table class="noborder" width="100%">';

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


print '<br>';


// Others params

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('OtherOptions') . '</td>';
print "</tr>\n";

if (! empty($user->admin))
{
    // TO DO Mutualize code for yes/no constants
    $var = ! $var;
    print "<tr " . $bc[$var] . ">";
    print '<td>' . $langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_TODO") . '</td>';
    if (! empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO)) {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsorttodo&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsorttodo&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';

    $var = ! $var;
    print "<tr " . $bc[$var] . ">";
    print '<td>' . $langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_DONE") . '</td>';
    if (! empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE)) {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsortdone&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setlistsortdone&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';

    $var = ! $var;
    print "<tr " . $bc[$var] . ">";
    print '<td>' . $langs->trans("BANK_DISABLE_DIRECT_INPUT") . '</td>';
    if (! empty($conf->global->BANK_DISABLE_DIRECT_INPUT)) {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setdisabledirectinput&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setdisabledirectinput&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';
    
    $var = ! $var;
    print "<tr " . $bc[$var] . ">";
    print '<td>' . $langs->trans("ACCOUNTING_MANAGE_ZERO") . '</td>';
    if (! empty($conf->global->ACCOUNTING_MANAGE_ZERO)) {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setmanagezero&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=setmanagezero&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';
}


// Param a user $user->rights->accountancy->chartofaccount can access
foreach ($list as $key) 
{
    $var = ! $var;

    print '<tr ' . $bc[$var] . ' class="value">';
    
    if (! empty($conf->global->ACCOUNTING_MANAGE_ZERO) && ($key == 'ACCOUNTING_LENGTH_GACCOUNT' || $key == 'ACCOUNTING_LENGTH_AACCOUNT')) continue;

    // Param
    $label = $langs->trans($key);
    print '<td>'.$label.'</td>';
    // Value
    print '<td align="right">';
    print '<input type="text" class="maxwidth100" id="' . $key . '" name="' . $key . '" value="' . $conf->global->$key . '">';
    print '</td>';
    
    print '</tr>';
}


print '</table>';




dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '<br>';
print '<br>';

print $langs->trans("AccountancySetupDoneFromAccountancyMenu", $langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy"));

print '<br>';
print '</form>';

llxFooter();
$db->close();
