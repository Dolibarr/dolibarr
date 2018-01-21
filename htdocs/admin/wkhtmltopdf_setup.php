<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2017       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    admin/wkhtmltopdf_setup.php
 * \ingroup wkhtmltopdf
 * \brief   wkhtmltopdf setup page.
 */

// Load Dolibarr environment
require '../main.inc.php';

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/wkhtmltopdf.lib.php';


// Translations
$langs->loadLangs(array("admin", "wkhtmltopdf"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

if ($action == 'update') {
    dolibarr_set_const($db, "WKHTMLTOPDF_PATH", GETPOST('wkpath', 'alpha'), 'chaine', 0, "chemin vers binaire wkhtmltopdf", $conf->entity);
}


/*
 * View
 */

$page_name = "WkHtmlToPdfSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = wkhtmltopdfAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("ModuleWkHtmlToPdfName"),
    -1,
    "wkhtmltopdf"
);

// Setup page goes here
echo $langs->trans("WkHtmlToPdfSetupPage");


if ($action == 'edit') {
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('WKHTMLTOPDF_PATH'), $langs->trans('WKHTMLTOPDF_PATH_Tooltip'));
    print '</td><td><input class="flat" name="wkpath" size="100" value="' . $conf->global->WKHTMLTOPDF_PATH . '"></td></tr>';

    print '</table>';

    print '<br><div class="center">';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</div>';

    print '</form>';
    print '<br>';
} else {
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans('WKHTMLTOPDF_PATH'), $langs->trans('WKHTMLTOPDF_PATH_Tooltip'));
    print '</td><td>' . $conf->global->WKHTMLTOPDF_PATH . '</td></tr>';

    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';
}


// Page end
dol_fiche_end();

llxFooter();
$db->close();
