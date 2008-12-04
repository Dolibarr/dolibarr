<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/categories/index.php
        \ingroup    category
        \brief      Page accueil espace categories
		\version	$Id$
*/

require("./pre.inc.php");

$type=$_GET['type'];

if (!$user->rights->categorie->lire) accessforbidden();



/**
 * Affichage page accueil
 */

$c = new Categorie($db);
$html = new Form($db);

if (! $type)    $title=$langs->trans("ProductsCategoriesArea");
if ($type == 1) $title=$langs->trans("SuppliersCategoriesArea");
if ($type == 2) $title=$langs->trans("CustomersCategoriesArea");


llxHeader("","",$title);

print_fiche_titre($title);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';


/*
 * Zone recherche produit/service
 */
print '<form method="post" action="index.php?type='.$_GET['type'].'">';
print '<input type="hidden" name="type" value="'.$_GET['type'].'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td>';
print '</tr>';
print '<tr '.$bc[0].'><td>';
print $langs->trans("Name").':</td><td><input class="flat" type="text" size="20" name="catname" value="' . $_POST['catname'] . '"/></td><td><input type="submit" class="button" value="'.$langs->trans ("Search").'"></td></tr>';
/*
// faire une rech dans une sous catégorie uniquement
print '<tr '.$bc[0].'><td>';
print $langs->trans("SubCatOf").':</td><td>';

print $html->select_all_categories('','subcatof');
print '</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans ("Search").'"></td></tr>';
*/

print '</table></form>';

print '</td><td valign="top" width="70%">';


/*
 * Catégories trouvées
 */
if($_POST['catname'] || $_REQUEST['id'])
{
	$cats = $c->rechercher($_REQUEST['id'],$_POST['catname'],$_POST['type']);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';

	$var=true;
	foreach ($cats as $cat)
	{
		$var = ! $var;
		print "\t<tr ".$bc[$var].">\n";
		print "\t\t<td><a href='viewcat.php?id=".$cat->id."&amp;type=".$type."'>".$cat->label."</a></td>\n";
		print "\t\t<td>".$cat->description."</td>\n";
		print "\t</tr>\n";
	}
	print "</table>";
}


print '</td></tr></table>';

print '<br>';


// Charge tableau des categories
$cate_arbo = $c->get_full_arbo($_GET['type']);

	
/*
* Catégories en javascript
*/


if ($conf->use_javascript_ajax)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("CategoriesTree").'</td>';
	print '<td align="right">';
	if ($_GET["expand"] != 'all')
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?expand=all&type='.$_GET['type'].'">'.$langs->trans("ExpandAll").'</a>';
		print '</td><td width="18"><img border="0" src="'.DOL_URL_ROOT.'/includes/treemenu/images/folder-expanded.gif">';
	}
	if ($_GET["expand"] && $_GET["expand"] != 'none')
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?expand=none&type='.$_GET['type'].'">'.$langs->trans("UndoExpandAll").'</a>';
		print '</td><td width="18"><img border="0" src="'.DOL_URL_ROOT.'/includes/treemenu/images/folder.gif">';
	}
	print '</td>';
	print '</tr>';

	print '<tr><td colspan="2">';

	if (sizeof($cate_arbo))
	{
		require_once(DOL_DOCUMENT_ROOT.'/includes/treemenu/TreeMenu.php');
		
		$menu  = new HTML_TreeMenu();
		$icon         = 'folder.gif';
		$expandedIcon = 'folder-expanded.gif';
	
		// Création noeud racine
		$node=array();
		$rootnode='-1';
		$node[$rootnode] = new HTML_TreeNode(array(
			'text' => $langs->trans("AllCats"),
			'link' => '',
			'icon' => 'base.gif',
			'expandedIcon' => 'base.gif',
			'expanded' => true)
			//,array('onclick' => "alert('foo'); return false", 'onexpand' => "alert('Expanded')")
		);
	
		// Ajoute id_mere sur tableau cate_arbo
		$i=0;
		foreach ($cate_arbo as $key => $val)
		{
			$i++;
			$nodeparent=ereg_replace('_[0-9]+$','',$cate_arbo[$key]['fullpath']);
			if (! $nodeparent) $nodeparent=$rootnode;
			// Definition du nouvel element a ajouter dans l'arbre
			$newelement=array(
					'text' => $cate_arbo[$key]['label'],
					//'link' => $_SERVER["PHP_SELF"].'?id='.$cate_arbo[$key]['id'],
					'link' => DOL_URL_ROOT.'/categories/viewcat.php?id='.$cate_arbo[$key]['id'].'&amp;type='.$type,
					'icon' => $icon,
					'expandedIcon' => $expandedIcon
			);

			if ($_GET["expand"])
			{
				$patharray=split('_',$cate_arbo[$key]['fullpath']);
				$level=(sizeof($patharray)-1);
				if ($_GET["expand"] == 'all' || $level <= $_GET["expand"]) {
					$newelement['expanded']=true;
				}
				if ($_GET["expand"] == 'none') 
				{
					$newelement['expanded']=false;
				}
			}
			//echo $nodeparent."|";
			//print 'x'.$cate_arbo[$key]['fullpath'].'  expand='.$newelement['expanded'].'<br>';
			$node[$cate_arbo[$key]['fullpath']]=&$node[$nodeparent]->addItem(new HTML_TreeNode($newelement));
			//print 'Resultat: noeud '.$cate_arbo[$key]['fullpath']." créé<br>\n";
		}
		
		$menu->addItem($node[$rootnode]);
		
		// Affiche arbre
		print '<script src="'.DOL_URL_ROOT.'/includes/treemenu/TreeMenu.js" language="JavaScript" type="text/javascript"></script>';
		$treeMenu = new HTML_TreeMenu_DHTML($menu,
			array(
			'images' => DOL_URL_ROOT.'/theme/common/treemenu',
			'defaultClass' => 'treeMenuDefault',
			'noTopLevelImages' => false,
			'jsObjectName' => 'tree_categories',
			'usePersistence' => false
			),
			true);
		$treeMenu->printMenu();
		
		//$listBox  = new HTML_TreeMenu_Listbox($menu, array('linkTarget' => '_self'));
		//$listBox->printMenu();
	
	}
	else
	{
		print $langs->trans("NoneCategory");	
	}

	print '</td></tr>';
	
	print "</table>";
	print '<br>';
}
else
{
	/*
	* Catégories principales en HTML pure
	*/
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("AllCats").'</td><td>'.$langs->trans("Description").'</td></tr>';

	if (sizeof($cate_arbo))
	{
		if (is_array($cate_arbo))
		{
			$var=true;
			foreach($cate_arbo as $key => $value)
			{
				$var = ! $var;
				print "\t<tr ".$bc[$var].">\n";
				print '<td><a href="viewcat.php?id='.$cate_arbo[$key]['id'].'&amp;type='.$type.'">'.$cate_arbo[$key]['fulllabel'].'</a></td>';
				print '<td>'.$c->get_desc($cate_arbo[$key]['id']).'</td>';
				print "\t</tr>\n";
			}
		}
		
	}

	print "</table>";
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
