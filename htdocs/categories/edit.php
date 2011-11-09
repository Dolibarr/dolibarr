<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");


$id=GETPOST('id');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');

if ($id == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id);



/*
 * Actions
 */

// Action mise a jour d'une categorie
if ($action == 'update' && $user->rights->categorie->creer)
{
	$categorie = new Categorie($db);
	$result=$categorie->fetch($id);

	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	$categorie->socid          = ($_POST["socid"] ? $_POST["socid"] : 'null');
	$categorie->visible        = $_POST["visible"];

	if($_POST['catMere'] != "-1")
		$categorie->id_mere = $_POST['catMere'];
	else
		$categorie->id_mere = "";


	if (! $categorie->label)
	{
		$_GET["action"] = 'create';
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
	}
	if (! $categorie->description)
	{
		$_GET["action"] = 'create';
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Description"));
	}
	if (! $categorie->error)
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


$categorie = new Categorie($db, $id);
$form = new Form($db);

print '<table class="notopnoleft" border="0" width="100%">';

print '<tr><td class="notopnoleft" valign="top" width="30%">';

print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$categorie->id.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

print '<table class="border" width="100%">';

// Ref
print '<tr><td class="fieldrequired">';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="nom" name ="nom" value="'.$categorie->label.'" />';
print '</tr>';

// Description
print '<tr>';
print '<td width="25%">'.$langs->trans("Description").'</td>';
print '<td>';
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
$doleditor=new DolEditor('description',$categorie->description,'',200,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,ROWS_6,50);
$doleditor->Create();
print '</td></tr>';

// Parent category
print '<tr><td>'.$langs->trans("In").'</td><td>';
print $form->select_all_categories($type,$categorie->id_mere,'catMere',64,$categorie->id);
print '</td></tr>';

print '</table>';
print '<br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</center>';

print '</form>';

print '</td></tr></table>';

$db->close();

llxFooter();
?>
