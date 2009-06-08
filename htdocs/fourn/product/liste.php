<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
		\file       htdocs/fourn/product/liste.php
		\ingroup    produit
		\brief      Page liste des produits ou services
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");

$langs->load("products");
$langs->load("suppliers");

if (!$user->rights->produit->lire && !$user->rights->service->lire) accessforbidden();

$sref=isset($_GET["sref"])?$_GET["sref"]:$_POST["sref"];
$sRefSupplier=isset($_GET["srefsupplier"])?$_GET["srefsupplier"]:$_POST["srefsupplier"];
$snom=isset($_GET["snom"])?$_GET["snom"]:$_POST["snom"];

$type=isset($_GET["type"])?$_GET["type"]:$_POST["type"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = $_GET["page"];
if ($page < 0) {
$page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="DESC";

if (! empty($_POST["button_removefilter"]))
{
	$sref="";
	$sRefSupplier="";
	$snom="";
}

if ($_GET["fourn_id"] > 0 || $_POST["fourn_id"] > 0)
{
	$fourn_id = isset($_GET["fourn_id"])?$_GET["fourn_id"]:$_POST["fourn_id"];
}

if (isset($_REQUEST['catid']))
{
	$catid = $_REQUEST['catid'];
}



/*
* Mode Liste
*
*/

$productstatic = new Product($db);
$companystatic = new Societe($db);

$title=$langs->trans("ProductsAndServices");

if ($fourn_id)
{
	$supplier = new Fournisseur($db);
	$supplier->fetch($fourn_id);
}

$sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type,";
$sql .= " pf.fk_soc, pf.ref_fourn,";
$sql .= " ppf.price as price, ppf.quantity as qty, ppf.unitprice,";
$sql .= " s.rowid as socid, s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
if ($catid)
{
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pf.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as ppf ON ppf.fk_product_fournisseur = pf.rowid";

if ($_POST["mode"] == 'search')
{
	$sql .= " WHERE p.ref like '%".$_POST["sall"]."%'";
	$sql .= " OR p.label like '%".$_POST["sall"]."%'";
}
else
{
	$sql .= " WHERE 1=1";
	if ($_GET["type"] || $_POST["type"])
	{
		$sql .= " AND p.fk_product_type = ".(isset($_GET["type"])?$_GET["type"]:$_POST["type"]);
	}
	if ($sref)
	{
		$sql .= " AND p.ref like '%".$sref."%'";
	}
	if ($sRefSupplier)
	{
		$sql .= " AND pf.ref_fourn like '%".$sRefSupplier."%'";
	}
	if ($snom)
	{
		$sql .= " AND p.label like '%".$snom."%'";
	}
	if($catid)
	{
		$sql .= " AND cp.fk_categorie = ".$catid;
	}

}
if ($fourn_id > 0)
{
	$sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
}
$sql .= " ORDER BY ".$sortfield." ".$sortorder;
$sql .= $db->plimit($limit + 1 ,$offset);


dol_syslog("fourn/product/liste: sql=".$sql);

$resql = $db->query($sql) ;
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && ( isset($_POST["sall"]) || $snom || $sref ) )
	{
		$objp = $db->fetch_object($resql);
		Header("Location: fiche.php?id=".$objp->rowid);
		exit;
	}

	if (! empty($supplier->id)) $texte = $langs->trans("ListOfSupplierProductForSupplier",$supplier->nom);
	else $texte = $langs->trans("List");

	llxHeader("","",$texte);


	$param="&envente=$envente&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":"");
	print_barre_liste($texte, $page, "liste.php", $param, $sortfield, $sortorder,'',$num);


	if (isset($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie ($db, $catid);
		$ways = $c->print_all_ways(' &gt; ','fourn/product/liste.php');
		print " &gt; ".$ways[0]."<br />\n";
		print "</div><br />";
	}


	print '<table class="liste" width="100%">';

	// Lignes des titres
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),"liste.php", "p.ref",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("RefSupplierShort"),"liste.php", "pf.ref_fourn",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),"liste.php", "p.label",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Supplier"),"liste.php", "pf.fk_soc",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("BuyingPrice"),"liste.php", "ppf.price",$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("QtyMin"),"liste.php", "ppf.qty",$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("UnitPrice"),"liste.php", "ppf.unitprice",$param,"",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<form action="liste.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	if ($fourn_id > 0) print '<input type="hidden" name="fourn_id" value="'.$fourn_id.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" value="'.$sref.'" size="12">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="srefsupplier" value="'.$sRefSupplier.'" size="12">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="4" align="right">';
	print '<input type="image" class="liste_titre" value="button_search" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '&nbsp; <input type="image" class="liste_titre" value="button_removefilter" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
	print '</td>';
	print '</tr>';
	print '</form>';

	$oldid = '';
	$var=True;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";

		print '<td>';
		$productstatic->id=$objp->rowid;
		$productstatic->ref=$objp->ref;
		$productstatic->type=$objp->fk_product_type;
		print $productstatic->getNomUrl(1,'supplier');
		print '</td>';

		print '<td>'.$objp->ref_fourn.'</td>';

		print '<td>'.$objp->label.'</td>'."\n";

		$companystatic->nom=$objp->nom;
		$companystatic->id=$objp->socid;
		print '<td>'.$companystatic->getNomUrl(1,'supplier').'</td>';

		print '<td align="right">'.price($objp->price).'</td>';

		print '<td align="right">'.$objp->qty.'</td>';

		print '<td align="right">'.price($objp->unitprice).'</td>';

		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print "</table>";


}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
