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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/product/traduction.php
 *	\ingroup    product
 *	\brief      Page de traduction des produits
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('categories', 'languages'));

$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$type   = GETPOST('type', 'aZ09');

if (is_numeric($type)) $type=Categorie::$MAP_ID_TO_CODE[$type];	// For backward compatibility

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');

if ($id == "")
{
	dol_print_error('', 'Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);


/*
 * Actions
 */

// retour a l'affichage des traduction si annulation
if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
}


// Validation de l'ajout
if ($action == 'vadd' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->categorie->creer ))
{
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	// update de l'objet
	if ( $_POST["forcelangprod"] == $current_lang )
	{
		$object->label			= $_POST["libelle"];
		$object->description	= dol_htmlcleanlastbr($_POST["desc"]);
	}
	else
	{
		$object->multilangs[$_POST["forcelangprod"]]["label"]			= $_POST["libelle"];
		$object->multilangs[$_POST["forcelangprod"]]["description"]   	= dol_htmlcleanlastbr($_POST["desc"]);
	}

	// sauvegarde en base
	if ( $object->setMultiLangs($user) > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'add';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Validation de l'edition
if ($action == 'vedit' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->categorie->creer))
{
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ($object->multilangs as $key => $value) // enregistrement des nouvelles valeurs dans l'objet
	{
		if ( $key == $current_lang )
		{
			$object->label			= $_POST["libelle-".$key];
			$object->description	= dol_htmlcleanlastbr($_POST["desc-".$key]);
		}
		else
		{
			$object->multilangs[$key]["label"]			= $_POST["libelle-".$key];
			$object->multilangs[$key]["description"]	= dol_htmlcleanlastbr($_POST["desc-".$key]);
		}
	}

	if ( $object->setMultiLangs($user) > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$result = $object->fetch($id, $ref);


/*
 * View
 */

llxHeader("", "", $langs->trans("Translation"));

$form = new Form($db);
$formadmin=new FormAdmin($db);
$formother = new FormOther($db);

if ($type == Categorie::TYPE_PRODUCT)       $title=$langs->trans("ProductsCategoryShort");
elseif ($type == Categorie::TYPE_SUPPLIER)  $title=$langs->trans("SuppliersCategoryShort");
elseif ($type == Categorie::TYPE_CUSTOMER)  $title=$langs->trans("CustomersCategoryShort");
elseif ($type == Categorie::TYPE_MEMBER)    $title=$langs->trans("MembersCategoryShort");
elseif ($type == Categorie::TYPE_CONTACT)   $title=$langs->trans("ContactCategoriesShort");
elseif ($type == Categorie::TYPE_ACCOUNT)   $title=$langs->trans("AccountsCategoriesShort");
elseif ($type == Categorie::TYPE_PROJECT)   $title=$langs->trans("ProjectsCategoriesShort");
elseif ($type == Categorie::TYPE_USER)      $title=$langs->trans("UsersCategoriesShort");
else                                        $title=$langs->trans("Category");

$head = categories_prepare_head($object, $type);

// Calculate $cnt_trans
$cnt_trans = 0;
if (! empty($object->multilangs))
{
    foreach ($object->multilangs as $key => $value)
    {
        $cnt_trans++;
    }
}

dol_fiche_head($head, 'translation', $title, -1, 'category');

$linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';

$object->ref = $object->label;
$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way)
{
    $morehtmlref.=$way."<br>\n";
}
$morehtmlref.='</div>';

dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

print '<br>';

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border" width="100%">';

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
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=add&id='.$object->id.'">'.$langs->trans("Add").'</a>';
        if ($cnt_trans > 0) print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$object->id.'">'.$langs->trans("Update").'</a>';
    }
}

print "\n</div>\n";



if ($action == 'edit')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	if (! empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
		    print "<br><b><u>".$langs->trans('Language_'.$key)." :</u></b><br>";
			print '<table class="border" width="100%">';
			print '<tr><td class="titlefield fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.$object->multilangs[$key]["label"].'"></td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
			$doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
			$doleditor->Create();
			print '</td></tr>';

			print '</tr>';
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
    if ($cnt_trans) print '<div class="underbanner clearboth"></div>';

    if (! empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
		    $s=picto_from_langcode($key);
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.($s?$s.' ':'')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', '').'</a></td></tr>';
			print '<tr><td class="titlefield">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td>'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			if (! empty($conf->global->CATEGORY_USE_OTHER_FIELD_IN_TRANSLATION))
			{
                print '<tr><td>'.$langs->trans('Other').' ('.$langs->trans("NotUsed").')</td><td>'.$object->multilangs[$key]["other"].'</td></tr>';
			}
			print '</table>';
		}
	}
	if (! $cnt_trans && $action != 'add') print '<div class="opacitymedium">'. $langs->trans('NoTranslation').'</div>';
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
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="vadd">';
	print '<input type="hidden" name="id" value="'.$id.'">';

	print '<table class="border" width="100%">';
	print '<tr><td class="titlefield fieldrequired">'.$langs->trans('Translation').'</td><td>';
    print $formadmin->select_language('', 'forcelangprod', 0, $object->multilangs);
	print '</td></tr>';
	print '<tr><td class="fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td>'.$langs->trans('Description').'</td><td>';
	$doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '</tr>';
	print '</table>';

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
