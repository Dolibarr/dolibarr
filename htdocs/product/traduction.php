<?php
/* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
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

$langs->load("products");
$langs->load("languages");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);


/*
 * Actions
 */

// retour a l'affichage des traduction si annulation
if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
}

if ($action == 'delete' && GETPOST('langtodelete','alpha'))
{
	$product = new Product($db);
	$product->fetch($id);
	$product->delMultiLangs(GETPOST('langtodelete','alpha'));
}

// Add translation
if ($action == 'vadd' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$product = new Product($db);
	$product->fetch($id);
	$current_lang = $langs->getDefaultLang();

	// update de l'objet
	if ( $_POST["forcelangprod"] == $current_lang )
	{
		$product->label			= $_POST["libelle"];
		$product->description	= dol_htmlcleanlastbr($_POST["desc"]);
		$product->note			= dol_htmlcleanlastbr($_POST["note"]);
	}
	else
	{
		$product->multilangs[$_POST["forcelangprod"]]["label"]			= $_POST["libelle"];
		$product->multilangs[$_POST["forcelangprod"]]["description"]	= dol_htmlcleanlastbr($_POST["desc"]);
		$product->multilangs[$_POST["forcelangprod"]]["note"]			= dol_htmlcleanlastbr($_POST["note"]);
	}

	// sauvegarde en base
	if ( $product->setMultiLangs() > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'add';
		setEventMessage($product->error,'errors');
	}
}

// Edit translation
if ($action == 'vedit' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$product = new Product($db);
	$product->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ( $product->multilangs as $key => $value ) // enregistrement des nouvelles valeurs dans l'objet
	{
		if ( $key == $current_lang )
		{
			$product->label			= $_POST["libelle-".$key];
			$product->description	= dol_htmlcleanlastbr($_POST["desc-".$key]);
			$product->note			= dol_htmlcleanlastbr($_POST["note-".$key]);
		}
		else
		{
			$product->multilangs[$key]["label"]			= $_POST["libelle-".$key];
			$product->multilangs[$key]["description"]	= dol_htmlcleanlastbr($_POST["desc-".$key]);
			$product->multilangs[$key]["note"]			= dol_htmlcleanlastbr($_POST["note-".$key]);
		}
	}

	if ( $product->setMultiLangs() > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessage($product->error,'errors');
	}
}

// Delete translation
if ($action == 'vdelete' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$product = new Product($db);
	$product->fetch($id);
	$langtodelete=GETPOST('langdel','alpha');

	
	if ( $product->delMultiLangs($langtodelete) > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessage($product->error,'errors');
	}
}

$product = new Product($db);
$result = $product->fetch($id,$ref);


/*
 * View
 */

llxHeader("","",$langs->trans("Translation"));

$form = new Form($db);
$formadmin=new FormAdmin($db);

$head=product_prepare_head($product, $user);
$titre=$langs->trans("CardProduct".$product->type);
$picto=($product->type==Product::TYPE_SERVICE?'service':'product');
dol_fiche_head($head, 'translation', $titre, 0, $picto);

print '<table class="border" width="100%">';

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
print $form->showrefnav($product,'ref','',1,'ref');
print '</td>';
print '</tr>';
print '</table>';

if ($action == 'edit')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$product->id.'">';

	if (! empty($product->multilangs))
	{
		foreach ($product->multilangs as $key => $value)
		{
			print "<br><b><u>".$langs->trans('Language_'.$key)." :</u></b><br>";
			print '<table class="border" width="100%">';
			print '<tr><td valign="top" width="15%" class="fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.$product->multilangs[$key]["label"].'"></td></tr>';
			print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td>';

			$doleditor = new DolEditor("desc-$key", $product->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
			$doleditor->Create();

			print '</td></tr>';
			print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td>';

			$doleditor = new DolEditor("note-$key", $product->multilangs[$key]["note"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
			$doleditor->Create();

			print '</td></tr>';
			print '<tr height="30px"><td colspan="2" align="right"><a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=vdelete&id='.$product->id.'&langdel='.$key.'">'.$langs->trans("Delete").'</a></td></tr>';
			print '</table>';
		}
	}

	print '<br><div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

}
else
{
	$cnt_trans = 0;
	if (! empty($product->multilangs))
	{
		foreach ($product->multilangs as $key => $value)
		{
			$cnt_trans++;
			$s=picto_from_langcode($key);
			print "<br>".($s?$s.' ':'')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', '')."</a><br>";
			print '<table class="border" width="100%">';
			print '<tr><td width="15%">'.$langs->trans('Label').'</td><td>'.$product->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td width="15%">'.$langs->trans('Description').'</td><td>'.$product->multilangs[$key]["description"].'</td></tr>';
			print '<tr><td width="15%">'.$langs->trans('Note').'</td><td>'.$product->multilangs[$key]["note"].'</td></tr>';
			print '</table>';
		}
	}
	if (! $cnt_trans) print '<br>'. $langs->trans('NoTranslation');
}

print "</div>\n";


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($action == '')
if ($user->rights->produit->creer || $user->rights->service->creer)
{
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=add&id='.$product->id.'">'.$langs->trans("Add").'</a>';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=edit&id='.$product->id.'">'.$langs->trans("Update").'</a>';
}

print "\n</div>\n";


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
	print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

	print '<table class="border" width="100%">';
	print '<tr><td valign="top" width="15%" class="fieldrequired">'.$langs->trans('Language').'</td><td>';
    print $formadmin->select_language('','forcelangprod',0,$product->multilangs,1);
	print '</td></tr>';
	print '<tr><td valign="top" width="15%" class="fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td>';

	$doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
	$doleditor->Create();

	print '</td></tr>';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td>';

	$doleditor = new DolEditor('note', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
	$doleditor->Create();

	print '</td></tr>';
	print '</tr>';
	print '</table>';

	print '<br><div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

	print '<br>';
}

llxFooter();
$db->close();
