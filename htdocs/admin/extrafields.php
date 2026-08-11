<?php
/* Copyright (C) 2026  Frédéric France  <frederic.france@free.fr>
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
 *      \file       htdocs/admin/extrafields.php
 *		\ingroup    core
 *		\brief      Single, secured admin page to create/edit/delete the
 *		            extrafields of any registered object type. The set of
 *		            accepted object types is a closed whitelist — see
 *		            core/lib/admin_extrafields.lib.php.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin_extrafields.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

if (!$user->admin) {
	accessforbidden();
}

$elementtype = GETPOST('elementtype', 'aZ09');

$extrafieldsadminmap = getExtrafieldsAdminMap();
if ($elementtype === '' || !array_key_exists($elementtype, $extrafieldsadminmap)) {
	accessforbidden('Bad or missing value for parameter elementtype');
}
$pagedef = $extrafieldsadminmap[$elementtype];

$langs->loadLangs($pagedef['langs']);

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$type2label = ExtraFields::getListOfTypesLabels();

$action = GETPOST('action', 'aZ09');
$attrname = GETPOST('attrname', 'alpha');


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';


/*
 * View
 */

$title = is_callable($pagedef['title']) ? $pagedef['title']() : $langs->trans($pagedef['title']);
$headlabel = is_callable($pagedef['headlabel']) ? $pagedef['headlabel']() : $langs->trans($pagedef['headlabel']);
if (isset($pagedef['textobject'])) {
	$textobject = is_callable($pagedef['textobject']) ? $pagedef['textobject']() : $langs->transnoentitiesnoconv($pagedef['textobject']);
} else {
	$textobject = $headlabel;
}

$help_url = $pagedef['helpurl'];
llxHeader('', $title, $help_url);

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($title, $linkback, 'title_setup');

require_once DOL_DOCUMENT_ROOT.'/'.$pagedef['headfile'];
$head = call_user_func($pagedef['headfunction']);

print dol_get_fiche_head($head, $pagedef['tabid'], $headlabel, -1, $pagedef['headpicto']);

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

print dol_get_fiche_end();


// Creation of an optional field
if ($action == 'create') {
	print '<br><div id="newattrib"></div>';
	print load_fiche_titre($langs->trans('NewAttribute'));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
	print '<br><div id="editattrib"></div>';
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));

	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();
$db->close();
