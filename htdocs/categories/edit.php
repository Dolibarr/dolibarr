<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/categories/edit.php
        \ingroup    category
        \brief      Page d'edition de categorie produit
        \version    $Revision$
*/

require "./pre.inc.php";

if (!$user->rights->categorie->lire)
  accessforbidden();
  
// If socid provided by ajax company selector
if (! empty($_POST['socid_id']))
{
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

// Action mise à jour d'une catégorie
if ($_POST["action"] == 'update' && $user->rights->categorie->creer)
{
	$categorie = new Categorie ($db);
	$result=$categorie->fetch($_REQUEST['id']);
	
	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	$categorie->socid          = $_POST["socid"];
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
			header('Location: '.DOL_URL_ROOT.'/categories/viewcat.php?id='.$categorie->id);
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
 * Affichage fiche
 */

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("ModifCat"));
print "<br>";

if ($mesg)
{
	print '<div class="error">';
	print $mesg;
	print '</div>';
}

$categorie = new Categorie ($db, $_REQUEST['id']);
$html = new Form($db);

print '<table class="notopnoleft" border="0" width="100%">';

print '<tr><td class="notopnoleft" valign="top" width="30%">';

print "\n";
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$categorie->id.'">';

print '<table class="border" width="100%">';
print '<tr><td>';
print $langs->trans("Ref").'</td>';
print '<td><input type="text" size="25" id="nom" name ="nom" value="'.$categorie->label.'" />';
print '</tr>';

print '<tr>';
print '<td width="25%">'.$langs->trans("Description").'</td>';
print '<td>';

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
	print '<tr><td>'.$langs->trans ("AssignedToCustomer").'</td><td>';
	print $html->select_societes($categorie->socid,'socid','s.client = 1 AND s.fournisseur = 0',1);
	print '</td></tr>';
	print '<input type="hidden" name="catMere" value="-1">';
	print '<input type="hidden" name="visible" value="1">';
}
else
{
	print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
	print $html->select_all_categories($categorie->type,$categorie->id_mere);
	print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("ContentsVisibleByAll").'</td><td>';
	print $html->selectyesno("visible",$categorie->visible,1);
	print '</td></tr>';
}
		
print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td></tr>';
print '</table></form>';

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
