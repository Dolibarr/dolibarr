<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 * \file    admin/about.php
 * \ingroup mymodule
 * \brief   About page of module MyModule.
 *
 * MyModuleDescription.
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/mymodule.lib.php';

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("mymodule@mymodule");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$page_name = "MyModuleAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = mymoduleAdminPrepareHead();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("MyModuleName"),
	0,
	'mymodule@mymodule'
);

// About page goes here
echo $langs->trans("MyModuleAboutPage");

echo '<br>';

require_once '../core/modulebuilder/mymodule/core/modules/modMyModule.class.php';
$tmpmodule = new MyModule($db);
print $tmpmodule->getDescLong();

// Page end
dol_fiche_end();
llxFooter();
$db->close();
