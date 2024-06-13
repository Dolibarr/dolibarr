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
 *      \file       htdocs/takepos/admin/orderprinters.php
 *      \ingroup    takepos
 *      \brief      Home page of category area
 */

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->loadLangs(array("main", "categories", "takepos", "printing"));

$id = GETPOSTINT('id');
$type = (GETPOST('type', 'aZ09') ? GETPOST('type', 'aZ09') : Categorie::TYPE_PRODUCT);
$catname = GETPOST('catname', 'alpha');
$action = GETPOST('action', 'aZ09');
$printer1 = GETPOST('printer1', 'alpha');
$printer2 = GETPOST('printer2', 'alpha');
$printer3 = GETPOST('printer3', 'alpha');

if (is_numeric($type)) {
	$type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility
}

if (!$user->hasRight('categorie', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == "SavePrinter1") {
	$printedcategories = ";";
	if (is_array($printer1)) {
		foreach ($printer1 as $cat) {
			$printedcategories = $printedcategories.$cat.";";
		}
	}
	dolibarr_set_const($db, "TAKEPOS_PRINTED_CATEGORIES_1", $printedcategories, 'chaine', 0, '', $conf->entity);
}

if ($action == "SavePrinter2") {
	$printedcategories = ";";
	if (is_array($printer2)) {
		foreach ($printer2 as $cat) {
			$printedcategories = $printedcategories.$cat.";";
		}
	}
	dolibarr_set_const($db, "TAKEPOS_PRINTED_CATEGORIES_2", $printedcategories, 'chaine', 0, '', $conf->entity);
}

if ($action == "SavePrinter3") {
	$printedcategories = ";";
	if (is_array($printer3)) {
		foreach ($printer3 as $cat) {
			$printedcategories = $printedcategories.$cat.";";
		}
	}
	dolibarr_set_const($db, "TAKEPOS_PRINTED_CATEGORIES_3", $printedcategories, 'chaine', 0, '', $conf->entity);
}


/*
 * View
 */

$categstatic = new Categorie($db);
$form = new Form($db);

if ($type == Categorie::TYPE_PRODUCT) {
	$title = $langs->trans("ProductsCategoriesArea");
	$typetext = 'product';
} elseif ($type == Categorie::TYPE_SUPPLIER) {
	$title = $langs->trans("SuppliersCategoriesArea");
	$typetext = 'supplier';
} elseif ($type == Categorie::TYPE_CUSTOMER) {
	$title = $langs->trans("CustomersCategoriesArea");
	$typetext = 'customer';
} elseif ($type == Categorie::TYPE_MEMBER) {
	$title = $langs->trans("MembersCategoriesArea");
	$typetext = 'member';
} elseif ($type == Categorie::TYPE_CONTACT) {
	$title = $langs->trans("ContactsCategoriesArea");
	$typetext = 'contact';
} elseif ($type == Categorie::TYPE_ACCOUNT) {
	$title = $langs->trans("AccountsCategoriesArea");
	$typetext = 'bank_account';
} elseif ($type == Categorie::TYPE_PROJECT) {
	$title = $langs->trans("ProjectsCategoriesArea");
	$typetext = 'project';
} elseif ($type == Categorie::TYPE_USER) {
	$title = $langs->trans("UsersCategoriesArea");
	$typetext = 'user';
} else {
	$title = $langs->trans("CategoriesArea");
	$typetext = 'unknown';
}

$arrayofjs = array(
	'/includes/jquery/plugins/jquerytreeview/jquery.treeview.js',
	'/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js',
);
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('', $title, '', '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-takepos page-admin_orderprinters');


print load_fiche_titre($langs->trans("OrderPrinters"));

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';

print '</div><div class="fichetwothirdright">';

print '</div></div>';


print '<div class="fichecenter"><br>';


// Charge tableau des categories
$cate_arbo = $categstatic->get_full_arbo($typetext);

// Define fulltree array
$fulltree = $cate_arbo;

// Define data (format for treeview)
$data = array();
$data[] = array('rowid'=>0, 'fk_menu'=>-1, 'title'=>"racine", 'mainmenu'=>'', 'leftmenu'=>'', 'fk_mainmenu'=>'', 'fk_leftmenu'=>'');
foreach ($fulltree as $key => $val) {
	$categstatic->id = $val['id'];
	$categstatic->ref = $val['label'];
	$categstatic->color = $val['color'];
	$categstatic->type = $type;

	$li = $categstatic->getNomUrl(1, '', 60);

	$desc = dol_htmlcleanlastbr($val['description']);

	$data[] = array(
		'rowid' => $val['rowid'],
		'fk_menu' => empty($val['fk_menu']) ? 0 : $val['fk_menu'],
		'fk_parent' => $val['fk_parent'],
		'label' => $val['label']
	);
}

//Printer1
print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Printer").' 1</td><td></td><td class="right">';
print '</td></tr>';
$nbofentries = (count($data) - 1);
print '<form action="orderprinters.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($nbofentries > 0) {
	print '<tr class="pair"><td colspan="3">';
	print '<input type="hidden" name="action" value="SavePrinter1">';
	foreach ($data as $row) {
		if (strpos(getDolGlobalString('TAKEPOS_PRINTED_CATEGORIES_1'), ';'.$row["rowid"].';') !== false) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		if ($row["fk_menu"] >= 0) {
			print '<input type="checkbox" name="printer1[]" value="'.$row["rowid"].'" '.$checked.'>'.$row["label"].'<br>';
		}
	}
	print '</td></tr>';
} else {
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
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></form><br><br>';

//Printer2
print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Printer").' 2</td><td></td><td class="right">';
print '</td></tr>';
$nbofentries = (count($data) - 1);
print '<form action="orderprinters.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($nbofentries > 0) {
	print '<tr class="pair"><td colspan="3">';
	print '<input type="hidden" name="action" value="SavePrinter2">';
	foreach ($data as $row) {
		if (strpos(getDolGlobalString('TAKEPOS_PRINTED_CATEGORIES_2'), ';'.$row["rowid"].';') !== false) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		if ($row["fk_menu"] >= 0) {
			print '<input type="checkbox" name="printer2[]" value="'.$row["rowid"].'" '.$checked.'>'.$row["label"].'<br>';
		}
	}
	print '</td></tr>';
} else {
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
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></form>';

//Printer3
print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Printer").' 3</td><td></td><td class="right">';
print '</td></tr>';
$nbofentries = (count($data) - 1);
print '<form action="orderprinters.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($nbofentries > 0) {
	print '<tr class="pair"><td colspan="3">';
	print '<input type="hidden" name="action" value="SavePrinter3">';
	foreach ($data as $row) {
		if (strpos(getDolGlobalString('TAKEPOS_PRINTED_CATEGORIES_3'), ';'.$row["rowid"].';') !== false) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		if ($row["fk_menu"] >= 0) {
			print '<input type="checkbox" name="printer3[]" value="'.$row["rowid"].'" '.$checked.'>'.$row["label"].'<br>';
		}
	}
	print '</td></tr>';
} else {
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
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></form>';

print '</div>';

llxFooter();

$db->close();
