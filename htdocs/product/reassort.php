<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/product/reassort.php
 *  \ingroup    produit
 *  \brief      Page liste des produits ou services
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

$langs->load("products");
$langs->load("stocks");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service');


$sref=isset($_GET["sref"])?$_GET["sref"]:$_POST["sref"];
$snom=isset($_GET["snom"])?$_GET["snom"]:$_POST["snom"];
$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
$type=isset($_GET["type"])?$_GET["type"]:$_POST["type"];
$sref=trim($sref);
$snom=trim($snom);
$sall=trim($sall);
$type=trim($type);

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
if (! $sortfield) $sortfield="stock_physique";
if (! $sortorder) $sortorder="ASC";
$page = $_GET["page"];
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (isset($_POST["button_removefilter_x"]))
{
	$sref="";
	$snom="";
}

if (isset($_REQUEST['catid']))
{
	$catid = $_REQUEST['catid'];
}

// Load sale and categ filters
$search_sale = isset($_GET["search_sale"])?$_GET["search_sale"]:$_POST["search_sale"];
$search_categ = isset($_GET["search_categ"])?$_GET["search_categ"]:$_POST["search_categ"];



/*
 * View
 */

$htmlother=new FormOther($db);

$title=$langs->trans("ProductsAndServices");

$sql = 'SELECT p.rowid, p.ref, p.label, p.price, p.fk_product_type, p.tms as datem,';
$sql.= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte,';
$sql.= ' SUM(s.reel) as stock_physique';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product_stock as s,';
$sql.= ' '.MAIN_DB_PREFIX.'product as p';
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_product as cp";
if ($_GET["fourn_id"] > 0)
{
	$fourn_id = $_GET["fourn_id"];
	$sql.= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}
$sql.= " WHERE p.rowid = s.fk_product";
$sql.= " AND p.entity = ".$conf->entity;
if ($search_categ) $sql.= " AND p.rowid = cp.fk_product";	// Join for the needed table to filter by categ
if (!$user->rights->produit->hidden && !$user->rights->service->hidden)
{
	$sql.=' AND p.hidden=0';
}
else
{
	if (!$user->rights->produit->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 0)';
	if (!$user->rights->service->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 1)';
}
if ($sall)
{
	$sql.= " AND (p.ref like '%".addslashes($sall)."%' OR p.label like '%".addslashes($sall)."%' OR p.description like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%')";
}
if ($type==1)
{
	$sql.= " AND p.fk_product_type = '1'";
}
else
{
	$sql.= " AND p.fk_product_type <> '1'";
}
if ($sref)
{
	$sql.= " AND p.ref like '%".$sref."%'";
}
if ($snom)
{
	$sql.= " AND p.label like '%".addslashes($snom)."%'";
}
if (isset($_GET["tosell"]) && strlen($_GET["tosell"]) > 0)
{
	$sql.= " AND p.tosell = ".$_GET["tosell"];
}
if (isset($_GET["tobuy"]) && strlen($_GET["tobuy"]) > 0)
{
    $sql.= " AND p.tobuy = ".$_GET["tobuy"];
}
if($catid)
{
	$sql.= " AND cp.fk_categorie = ".$catid;
}
if ($fourn_id > 0)
{
	$sql.= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
}
// Insert categ filter
if ($search_categ)
{
	$sql .= " AND cp.fk_categorie = ".addslashes($search_categ);
}
$sql.= " GROUP BY p.rowid, p.ref, p.label, p.price, p.fk_product_type, p.tms,";
$sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1 ,$offset);
$resql = $db->query($sql) ;

