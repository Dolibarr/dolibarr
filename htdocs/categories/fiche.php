<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/categories/fiche.php
		\ingroup    category
		\brief      Page creation nouvelle categorie
*/

require "./pre.inc.php";
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

if (!$user->rights->categorie->lire) accessforbidden();

if (isset ($_REQUEST['choix']))
{
	$nbcats = $_REQUEST['choix'];
}
else
{ // par défault, une nouvelle catégorie sera dans une seule catégorie mère
	$nbcats = 1;
}

if ($_REQUEST['origin'])
{
	if($_GET['type'] == 0)$idProdOrigin = $_REQUEST['origin'];
	if($_GET['type'] == 1)$idSupplierOrigin = $_REQUEST['origin'];
	if($_GET['type'] == 2)$idCompanyOrigin = $_REQUEST['origin'];
	
}




llxHeader("","",$langs->trans("Categories"));
$html = new Form($db);

// Action ajout d'une catégorie
if ($_POST["action"] == 'add' && $user->rights->categorie->creer)
{
	$categorie = new Categorie($db);

	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	$categorie->visible        = $_POST["visible"];
	$categorie->type		   = $_POST["type"];
	if($_POST['catMere'] != "-1")
	$categorie->id_mere = $_POST['catMere'];

	if (! $categorie->label)
	{
		$categorie->error = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
		$_GET["action"] = 'create';
	}
	else if (! $categorie->description)
	{
		$categorie->error = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Description"));
		$_GET["action"] = 'create';
	}
	
	if ($categorie->error =="")
	{
		if ($categorie->create() > 0)
		{
			$_GET["action"] = 'confirmed';
			$_POST["addcat"] = '';

		}
	}
	/*
	* Action confirmation de création de la catégorie
	*/

	if ($_GET["action"] == 'confirmed')
	{
		print_titre($langs->trans("NewCategory"));
		print '<br>';
		
		print '<table class="notopnoleft" width="100%">';
		print '<tr><td valign="top" class="notopnoleft" width="30%">';
		
		print '<div class="ok">'.$langs->trans("CategorySuccessfullyCreated",$categorie->label).'</div>';
		print '<br>';
		
		if ($idProdOrigin)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/categorie.php?id='.$idProdOrigin.'">'.$langs->trans("ReturnInProduct").'</a>';
		}
		if ($idSupplierOrigin || $idCompanyOrigin)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'">'.$langs->trans("ReturnInCompany").'</a>';
		}

		print '</td></tr></table>';
	}
}


if ($user->rights->categorie->creer)
{
	/*
	 * Fiche en mode création
	 */
	if ($_GET["action"] == 'create' || $_POST["addcat"] == 'addcat')
	{
		if($categorie->error != "")
		{
			print '<div class="error">';
			print $categorie->error;
			print '</div>';
		}
		print '<form action="fiche.php?type='.$_GET['type'].'" method="post">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="type" value='.$_GET['type'].'>';
		if ($_REQUEST['origin'])
		{
			print '<input type="hidden" name="origin" value='.$_REQUEST['origin'].'>';
		}
		print '<input type="hidden" name="nom" value="'.$nom.'">';
		print '<input type="hidden" name="description" value="'.$description.'">';
		print_fiche_titre($langs->trans("CreateCat"));

		print '<table class="border" width="100%" class="notopnoleftnoright">';
		print '<tr>';
		print '<td width="25%">'.$langs->trans("Ref").'</td><td><input name="nom" size="25" value="'.$categorie->label.'">';
		print'</td></tr>';
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
	    require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('description',$categorie->description,200,'dolibarr_notes');
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="description" rows="'.ROWS_6.'" cols="50">';
			print $categorie->description;
			print '</textarea>';
		}
		
		print '</td></tr>';
		print '<tr><td>'.$langs->trans ("AddIn").'</td><td>';
		print $html->select_all_categories($_GET['type']);
		print '</td></tr>';
		print '<tr><td>'.$langs->trans ("ContentsVisibleByAll").'</td><td>';
		print $html->selectyesno("visible", 1,1);
		print '</td></tr>';
		print '<tr><td colspan="2" align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" id="creation"/>';
		print '</td></tr></form>';

	}
}
print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
