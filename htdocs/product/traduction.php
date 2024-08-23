<?php
/* Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012 Destailleur Laurent 	<eldy@users.sourceforge.net>
 * Copyright (C) 2014 	   Henry Florian 		<florian.henry@open-concept.pro>
 * Copyright (C) 2023 	   Benjamin Falière		<benjamin.faliere@altairis.fr>
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
 *	\file       htdocs/product/traduction.php
 *	\ingroup    product
 *	\brief      Page for translation of product descriptions
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'languages'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

if ($id > 0 || !empty($ref)) {
	$object = new Product($db);
	$object->fetch($id, $ref);
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('producttranslationcard', 'globalcard'));

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}

// Permissions
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'creer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'creer')));


/*
 * Actions
 */

$parameters = array('id'=>$id, 'ref'=>$ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	// retour a l'affichage des traduction si annulation
	if ($cancel == $langs->trans("Cancel")) {
		$action = '';
	}

	if ($action == 'delete' && GETPOST('langtodelete', 'alpha') && $usercancreate) {
		$object = new Product($db);
		$object->fetch($id);
		$object->delMultiLangs(GETPOST('langtodelete', 'alpha'), $user);
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		$action = '';
	}

	// Add translation
	if ($action == 'vadd' && $cancel != $langs->trans("Cancel") && $usercancreate) {
		$object = new Product($db);
		$object->fetch($id);
		$current_lang = $langs->getDefaultLang();

		// update de l'objet
		if (GETPOST("forcelangprod") == $current_lang) {
			$object->label = GETPOST("libelle");
			$object->description = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
			$object->other = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));

			$object->update($object->id, $user);
		} else {
			$object->multilangs[GETPOST("forcelangprod")]["label"] = GETPOST("libelle");
			$object->multilangs[GETPOST("forcelangprod")]["description"] = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
			$object->multilangs[GETPOST("forcelangprod")]["other"] = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));
		}

		// save in database
		if (GETPOST("forcelangprod")) {
			$result = $object->setMultiLangs($user);
		} else {
			$object->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language"));
			$result = -1;
		}

		if ($result > 0) {
			$action = '';
		} else {
			$action = 'add';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Edit translation
	if ($action == 'vedit' && $cancel != $langs->trans("Cancel") && $usercancreate) {
		$object = new Product($db);
		$object->fetch($id);
		$current_lang = $langs->getDefaultLang();

		foreach ($object->multilangs as $key => $value) { // enregistrement des nouvelles valeurs dans l'objet
			if ($key == $current_lang) {
				$object->label = GETPOST("libelle-" . $key);
				$object->description = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
				$object->other = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));

				$object->update($object->id, $user);
			} else {
				$object->multilangs[$key]["label"] = GETPOST("libelle-" . $key);
				$object->multilangs[$key]["description"] = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
				$object->multilangs[$key]["other"] = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));
			}
		}

		$result = $object->setMultiLangs($user);
		if ($result > 0) {
			$action = '';
		} else {
			$action = 'edit';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Delete translation
	if ($action == 'vdelete' && $cancel != $langs->trans("Cancel") && $usercancreate) {
		$object = new Product($db);
		$object->fetch($id);
		$langtodelete = GETPOST('langdel', 'alpha');

		$result = $object->delMultiLangs($langtodelete, $user);
		if ($result > 0) {
			$action = '';
		} else {
			$action = 'edit';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

$object = new Product($db);
$result = $object->fetch($id, $ref);


/*
 * View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Translation');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Translation');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-product page-translation');

$form = new Form($db);
$formadmin = new FormAdmin($db);

$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct".$object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');


// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($object->multilangs)) {
	foreach ($object->multilangs as $key => $value) {
		$cnt_trans++;
	}
}


print dol_get_fiche_head($head, 'translation', $titre, 0, $picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';

$shownav = 1;
if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
	$shownav = 0;
}

dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', '', '', '', 0, '', '', 1);

print dol_get_fiche_end();



/*
 * Action bar
 */
print "\n".'<div class="tabsAction">'."\n";

$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
if (empty($reshook)) {
	if ($action == '') {
		if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
			print '<a class="butAction" href="' . DOL_URL_ROOT . '/product/traduction.php?action=add&token='.newToken().'&id=' . $object->id . '">' . $langs->trans("Add") . '</a>';
			if ($cnt_trans > 0) {
				print '<a class="butAction" href="' . DOL_URL_ROOT . '/product/traduction.php?action=edit&token='.newToken().'&id=' . $object->id . '">' . $langs->trans("Modify") . '</a>';
			}
		}
	}
}

print "\n".'</div>'."\n";



if ($action == 'edit') {
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	if (!empty($object->multilangs)) {
		$i = 0;
		foreach ($object->multilangs as $key => $value) {
			$i++;

			$s = picto_from_langcode($key);
			print($i > 1 ? "<br>" : "").($s ? $s.' ' : '').' <div class="inline-block margintop marginbottomonly"><b>'.$langs->trans('Language_'.$key).'</b></div><div class="inline-block floatright"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom marginrightonly"').'</a></div>';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.dol_escape_htmltag($object->multilangs[$key]["label"]).'"></td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
			$doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_3, '90%');
			$doleditor->Create();
			print '</td></tr>';
			if (getDolGlobalString('PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION')) {
				print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td><td>';
				$doleditor = new DolEditor("other-$key", $object->multilangs[$key]["other"], '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_3, '90%');
				$doleditor->Create();
			}
			print '</td></tr>';
			print '</table>';
		}
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	print '<br>';

	print $form->buttonsSaveCancel();

	print '</form>';
} elseif ($action != 'add') {
	if (!empty($object->multilangs)) {
		$i = 0;
		foreach ($object->multilangs as $key => $value) {
			$i++;

			$s = picto_from_langcode($key);
			print($i > 1 ? "<br>" : "").($s ? $s.' ' : '').' <div class="inline-block marginbottomonly"><b>'.$langs->trans('Language_'.$key).'</b></div><div class="inline-block floatright"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom marginrightonly"').'</a></div>';

			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="titlefieldcreate">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			if (getDolGlobalString('PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION')) {
				print '<tr><td>'.$langs->trans("NotePrivate").'</td><td>'.$object->multilangs[$key]["other"].'</td></tr>';
			}
			print '</table>';
			print '</div>';
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
	print '<input type="hidden" name="id" value="'.GETPOSTINT("id").'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';
	print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Language').'</td><td>';
	print $formadmin->select_language(GETPOST('forcelangprod'), 'forcelangprod', 0, $object->multilangs, 1);
	print '</td></tr>';
	print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
	$doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';
	// Other field (not used)
	if (getDolGlobalString('PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION')) {
		print '<tr><td class="tdtop">'.$langs->trans('NotePrivate').'</td><td>';
		$doleditor = new DolEditor('other', '', '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_3, '90%');
		$doleditor->Create();
		print '</td></tr>';
	}
	print '</table>';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';

	print '<br>';
}

// End of page
llxFooter();
$db->close();
