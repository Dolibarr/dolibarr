<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/product/stock/index.php
 *  \ingroup    stock
 *  \brief      Home page of stock area
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('stocks', 'productbatch'));

// Security check
$result=restrictedArea($user,'stock');


/*
 * View
 */

$producttmp=new Product($db);

$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("",$langs->trans("Stocks"),$help_url);

print load_fiche_titre($langs->trans("StocksArea"));


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    print '<form method="post" action="'.DOL_URL_ROOT.'/product/stock/list.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder nohover" width="100%">';
    print "<tr class=\"liste_titre\">";
    print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
    print "<tr ".$bc[false]."><td>";
    print $langs->trans("Warehouse").':</td><td><input class="flat" type="text" size="18" name="sall"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    print "</table></form><br>";
}


$sql = "SELECT e.ref as label, e.rowid, e.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " WHERE e.statut in (0,1)";
$sql.= " AND e.entity IN (".getEntity('stock').")";
$sql.= $db->order('e.statut','DESC');
$sql.= $db->plimit(15, 0);

$result = $db->query($sql);

if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Warehouses").'</th></tr>';

    if ($num)
    {
        $entrepot=new Entrepot($db);

        while ($i < $num)
        {
            $objp = $db->fetch_object($result);

            print '<tr class="oddeven">';
            print "<td><a href=\"card.php?id=$objp->rowid\">".img_object($langs->trans("ShowStock"),"stock")." ".$objp->label."</a></td>\n";
            print '<td align="right">'.$entrepot->LibStatut($objp->statut,5).'</td>';
            print "</tr>\n";
            $i++;
        }
        $db->free($result);

    }
    print "</table>";
}
else
{
    dol_print_error($db);
}


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Last movements
$max=10;
$sql = "SELECT p.rowid, p.label as produit, p.tobatch, p.tosell, p.tobuy,";
$sql.= " e.ref as stock, e.rowid as entrepot_id,";
$sql.= " m.value as qty, m.datem, m.batch, m.eatby, m.sellby";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= ", ".MAIN_DB_PREFIX."stock_mouvement as m";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock').")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql.= " AND p.fk_product_type = 0";
$sql.= $db->order("datem","DESC");
$sql.= $db->plimit($max,0);

dol_syslog("Index:list stock movements", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<th>'.$langs->trans("LastMovements",min($num,$max)).'</th>';
	print '<th>'.$langs->trans("Product").'</th>';
	if (! empty($conf->productbatch->enabled))
	{
		print '<th>'.$langs->trans("Batch").'</th>';
		print '<th>'.$langs->trans("SellByDate").'</th>';
		print '<th>'.$langs->trans("EatByDate").'</th>';
	}
	print '<th>'.$langs->trans("Warehouse").'</th>';
	print '<th align="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/product/stock/mouvement.php">'.$langs->trans("FullList").'</a></th>';
	print "</tr>\n";

	$i=0;
	while ($i < min($num,$max))
	{
		$objp = $db->fetch_object($resql);

		$producttmp->id = $objp->rowid;
		$producttmp->ref = $objp->produit;
		$producttmp->status_batch = $objp->tobatch;
		$producttmp->status_sell = $objp->tosell;
		$producttmp->status_buy = $objp->tobuy;

		print '<tr class="oddeven">';
		print '<td>'.dol_print_date($db->jdate($objp->datem),'dayhour').'</td>';
		print '<td class="tdoverflowmax200">';
		print $producttmp->getNomUrl(1);
		print "</td>\n";
		if (! empty($conf->productbatch->enabled))
		{
			print '<td>'.$objp->batch.'</td>';
			print '<td>'.dol_print_date($db->jdate($objp->sellby),'day').'</td>';
			print '<td>'.dol_print_date($db->jdate($objp->eatby),'day').'</td>';
		}
		print '<td class="tdoverflowmax200"><a href="card.php?id='.$objp->entrepot_id.'">';
		print img_object($langs->trans("ShowWarehouse"),"stock").' '.$objp->stock;
		print "</a></td>\n";
		print '<td align="right">';
		if ($objp->qty > 0) print '+';
		print $objp->qty.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";
}

//print '</td></tr></table>';
print '</div></div></div>';

llxFooter();

$db->close();
