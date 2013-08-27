<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/categories/index.php
 *      \ingroup    category
 *      \brief      Home page of category area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';

$langs->load("categories");

if (! $user->rights->categorie->lire) accessforbidden();

$id=GETPOST('id','int');
$type=(GETPOST('type') ? GETPOST('type') : 0);
$catname=GETPOST('catname','alpha');
$section=(GETPOST('section')?GETPOST('section'):0);


/*
 * View
 */

$categstatic = new Categorie($db);
$form = new Form($db);

if ($type == 0) $title=$langs->trans("ProductsCategoriesArea");
elseif ($type == 1) $title=$langs->trans("SuppliersCategoriesArea");
elseif ($type == 2) $title=$langs->trans("CustomersCategoriesArea");
elseif ($type == 3) $title=$langs->trans("MembersCategoriesArea");
elseif ($type == 4) $title=$langs->trans("ContactsCategoriesArea");
else $title=$langs->trans("CategoriesArea");

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('',$title,'','',0,0,$arrayofjs,$arrayofcss);


print_fiche_titre($title);

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Zone recherche produit/service
 */
print '<form method="post" action="index.php?type='.$type.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td>';
print '</tr>';
print '<tr '.$bc[0].'><td>';
print $langs->trans("Name").':</td><td><input class="flat" type="text" size="20" name="catname" value="' . $catname . '"/></td><td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
/*
// faire une rech dans une sous categorie uniquement
print '<tr '.$bc[0].'><td>';
print $langs->trans("SubCatOf").':</td><td>';

print $form->select_all_categories('','subcatof');
print '</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans ("Search").'"></td></tr>';
*/

print '</table></form>';


//print '</td><td valign="top" width="70%">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Categories found
 */
if ($catname || $id > 0)
{
	$cats = $categstatic->rechercher($id,$catname,$type);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';

	$var=true;
	foreach ($cats as $cat)
	{
		$var = ! $var;
		print "\t<tr ".$bc[$var].">\n";
		print "\t\t<td>";
		$categstatic->id=$cat->id;
		$categstatic->ref=$cat->label;
		$categstatic->label=$cat->label;
		$categstatic->type=$cat->type;
		print $categstatic->getNomUrl(1,'');
		print "</td>\n";
		print "\t\t<td>".$cat->description."</td>\n";
		print "\t</tr>\n";
	}
	print "</table>";
}
else print '&nbsp;';


//print '</td></tr></table>';
print '</div></div></div>';

print '<div class="fichecenter"><br>';


// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($type);

// Define fulltree array
$fulltree=$cate_arbo;

// Define data (format for treeview)
$data=array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($fulltree as $key => $val)
{
	$categstatic->id=$val['id'];
	$categstatic->ref=$val['label'];
	$categstatic->type=$type;
	$li=$categstatic->getNomUrl(1,'',60);

	$data[] = array(
	'rowid'=>$val['rowid'],
	'fk_menu'=>$val['fk_parent'],
	'entry'=>'<table class="nobordernopadding centpercent"><tr><td>'.$li.
	'</td><td width="50%">'.
	' '.$val['description'].'</td>'.
	'<td align="right" width="20px;"><a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$val['id'].'&type='.$type.'">'.img_view().'</a></td>'.
	'</tr></table>'
	);
}


print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td>'.$langs->trans("Description").'</td><td align="right">';
if (! empty($conf->use_javascript_ajax))
{
	print '<div id="iddivjstreecontrol"><a href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a> | <a href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a></div>';
}
print '</td></tr>';

$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr><td colspan="3">';
	tree_recur($data,$data[0],0);
	print '</td></tr>';
}
else
{
	print '<tr>';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";

print '</div>';

llxFooter();

$db->close();
?>
