<?php
/* Copyright (C) 2005       Matthieu Valleton   <mv@seeschloss.org>
 * Copyright (C) 2005       Eric Seigne         <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2016  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin      <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/categories/index.php
 *      \ingroup    category
 *      \brief      Home page of category area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->load("categories");

if (!$user->rights->categorie->lire) accessforbidden();

$id = GETPOST('id', 'int');
$type = (GETPOST('type', 'aZ09') ? GETPOST('type', 'aZ09') : Categorie::TYPE_PRODUCT);
$catname = GETPOST('catname', 'alpha');

$categstatic = new Categorie($db);
if (is_numeric($type)) $type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility


/*
 * View
 */

$form = new Form($db);

$typetext = $type;
if ($type == Categorie::TYPE_ACCOUNT) 			$title = $langs->trans('AccountsCategoriesArea');
elseif ($type == Categorie::TYPE_WAREHOUSE) 	$title = $langs->trans('StocksCategoriesArea');
elseif ($type == Categorie::TYPE_ACTIONCOMM)	$title = $langs->trans('ActionCommCategoriesArea');
elseif ($type == Categorie::TYPE_WEBSITE_PAGE)	$title = $langs->trans('WebsitePagesCategoriesArea');
else 											$title = $langs->trans(ucfirst($type).'sCategoriesArea');

$arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('', $title, '', '', 0, 0, $arrayofjs, $arrayofcss);


$newcardbutton = '';
if (!empty($user->rights->categorie->creer)) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewCategory'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/categories/card.php?action=create&type='.$type.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?type='.$type));
}

print load_fiche_titre($title, $newcardbutton, 'object_category');

print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Zone recherche produit/service
 */
print '<form method="post" action="index.php?type='.$type.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="type" value="'.$type.'">';


print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td>';
print '</tr>';
print '<tr class="oddeven nohover"><td>';
print $langs->trans("Name").':</td><td><input class="flat inputsearch" type="text" name="catname" value="'.$catname.'"/></td><td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print '</table></form>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Categories found
 */
if ($catname || $id > 0)
{
	$cats = $categstatic->rechercher($id, $catname, $typetext);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';

	foreach ($cats as $cat)
	{
		print "\t".'<tr class="oddeven">'."\n";
		print "\t\t<td>";
		$categstatic->id = $cat->id;
		$categstatic->ref = $cat->label;
		$categstatic->label = $cat->label;
		$categstatic->type = $cat->type;
		$categstatic->color = $cat->color;
		print '<span class="noborderoncategories" '.($categstatic->color ? ' style="background: #'.$categstatic->color.';"' : ' style="background: #aaa"').'>';
		print $categstatic->getNomUrl(1, '');
		print '</span>';
		print "</td>\n";
		print "\t\t<td>";
		print dolGetFirstLineOfText($cat->description);
		print "</td>\n";
		print "\t</tr>\n";
	}
	print "</table>";
}
else print '&nbsp;';


print '</div></div></div>';

print '<div class="fichecenter"><br>';


// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($typetext);

// Define fulltree array
$fulltree = $cate_arbo;

// Load possible missing includes
if ($conf->global->CATEGORY_SHOW_COUNTS)
{
	if ($type == Categorie::TYPE_MEMBER)	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	if ($type == Categorie::TYPE_ACCOUNT)	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	if ($type == Categorie::TYPE_PROJECT)	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	if ($type == Categorie::TYPE_USER)		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
}

// Define data (format for treeview)
$data = array();
$data[] = array('rowid'=>0, 'fk_menu'=>-1, 'title'=>"racine", 'mainmenu'=>'', 'leftmenu'=>'', 'fk_mainmenu'=>'', 'fk_leftmenu'=>'');
foreach ($fulltree as $key => $val)
{
	$categstatic->id = $val['id'];
	$categstatic->ref = $val['label'];
	$categstatic->color = $val['color'];
	$categstatic->type = $type;
	$li = $categstatic->getNomUrl(1, '', 60);
	$desc = dol_htmlcleanlastbr($val['description']);

	$counter = '';

	if ($conf->global->CATEGORY_SHOW_COUNTS)
	{
		// we need only a count of the elements, so it is enough to consume only the id's from the database
		$elements = $categstatic->getObjectsInCateg($type, 1);
		$counter = "<td class='left' width='40px;'>".(is_countable($elements) ? count($elements) : '0')."</td>";
	}

	$data[] = array(
	'rowid'=>$val['rowid'],
	'fk_menu'=>$val['fk_parent'],
	'entry'=>'<table class="nobordernopadding centpercent"><tr><td><span class="noborderoncategories" '.($categstatic->color ? ' style="background: #'.$categstatic->color.';"' : ' style="background: #aaa"').'>'.$li.'</span></td>'.$counter.
		'<td class="right" width="20px;"><a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$val['id'].'&type='.$type.'">'.img_view().'</a></td></tr></table>'
	);
}


//print_barre_liste('', 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, 0, '', 0, $newcardbutton, '', 0, 1, 1);

print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td></td><td class="right">';
if (!empty($conf->use_javascript_ajax))
{
	print '<div id="iddivjstreecontrol"><a class="notasortlink" href="#">'.img_picto('', 'folder', 'class="paddingright"').$langs->trans("UndoExpandAll").'</a> | <a class="notasortlink" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').$langs->trans("ExpandAll").'</a></div>';
}
print '</td></tr>';

$nbofentries = (count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr class="pair"><td colspan="3">';
	tree_recur($data, $data[0], 0);
	print '</td></tr>';
}
else
{
	print '<tr class="pair">';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('', 'treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";

print '</div>';

// End of page
llxFooter();
$db->close();
