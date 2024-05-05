<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
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
 *      \file       htdocs/modulebuilder/template/admin/myobject_extrafields.php
 *		\ingroup    mymodule
 *		\brief      Page to setup extra fields of myobject
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

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once '../lib/mymodule.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('mymodule@mymodule', 'admin'));

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$type2label = ExtraFields::getListOfTypesLabels();

$action = GETPOST('action', 'aZ09');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'mymodule_myobject'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';



/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("MyObject");

$help_url = '';
$page_name = "MyModuleSetup";

llxHeader('', $langs->trans("MyModuleSetup"), $help_url, '', 0, 0, '', '', '', 'mod-mymodule page-admin_extrafields');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');


$head = mymoduleAdminPrepareHead();

print dol_get_fiche_head($head, 'myobject_extrafields', $langs->trans($page_name), -1, 'mymodule@mymodule');

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

print dol_get_fiche_end();


// Buttons
if ((float) DOL_VERSION < 17) {	// On v17+, the "New Attribute" button is included into tpl.
	if ($action != 'create' && $action != 'edit') {
		print '<div class="tabsAction">';
		print '<a class="butAction reposition" href="'.$_SERVER["PHP_SELF"].'?action=create">'.$langs->trans("NewAttribute").'</a>';
		print "</div>";
	}
}


/*
 * Creation of an optional field
 */
if ($action == 'create') {
	print '<br><div id="newattrib"></div>';
	print load_fiche_titre($langs->trans('NewAttribute'));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/*
 * Edition of an optional field
 */
if ($action == 'edit' && !empty($attrname)) {
	print "<br>";
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();
$db->close();
