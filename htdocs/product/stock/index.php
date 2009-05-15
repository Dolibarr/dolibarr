<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
   \file       htdocs/product/stock/index.php
   \ingroup    stock
   \brief      Page accueil stocks produits
   \version    $Revision$
*/

require_once("./pre.inc.php");
require_once("./entrepot.class.php");

$langs->load("stocks");

if (!$user->rights->stock->lire)
  accessforbidden();

llxHeader("","",$langs->trans("Stocks"));

print_fiche_titre($langs->trans("StocksArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche entrepot
 */
print '<form method="post" action="'.DOL_URL_ROOT.'/product/stock/liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").':</td><td><input class="flat" type="text" size="18" name="sref"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "<tr $bc[0]><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
print "</table></form><br>";

$sql = "SELECT e.label, e.rowid, e.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " WHERE e.statut in (0,1)";
$sql.= " AND e.entity = ".$conf->entity;
$sql.= " ORDER BY e.statut DESC ";
$sql.= $db->plimit(15 ,0);

$result = $db->query($sql) ;

if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Warehouses").'</td></tr>';

    if ($num)
    {
        $entrepot=new Entrepot($db);

        $var=True;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
            print "<td><a href=\"fiche.php?id=$objp->rowid\">".img_object($langs->trans("ShowStock"),"stock")." ".$objp->label."</a></td>\n";
            print '<td align="right">'.$entrepot->LibStatut($objp->statut,3).'</td>';
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

print '</td><td valign="top" width="70%" class="notopnoleft">';

// Last movements
$max=10;
$sql = "SELECT p.rowid, p.label as produit,";
$sql.= " s.label as stock, s.rowid as entrepot_id,";
$sql.= " m.value, ".$db->pdate("m.datem")." as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as s";
$sql.= ", ".MAIN_DB_PREFIX."stock_mouvement as m";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}
$sql.= " WHERE m.fk_product = p.rowid";
$sql.= " AND m.fk_entrepot = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
	$sql.= " AND IFNULL(c.visible,1)=1";
}
$sql.= " ORDER BY datem DESC";
$sql.= $db->plimit($max,0);

dol_syslog("Index:list stock movements sql=".$sql, LOG_DEBUG);
$resql = $db->query($sql) ;
if ($resql)
{
	$num = $db->num_rows($resql);

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td>'.$langs->trans("LastMovements",min($num,$max)).'</td>';
	print '<td>'.$langs->trans("Product").'</td>';
	print '<td>'.$langs->trans("Warehouse").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php">'.$langs->trans("FullList").'</a></td>';
	print "</tr>\n";

	$var=True;
	$i=0;
	while ($i < min($num,$max))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td>'.dol_print_date($objp->datem,'dayhour').'</td>';
		print "<td><a href=\"../fiche.php?id=$objp->rowid\">";
		print img_object($langs->trans("ShowProduct"),"product").' '.$objp->produit;
		print "</a></td>\n";
		print '<td><a href="fiche.php?id='.$objp->entrepot_id.'">';
		print img_object($langs->trans("ShowWarehouse"),"stock").' '.$objp->stock;
		print "</a></td>\n";
		print '<td align="right">';
		if ($objp->value > 0) print '+';
		print $objp->value.'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";	
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
