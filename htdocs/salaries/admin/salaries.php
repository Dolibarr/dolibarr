<?php
/* Copyright (C) 2014-2019  Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 * \file		htdocs/salaries/admin/salaries.php
 * \ingroup		Salaries
 * \brief		Setup page to configure salaries module
 */

require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'salaries'));

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

// Other parameters SALARIES_*
$list = array(
		'SALARIES_ACCOUNTING_ACCOUNT_PAYMENT',
);

/*
 * Actions
 */

if ($action == 'update')
{
    $error = 0;

    foreach ($list as $constname) {
        $constvalue = GETPOST($constname, 'alpha');

        if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
            $error++;
        }
    }

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

llxHeader('', $langs->trans('SalariesSetup'));

$form = new Form($db);
if (!empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('SalariesSetup'), $linkback, 'title_setup');

$head = salaries_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Salaries"), -1, 'payment');

// Document templates
print load_fiche_titre($langs->trans("Options"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

/*
 *  Params
 */
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td width="60">'.$langs->trans("Value")."</td>\n";
print "</tr>\n";

foreach ($list as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td class="fieldrequired" width="50%"><label for="'.$key.'">'.$label.'</label></td>';

	// Value
	print '<td>';
	if (!empty($conf->accounting->enabled))
	{
		print $formaccounting->select_account($conf->global->$key, $key, 1, '', 1, 1);
	}
	else
	{
		print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	}
	print '</td></tr>';
}

print '</tr>';

print "</table>\n";

//dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
