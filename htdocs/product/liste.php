<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
if (! $sortfield) $sortfield="p.ref";
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

if ($_GET["canvas"] <> '' && file_exists('canvas/product.'.$_GET["canvas"].'.class.php') )
{
  $class = 'Product'.ucfirst($_GET["canvas"]);
  include_once('canvas/product.'.$_GET["canvas"].'.class.php');

  $object = new $class($db);
  $object->LoadListDatas($limit, $offset, $sortfield, $sortorder);
}
else
{
  $title=$langs->trans("ProductsAndServices");

  if (isset($_GET["type"]) || isset($_POST["type"]))
    {
    	if ($type==1) { $texte = $langs->trans("Services"); }
		else { $texte = $langs->trans("Products"); }
    } else {
      $texte = $langs->trans("ProductsAndServices");
    }
}



$sql = 'SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.price_base_type,';
$sql.= ' p.fk_product_type, '.$db->pdate('p.tms').' as datem,';
$sql.= ' p.duration, p.envente as statut, p.seuil_stock_alerte';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
if ($catid || ($conf->categorie->enabled && !$user->rights->categorie->voir))
{
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
}

if ($_GET["fourn_id"] > 0)
{
  $fourn_id = $_GET["fourn_id"];
  $sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}
$sql .= " WHERE 1=1";
if ($sall)
{
  $sql .= " AND (p.ref like '%".addslashes($sall)."%' OR p.label like '%".addslashes($sall)."%' OR p.description like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%')";
}
# if the type is not 1, we show all products (type = 0,2,3)
if (strlen($_GET["type"]) || strlen($_POST["type"]))
{
  if ($type==1) {
     $sql .= " AND p.fk_product_type = '1'";
  } else {
     $sql .= " AND p.fk_product_type <> '1'";
  }
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
if (isset($_GET["canvas"]) && strlen($_GET["canvas"]) > 0)
{
  $sql .= " AND p.canvas = '".mysql_escape_string($_GET["canvas"])."'";
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
     
  llxHeader("","",$texte);
  
  if ($sref || $snom || $sall || $_POST["search"])
    {
      print_barre_liste($texte, $page, "liste.php", "&amp;sref=".$sref."&amp;snom=".$snom."&amp;sall=".$sall."&amp;envente=".$_POST["envente"], $sortfield, $sortorder,'',$num);
    }
  else
    {
      print_barre_liste($texte, $page, "liste.php", "&amp;sref=$sref&amp;snom=$snom&amp;fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
    }
  
  if (isset($catid))
    {
      print "<div id='ways'>";
      $c = new Categorie ($db, $catid);
      $ways = $c->print_all_ways(' &gt; ','product/liste.php');
      print " &gt; ".$ways[0]."<br />\n";
      print "</div><br />";
    }

  $smarty->template_dir = DOL_DOCUMENT_ROOT;

  if ($_GET["canvas"] <> '' && file_exists($smarty->template_dir . '/product/canvas/'.$_GET["canvas"].'/liste.tpl') )
    {
      $smarty->assign('datas', $object->list_datas);
	  $smarty->assign('url_root', $dolibarr_main_url_root);
	  $smarty->assign('theme', $conf->theme);
	  $smarty->assign('langs', $langs);
      $smarty->display('product/canvas/'.$_GET["canvas"].'/liste.tpl');
    }
  else
    {
      print '<form action="liste.php" method="post" name="formulaire">';
      print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
      print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
      print '<input type="hidden" name="type" value="'.$type.'">';

      print '<table class="liste" width="100%">';
      
      // Lignes des titres
      print "<tr class=\"liste_titre\">";
      print_liste_field_titre($langs->trans("Ref"),"liste.php", "p.ref","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield,$sortorder);
      print_liste_field_titre($langs->trans("Label"),"liste.php", "p.label","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield,$sortorder);
      print_liste_field_titre($langs->trans("DateModification"),"liste.php", "p.tms","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="center"',$sortfield,$sortorder);
      if ($conf->service->enabled && $type != 0) print_liste_field_titre($langs->trans("Duration"),"liste.php", "p.duration","&amp;envente=$envente&".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="center"',$sortfield,$sortorder);
      print_liste_field_titre($langs->trans("SellingPrice"),"liste.php", "p.price","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
      if ($conf->stock->enabled && $user->rights->stock->lire && $type != 1) print '<td class="liste_titre" align="right">'.$langs->trans("Stock").'</td>';
      print_liste_field_titre($langs->trans("Status"),"liste.php", "p.envente","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&amp;fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield,$sortorder);
      print "</tr>\n";
      
      // Lignes des champs de filtre
      print '<tr class="liste_titre">';
      print '<td class="liste_titre" align="left">';
      print '<input class="flat" type="text" name="sref" value="'.$sref.'">';
      print '</td>';
      print '<td class="liste_titre" align="left">';
      print '<input class="flat" type="text" name="snom" value="'.$snom.'">';
      print '</td>';
      print '<td class="liste_titre">';
      print '&nbsp;';
      print '</td>';
      if ($conf->service->enabled && $type != 0)
      {
      	print '<td class="liste_titre">';
      	print '&nbsp;';
      	print '</td>';
      }
      print '<td class="liste_titre">';
      print '&nbsp;';
      print '</td>';
      if ($conf->stock->enabled && $user->rights->stock->lire && $type != 1)
      {
      	print '<td class="liste_titre">';
      	print '&nbsp;';
      	print '</td>';
      }
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
	  print '<tr '.$bc[$var].'>';
	
		// Ref
	  print '<td nowrap="nowrap">';
	  print "<a href=\"fiche.php?id=$objp->rowid\">";
	  if ($objp->fk_product_type==1)
	  	print img_object($langs->trans("ShowService"),"service");
	  else
	  	print img_object($langs->trans("ShowProduct"),"product");
	  print '</a> ';
	  print '<a href="fiche.php?id='.$objp->rowid.'">'.$objp->ref."</a></td>\n";

	  // Label
	  print '<td>'.$objp->label.'</td>';
	  
	  // Date
	  print '<td align="center">'.dolibarr_print_date($objp->datem,'day')."</td>\n";

	  // Duration
	  if ($conf->service->enabled && $type != 0) 
	  {
	  	print '<td align="center">';
	    if (eregi('([0-9]+)y',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationYear");
	    elseif (eregi('([0-9]+)m',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationMonth");
	    elseif (eregi('([0-9]+)d',$objp->duration,$regs)) print $regs[1].' '.$langs->trans("DurationDay");
	    else print $objp->duration;
	    print '</td>';
	  }
	  
	  // Price
	  print '<td align="right">';
	  if ($objp->price_base_type == 'TTC') print price($objp->price_ttc).' '.$langs->trans("TTC");
	  else print price($objp->price).' '.$langs->trans("HT");
	  
	  print '</td>';
	  
		// Affichage du stock
		if ($conf->stock->enabled && $user->rights->stock->lire && $type != 1)
		{
			if ($objp->fk_product_type != 1)
			{
				$product_static->id = $objp->rowid;
				$product_static->load_stock();
				if ($product_static->stock_reel < $objp->seuil_stock_alerte)
				{
					print '<td align="right">'.$product_static->stock_reel.' '.img_warning($langs->trans("StockTooLow")).'</td>';
				}
				else
				{
					print '<td align="right">'.$product_static->stock_reel.'</td>';
				}
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
		}
	  
	  // Statut
	  print '<td align="right" nowrap="nowrap">'.$product_static->LibStatut($objp->statut,5).'</td>';

	  print "</tr>\n";
	  $i++;
	}
      
      if ($num > $conf->liste_limit)
	{
	  if ($sref || $snom || $sall || $_POST["search"])
	    {
	      print_barre_liste($texte, $page, "liste.php", "&amp;sref=".$sref."&amp;snom=".$snom."&amp;sall=".$sall."&amp;envente=".$_POST["envente"], $sortfield, $sortorder,'',$num);
	    }
	  else
	    {
	      print_barre_liste($texte, $page, "liste.php", "&amp;sref=$sref&amp;snom=$snom&amp;fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
	    }
	}
      
      $db->free($resql);
      
      print "</table>";
      print '</form>';    
    }
}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
