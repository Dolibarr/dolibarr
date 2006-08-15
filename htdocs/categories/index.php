<?php
/* Copyright (C) 2005 Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005 Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/categories/index.php
        \ingroup    categorie
        \brief      Page accueil espace categories
*/

require "./pre.inc.php";

if (!$user->rights->categorie->lire) accessforbidden();


/**
 * Affichage page accueil
 */

llxHeader("","",$langs->trans("ProductsCategoriesArea"));
$html = new Form($db);
print_fiche_titre($langs->trans("ProductsCategoriesArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

$c = new Categorie ($db);

/*
 * Zone recherche produit/service
 */
print '<form method="post" action="index.php">';
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

if($_POST['catname'])
{
	$cats = $c->rechercher_par_nom ($_POST['catname']);
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';

	$var=true;
	foreach ($cats as $cat)
	{
		$var = ! $var;
		print "\t<tr ".$bc[$var].">\n";
		print "\t\t<td><a href='viewcat.php?id=".$cat->id."'>".$cat->label."</a></td>\n";
		print "\t\t<td>".$cat->description."</td>\n";
		print "\t</tr>\n";
	}
	print "</table>";
}


print '</td></tr></table>';

print '<br>';


// Charge tableau des categories
$cate_arbo = $c->get_full_arbo();

	
/*
* Catégories en javascript
*/

/*
if ($conf->use_javascript)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("AllCats").'</td></tr>';

	print '<tr><td>';

	require_once(DOL_DOCUMENT_ROOT.'/includes/treemenu/TreeMenu.php');
	
	$menu  = new HTML_TreeMenu();
	$icon         = 'folder.gif';
	$expandedIcon = 'folder-expanded.gif';

	// Création noeud racine
	$node=array();
	$currentnode=-1;
	$node[$currentnode] = new HTML_TreeNode(
		array('text' => $langs->trans("AllCats"), 'link' => '', 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true)
		//,array('onclick' => "alert('foo'); return false", 'onexpand' => "alert('Expanded')")
	);
	$node1 = new HTML_TreeNode(
		array('text' => $langs->trans("AllCats"), 'link' => '', 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true)
		//,array('onclick' => "alert('foo'); return false", 'onexpand' => "alert('Expanded')")
	);

	// Ajoute id_mere sur tableau cate_arbo
	foreach ($cate_arbo as $key => $val)
	{

		print 'x '.$cate_arbo[$key]['id'].' '.$cate_arbo[$key]['level'].' '.$cate_arbo[$key]['id_mere'].'<br>';

	}
	
	$node1->addItem(new HTML_TreeNode(array('text' => "Second level, item y", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$node1_1 = $node1->addItem(new HTML_TreeNode(array('text' => "Second level", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$node1_1_1 = $node1_1->addItem(new HTML_TreeNode(array('text' => "Third level", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$node1_1_1_1 = $node1_1_1->addItem(new HTML_TreeNode(array('text' => "Fourth level", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$node1_1_1_1->addItem(new HTML_TreeNode(array('text' => "Fifth level", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'cssClass' => 'treeMenuBold')));
	$node1_1->addItem(new HTML_TreeNode(array('text' => "Third Level, item 2", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$node1->addItem(new HTML_TreeNode(array('text' => "Second level, item 3", 'link' => $_SERVER["PHP_SELF"], 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
	$menu->addItem($node1);
	
	// Affiche arbre
	print '<script src="'.DOL_URL_ROOT.'/includes/treemenu/TreeMenu.js" language="JavaScript" type="text/javascript"></script>';
	
	$treeMenu = new HTML_TreeMenu_DHTML($menu, array('images' => DOL_URL_ROOT.'/includes/treemenu/images', 'defaultClass' => 'treeMenuDefault', false));
	$treeMenu->printMenu();
	
	//$listBox  = new HTML_TreeMenu_Listbox($menu, array('linkTarget' => '_self'));
	//$listBox->printMenu();

	print '</td></tr>';
	
	print "</table>";
	print '<br>';
}
*/

/*
* Catégories principales en HTML pure
*/
if (1 == 1)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("AllCats").'</td><td>'.$langs->trans("Desc").'</td></tr>';
	
	if (is_array($cate_arbo))
	{
		$var=true;
		foreach($cate_arbo as $key => $value)
		{
			$var = ! $var;
			print "\t<tr ".$bc[$var].">\n";
			print '<td><a href="viewcat.php?id='.$cate_arbo[$key]['id'].'">'.$cate_arbo[$key]['fulllabel'].'</a></td>';
			print '<td>'.$c->get_desc($cate_arbo[$key]['id']).'</td>';
			print "\t</td>\n";
			print "\t</tr>\n";
		}
	}
	
	print "</table>";
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
