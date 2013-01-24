<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
else $title=$langs->trans("CategoriesArea");

llxHeader("","",$title);

print_fiche_titre($title);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';


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

print '</td><td valign="top" width="70%">';


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


print '</td></tr></table>';

print '<br>';


// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($type);

// Define fulltree array
$fulltree=$cate_arbo;

print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td colspan="3">'.$langs->trans("Description").'</td></tr>';


// ----- This section will show a tree from a fulltree array -----
// $section must also be defined
// ---------------------------------------------------------------


// Root title line
print '<tr><td>';
print '<table class="nobordernopadding"><tr class="nobordernopadding">';
print '<td align="left" width="24">';
print img_picto_common('','treemenu/base.gif');
print '</td><td align="left">'.$langs->trans("AllCats");
print '</td>';
print '</tr></table></td>';
print '<td align="right">&nbsp;</td>';
print '<td align="right">&nbsp;</td>';
//print '<td align="right">&nbsp;</td>';
print '</tr>';



// Define fullpathselected ( _x_y_z ) of $section parameter
$fullpathselected='';
if (! empty($section))
{
	foreach($fulltree as $key => $val)
	{
		//print $val['id']."-".$section."<br>";
		if ($val['id'] == $section)
		{
			$fullpathselected=$val['fullpath'];
			break;
		}
	}
}
//print "fullpathselected=".$fullpathselected."<br>";

// Update expandedsectionarray in session
$expandedsectionarray=array();
if (isset($_SESSION['dol_catexpandedsectionarray'.$type])) $expandedsectionarray=explode(',',$_SESSION['dol_catexpandedsectionarray'.$type]);

if (! empty($section) && $_GET['sectionexpand'] == 'true')
{
	// We add all sections that are parent of opened section
	$pathtosection=explode('_',$fullpathselected);
	foreach($pathtosection as $idcursor)
	{
		if ($idcursor && ! in_array($idcursor,$expandedsectionarray))	// Not already in array
		{
			$expandedsectionarray[]=$idcursor;
		}
	}
	$_SESSION['dol_catexpandedsectionarray'.$type]=join(',',$expandedsectionarray);
}
if (! empty($section) && $_GET['sectionexpand'] == 'false')
{
	// We removed all expanded sections that are child of the closed section
	$oldexpandedsectionarray=$expandedsectionarray;
	$expandedsectionarray=array();
	foreach($oldexpandedsectionarray as $sectioncursor)
	{
		// is_in_subtree(fulltree,sectionparent,sectionchild)
		if ($sectioncursor && ! is_in_subtree($fulltree,$section,$sectioncursor)) $expandedsectionarray[]=$sectioncursor;
	}
	$_SESSION['dol_catexpandedsectionarray'.$type]=join(',',$expandedsectionarray);
}
//print $_SESSION['dol_catexpandedsectionarray'.$type].'<br>';

$nbofentries=0;
$oldvallevel=0;
$var=true;
foreach($fulltree as $key => $val)
{
	//$fullpathparent=preg_replace('/_[^_]+$/i','',$val['fullpath']);

	// Define showline
	$showline=0;

	//var_dump($expandedsectionarray);

	// If directory is son of expanded directory, we show line
	if (isset($val['fk_parent']) && in_array($val['fk_parent'],$expandedsectionarray)) $showline=4;
	// If directory is parent of selected directory or is selected directory, we show line
	elseif (preg_match('/'.$val['fullpath'].'_/i',$fullpathselected.'_')) $showline=2;
	// If we are level one we show line
	elseif ($val['level'] < 2) $showline=1;
	//print 'xxx '.$val['level'].' - '.$fullpathselected.' - '.$val['fullpath'].' - '.$val['fk_parent'].' showline='.$showline.'<br>'."\n";

	if ($showline)
	{
        $var=!$var;

	    if (in_array($val['id'],$expandedsectionarray)) $option='indexexpanded';
		else $option='indexnotexpanded';
		//print $option;

        print "<tr ".$bc[$var].">";

		// Show tree graph pictos
		print '<td align="left">';
		print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>';
		$resarray=tree_showpad($fulltree,$key);
		$a=$resarray[0];
		$nbofsubdir=$resarray[1];
		$nboffilesinsubdir=$resarray[2];
		print '</td>';

		// Show picto
		print '<td valign="top">';
		//print $val['fullpath']."(".$showline.")";
		$n='2';
		if (! in_array($val['id'],$expandedsectionarray)) $n='3';
		if (! in_array($val['id'],$expandedsectionarray)) $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/plustop'.$n.'.gif','',1);
		else $ref=img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop'.$n.'.gif','',1);
		if ($option == 'indexexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;type='.$type.'&amp;sectionexpand=false">';
    	if ($option == 'indexnotexpanded') $lien = '<a href="'.$_SERVER["PHP_SELF"].'?section='.$val['id'].'&amp;type='.$type.'&amp;sectionexpand=true">';
    	$newref=str_replace('_',' ',$ref);
    	$lienfin='</a>';
    	print $lien.$newref.$lienfin;
		if (! in_array($val['id'],$expandedsectionarray)) print img_picto('','object_category');
		else print img_picto('','object_category-expanded');
		print '</td>';
		// Show link
		print '<td valign="middle">';
		//if ($section == $val['id']) print ' <u>';
		// We don't want a link ... why ?
		$categstatic->id=$val['id'];
		$categstatic->ref=$val['label'];
		$categstatic->type=$type;
		print ' &nbsp;'.$categstatic->getNomUrl(0,'',60);

		//print ' &nbsp;'.dol_trunc($val['label'],28);
		//if ($section == $val['id']) print '</u>';
		print '</td>';
		print '</tr></table>';
		print "</td>\n";

		// Description
		print '<td>';
		print dol_trunc($val['description'],48);
		print '</td>';

		// Link to category card
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$val['id'].'&type='.$type.'">'.img_view().'</a></td>';

		// Add link
		//print '<td align="right"><a href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&amp;catParent='.$val['id'].'">'.img_edit_add().'</a></td>';
		//print '<td align="right">&nbsp;</td>';

		print "</tr>\n";
	}

	$oldvallevel=$val['level'];
	$nbofentries++;
}


// If nothing to show
if ($nbofentries == 0)
{
	print '<tr>';
	print '<td class="left"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td>'.img_picto('',DOL_URL_ROOT.'/theme/common/treemenu/minustop3.gif','',1).'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '<td colspan="4">&nbsp;</td>';
	print '</tr>';
}

// ----- End of section -----
// --------------------------

print "</table>";


llxFooter();
$db->close();
?>
