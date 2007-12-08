<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
   \file       htdocs/product/liste.php
   \ingroup    produit
   \brief      Page liste des produits ou services
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/product.class.php');
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("products");
$langs->load("stocks");

$user->getrights('produit');

if (!$user->rights->produit->lire)
  accessforbidden();


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
if (! $sortfield) $sortfield="stock_real";
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


/*
 * Affichage mode liste
 *
 */

$title=$langs->trans("ProductsAndServices");

$sql = 'SELECT p.rowid, p.ref, p.label, p.price, p.fk_product_type, '.$db->pdate('p.tms').' as datem,';
$sql.= ' p.duration, p.envente as statut, p.seuil_stock_alerte,';
$sql.= ' p.stock_commande,';
$sql.= ' SUM(s.reel) as stock,';
$sql.= ' (SUM(s.reel) - p.stock_commande) as stock_real'; //Todo: Il faudra additionner les commandes fournisseurs
$sql.= ' FROM '.MAIN_DB_PREFIX.'product_stock as s,';
$sql.= ' '.MAIN_DB_PREFIX.'product as p';
if ($catid || ($conf->categorie->enabled && ! $user->rights->categorie->voir))
{
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}

if ($_GET["fourn_id"] > 0)
{
  $fourn_id = $_GET["fourn_id"];
  $sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}
$sql .= " WHERE p.rowid = s.fk_product";
if ($sall)
{
  $sql .= " AND (p.ref like '%".addslashes($sall)."%' OR p.label like '%".addslashes($sall)."%' OR p.description like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%')";
}
if ($type==1)
{
  $sql .= " AND p.fk_product_type = '1'";
}
else
{
  $sql .= " AND p.fk_product_type <> '1'";
}
if ($sref)
{
  $sql .= " AND p.ref like '%".$sref."%'";
}
if ($snom)
{
  $sql .= " AND p.label like '%".addslashes($snom)."%'";
}
if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
{
  $sql .= " AND p.envente = ".$_GET["envente"];
}
if($catid)
{
  $sql .= " AND cp.fk_categorie = ".$catid;
}
if ($conf->categorie->enabled && !$user->rights->categorie->voir)
{
  $sql.= ' AND IFNULL(c.visible,1)=1';
}
if ($fourn_id > 0)
{
  $sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
}
$sql .= " GROUP BY p.rowid";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1 ,$offset);
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
  
  if (isset($_GET["envente"]) || isset($_POST["envente"]))
    {
      $envente = (isset($_GET["envente"])?$_GET["envente"]:$_POST["envente"]);
    }
  
  if (isset($_GET["type"]) || isset($_POST["type"]))
    {
      if ($type==1) { $texte = $langs->trans("Services"); }
      else { $texte = $langs->trans("Products"); }
    } else {
      $texte = $langs->trans("ProductsAndServices");
    }
  
  
  llxHeader("","",$texte);
  
  if ($sref || $snom || $sall || $_POST["search"])
    {
      print_barre_liste($texte, $page, "reassort.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall."&amp;envente=".$_POST["envente"], $sortfield, $sortorder,'',$num);
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
      print " &gt; ".$ways[0]."<br />\n";
      print "</div><br />";
    }

    print '<table class="liste" width="100%">';
    
    // Lignes des titres
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"),"reassort.php", "p.ref","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Label"),"reassort.php", "p.label","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("RealStock"),"reassort.php", "stock_real","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
    if ($conf->service->enabled && $type == 1) print_liste_field_titre($langs->trans("Duration"),"reassort.php", "p.duration","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("PhysicalStock"),"reassort.php", "stock","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("StockLimit"),"reassort.php", "stock","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),"reassort.php", "p.envente","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

    // Lignes des champs de filtre
    print '<form action="reassort.php" method="post" name="formulaire">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="sref" value="'.$sref.'">';
    print '</td>';
    print '<td class="liste_titre" valign="right">';
    print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
    print '</td>';
    if ($conf->service->enabled && $type == 1) 
    {
      print '<td class="liste_titre">';
      print '&nbsp;';
      print '</td>';
    }
    print '<td class="liste_titre" colspan="3">';
    print '&nbsp;';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
    print '</td>';
    print '</tr>';
    print '</form>';

    $product_static=new Product($db);
    
    $var=True;
    while ($i < min($num,$limit))
      {
        $objp = $db->fetch_object($resql);
        
	// Multilangs
	if ($conf->global->MAIN_MULTILANGS) // si l'option est active
	  {
	    $sql = "SELECT label FROM ".MAIN_DB_PREFIX."product_det";
	    $sql.= " WHERE fk_product=".$objp->rowid." AND lang='". $langs->getDefaultLang() ."'";
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
        print "<a href=\"fiche.php?id=$objp->rowid\">";
        if ($objp->fk_product_type==1) 
	  {
	    print img_object($langs->trans("ShowService"),"service");
	  }
        else
	  {
	    if ( $objp->stock_real > $objp->seuil_stock_alerte) {
	      print img_object($langs->trans("ShowProduct"),"product");
	    } else {
	      print img_warning($langs->trans("StockTooLow"));
	    }
	  }
        print '</a> ';
        print '<a href="fiche.php?id='.$objp->rowid.'">'.$objp->ref.'</a></td>';
        print '<td>'.$objp->label.'</td>';

        if ($conf->service->enabled && $type == 1) 
        {
            print '<td align="center">';
            if (eregi('([0-9]+)y',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
            elseif (eregi('([0-9]+)m',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
            elseif (eregi('([0-9]+)d',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
            else print $objp->duration;
            print '</td>';
        }
        print '<td align="right">'.$objp->stock_real.'</td>';
        print '<td align="right">'.$objp->stock.'</td>';
        print '<td align="right">'.$objp->seuil_stock_alerte.'</td>';
        print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->statut,5).'</td>';
        print "</tr>\n";
        $i++;
    }
    
    if ($num > $conf->liste_limit)
    {
      if ($sref || $snom || $sall || $_POST["search"])
	{
	  print_barre_liste($texte, $page, "reassort.php", "&sref=".$sref."&snom=".$snom."&amp;sall=".$sall."&amp;envente=".$_POST["envente"], $sortfield, $sortorder,'',$num);
	}
      else
	{
	  print_barre_liste($texte, $page, "reassort.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
	}
    }
    
    $db->free($resql);
    
    print "</table>";
    
}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
