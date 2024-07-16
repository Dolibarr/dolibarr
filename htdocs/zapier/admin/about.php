<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Frédéric FRANCE <frederic.france@free.fr>
 *
 *
 * LICENSE =================================================================
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
 *
 */

/**
 *    \file       htdocs/zapier/admin/about.php
 *    \ingroup    zapier
 *    \brief      About page of module Zapier.
 */

// Load Dolibarr environment
require '../../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/zapier.lib.php';

// Translations
$langs->loadLangs(array('admin', 'errors', 'zapier'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

if (!isModEnabled('zapier')) {
	accessforbidden();
}
if (empty($user->admin)) {
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

$page_name = "ZapierForDolibarrSetup";
$help_url = 'EN:Module_Zapier';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_zapier');

// Configuration header
$head = zapierAdminPrepareHead();
print dol_get_fiche_head($head, 'about', '', 0, 'zapier');

dol_include_once('/core/modules/modZapier.class.php');
$tmpmodule = new modZapier($db);
print $tmpmodule->getDescLong();

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
