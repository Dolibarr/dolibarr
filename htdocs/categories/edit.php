<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
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
 *      \file       htdocs/categories/edit.php
 *      \ingroup    category
 *      \brief      Page d'edition de categorie produit
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("categories");

$id=GETPOST('id','int');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');

$socid=GETPOST('socid','int');
$nom=GETPOST('nom');
$description=GETPOST('description');
$visible=GETPOST('visible');
$parent=GETPOST('parent');

if ($id == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');



/*
 * Actions
 */

// Action mise a jour d'une categorie
if ($action == 'update' && $user->rights->categorie->creer)
{
	$categorie = new Categorie($db);
	$result=$categorie->fetch($id);

	$categorie->label          = $nom;
	$categorie->description    = dol_htmlcleanlastbr($description);
	$categorie->socid          = ($socid ? $socid : 'null');
	$categorie->visible        = $visible;

	if ($parent != "-1")
		$categorie->fk_parent = $parent;
	else
		$categorie->fk_parent = "";


	if (empty($categorie->label))
	{
		$action = 'create';
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
	}
	if (empty($categorie->description))
	{
		$action = 'create';
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Description"));
	}
	if (empty($categorie->error))
	{
		if ($categorie->update($user) > 0)
		{
			header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$categorie->id.'&type='.$type);
			exit;
		}
		else
		{
			$mesg=$categorie->error;
		}
	}
	else
	{
		$mesg=$categorie->error;
	}
}



/*
 * View
 */

llxHeader("","",$langs->trans("Categories"));

print_fiche_titre($langs->trans("ModifCat"));


dol_htmloutput_errors($mesg);


$object = new Categorie($db);
$object->fetch($id);

$form = new Form($db);

print '<table class="notopnoleft" border="0" width="100%">';

print '<tr><td class="notopnoleft" valign="top" width="30%">';

print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

print '<table class="border" width="100%">';

// Ref
print '<tr><td class="fieldrequired">';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="nom" name ="nom" value="'.$object->label.'" />';
print '</tr>';

// Description
print '<tr>';
print '<td width="25%">'.$langs->trans("Description").'</td>';
print '<td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor=new DolEditor('description',$object->description,'',200,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,ROWS_6,50);
$doleditor->Create();
print '</td></tr>';

// Parent category
print '<tr><td>'.$langs->trans("In").'</td><td>';
print $form->select_all_categories($type,$object->fk_parent,'parent',64,$object->id);
print '</td></tr>';

print '</table>';
print '<br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</center>';

print '</form>';

print '</td></tr></table>';


llxFooter();
$db->close();
?>
