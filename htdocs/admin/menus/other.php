<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@capnetworks.com>
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
 */

/**
 *	    \file       htdocs/admin/menus/other.php
 *      \ingroup    core
 *      \brief      Menus options setup
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (! $user->admin) accessforbidden();

$action=GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == 'activate_hidemenu')
{
	dolibarr_set_const($db, "MAIN_MENU_HIDE_UNAUTHORIZED", '1','chaine',0,'',$conf->entity);
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}
else if ($action == 'disable_hidemenu')
{
	dolibarr_del_const($db, "MAIN_MENU_HIDE_UNAUTHORIZED",$conf->entity);
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

llxHeader('',$langs->trans("Setup"));

print load_fiche_titre($langs->trans("Menus"),'','title_setup');


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/other.php";
$head[$h][1] = $langs->trans("Miscellaneous");
$head[$h][2] = 'misc';
$h++;

dol_fiche_head($head, 'misc', $langs->trans("Menus"));


// Other Options
$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td align="center" width="80">'.$langs->trans("Status").'</td>';
print '</tr>';

// Hide unauthorized menu
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("HideUnauthorizedMenu").'</td>';
print '<td align="center">';
if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=activate_hidemenu">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=disable_hidemenu">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print "</td>";
print '</tr>';

print '</table>';


llxFooter();
$db->close();
