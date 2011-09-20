<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/product/stock/liste.php
 *      \ingroup    stock
 *      \brief      Page liste des stocks
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");

$langs->load("stocks");

if (!$user->rights->stock->lire)
  accessforbidden();

$sref=isset($_GET["sref"])?$_GET["sref"]:$_POST["sref"];
$snom=isset($_GET["snom"])?$_GET["snom"]:$_POST["snom"];
$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
if (! $sortfield) $sortfield="e.label";
if (! $sortorder) $sortorder="ASC";
$page = $_GET["page"];
if ($page < 0) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page;

  
$sql  = "SELECT e.rowid, e.label as ref, e.statut, e.lieu, e.address, e.cp, e.ville, e.fk_pays";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " WHERE e.entity = ".$conf->entity;
if ($sref)
{
    $sql.= " AND e.label like '%".$sref."%'";
}
if ($sall)
{
    $sql.= " AND (e.description like '%".$sall."%' OR e.lieu like '%".$sall."%' OR e.address like '%".$sall."%' OR e.ville like '%".$sall."%')";
}
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit + 1 ,$offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("ListOfWarehouses"),$help_url);

	print_barre_liste($langs->trans("ListOfWarehouses"), $page, "liste.php", "", $sortfield, $sortorder,'',$num);

	print '<table class="noborder" width="100%">';

	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),"liste.php", "e.label","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("LocationSummary"),"liste.php", "e.lieu","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php", "e.statut",'','','align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	if ($num) {
		$entrepot=new Entrepot($db);

		$var=True;
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr $bc[$var]>";
			print '<td><a href="fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$objp->ref.'</a></td>';
			print '<td>'.$objp->lieu.'</td>';
			print '<td align="right">'.$entrepot->LibStatut($objp->statut,5).'</td>';
			print "</tr>\n";
			$i++;
		}
	}

	$db->free($result);

	print "</table>";

}
else
{
  dol_print_error($db);
}


$db->close();

llxFooter();
?>
