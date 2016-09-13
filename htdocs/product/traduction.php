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
	$object = new Product($db);
	$object->fetch($id);
	$object->delMultiLangs(GETPOST('langtodelete','alpha'), $user);
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
	if ( $_POST["forcelangprod"] == $current_lang )
	{
		$object->label			= $_POST["libelle"];
		$object->description	= dol_htmlcleanlastbr($_POST["desc"]);
		$object->note			= dol_htmlcleanlastbr($_POST["note"]);
	}
	else
	{
		$object->multilangs[$_POST["forcelangprod"]]["label"]			= $_POST["libelle"];
		$object->multilangs[$_POST["forcelangprod"]]["description"]	= dol_htmlcleanlastbr($_POST["desc"]);
		$object->multilangs[$_POST["forcelangprod"]]["note"]			= dol_htmlcleanlastbr($_POST["note"]);
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

// Edit translation
if ($action == 'vedit' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$object = new Product($db);
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ( $object->multilangs as $key => $value ) // enregistrement des nouvelles valeurs dans l'objet
	{
		if ( $key == $current_lang )
		{
			$object->label			= $_POST["libelle-".$key];
			$object->description	= dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->note			= dol_htmlcleanlastbr($_POST["note-".$key]);
		}
		else
		{
			$object->multilangs[$key]["label"]			= $_POST["libelle-".$key];
			$object->multilangs[$key]["description"]	= dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->multilangs[$key]["note"]			= dol_htmlcleanlastbr($_POST["note-".$key]);
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

// Delete translation
if ($action == 'vdelete' &&
$cancel != $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$object = new Product($db);
	$object->fetch($id);
	$langtodelete=GETPOST('langdel','alpha');


	if ( $object->delMultiLangs($langtodelete, $user) > 0 )
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
$result = $object->fetch($id,$ref);


/*
 * View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label,16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('Translation');
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('Translation');
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formadmin=new FormAdmin($db);

$head=product_prepare_head($object);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==Product::TYPE_SERVICE?'service':'product');


if ($action == 'edit')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

    dol_fiche_head($head, 'translation', $titre, 0, $picto);
    
    $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';
    
    dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');
	
	if (! empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$s=picto_from_langcode($key);
			print "<br>".($s?$s.' ':'')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', '')."</a><br>";
		    
			print '<table class="border" width="100%">';
			print '<tr><td valign="top" width="15%" class="fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.$object->multilangs[$key]["label"].'"></td></tr>';
			print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td>';

			$doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
			$doleditor->Create();

			print '</td></tr>';
			print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td>';

			$doleditor = new DolEditor("note-$key", $object->multilangs[$key]["note"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 3, 80);
			$doleditor->Create();

			print '</td></tr>';
			print '</table>';
		}
	}

    dol_fiche_end();
    
	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

}
else
{
    dol_fiche_head($head, 'translation', $titre, 0, $picto);
    
    $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';
    
    dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref');
    
    $cnt_trans = 0;
	if (! empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$cnt_trans++;
			$s=picto_from_langcode($key);
			print "<br>".($s?$s.' ':'')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', '')."</a><br>";
			print '<table class="border" width="100%">';
			print '<tr><td width="15%">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td width="15%">'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			print '<tr><td width="15%">'.$langs->trans('Note').'</td><td>'.$object->multilangs[$key]["note"].'</td></tr>';
			print '</table>';
		}
	}
	if (! $cnt_trans) print '<br>'. $langs->trans('NoTranslation');

	dol_fiche_end();
}



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($action == '')
if ($user->rights->produit->creer || $user->rights->service->creer)
{
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=add&id='.$object->id.'">'.$langs->trans("Add").'</a>';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=edit&id='.$object->id.'">'.$langs->trans("Update").'</a>';
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
    print $formadmin->select_language('','forcelangprod',0,$object->multilangs,1);
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
