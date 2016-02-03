<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
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
 *      \file       htdocs/product/stock/list.php
 *      \ingroup    stock
 *      \brief      Page with warehouse and stock value
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

$langs->load("stocks");

// Security check
$result=restrictedArea($user,'stock');

$search_ref=GETPOST("sref","alpha")?GETPOST("sref","alpha"):GETPOST("search_ref","alpha");
$search_label=GETPOST("snom","alpha")?GETPOST("snom","alpha"):GETPOST("search_label","alpha");
$sall=GETPOST("sall","alpha");
$search_status=GETPOST("search_status","int");

$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="e.label";
if (! $sortorder) $sortorder="ASC";
$page = GETPOST("page");
if ($page < 0) $page = 0;
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page;

$year = strftime("%Y",time());

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.label'=>"Ref",
    'e.lieu'=>"LocationSummary",
    'e.description'=>"Description",
    'e.address'=>"Address",
    'e.zip'=>'Zip',
    'e.town'=>'Town',
);



/*
 *	View
 */

$form=new Form($db);
$warehouse=new Entrepot($db);

$sql = "SELECT e.rowid, e.label as ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays,";
$sql.= " SUM(p.pmp * ps.reel) as estimatedvalue, SUM(p.price * ps.reel) as sellvalue, SUM(ps.reel) as stockqty";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
$sql.= " WHERE e.entity IN (".getEntity('stock', 1).")";
if ($search_ref) $sql.= natural_search("e.label", $search_ref);			// ref
if ($search_label) $sql.= natural_search("e.lieu", $search_label);		// label
if ($search_status != '' && $search_status >= 0) $sql.= " AND e.statut = ".$search_status;
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
$sql.= " GROUP BY e.rowid, e.label, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays";
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
	// fetch totals
	$line = $total = $totalsell = $totalStock = 0;
	while ($line < $totalnboflines)
	{
		$objp = $db->fetch_object($result);
		$total += price2num($objp->estimatedvalue,'MU');
		$totalsell += price2num($objp->sellvalue,'MU');
		$totalStock += $objp->stockqty;
		$line++;
	}
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("ListOfWarehouses"),$help_url);

	print_barre_liste($langs->trans("ListOfWarehouses"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, $totalnboflines);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	if ($sall)
	{
	    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
	}
	
	$moreforfilter='';
	
	print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "e.label","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("LocationSummary"),$_SERVER["PHP_SELF"], "e.lieu","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PhysicalStock"), $_SERVER["PHP_SELF"], "stockqty",'','','align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("EstimatedStockValue"), $_SERVER["PHP_SELF"], "e.valo_pmp",'','','align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("EstimatedStockValueSell"), $_SERVER["PHP_SELF"], "",'','','align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"], "e.statut",'','','align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_ref" size="6" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';

	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_label" size="10" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';

	print '<td class="liste_titre" colspan="3">';
	print '</td>';

	print '<td class="liste_titre" align="right">';
	print $form->selectarray('search_status', $warehouse->statuts, $search_status, 1, 0, 0, '', 1);
	print '</td>';

	print '<td class="liste_titre nowrap" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print '</tr>';

	if ($num)
	{
		$entrepot=new Entrepot($db);
        $var=false;
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);
            $entrepot->id = $objp->rowid;
            $entrepot->libelle = $objp->ref;
            $entrepot->lieu = $objp->lieu;
            print "<tr ".$bc[$var].">";
            print '<td>' . $entrepot->getNomUrl(1) . '</td>';
            // Location
            print '<td>'.$objp->lieu.'</td>';
            // Stock qty
            print '<td align="right">'.price2num($objp->stockqty,5).'</td>';
            // PMP value
            print '<td align="right">';
            if (price2num($objp->estimatedvalue,'MT')) print price(price2num($objp->estimatedvalue,'MT'),1);
            else print '';
            print '</td>';
            // Selling value
            print '<td align="right">';
            if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($objp->sellvalue,'MT'),1);
            else
			{
				$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
            	print $form->textwithtooltip($langs->trans("Variable"),$htmltext);
			}
            print '</td>';
            // Status
            print '<td align="right">'.$entrepot->LibStatut($objp->statut,5).'</td>';

            print '<td></td>';

            print "</tr>\n";

            $var=!$var;
            $i++;
		}

		if ($totalnboflines-$offset <= $limit)
		{
    		print '<tr class="liste_total">';
            print '<td colspan="2" align="right">'.$langs->trans("Total").'</td>';
			print '<td align="right">'.price2num($totalStock,5).'</td>';
            print '<td align="right">'.price(price2num($total,'MT'),1,$langs,0,0,-1,$conf->currency).'</td>';
            print '<td align="right">';
    		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalsell,'MT'),1,$langs,0,0,-1,$conf->currency);
            else
    		{
    			$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
               	print $form->textwithtooltip($langs->trans("Variable"),$htmltext);
    		}
            print '</td>';
            print '<td></td>';
            print '<td></td>';
            print "</tr>\n";
		}
	}

	$db->free($result);

	print "</table>";

	print '</form>';
}
else
{
  dol_print_error($db);
}


llxFooter();

$db->close();
