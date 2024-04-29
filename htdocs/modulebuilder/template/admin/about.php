<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/admin/about.php
 * \ingroup mymodule
 * \brief   About page of module MyModule.
 */

// Load Dolibarr environment
// Include the main.inc.php file of the closest parent
// Goes up until main Dolibarr main.inc.php
// Everyone can create its own main.inc.php inside its directory / module, as long as you include the parent one.
$pathMainInclude = "main.inc.php";
$res = 0; $limit = 15;
while ( ! $res && $limit ) {
    if ( file_exists(__DIR__.'/'.$pathMainInclude)) {
        $res = require_once __DIR__.'/'.$pathMainInclude;
    }
    // Trying one folder up
    $pathMainInclude = "../".$pathMainInclude;
    $limit--;
}
if ( ! $limit ) { die('Include of main fails'); }

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/mymodule.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "mymodule@mymodule"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "MyModuleSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-mymodule page-admin_about');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = mymoduleAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($title), 0, 'mymodule@mymodule');

dol_include_once('/mymodule/core/modules/modMyModule.class.php');
$tmpmodule = new modMyModule($db);
print $tmpmodule->getDescLong();

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
