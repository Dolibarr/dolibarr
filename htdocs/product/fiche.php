<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/product/fiche.php
        \ingroup    product
		\brief      Page de la fiche produit
		\version    $Revision$
*/

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");

$langs->load("products");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (!$user->rights->produit->lire)
{
  accessforbidden();
}


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


if ($_GET["action"] == 'fastappro')
{
  $product = new Product($db);
  $product->fetch($_GET["id"]);
  $result = $product->fastappro($user);
  Header("Location: fiche.php?id=".$_GET["id"]);
}


// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->produit->creer)
{
  $product = new Product($db);

  $product->ref            = $_POST["ref"];
  $product->libelle        = $_POST["libelle"];
  $product->price          = $_POST["price"];
  $product->tva_tx         = $_POST["tva_tx"];
  $product->type           = $_POST["type"];
  $product->envente        = $_POST["statut"];
  $product->description    = $_POST["desc"];
  $product->duration_value = $_POST["duration_value"];
  $product->duration_unit  = $_POST["duration_unit"];
  $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
 
  $e_product = $product;

  $id = $product->create($user);

  if ($id > 0)
    {
      Header("Location: fiche.php?id=$id");
    }
  else
    {
      if ($id == -3)
	{
	  $_error = 1;
	  $_GET["action"] = "create";
	  $_GET["type"] = $_POST["type"];
	}
    }
}

// Action mise a jour d'un produit ou service
if ($_POST["action"] == 'update' && 
    $_POST["cancel"] <> $langs->trans("Cancel") && 
    $user->rights->produit->creer)
{
  $product = new Product($db);
  if ($product->fetch($_POST["id"]))
    {
      $product->ref                = $_POST["ref"];
      $product->libelle            = $_POST["libelle"];
      $product->price              = $_POST["price"];
      $product->tva_tx             = $_POST["tva_tx"];
      $product->description        = $_POST["desc"];
      $product->envente            = $_POST["statut"];
      $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
      $product->duration_value = $_POST["duration_value"];
      $product->duration_unit = $_POST["duration_unit"];
      
      if ($product->check())
	{
	  if ($product->update($product->id, $user) > 0)
	    {
	      $_GET["action"] = '';
          $_GET["id"] = $_POST["id"];
	    }
	  else
	    {
	      $_GET["action"] = 're-edit';
	      $_GET["id"] = $_POST["id"];
	      $mesg = $product->mesg_error;
	    }
	}
      else
	{
	  $_GET["action"] = 're-edit';
      $_GET["id"] = $_POST["id"];
	  $mesg = $langs->trans("ErrorProductBadRefOrLabel");
	}
    }
}


if ($_POST["action"] == 'addinpropal')
{
  $propal = New Propal($db);
  $propal->fetch($_POST["propalid"]);

  $result =  $propal->insert_product($_GET["id"], $_POST["qty"], $_POST["remise_percent"]);
  if ( $result < 0)
    {
      $mesg = $langs->trans("ErrorUnknown").": $result";
    }

  Header("Location: ../comm/propal.php?propalid=".$propal->id);
}


if ($_POST["action"] == 'addinfacture' && $user->rights->facture->creer)
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);

  $facture = New Facture($db);

  $facture->fetch($_POST["factureid"]);
  $facture->addline($_POST["factureid"], 
		    addslashes($product->libelle), 
		    $product->price, 
		    $_POST["qty"], 
		    $product->tva_tx, $product->id);

  Header("Location: ../compta/facture.php?facid=".$facture->id);

}

if ($_POST["action"] == 'add_fourn' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      if ($product->add_fournisseur($user, $_POST["id_fourn"], $_POST["ref_fourn"]) > 0)
	{
	  $action = '';
	  $mesg = 'Founisseur ajouté';
	}
      else
	{
	  $action = '';
	}
    }
}
if ($_GET["action"] == 'remove_fourn')
{
  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      if ($product->remove_fournisseur($user, $_GET["id_fourn"]) > 0)
	{
	  $_GET["action"] = '';
	  $mesg = $langs->trans("SupplierRemoved");
	}
      else
	{
	  $_GET["action"] = '';
	}
    }
}


if ($_POST["action"] == 'update_price' && 
    $_POST["cancel"] <> $langs->trans("Cancel") && $user->rights->produit->creer)
{
  $product = new Product($db);

  $result = $product->fetch($_GET["id"]);

  $product->price = ereg_replace(" ","",$_POST["price"]);

  if ( $product->update_price($product->id, $user) > 0 )

    {
      $_GET["action"] = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $_GET["action"] = 'edit_price';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
    }
}


