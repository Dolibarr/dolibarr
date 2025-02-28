<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file    htdocs/webportal/admin/setup.php
 * \ingroup webportal
 * \brief   WebPortal setup page.
 */

// Load Dolibarr environment
require_once "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/webportal/lib/webportal.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "webportal", "website"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('webportalthemesetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

// Access control
if (!$user->admin) {
	accessforbidden();
}

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);

require_once __DIR__ . '/../class/webPortalTheme.class.php';
$webPortalTheme = new WebPortalTheme();

// Setup conf for secondary color
$item = $formSetup->newItem('WEBPORTAL_PRIMARY_COLOR');
$item->setAsColor();
$item->defaultFieldValue = $webPortalTheme->primaryColorHex;


// Logo URL
$item = $formSetup->newItem('WEBPORTAL_LOGIN_LOGO_URL');
$item->fieldAttr = array('type'=>'url', 'size'=> 50, 'placeholder'=>'https://');

$item = $formSetup->newItem('WEBPORTAL_MENU_LOGO_URL');
$item->fieldAttr = array('type'=>'url', 'size'=> 50, 'placeholder'=>'https://');



// Background URL
$item = $formSetup->newItem('WEBPORTAL_LOGIN_BACKGROUND');
$item->fieldAttr = array('type'=>'url', 'size'=> 50, 'placeholder'=>'https://');

$item = $formSetup->newItem('WEBPORTAL_BANNER_BACKGROUND');
$item->fieldAttr = array('type'=>'url', 'size'=> 50, 'placeholder'=>'https://');


$item = $formSetup->newItem('WEBPORTAL_BANNER_BACKGROUND_IS_DARK')->setAsYesNo();

$setupnotempty += count($formSetup->items);



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

// Force always edit mode
if (empty($action) || $action == 'update') {
	$action = 'edit';
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "WebPortalSetup";

llxHeader('', $langs->trans($title), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = webportalAdminPrepareHead();
print dol_get_fiche_head($head, 'themesettings', $langs->trans($title), -1, "webportal");

// Setup page goes here
//print info_admin($langs->trans("UserAccountForWebPortalAreInThirdPartyTabHelp"));

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>';
	print '</div>';
} else {
	print '<br>' . $langs->trans("NothingToSetup");
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
