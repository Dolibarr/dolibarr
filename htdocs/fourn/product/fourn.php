<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!
  \file       htdocs/fourn/product/fourn.php
  \ingroup    product
  \brief      Page de la fiche produit fournisseur
  \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$user->getrights('produit');

if (!$user->rights->produit->lire) accessforbidden();

llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $product = new Product($db);
      $result = $product->fetch($_GET["id"]);
      
      $fourn = new Fournisseur($db);
      $result = $fourn->fetch($_GET["id_fourn"]);
    }
  
  if ( $result )
    { 
      
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  /*
	   *  En mode visu
	   */
	  
	  $h=0;
          
	  $head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("ProductCard");
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/fourn/product/fourn.php?id=".$product->id.'&amp;id_fourn='.$_GET["id_fourn"];
	  $head[$h][1] = $langs->trans("SupplierCard");
	  $hselected = $h;
	  $h++;
	  
	  dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);
	  
	  print '<table class="border" width="100%">';
	  print "<tr>";
	  print '<td width="20%">'.$langs->trans("InternalRef").'</td><td width="40%">'.$product->ref.'</td>';
	  print '</tr>';
	  print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';
	  
	  print "<tr>";
	  print '<td width="20%">'.$langs->trans("Supplier").'</td><td width="40%">'.$fourn->nom_url.'</td>';
	  print '</tr>';
	  
	  
	  
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';
	  
	  if ($product->type == 1)
	    {
	      print '<tr><td>'.$langs->trans("Duration").'</td><td>'.$product->duration_value.'&nbsp;';
	      
	      if ($product->duration_value > 1)
		{
		  $dur=array("d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
		}
	      else
		{
		  $dur=array("d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
		}
	      print $langs->trans($dur[$product->duration_unit])."&nbsp;";
	      
	      print '</td></tr>';
	    }
	  print "</table><br>\n";

	  print '<table class="border" width="100%">';
	  print '<tr class="liste_titre"><td>';
	  print $langs->trans("Date").'</td>';
	  print '<td align="right">'.$langs->trans("Price").'</td>';
	  print '<td align="center">'.$langs->trans("Quantity").'</td>';
	  print '</tr>';

	  /*
	   * Prix
	   */
	  
	  $sql = "SELECT p.price, p.quantity,".$db->pdate("tms") ." as date_releve";
	  $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as p";
	  $sql .=" WHERE p.fk_soc = ".$fourn->id;
	  $sql .= " AND p.fk_product = ".$product->id;
	  $sql .= " ORDER BY p.quantity ASC";
	  $resql= $db->query($sql) ;
	  if ($resql)
	    {
	      $num_fournisseur = $db->num_rows($resql);
	      $i = 0;
	      $var=True;      
	      while ($i < $num_fournisseur)
		{
		  $objp = $db->fetch_object($resql);
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print '<td>'.dolibarr_print_date($objp->date_releve).'</td>';
		  print '<td align="right">'.price($objp->price).'</td>';
		  print '<td align="center">'.$objp->quantity.'</td></tr>';
		  
		  $i++;
		}
	      $db->free($resql);
	    }
	  print '</table>';
	  print "</div>\n";
	}     
     
      /*
       *
       * Fiche en mode edition
       *
       */
      if (($_GET["action"] == 'edit' || $_GET["action"] == 're-edit') && $user->rights->produit->creer)
	{

	  print_fiche_titre('Edition de la fiche '.$types[$product->type].' : '.$product->ref, "");

	  if ($mesg) {
	    print '<br><div class="error">'.$mesg.'</div><br>';
	  }
	  
	  print "<form action=\"fiche.php\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%">';
	  print "<tr>".'<td width="20%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
	  print '<td>'.$langs->trans("Label").'</td><td colspan="2"><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

	  $langs->load("bills");
	  print '<tr><td>'.$langs->trans("VATRate").'</td><td colspan="2">';
	  $html = new Form($db);
	  print $html->select_tva("tva_tx", $product->tva_tx);
	  print '</td></tr>';
	  print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
	  print '<select name="statut">';
	  if ($product->envente)
	    {
	      print '<option value="1" selected>'.$langs->trans("OnSell").'</option>';
	      print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
	    }
	  else
	    {
	      print '<option value="1">'.$langs->trans("OnSell").'</option>';
	      print '<option value="0" selected>'.$langs->trans("NotOnSell").'</option>';
	    }
	  print '</td></tr>';
	  if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
	    {
	      print "<tr>".'<td>Seuil stock</td><td colspan="2">';
	      print '<input name="seuil_stock_alerte" size="4" value="'.$product->seuil_stock_alerte.'">';
	      print '</td></tr>';
	    }
	  else
	    {
	      print '<input name="seuil_stock_alerte" type="hidden" value="0">';
	    }
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $product->description;
	  print "</textarea></td></tr>";

	  if ($product->type == 1)
	    {
	      print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="2"><input name="duration_value" size="3" maxlength="5" value="'.$product->duration_value.'">';
	      print '&nbsp; ';
	      print '<input name="duration_unit" type="radio" value="d"'.($product->duration_unit=='d'?' checked':'').'>'.$langs->trans("Day");
	      print '&nbsp; ';
	      print '<input name="duration_unit" type="radio" value="w"'.($product->duration_unit=='w'?' checked':'').'>'.$langs->trans("Week");
	      print '&nbsp; ';
	      print '<input name="duration_unit" type="radio" value="m"'.($product->duration_unit=='m'?' checked':'').'>'.$langs->trans("Month");
	      print '&nbsp; ';
	      print '<input name="duration_unit" type="radio" value="y"'.($product->duration_unit=='y'?' checked':'').'>'.$langs->trans("Year");

	      print '</td></tr>';
	    }

	  print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	}


      /* ************************************************************************** */
      /*                                                                            */ 
      /* Barre d'action                                                             */ 
      /*                                                                            */ 
      /* ************************************************************************** */
      
      print "\n<div class=\"tabsAction\">\n";
      
      if ($_GET["action"] == '')
	{
	  
	  if ( $user->rights->produit->creer)
	    {
	      print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';
	    }
	}
      
      print "\n</div>\n";
      
    }  
}
else
{
  print $langs->trans("ErrorUnknown");
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
    
