<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
   \file       htdocs/product/stats/commande_fournisseur.php
   \ingroup    product, service, commande
   \brief      Page des stats des commandes fournisseurs pour un produit
   \version    $Revision$
*/


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("orders");
$langs->load("products");
$langs->load("companies");

$mesg = '';

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="c.date_creation";


// Securite
$socid = 0;
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
else
{
  $socid = 0;
}

/*
 * Affiche fiche
 *
 */
$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
    $product = new Product($db);
    if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
    
    llxHeader("","",$langs->trans("CardProduct".$product->type));

    if ($result > 0)
    {
      /*
       *  En mode visu
       */
      $head=product_prepare_head($product, $user);
      $titre=$langs->trans("CardProduct".$product->type);
      dolibarr_fiche_head($head, 'referers', $titre);
            
      print '<table class="border" width="100%">';
      
      // Reference
      print '<tr>';
      print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="3">';
	  print $html->showrefnav($product,'ref');
      print '</td></tr>';
      
      // Libelle
      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';
      
      // Prix
      print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">';
		if ($product->price_base_type == 'TTC')
		{
			print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
		}
		else
		{
			print price($product->price).' '.$langs->trans($product->price_base_type);
		}
		print '</td></tr>';
      
      // Statut
      print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
      print $product->getLibStatut(2);
      print '</td></tr>';

      show_stats_for_company($product,$socid);
        
      print "</table>";
      print '</div>';
      
      $sql = "SELECT distinct(s.nom), s.rowid as socid, s.code_client, c.rowid, c.total_ht as amount, c.ref,";
      $sql.= " ".$db->pdate("c.date_creation")." as date, c.fk_statut as statut, c.rowid as commandeid";
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
      $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."commande_fournisseur as c, ".MAIN_DB_PREFIX."commande_fournisseurdet as d";
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
      $sql.= " WHERE c.fk_soc = s.rowid";
      $sql.= " AND d.fk_commande = c.rowid AND d.fk_product =".$product->id;
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
      if ($socid)
        {
	  $sql .= " AND c.fk_soc = ".$socid;
        }
      $sql.= " ORDER BY $sortfield $sortorder ";
      $sql.= $db->plimit($conf->liste_limit +1, $offset);
      
      $result = $db->query($sql);
      if ($result)
        {
	  $num = $db->num_rows($result);
	  
	  print_barre_liste($langs->trans("SuppliersOrders"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num);
	  
	  $i = 0;
	  print "<table class=\"noborder\" width=\"100%\">";
	  
	  print '<tr class="liste_titre">';
	  print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"c.rowid","","&amp;id=".$_GET["id"],'',$sortfield);
	  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$_GET["id"],'',$sortfield);
	  print_liste_field_titre($langs->trans("SupplierCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$_GET["id"],'',$sortfield);
	  print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"c.date_creation","","&amp;id=".$_GET["id"],'align="center"',$sortfield);
	  print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"c.amount_ht","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
	  print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"c.fk_statut","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
	  print "</tr>\n";
	  
	  $commandestatic=new Commande($db);
	  
	  if ($num > 0)
            {
	      $var=True;
	      while ($i < $num && $i < $conf->liste_limit)
                {
		  $objp = $db->fetch_object($result);
		  $var=!$var;
		  
		  print "<tr $bc[$var]>";
		  print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$objp->commandeid.'">'.img_object($langs->trans("ShowOrder"),"order").' ';
		  print $objp->ref;
		  print "</a></td>\n";
		  print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
		  print "<td>".$objp->code_client."</td>\n";
		  print "<td align=\"center\">";
		  print dolibarr_print_date($objp->date)."</td>";
		  print "<td align=\"right\">".price($objp->amount)."</td>\n";
		  print '<td align="right">'.$commandestatic->LibStatut($objp->statut,$objp->facture,5).'</td>';
		  print "</tr>\n";
		  $i++;
                }
            }
        }
      else
        {
	  dolibarr_print_error($db);
        }
      print "</table>";
      print '<br>';
      $db->free($result);
    }
}
else
{
  dolibarr_print_error();
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
