<?php
/* Copyright (C) 2014		Florian HENRY	<florian.henry@open-concept.pro>
 * Copyright (C) 2016-2020	Regis Houssin	<regis.houssin@inodbox.com>
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
 *	\file       multicompany/admin/multicompany.php
 *	\ingroup    multicompany
 *	\brief      Page to setup extra fields of entities
 */

// Dolibarr environment
$res=@include("../../main.inc.php");						// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
dol_include_once('/multicompany/lib/multicompany.lib.php');

// Security check
if (empty($user->admin) || ! empty($user->entity)) {
	accessforbidden();
}

$langs->loadLangs(array('admin', 'other'));

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) $type2label[$key] = $langs->transnoentitiesnoconv($val);

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'entity'; // Must be the $table_element of the class that manage extrafield


/*
 * Actions
 */

require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';


/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("Entities");

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySetup"), $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"), $linkback, 'multicompany@multicompany',0,'multicompany_title');

// Configuration header
$head = multicompany_prepare_head();
dol_fiche_head($head, 'attributes', $langs->trans("ModuleSetup"), -1);

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

dol_fiche_end();

// Buttons
if ($action != 'create' && $action != 'edit') {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=create">' . $langs->trans("NewAttribute") . '</a>';
	print '</div>';
}

/* ************************************************************************* */
/*                                                                            */
/* Creation d'un champ optionnel											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create') {
	print "<br>";
	print load_fiche_titre($langs->trans('NewAttribute'));

	require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************* */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname)) {
	print "<br>";
	print load_fiche_titre($langs->trans("FieldEdition", $attrname));

	require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();
$db->close();
