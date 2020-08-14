<?php
/* Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012 Destailleur Laurent <eldy@users.sourceforge.net>
 * Copyright (C) 2014 	   Henry Florian <florian.henry@open-concept.pro>
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
 *	\brief      Page de traduction des produits
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'languages'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * Actions
 */

// retour a l'affichage des traduction si annulation
if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
}

if ($action == 'delete' && GETPOST('langtodelete', 'alpha'))
{
	$object = new Product($db);
	$object->fetch($id);
	$object->delMultiLangs(GETPOST('langtodelete', 'alpha'), $user);
}

// Add translation
if ($action == 'vadd' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$object = new Product($db);
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	// update de l'objet
	if ($_POST["forcelangprod"] == $current_lang)
	{
		$object->label			= $_POST["libelle"];
		$object->description = dol_htmlcleanlastbr($_POST["desc"]);
		$object->other			= dol_htmlcleanlastbr($_POST["other"]);
		$object->update($object->id, $user);
	}
	else
	{
		$object->multilangs[$_POST["forcelangprod"]]["label"]		= $_POST["libelle"];
		$object->multilangs[$_POST["forcelangprod"]]["description"] = dol_htmlcleanlastbr($_POST["desc"]);
		$object->multilangs[$_POST["forcelangprod"]]["other"]		= dol_htmlcleanlastbr($_POST["other"]);
	}

	// sauvegarde en base
	if ($object->setMultiLangs($user) > 0)
	{
		$action = '';
	}
	else
	{
		$action = 'add';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Edit translation
if ($action == 'vedit' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$object = new Product($db);
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ($object->multilangs as $key => $value) // enregistrement des nouvelles valeurs dans l'objet
	{
		if ($key == $current_lang)
		{
			$object->label			= $_POST["libelle-".$key];
			$object->description = dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->other			= dol_htmlcleanlastbr($_POST["other-".$key]);
		}
		else
		{
			$object->multilangs[$key]["label"]			= $_POST["libelle-".$key];
			$object->multilangs[$key]["description"] = dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->multilangs[$key]["other"]			= dol_htmlcleanlastbr($_POST["other-".$key]);
		}
	}

	if ($object->setMultiLangs($user) > 0)
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Delete translation
if ($action == 'vdelete' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$object = new Product($db);
	$object->fetch($id);
	$langtodelete = GETPOST('langdel', 'alpha');


	if ($object->delMultiLangs($langtodelete, $user) > 0)
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
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
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Translation');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Translation');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formadmin = new FormAdmin($db);

$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct".$object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');


// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($object->multilangs))
{
    foreach ($object->multilangs as $key => $value)
    {
        $cnt_trans++;
    }
}


dol_fiche_head($head, 'translation', $titre, 0, $picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$shownav = 1;
if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', '', '', '', 0, '', '', 1);

dol_fiche_end();



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($action == '')
{
    if ($user->rights->produit->creer || $user->rights->service->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=add&id='.$object->id.'">'.$langs->trans("Add").'</a>';
        if ($cnt_trans > 0) print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=edit&id='.$object->id.'">'.$langs->trans("Update").'</a>';
    }
}

print "\n</div>\n";



if ($action == 'edit')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	if (!empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$s = picto_from_langcode($key);
			print "<br>".($s ? $s.' ' : '')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom"')."</a><br>";

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.dol_escape_htmltag($object->multilangs[$key]["label"]).'"></td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
			$doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
			$doleditor->Create();
			print '</td></tr>';
			if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION))
			{
                print '<tr><td class="tdtop">'.$langs->trans('Other').' ('.$langs->trans("NotUsed").')</td><td>';
                $doleditor = new DolEditor("other-$key", $object->multilangs[$key]["other"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
                $doleditor->Create();
			}
			print '</td></tr>';
			print '</table>';
		}
	}

	print '<br>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}
elseif ($action != 'add')
{
	if (!empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$s = picto_from_langcode($key);
			print ($s ? $s.' ' : '')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom"').'</a>';

			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="titlefieldcreate">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION))
			{
                print '<tr><td>'.$langs->trans('Other').' ('.$langs->trans("NotUsed").')</td><td>'.$object->multilangs[$key]["other"].'</td></tr>';
			}
			print '</table>';
			print '</div>';
		}
	}
	if (!$cnt_trans && $action != 'add') print '<div class="opacitymedium">'.$langs->trans('NoTranslation').'</div>';
}



/*
 * Form to add a new translation
 */

if ($action == 'add' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vadd">';
	print '<input type="hidden" name="id" value="'.GETPOST("id", 'int').'">';

	dol_fiche_head();

	print '<table class="border centpercent">';
	print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Language').'</td><td>';
    print $formadmin->select_language('', 'forcelangprod', 0, $object->multilangs, 1);
	print '</td></tr>';
	print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
	$doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';
    // Other field (not used)
    if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION))
    {
        print '<tr><td class="tdtop">'.$langs->trans('Other').' ('.$langs->trans("NotUsed").'</td><td>';
    	$doleditor = new DolEditor('other', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
    	$doleditor->Create();
    	print '</td></tr>';
    }
	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

	print '<br>';
}

// End of page
llxFooter();
$db->close();
