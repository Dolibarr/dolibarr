<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Modify') . '" name="button"></div>';

print '<br>';
print '<br>';

print $langs->trans("AccountancySetupDoneFromAccountancyMenu", $langs->transnoentitiesnoconv("Home").'-'.$langs->transnoentitiesnoconv("Financial").'-'.$langs->transnoentitiesnoconv("Accountancy"));

print '<br>';
print '</form>';

llxFooter();
$db->close();
