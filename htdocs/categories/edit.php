<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
 * Copyright (C) 2020      Frédéric France		<frederic.france@netlogic.fr>
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
 *      \file       htdocs/categories/edit.php
 *      \ingroup    category
 *      \brief      Page d'edition de categorie produit
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->load("categories");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alphanohtml');
$action = (GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'edit');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$socid = (int) GETPOST('socid', 'int');
$label = (string) GETPOST('label', 'alphanohtml');
$description = (string) GETPOST('description', 'restricthtml');
$color = preg_replace('/[^0-9a-f#]/i', '', (string) GETPOST('color', 'alphanohtml'));
$visible = (int) GETPOST('visible', 'int');
$parent = (int) GETPOST('parent', 'int');

if ($id == "") {
	dol_print_error('', 'Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result = $object->fetch($id, $label);
if ($result <= 0) {
	dol_print_error($db, $object->error); exit;
}

$type = $object->type;
if (is_numeric($type)) {
	$type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility
}

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

$error = 0;


/*
 * Actions
 */

if ($cancel) {
	if ($backtopage) {
		header("Location: ".$backtopage);
		exit;
	} else {
		header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$object->id.'&type='.$type);
		exit;
	}
}

// Action mise a jour d'une categorie
if ($action == 'update' && $user->rights->categorie->creer) {
	$object->oldcopy = dol_clone($object);
	$object->label = $label;
	$object->description    = dol_htmlcleanlastbr($description);
	$object->color          = $color;
	$object->socid          = ($socid > 0 ? $socid : 0);
	$object->visible        = $visible;
	$object->fk_parent = $parent != -1 ? $parent : 0;

	if (empty($object->label)) {
		$error++;
		$action = 'edit';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!$error && empty($object->error)) {
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}

		if (!$error && $object->update($user) > 0) {
			if ($backtopage) {
				header("Location: ".$backtopage);
				exit;
			} else {
				header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$object->id.'&type='.$type);
				exit;
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader("", "", $langs->trans("Categories"));

print load_fiche_titre($langs->trans("ModifCat"));

$object->fetch($id);


print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

print dol_get_fiche_head('');

print '<table class="border centpercent">';

// Ref
print '<tr><td class="titlefieldcreate fieldrequired">';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="label" name ="label" value="'.$object->label.'" />';
print '</tr>';

// Description
print '<tr>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', '', false, true, $conf->fckeditor->enabled, ROWS_6, '90%');
$doleditor->Create();
print '</td></tr>';

// Color
print '<tr>';
print '<td>'.$langs->trans("Color").'</td>';
print '<td>';
print $formother->selectColor($object->color, 'color');
print '</td></tr>';

// Parent category
print '<tr><td>'.$langs->trans("In").'</td><td>';
print $form->select_all_categories($type, $object->fk_parent, 'parent', 64, $object->id);
print '</td></tr>';

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (empty($reshook)) {
	print $object->showOptionals($extrafields, 'edit', $parameters);
}

print '</table>';


print dol_get_fiche_end();


print '<div class="center"><input type="submit" class="button" name"submit" value="'.$langs->trans("Modify").'"> &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
