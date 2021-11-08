<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * \file       htdocs/product/popuprop.php
 * \ingroup    propal, commande, facture, produit
 * \brief      List of products or services by popularity
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('commande', 'propal', 'bills', 'other'));

$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$type = GETPOST("type", "int");
$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : '';

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
if (!$sortfield) {
	$sortfield = "c";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

restrictedArea($user, 'produit|service', 0, 'product&product', '', '');


/*
 * View
 */

$form = new Form($db);

$helpurl = '';
if ($type == '0') {
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
} elseif ($type == '1') {
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
} else {
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}
$title = $langs->trans("Statistics");


llxHeader('', $title, $helpurl);

print load_fiche_titre($title, $mesg, 'product');


$param = '';
$title = $langs->trans("ListProductServiceByPopularity");
if ((string) $type == '1') {
	$title = $langs->trans("ListServiceByPopularity");
}
if ((string) $type == '0') {
	$title = $langs->trans("ListProductByPopularity");
}

if ($type != '') {
	$param .= '&type='.urlencode($type);
}
if ($mode != '') {
	$param .= '&mode='.urlencode($mode);
}

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php?id=all';
$head[$h][1] = $langs->trans("Chart");
$head[$h][2] = 'chart';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/popuprop.php';
$head[$h][1] = $langs->trans("ProductsPerPopularity");
$head[$h][2] = 'popularity';
$h++;


print dol_get_fiche_head($head, 'popularity', $langs->trans("Statistics"), -1);


// Array of liens to show
$infoprod = array();


// Add lines for object
$sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type as type, SUM(pd.qty) as c";
$textforqty = 'Qty';
if ($mode == 'facture') {
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as pd";
} elseif ($mode == 'commande') {
	$textforqty = 'NbOfQtyInOrders';
	$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as pd";
} elseif ($mode == 'propal') {
	$textforqty = 'NbOfQtyInProposals';
	$sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pd";
}
$sql .= ", ".MAIN_DB_PREFIX."product as p";
$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
$sql .= " AND p.rowid = pd.fk_product";
if ($type !== '') {
	$sql .= " AND fk_product_type = ".((int) $type);
}
$sql .= " GROUP BY p.rowid, p.label, p.ref, p.fk_product_type";

if (!empty($mode) && $mode != '-1') {
	$result = $db->query($sql);
	if ($result) {
		$totalnboflines = $db->num_rows($result);
	}

	$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			$infoprod[$objp->rowid] = array('type'=>$objp->type, 'ref'=>$objp->ref, 'label'=>$objp->label);
			$infoprod[$objp->rowid]['nbline'] = $objp->c;

			$i++;
		}
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}
//var_dump($infoprod);


$arrayofmode = array(
	'propal' => 'Proposals',
	'commande' => 'Orders',
	'facture' => 'Facture'
	);
$title .= ' '.$form->selectarray('mode', $arrayofmode, $mode, 1);
$title .= ' <input type="submit" class="button small" name="refresh" value="'.$langs->trans("Refresh").'">';


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="action" value="add">';
if ($backtopage) {
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
}
if ($backtopageforcancel) {
	print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
}


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '', 0, '', '', -1, 0, 0, 1);

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Type', $_SERVER["PHP_SELF"], 'p.fk_product_type', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($textforqty, $_SERVER["PHP_SELF"], 'c', '', $param, '', $sortfield, $sortorder, 'right ');
print "</tr>\n";

if ($mode && $mode != '-1') {
	foreach ($infoprod as $prodid => $vals) {
		// Multilangs
		if (!empty($conf->global->MAIN_MULTILANGS)) { // si l'option est active
			$sql = "SELECT label";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql .= " WHERE fk_product = ".((int) $prodid);
			$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";
			$sql .= " LIMIT 1";

			$resultp = $db->query($sql);
			if ($resultp) {
				$objtp = $db->fetch_object($resultp);
				if (!empty($objtp->label)) {
					$vals['label'] = $objtp->label;
				}
			}
		}

		print "<tr>";
		print '<td><a href="'.DOL_URL_ROOT.'/product/stats/card.php?id='.$prodid.'">';
		if ($vals['type'] == 1) {
			print img_object($langs->trans("ShowService"), "service");
		} else {
			print img_object($langs->trans("ShowProduct"), "product");
		}
		print " ";
		print $vals['ref'].'</a></td>';
		print '<td>';
		if ($vals['type'] == 1) {
			print $langs->trans("Service");
		} else {
			print $langs->trans("Product");
		}
		print '</td>';
		print '<td>'.$vals['label'].'</td>';
		print '<td class="right">'.$vals['nbline'].'</td>';
		print "</tr>\n";
		$i++;
	}
} else {
	print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("SelectTheTypeOfObjectToAnalyze").'</span></td></tr>';
}
print "</table>";

print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