if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && ($sall or $snom or $sref))
	{
		$objp = $db->fetch_object($resql);
		Header("Location: fiche.php?id=$objp->rowid");
		exit;
	}

	if (isset($_GET["tosell"]) || isset($_POST["tosell"]))
	{
		$tosell = (isset($_GET["tosell"])?$_GET["tosell"]:$_POST["tosell"]);
	}
    if (isset($_GET["tobuy"]) || isset($_POST["tobuy"]))
    {
        $tobuy = (isset($_GET["tobuy"])?$_GET["tobuy"]:$_POST["tobuy"]);
    }



	$helpurl='';
	$helpurl='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

	if (isset($_GET["type"]) || isset($_POST["type"]))
	{
		if ($type==1) { $texte = $langs->trans("Services"); }
		else { $texte = $langs->trans("Products"); }
	} else {
		$texte = $langs->trans("ProductsAndServices");
	}
	$texte.=' ('.$langs->trans("Stocks").')';


	llxHeader("",$title,$helpurl,$texte);

	if ($sref || $snom || $sall || $_POST["search"])
	{
		print_barre_liste($texte, $page, "reassort.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$_POST["tosell"]."&amp;tobuy=".$_POST["tobuy"], $sortfield, $sortorder,'',$num);
	}
	else
	{
		print_barre_liste($texte, $page, "reassort.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
	}

	if (isset($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie ($db, $catid);
		$ways = $c->print_all_ways(' &gt; ','product/reassort.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	print '<form action="reassort.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print '<table class="liste" width="100%">';

	// Filter on categories
 	$moreforfilter='';
	if ($conf->categorie->enabled)
	{
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$htmlother->select_categories(0,$search_categ,'search_categ');
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
 	if ($moreforfilter)
	{
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="9">';
	    print $moreforfilter;
	    print '</td></tr>';
	}

	$param="&tosell=$tosell&tobuy=$tobuy".(isset($type)?"&type=$type":"")."&fourn_id=$fourn_id&snom=$snom&sref=$sref";

	// Lignes des titres
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre($langs->trans("Ref"),"reassort.php", "p.ref",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),"reassort.php", "p.label",$param,"","",$sortfield,$sortorder);
	if ($conf->service->enabled && $type == 1) print_liste_field_titre($langs->trans("Duration"),"reassort.php", "p.duration",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("MininumStock"),"reassort.php", "p.seuil_stock_alerte",$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PhysicalStock"),"reassort.php", "stock_physique",$param,"",'align="right"',$sortfield,$sortorder);
	// TODO Add info of running suppliers/customers orders
	//print_liste_field_titre($langs->trans("TheoreticalStock"),"reassort.php", "stock_theorique",$param,"",'align="right"',$sortfield,$sortorder);
	print '<td class="liste_titre">&nbsp;</td>';
	print_liste_field_titre($langs->trans("Sell"),"reassort.php", "p.tosell",$param,"",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Buy"),"reassort.php", "p.tobuy",$param,"",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="sref" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
	print '</td>';
	if ($conf->service->enabled && $type == 1)
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
	print '</td>';
	print '</tr>';

	$product_static=new Product($db);

	$var=True;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		// Multilangs
		if ($conf->global->MAIN_MULTILANGS) // si l'option est active
		{
			$sql = "SELECT label";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql.= " WHERE fk_product=".$objp->rowid;
			$sql.= " AND lang='". $langs->getDefaultLang() ."'";
			$sql.= " LIMIT 1";

			$result = $db->query($sql);
			if ($result)
			{
				$objtp = $db->fetch_object($result);
				if ($objtp->label != '') $objp->label = $objtp->label;
			}
		}

		$var=!$var;
		print '<tr '.$bc[$var].'><td nowrap="nowrap">';
		$product_static->ref=$objp->ref;
		$product_static->id=$objp->rowid;
		$product_static->type=$objp->fk_product_type;
		print $product_static->getNomUrl(1,'',16);
		//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';
		print '<td>'.$objp->label.'</td>';

		if ($conf->service->enabled && $type == 1)
		{
			print '<td align="center">';
			if (preg_match('/([0-9]+)y/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
			elseif (preg_match('/([0-9]+)m/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
			elseif (preg_match('/([0-9]+)d/i',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
			else print $objp->duration;
			print '</td>';
		}
		//print '<td align="right">'.$objp->stock_theorique.'</td>';
		print '<td align="right">'.$objp->seuil_stock_alerte.'</td>';
		print '<td align="right">'.$objp->stock_physique.'</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$product_static->id.'">'.$langs->trans("Movements").'</a></td>';
		print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->statut,5,0).'</td>';
        print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->tobuy,5,1).'</td>';
		print "</tr>\n";
		$i++;
	}

	print "</table>";
	print '</form>';

	if ($num > $conf->liste_limit)
	{
		if ($sref || $snom || $sall || $_POST["search"])
		{
	  		print_barre_liste('', $page, "reassort.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$_POST["tosell"]."&amp;tobuy=".$_POST["tobuy"], $sortfield, $sortorder,'',$num, 0, '');
		}
		else
		{
	  		print_barre_liste('', $page, "reassort.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num, 0, '');
		}
	}

	$db->free($resql);

}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>