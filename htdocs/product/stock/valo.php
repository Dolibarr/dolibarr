<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/product/stock/valo.php
 *  \ingroup    stock
 *  \brief      Page de valorisation des stocks
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once("./entrepot.class.php");

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

$year = strftime("%Y",time());


/*
 *	View
 */

// Affichage valorisation par entrepot
$sql = "SELECT e.rowid as ref, e.label, e.statut, e.lieu";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= " WHERE e.entity = ".$conf->entity;
if ($sref)
{
	$sql.= " AND e.ref LIKE '%".$sref."%'";
}
if ($sall)
{
	$sql.= " AND (e.label LIKE '%".addslashes($sall)."%'";
	$sql.= " OR e.description LIKE '%".addslashes($sall)."%'";
	$sql.= " OR e.lieu LIKE '%".addslashes($sall)."%'";
	$sql.= " OR e.address LIKE '%".addslashes($sall)."%'";
	$sql.= " OR e.ville LIKE '%".addslashes($sall)."%')";
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($limit + 1, $offset);

$result = $db->query($sql) ;
if ($result)
{
	$num = $db->num_rows($result);

	$i = 0;

	llxHeader("","",$langs->trans("EnhancedValueOfWarehouses"));

	print_barre_liste($langs->trans("EnhancedValueOfWarehouses"), $page, "valo.php", "", $sortfield, $sortorder,'',$num);

	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),"valo.php", "e.label","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("LocationSummary"),"valo.php", "e.lieu","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PMPValue"),"valo.php", "valo",'','','align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"valo.php", "e.statut",'','','align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	if ($num)
	{
		$entrepot=new Entrepot($db);
		$total = 0;
		$var=false;
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);
			print "<tr $bc[$var]>";
			print '<td><a href="fiche.php?id='.$objp->ref.'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$objp->label.'</a></td>';
			print '<td>'.$objp->lieu.'</td>';
			print '<td align="right">';
			// This value is real QTY * PMP of products in llx_product_stock for the warehouse
			print $langs->trans("FeatureNotYetAvailableShort");
			print '</td>';
			print '<td align="right">'.$entrepot->LibStatut($objp->statut,5).'</td>';
			print "</tr>\n";
			$total += $objp->valo;
			$var=!$var;
			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="2" align="right">'.$langs->trans("Total").'</td>';
		print '<td align="right">'.price($total).' '.$langs->trans('Currency'.$conf->monnaie).'</td>';
		print '<td align="right">&nbsp;</td>';
		print "</tr>\n";

	}
	$db->free($result);
	print "</table>";

	print '<br />';

	$file='entrepot-'.$year.'.png';
	if (file_exists(DOL_DATA_ROOT.'/entrepot/temp/'.$file))
	{
		$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file='.$file;
		print '<img src="'.$url.'" alt="Valorisation du stock année '.($year).'">';
	}

	$file='entrepot-'.($year-1).'.png';
	if (file_exists(DOL_DATA_ROOT.'/entrepot/temp/'.$file))
	{
		$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file='.$file;
		print '<br /><img src="'.$url.'" alt="Valorisation du stock année '.($year-1).'">';
	}

}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
