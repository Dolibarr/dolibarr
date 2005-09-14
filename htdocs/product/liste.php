<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
	    \file       htdocs/product/liste.php
        \ingroup    produit
		\brief      Page liste des produits ou services
		\version    $Revision$
*/

require("./pre.inc.php");

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
if (! $sortorder) $sortorder="DESC";
$page = $_GET["page"];
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (isset($_POST["button_removefilter_x"]))
{
    $sref="";
    $snom="";
}


/*
 * Mode Liste
 *
 */

$title=$langs->trans("ProductsAndServices");

$sql = 'SELECT p.rowid, p.ref, p.label, p.price, p.fk_product_type, '.$db->pdate('p.tms').' as datem';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';

if ($_GET["fourn_id"] > 0)
{
    $fourn_id = $_GET["fourn_id"];
    $sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}
$sql .= " WHERE 1=1";
if ($sall)
{
    $sql .= " AND (p.ref like '%".$sall."%' OR p.label like '%".$sall."%' OR p.description like '%".$sall."%' OR p.note like '%".$sall."%')";
}
if (strlen($_GET["type"]) || strlen($_POST["type"]))
{
    $sql .= " AND p.fk_product_type = ".(strlen($_GET["type"])?$_GET["type"]:$_POST["type"]);
}
if ($sref)
{
    $sql .= " AND p.ref like '%".$sref."%'";
}
if ($snom)
{
    $sql .= " AND p.label like '%".$snom."%'";
}
if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
{
    $sql .= " AND p.envente = ".$_GET["envente"];
}
else
{
    if ($fourn_id == 0)
    {
        $sql .= " AND p.envente = 1";
    }
}
if ($fourn_id > 0)
{
    $sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = $fourn_id";
}
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1 ,$offset);
$resql = $db->query($sql) ;

if ($resql)
{
    $num = $db->num_rows($resql);

    $i = 0;

    if ($num == 1 && (isset($_POST["sall"]) or $snom or $sref))
    {
        $objp = $db->fetch_object($resql);
        Header("Location: fiche.php?id=$objp->rowid");
    }

    if (isset($_GET["envente"]) || isset($_POST["envente"]))
    {
        $envente = (isset($_GET["envente"])?$_GET["envente"]:$_POST["envente"]);
    }
    else
    {
        $envente=1;
    }

    if (! $envente)
    {
        if (isset($_GET["type"]) || isset($_POST["type"])) {
            $type=isset($_GET["type"])?$_GET["type"]:$_POST["type"];
            if ($type) { $texte = $langs->trans("ServicesNotOnSell"); }
            else { $texte = $langs->trans("ProductsNotOnSell"); }
            } else {
                $texte = $langs->trans("ProductsAndServicesNotOnSell");
            }
        }
        else
        {
            if (isset($_POST["type"]) || isset($_GET["type"])) {
                if ($type) { $texte = $langs->trans("ServicesOnSell"); }
                else { $texte = $langs->trans("ProductsOnSell"); }
                } else {
                    $texte = $langs->trans("ProductsAndServicesOnSell");
                }
            }

            llxHeader("","",$texte);

            if ($sref || $snom || $_POST["sall"] || $_POST["search"])
            {
                print_barre_liste($texte, $page, "liste.php", "&sref=".$sref."&snom=".$snom."&amp;envente=".$_POST["envente"], $sortfield, $sortorder,'',$num);
            }
            else
            {
                print_barre_liste($texte, $page, "liste.php", "&sref=$sref&snom=$snom&fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":""), $sortfield, $sortorder,'',$num);
            }

            print '<table class="liste" width="100%">';

            // Lignes des titres
            print "<tr class=\"liste_titre\">";
            print_liste_field_titre($langs->trans("Ref"),"liste.php", "p.ref","&amp;envente=$envente".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield);
            print_liste_field_titre($langs->trans("Label"),"liste.php", "p.label","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","","",$sortfield);
            print_liste_field_titre($langs->trans("DateModification"),"liste.php", "p.tms","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="center"',$sortfield);
            print_liste_field_titre($langs->trans("SellingPrice"),"liste.php", "p.price","&envente=$envente&".(isset($type)?"&amp;type=$type":"")."&fourn_id=$fourn_id&amp;snom=$snom&amp;sref=$sref","",'align="right"',$sortfield);
            print "</tr>\n";

            // Lignes des champs de filtre
            print '<form action="liste.php" method="post" name="formulaire">';
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
            print '<td class="liste_titre">';
            print '&nbsp;';
            print '</td>';
            print '<td class="liste_titre" align="right">';
            print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
            print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" alt="'.$langs->trans("RemoveFilter").'">';
            print '</td>';
            print '</tr>';
            print '</form>';


            $var=True;
            while ($i < min($num,$limit))
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]><td>";
                print "<a href=\"fiche.php?id=$objp->rowid\">";
                if ($objp->fk_product_type) print img_object($langs->trans("ShowService"),"service");
                else print img_object($langs->trans("ShowProduct"),"product");
                print '</a> ';
                print '<a href="fiche.php?id='.$objp->rowid.'">'.$objp->ref.'</a></td>';
                print '<td>'.$objp->label.'</td>';
                print '<td align="center">'.dolibarr_print_date($objp->datem).'</td>';
                print '<td align="right">'.price($objp->price).'</td>';
                print "</tr>\n";
                $i++;
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
