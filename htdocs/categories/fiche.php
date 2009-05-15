<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Patrick Raguin	  	  <patrick.raguin@gmail.com>
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
 */

/**
		\file       htdocs/categories/fiche.php
		\ingroup    category
		\brief      Page creation nouvelle categorie
		\version	$Id$
*/

require "./pre.inc.php";
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

if (!$user->rights->categorie->lire) accessforbidden();

if (isset ($_REQUEST['choix']))
{
	$nbcats = $_REQUEST['choix'];
}
else
{ // par default, une nouvelle categorie sera dans une seule categorie mere
	$nbcats = 1;
}

if ($_REQUEST['origin'])
{
	if ($_GET['type'] == 0) $idProdOrigin = $_REQUEST['origin'];
	if ($_GET['type'] == 1) $idSupplierOrigin = $_REQUEST['origin'];
	if ($_GET['type'] == 2) $idCompanyOrigin = $_REQUEST['origin'];
}

if ($_REQUEST['catorigin'])
{
	if ($_GET['type'] == 0) $idCatOrigin = $_REQUEST['catorigin'];
}

// If socid provided by ajax company selector
if (! empty($_POST['socid_id']))
{
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}


/*
*	Actions
*/

if ($_POST["action"] == 'add' && $user->rights->categorie->creer)
{
	// Action ajout d'une categorie
	if ($_POST["cancel"])
	{
		if ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?id='.$idProdOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idSupplierOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$_GET["type"]);
			exit;
		}
	}

	$categorie = new Categorie($db);

	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	$categorie->socid          = ($_POST["socid"] ? $_POST["socid"] : 'null');
	$categorie->visible        = $_POST["visible"];
	$categorie->type		       = $_POST["type"];

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
		$result = $categorie->create();
		if ($result > 0)
		{
			$_GET["action"] = 'confirmed';
			$_POST["addcat"] = '';
		}
	}
}

if ($_POST["action"] == 'add' && $user->rights->categorie->creer)
{
	// Action confirmation de creation categorie
	if ($_GET["action"] == 'confirmed')
	{
		if ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?id='.$idProdOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		if ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		if ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idSupplierOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}

		header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$result.'&type='.$_POST["type"]);
		exit;
	}
}



llxHeader("","",$langs->trans("Categories"));
$html = new Form($db);

if ($user->rights->categorie->creer)
{
	/*
	 * Fiche en mode creation
	 */
	if ($_GET["action"] == 'create' || $_POST["addcat"] == 'addcat')
	{
		if($categorie->error != "")
		{
			print '<div class="error">';
			print $categorie->error;
			print '</div>';
		}
		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$_GET['type'].'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		//print '<input type="hidden" name="id" value="'.$_GET['id'].'">';			Mis dans origin
		//print '<input type="hidden" name="socid" value="'.$_GET['socid'].'">';	Mis dans origin
		print '<input type="hidden" name="type" value="'.$_GET['type'].'">';
		if ($_REQUEST['origin'])
		{
			print '<input type="hidden" name="origin" value="'.$_REQUEST['origin'].'">';
		}
		if ($_REQUEST['catorigin'])
		{
			print '<input type="hidden" name="catorigin" value="'.$_REQUEST['catorigin'].'">';
		}
		print '<input type="hidden" name="nom" value="'.$nom.'">';

		print_fiche_titre($langs->trans("CreateCat"));

		print '<table width="100%" class="border">';
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
			print dol_htmlentitiesbr_decode($categorie->description);
			print '</textarea>';
		}
		
		print '</td></tr>';
		if ($_GET['type'] == 0 && $conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			if ($_REQUEST['catorigin'])
			{
				print '<tr><td>'.$langs->trans ("AddIn").'</td><td>';
				print $html->select_all_categories($_GET['type'],$_REQUEST['catorigin']);
				print '</td></tr>';
				print '<tr><td>'.$langs->trans ("ContentsVisibleByAll").'</td><td>';
				print $html->selectyesno("visible", 1,1);
				print '</td></tr>';
				print '<input type="hidden" name="socid" value="'.$_GET['socid'].'">';
			}
			else
			{
				print '<tr><td>'.$langs->trans ("AssignedToCustomer").'</td><td>';
				print $html->select_societes($_REQUEST['socid_id'],'socid','s.client = 1 AND s.fournisseur = 0',1);
				print '</td></tr>';
				print '<input type="hidden" name="catMere" value="-1">';
				print '<input type="hidden" name="visible" value="1">';
			}
		}
		else
		{
			print '<tr><td>'.$langs->trans ("AddIn").'</td><td>';
			print $html->select_all_categories($_GET['type']);
			print '</td></tr>';
			print '<tr><td>'.$langs->trans ("ContentsVisibleByAll").'</td><td>';
			print $html->selectyesno("visible", 1,1);
			print '</td></tr>';
		}
		print '<tr><td colspan="2" align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" />';
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel" />';
		print '</td></tr>';
		print '</table>';
		print '</form>';
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
