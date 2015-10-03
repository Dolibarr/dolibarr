<?php
/* Copyright (C) 2014		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 * \file		htdocs/admin/salaries.php
 * \ingroup		Salaries
 * \brief		Setup page to configure salaries module
 */

require '../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load("salaries");

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

// Other parameters SALARIES_*
$list = array (
		'SALARIES_ACCOUNTING_ACCOUNT_PAYMENT',
		'SALARIES_ACCOUNTING_ACCOUNT_CHARGE'
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

    if (! $error)
    {
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        setEventMessage($langs->trans("Error"),'errors');
    }
}

/*
 * View
 */

llxHeader('',$langs->trans('SalariesSetup'));

$form = new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('SalariesSetup'),$linkback,'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

/*
 *  Params
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Options') . '</td>';
print "</tr>\n";

foreach ($list as $key)
{
	$var=!$var;

	print '<tr '.$bc[$var].' class="value">';

	// Param
	$label = $langs->trans($key);
	print '<td><label for="'.$key.'">'.$label.'</label></td>';

	// Value
	print '<td>';
	print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	print '</td></tr>';
}

print '</tr>';

print '</form>';
print "</table>\n";

print '<br /><div style="text-align:center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

llxFooter();
$db->close();