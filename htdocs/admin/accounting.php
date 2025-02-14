<?php
/* Copyright (C) 2018-2021	Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 */

/**
 *	\file       htdocs/admin/accounting.php
 *	\ingroup    accounting
 *	\brief      Setup page to configure accounting module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'adminaccoutant'; // To manage different context of search

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies', 'accountancy'));

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

// Nothing


/*
 * View
 */

$title = $langs->trans("ConfigAccountingExpert");
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-admin page-accounting');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

print "<br>\n";
$texttoshow = $langs->trans("AccountancySetupDoneFromAccountancyMenu", '{s1}'.$langs->transnoentitiesnoconv("Accounting").' - '.$langs->transnoentitiesnoconv("Setup").'{s2}');
$texttoshow = str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/accountancy/index.php?mainmenu=accountancy&leftmenu=accountancy_admin">', $texttoshow);
$texttoshow = str_replace('{s2}', '</a>'.img_picto("", "url", 'class="paddingleft"'), $texttoshow);
print '<span class="opacitymedium">'.$texttoshow."</span><br>\n";
print "<br>\n";

llxFooter();

$db->close();