if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
  Header("Location: fiche.php?id=".$_POST["id"]);
}




llxHeader("","",$langs->trans("CardProduct".$product->type));


/*
 * Création du produit
 *
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer)
{
  $html = new Form($db);
  $nbligne=0;
  $product = new Product($db);
  if ($_error == 1)
    {
      $product = $e_product;
    }

  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";
  print '<div class="titre">';
  if ($_GET["type"]==0) { print $langs->trans("NewProduct"); }
  if ($_GET["type"]==1) { print $langs->trans("NewService"); }
  print '</div><br>'."\n";
      
  print '<table class="border" width="100%">';
  print '<tr>';
  print '<td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$product->ref.'">';
  if ($_error == 1)
    {
      print "Cette référence existe déjà";
    }
  print '</td></tr>';
  print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';
  print '<tr><td>'.$langs->trans("SellingPrice").'</td><td><input name="price" size="10" value="'.$product->price.'"></td></tr>';
 
  $langs->load("bills");
  print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
  print $html->select_tva("tva_tx",$conf->defaulttx);
  print '</td></tr>';
 
  print '<tr><td>'.$langs->trans("Status").'</td><td>';
  print '<select name="statut">';
  print '<option value="1">'.$langs->trans("OnSell").'</option>';
  print '<option value="0" selected>'.$langs->trans("NotOnSell").'</option>';
  print '</td></tr>';
  
  if ($_GET["type"] == 0 && defined("MAIN_MODULE_STOCK"))
    {
      print "<tr>".'<td>Seuil stock</td><td colspan="2">';
      print '<input name="seuil_stock_alerte" size="4" value="0">';
      print '</td></tr>';
    }
  else
    {
      print '<input name="seuil_stock_alerte" type="hidden" value="0">';
    }
  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  if ($_GET["type"] == 1)
    {
      print '<tr><td>'.$langs->trans("Duration").'</td><td><input name="duration_value" size="6" maxlength="5" value="'.$product->duree.'"> &nbsp;';
      print '<input name="duration_unit" type="radio" value="d">'.$langs->trans("Day").'&nbsp;';
      print '<input name="duration_unit" type="radio" value="w">'.$langs->trans("Week").'&nbsp;';
      print '<input name="duration_unit" type="radio" value="m">'.$langs->trans("Month").'&nbsp;';
      print '<input name="duration_unit" type="radio" value="y">'.$langs->trans("Year").'&nbsp;';
      print '</td></tr>';
    }
  
  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  /*
   * Fiche produit
   */
  if ($_GET["id"])
    {

      if ($_GET["action"] <> 're-edit')
	{
	  $product = new Product($db);
	  $result = $product->fetch($_GET["id"]);
	}

      if ( $result )
	{ 

	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {
	      /*
	       *  En mode visu
	       */
	      
	      // Zone recherche
	      print '<div class="formsearch">';
	      print '<form action="liste.php" method="post">';
	      print '<input type="hidden" name="type" value="'.$product->type.'">';
	      print $langs->trans("Ref").': <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="'.$langs->trans("Go").'"> &nbsp;';
	      print $langs->trans("Label").': <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="'.$langs->trans("Go").'">';
	      print '</form></div>';
	      
	      $h=0;
          
	      $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Card");
	      $hselected = $h;
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Price");
	      $h++;

	      if($product->type == 0)
		{
		  if ($conf->stock->enabled)
		    {
		      $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
		      $head[$h][1] = 'Stock';
		      $h++;
		    }

		  $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
		  $head[$h][1] = 'Fournisseurs';
		  $h++;

		}
	      
	      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans('Statistics');
	      $h++;


	      dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


	      print($mesg);
	      print '<table class="border" width="100%">';
	      print "<tr>";
	      print '<td width="20%">'.$langs->trans("Ref").'</td><td width="40%">'.$product->ref.'</td>';
	      print '<td width="40%">';
	      if ($product->envente)
		{
		  print $langs->trans("OnSell");
		}
	      else
		{
		  print $langs->trans("NotOnSell");
		}
	      print '</td></tr>';
	      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';
	      print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td>';
	      if ($product->type == 0)
		{
		  $nblignefour=4;
		}
	      else
		{
		  $nblignefour=4;
		} 
		
	      print '<td valign="top" rowspan="'.$nblignefour.'">';
	      print $langs->trans("Suppliers").' [<a href="fiche.php?id='.$product->id.'&amp;action=ajout_fourn">'.$langs->trans("Add").'</a>]';

	      $sql = "SELECT s.nom, s.idp";
	      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur as pf";
	      $sql .=" WHERE pf.fk_soc = s.idp AND pf.fk_product = ".$product->id;
	      $sql .= " ORDER BY lower(s.nom)";
	      
	      if ( $db->query($sql) )
		{
		  $num_fournisseur = $db->num_rows();
		  $i = 0;
		  print '<table class="noborder" width="100%">';
		  $var=True;      
		  while ($i < $num_fournisseur)
		    {
		      $objp = $db->fetch_object($i);	  
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="../fourn/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		      print '<td align="right">';
		      print '<a href="fiche.php?id='.$product->id.'&amp;action=remove_fourn&amp;id_fourn='.$objp->idp.'">';
		      print img_disable($langs->trans("Remove")).'</a></td></tr>';
		      $i++;
		    }
		  print '</table>';
		  $db->free();
		}

	      print '</td></tr>';

	      $langs->load("bills");
	      print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.$product->tva_tx.' %</td></tr>';
	      if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
		{
		  print '<tr><td><a href="stock/product.php?id='.$product->id.'">'.$langs->trans("Stock").'</a></td>';
		  if ($product->no_stock)
		    {
		      print "<td>Pas de définition de stock pour ce produit";
		    }
		  else
		    {
		      if ($product->stock_reel <= $product->seuil_stock_alerte)
			{
			  print '<td class="alerte">'.$product->stock_reel.' Seuil : '.$product->seuil_stock_alerte;
			}
		      else
			{
		      print "<td>".$product->stock_reel;
			}
		    }
		  print '</td></tr>';
		}
	      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';

	      if ($product->type == 1)
		{
		  print '<tr><td>'.$langs->trans("Duration").'</td><td>'.$product->duration_value.'&nbsp;';

		  if ($product->duration_value > 1)
  	      {
            $dur=array("d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
		  }
          else {
            $dur=array("d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
          }
          print $langs->trans($dur[$product->duration_unit])."&nbsp;";

		  print '</td></tr>';
		}
	      print "</table><br>\n";

      print "</div>\n";
    }

      
    /*
     * Edition du prix
     *
     */
    if ($_GET["action"] == 'edit_price' && $user->rights->produit->creer)
	{
	  print '<div class="titre">'.$langs->trans("NewPrice").'</div>';
	  print '<form action="fiche.php?id='.$product->id.'" method="post">';
	  print '<input type="hidden" name="action" value="update_price">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('SellingPrice').'</td><td><input name="price" size="10" value="'.price($product->price).'"></td></tr>';
	  print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	}
      
      /*
       * Ajouter un fournisseur
       *
       */
      if ($_GET["action"] == 'ajout_fourn' && $user->rights->produit->creer)
	{
	  $langs->load("suppliers");
	  
	  print_titre($langs->trans("AddSupplier"));
	  print '<form action="fiche.php?id='.$product->id.'" method="post">';
	  print '<input type="hidden" name="action" value="add_fourn">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<table class="border" width="100%"><tr>';
	  print '<td>'.$langs->trans("Suppliers").'</td><td><select name="id_fourn">';
	  
	  $sql = "SELECT s.idp, s.nom, s.ville FROM ".MAIN_DB_PREFIX."societe as s WHERE s.fournisseur=1";	     
	  $sql .= " ORDER BY lower(s.nom)";
	  
	  if ($db->query($sql))
		{
		  $num = $db->num_rows();
		  $i = 0;		  		  
		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($i);
		      print '<option value="'.$obj->idp.'">'.$obj->nom . ($obj->ville?" ($obj->ville)":"");
		      $i++;
		    }

	    }
	  print '</select></td><td>'.$langs->trans("Ref").'</td><td><input name="ref_fourn" size="25" value=""></td></tr>';
	  print '<tr><td colspan="4" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	}    
      
	}
      
    /*
     * Fiche en mode edition
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
		  print '<input name="duration_unit" type="radio" value="d"'.($product->duration_unit=='d'?' checked':'').'>'.$langs->trans("day");
		  print '&nbsp; ';
		  print '<input name="duration_unit" type="radio" value="w"'.($product->duration_unit=='w'?' checked':'').'>'.$langs->trans("week");
		  print '&nbsp; ';
		  print '<input name="duration_unit" type="radio" value="m"'.($product->duration_unit=='m'?' checked':'').'>'.$langs->trans("month");
		  print '&nbsp; ';
		  print '<input name="duration_unit" type="radio" value="y"'.($product->duration_unit=='y'?' checked':'').'>'.$langs->trans("year");

	      print '</td></tr>';
	    }

	  print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	}
    }
  else
    {
      print $langs->trans("ErrorUnknown");
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
  if ($product->type == 0 && $user->rights->produit->commander && $num_fournisseur == 1)
    {
      print '<a class="tabAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">'.$langs->trans("Commander").'</a>';
    }




  if ( $user->rights->produit->creer)
    {
      print '<a class="tabAction" href="fiche.php?action=edit_price&amp;id='.$product->id.'">'.$langs->trans("UpdatePrice").'</a>';
    }

  if ( $user->rights->produit->creer)
    {
      print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';
    }
}

if ($product->type == 0 && defined("MAIN_MODULE_STOCK"))
{
  print '<a class="tabAction" href="stock/product.php?id='.$product->id.'&amp;action=correction">Correction stock</a>';
}

print "\n</div>\n";



if ($_GET["id"] && $_GET["action"] == '' && $product->envente)
{
  $htmls = new Form($db);
  $propal = New Propal($db);

  print '<table width="100%" class="noborder">';
  if($user->rights->propale->creer)
    {
      print "<tr>".'<td width="50%" valign="top">';
      print_titre("Ajouter à ma proposition") . '</td>';

      print '<td width="50%" valign="top">';
      print_titre("Ajouter aux autres propositions") . '</td>';

      print '</tr>';
      print "<tr>".'<td width="50%" valign="top">';
      $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.ref,".$db->pdate("p.datep")." as dp";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
      $sql .=" WHERE p.fk_soc = s.idp AND p.fk_statut = 0 AND p.fk_user_author = ".$user->id;
      $sql .= " ORDER BY p.datec DESC, tms DESC";
      
      if ( $db->query($sql) )
	{
	  $num = $db->num_rows();
	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  $var=True;      
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object($i);	  
	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print "<td><a href=\"../comm/propal.php?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	      print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></TD>\n";
	      print "<td>". strftime("%d %b",$objp->dp)."</td>\n";
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinpropal">';	     
	      print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print " ".$product->stock_proposition;
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
	      print "</td>";
	      print '</form></tr>';
	      $i++;
	    }      
	  print "</table>";
	  $db->free();
	}

      print '</td>';

      if($user->rights->propale->creer)
	{
	  print '<td width="50%" valign="top">';

	  $otherprop = $propal->liste_array(1, '<>'.$user->id);
	  if (sizeof($otherprop))
	    {
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinpropal">';
	      print '<table class="border" width="100%">';
	      print "<tr>".'<td>Autres Propositions</td><td>';
	      $htmls->select_array("propalid", $otherprop);
	      print '</td><td>';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
	      print '</td></tr>';
	      print '</table></form>';
	    }
	  print '</td>';
	}
      print '</tr>';
    }

  if($user->rights->facture->creer)
    {
      $langs->load("bills");

      print "<tr>".'<td width="50%" valign="top">';
      print_titre("Ajouter à ma facture");
      print '</td><td width="50%" valign="top">';
      print_titre("Ajouter aux autres factures");
      print '</td></tr>';
      print "<tr>".'<td width="50%" valign="top">';
      $sql = "SELECT s.nom, s.idp, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
      $sql .=" WHERE f.fk_soc = s.idp AND f.fk_statut = 0 AND f.fk_user_author = ".$user->id;
      $sql .= " ORDER BY f.datec DESC, f.rowid DESC";
      
      if ( $db->query($sql) )
	{
	  $num = $db->num_rows();
	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  $var=True;      
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print "<td><a href=\"../compta/facture.php?facid=$objp->factureid\">$objp->facnumber</a></TD>\n";
	      print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></TD>\n";      	 
	      print "<td>". strftime("%d %b",$objp->df)."</td>\n";
	      print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	      print '<input type="hidden" name="action" value="addinfacture">';
	      print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
	      print '<input type="text" name="qty" size="3" value="1">&nbsp;Rem.';
	      print '<input type="text" name="remise_percent" size="3" value="0"> %';
	      print '</td><td>';
	      print '<input type="submit" value="'.$langs->trans("Add").'">';
	      print "</td>";
	      print '</form></tr>';
	      $i++;
	    }      
	  print "</table>";
	  $db->free();
	}
      else
	{
	  dolibarr_print_error($db);
	}
      print '</td><td width="50%" valign="top">';
      print '</td></tr></table>';
    }
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
