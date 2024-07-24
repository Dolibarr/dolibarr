<?php
/* Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2016  Destailleur Laurent     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/categories/traduction.php
 *	\ingroup    categories
 *	\brief      Page of translation of categories
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('categories', 'languages'));

$id     = GETPOST('id', 'int');
$label  = GETPOST('label', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

if ($id == '' && $label == '') {
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
	$type = Categorie::$MAP_ID_TO_CODE[$type];   // For backward compatibility
}


/*
 * Actions
 */

$error = 0;

// return to translation view if cancelled
if ($cancel == $langs->trans("Cancel")) {
	$action = '';
}


// validation of addition
if ($action == 'vadd' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->categorie->creer)) {
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	// check parameters
	$forcelangprod = GETPOST('forcelangprod', 'alpha');
	$libelle = GETPOST('libelle', 'alpha');
	$desc = GETPOST('desc', 'restricthtml');

	if (empty($forcelangprod)) {
		$error++;
		$object->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Translation'));
	}

	if (!$error) {
		if (empty($libelle)) {
			$error++;
			$object->errors[] = $langs->trans('Language_'.$forcelangprod).' : '.$langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label'));
		}

		if (!$error) {
			// update the object
			if ($forcelangprod == $current_lang) {
				$object->label = $libelle;
				$object->description = dol_htmlcleanlastbr($desc);
			} else {
				$object->multilangs[$forcelangprod]["label"] = $libelle;
				$object->multilangs[$forcelangprod]["description"] = dol_htmlcleanlastbr($desc);
			}

			// save in base / sauvegarde en base
			$res = $object->setMultiLangs($user);
			if ($res < 0) {
				$error++;
			}
		}
	}

	if ($error) {
		$action = 'add';
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		$action = '';
	}
}

// validation of the edition
if ($action == 'vedit' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->categorie->creer)) {
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ($object->multilangs as $key => $value) {     // recording of new values in the object
		$libelle = GETPOST('libelle-'.$key, 'alpha');
		$desc = GETPOST('desc-'.$key, 'restricthtml');

		if (empty($libelle)) {
			$error++;
			$object->errors[] = $langs->trans('Language_'.$key).' : '.$langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Label'));
		}

		if ($key == $current_lang) {
			$object->label       = $libelle;
			$object->description = dol_htmlcleanlastbr($desc);
		} else {
			$object->multilangs[$key]["label"]       = $libelle;
			$object->multilangs[$key]["description"] = dol_htmlcleanlastbr($desc);
		}
	}

	if (!$error) {
		$res = $object->setMultiLangs($user);
		if ($res < 0) {
			$error++;
		}
	}

	if ($error) {
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		$action = '';
	}
}


/*
 * View
 */

$form      = new Form($db);
$formadmin = new FormAdmin($db);
$formother = new FormOther($db);

llxHeader("", "", $langs->trans("Translation"));

$title = Categorie::$MAP_TYPE_TITLE_AREA[$type];

$head = categories_prepare_head($object, $type);

// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($object->multilangs)) {
	foreach ($object->multilangs as $key => $value) {
		$cnt_trans++;
	}
}

print dol_get_fiche_head($head, 'translation', $langs->trans($title), -1, 'category');

$backtolist = (GETPOST('backtolist') ? GETPOST('backtolist') : DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.urlencode($type));
$linkback = '<a href="'.dol_sanitizeUrl($backtolist).'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter = 'type = '.((int) $object->type);
$object->ref = $object->label;
$morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way) {
	$morehtmlref .= $way."<br>\n";
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'label', $linkback, ($user->socid ? 0 : 1), 'label', 'label', $morehtmlref, '&type='.$type, 0, '', '', 1);

print '<br>';

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

// Description
print '<tr><td class="titlefield notopnoleft">';
print $langs->trans("Description").'</td><td>';
print dol_htmlentitiesbr($object->description);
print '</td></tr>';

// Color
print '<tr><td class="notopnoleft">';
print $langs->trans("Color").'</td><td>';
print $formother->showColor($object->color);
print '</td></tr>';

print '</table>';
print '</div>';

print dol_get_fiche_end();



/*
 * Action bar
 */

print "\n<div class=\"tabsAction\">\n";

if ($action == '') {
	if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=add&token='.newToken().'&id='.$object->id.'&type='.$type.'">'.$langs->trans('Add').'</a>';
		if ($cnt_trans > 0) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'&id='.$object->id.'&type='.$type.'">'.$langs->trans('Update').'</a>';
		}
	}
}

print "\n</div>\n";



if ($action == 'edit') {
	// WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	if (!empty($object->multilangs)) {
		foreach ($object->multilangs as $key => $value) {
			print "<br><b><u>".$langs->trans('Language_'.$key)." :</u></b><br>";
			print '<table class="border centpercent">';

			// Label
			$libelle = (GETPOST('libelle-'.$key, 'alpha') ? GETPOST('libelle-'.$key, 'alpha') : $object->multilangs[$key]['label']);
			print '<tr><td class="titlefield fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.$libelle.'"></td></tr>';

			// Desc
			$desc = (GETPOST('desc-'.$key) ? GETPOST('desc-'.$key) : $object->multilangs[$key]['description']);
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
			$doleditor = new DolEditor("desc-$key", $desc, '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
			$doleditor->Create();
			print '</td></tr>';

			print '</tr>';
			print '</table>';
		}
	}

	print '<br>';

	print $form->buttonsSaveCancel();

	print '</form>';
} elseif ($action != 'add') {
	if ($cnt_trans) {
		print '<div class="underbanner clearboth"></div>';
	}

	if (!empty($object->multilangs)) {
		foreach ($object->multilangs as $key => $value) {
			$s = picto_from_langcode($key);
			print '<table class="border centpercent">';
			print '<tr class="liste_titre"><td colspan="2">'.($s ? $s.' ' : '')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&langtodelete='.$key.'&type='.$type.'">'.img_delete('', '').'</a></td></tr>';
			print '<tr><td class="titlefield">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td>'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			if (!empty($conf->global->CATEGORY_USE_OTHER_FIELD_IN_TRANSLATION)) {
				print '<tr><td>'.$langs->trans('Other').' ('.$langs->trans("NotUsed").')</td><td>'.$object->multilangs[$key]["other"].'</td></tr>';
			}
			print '</table>';
		}
	}
	if (!$cnt_trans && $action != 'add') {
		print '<div class="opacitymedium">'.$langs->trans('NoTranslation').'</div>';
	}
}


/*
 * Form to add a new translation
 */

if ($action == 'add' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vadd">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print '<table class="border centpercent">';
	print '<tr><td class="titlefield fieldrequired">'.$langs->trans('Translation').'</td><td>';
	print $formadmin->select_language(GETPOST('forcelangprod', 'alpha'), 'forcelangprod', 0, $object->multilangs);
	print '</td></tr>';
	print '<tr><td class="fieldrequired">'.$langs->trans('Label').'</td>';
	print '<td><input name="libelle" class="minwidth200 maxwidth300" value="'.GETPOST('libelle', 'alpha').'"></td></tr>';
	print '<tr><td>'.$langs->trans('Description').'</td><td>';
	$doleditor = new DolEditor('desc', GETPOST('desc', 'restricthtml'), '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '</tr>';
	print '</table>';

	print $form->buttonsSaveCancel();

	print '</form>';

	print '<br>';
}

// End of page
llxFooter();
$db->close();
