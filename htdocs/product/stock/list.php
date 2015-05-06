<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
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

$sref=GETPOST("sref","alpha");
$snom=GETPOST("snom","alpha");
$sall=GETPOST("sall","alpha");

$sortfield = GETPOST("sortfield");
$sortorder = GETPOST("sortorder");
if (! $sortfield) $sortfield="e.label";
if (! $sortorder) $sortorder="ASC";
$page = GETPOST("page");
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page;

$year = strftime("%Y",time());


/*
 *	View
 */

$form=new Form($db);

$sql = "SELECT e.rowid, e.label as ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays,";
$sql.= " SUM(p.pmp * ps.reel) as estimatedvalue, SUM(p.price * ps.reel) as sellvalue";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
$sql.= " WHERE e.entity IN (".getEntity('stock', 1).")";
if ($sref)
{
    $sql.= " AND e.label LIKE '%".$db->escape($sref)."%'";
}
if ($sall)
{
    $sql.= " AND (e.label LIKE '%".$db->escape($sall)."%'";
    $sql.= " OR e.description LIKE '%".$db->escape($sall)."%'";
    $sql.= " OR e.lieu LIKE '%".$db->escape($sall)."%'";
    $sql.= " OR e.address LIKE '%".$db->escape($sall)."%'";
    $sql.= " OR e.town LIKE '%".$db->escape($sall)."%')";
}
$sql.= " GROUP BY e.rowid, e.label, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("ListOfWarehouses"),$help_url);

	print_barre_liste($langs->trans("ListOfWarehouses"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder,'',$num);

	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "e.label","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("LocationSummary"),$_SERVER["PHP_SELF"], "e.lieu","","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("EstimatedStockValue"), $_SERVER["PHP_SELF"], "e.valo_pmp",'','','align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("EstimatedStockValueSell"), $_SERVER["PHP_SELF"], "",'','','align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"], "e.statut",'','','align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	if ($num)
	{
		$entrepot=new Entrepot($db);
        $total = $totalsell = 0;
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
            print "</tr>\n";

            $total += price2num($objp->estimatedvalue,'MU');
            $totalsell += price2num($objp->sellvalue,'MU');

            $var=!$var;
            $i++;
		}

		print '<tr class="liste_total">';
        print '<td colspan="2" align="right">'.$langs->trans("Total").'</td>';
        print '<td align="right">'.price(price2num($total,'MT'),1,$langs,0,0,-1,$conf->currency).'</td>';
        print '<td align="right">';
		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalsell,'MT'),1,$langs,0,0,-1,$conf->currency);
        else
		{
			$htmltext=$langs->trans("OptionMULTIPRICESIsOn");
           	print $form->textwithtooltip($langs->trans("Variable"),$htmltext);
		}
        print '</td>';
        print '<td align="right">&nbsp;</td>';
        print "</tr>\n";
	}

	$db->free($result);

	print "</table>";

}
else
{
  dol_print_error($db);
}


llxFooter();

$db->close();
