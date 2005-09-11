<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/fourn/product/fiche.php
        \ingroup    product
        \brief      Page de la fiche produit
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (!$user->rights->produit->lire) accessforbidden();


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

/*
 *
 */

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
    $product->catid          = $_POST["catid"];
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
            $mesg='<div class="error">'.$product->error.'</div>';
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
      $product->description        = $_POST["desc"];
      $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
      $product->duration_value     = $_POST["duration_value"];
      $product->duration_unit      = $_POST["duration_unit"];
      
      if ($product->check())
	{
	  if ($product->update($product->id, $user) > 0)
	    {
	      $_GET["action"] = '';
	      $_GET["id"] = $_POST["id"];

	      Header("Location: fiche.php?id=".$_POST["id"]);

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

if ($_POST["action"] == 'add_fourn' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      if ($product->add_fournisseur($user, $_POST["id_fourn"], $_POST["ref_fourn"]) > 0)
	{
	  $action = '';
	  $mesg = $langs->trans("SupplierAdded");
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


// Le produit n'est pas encore chargé a ce stade
llxHeader("","",$langs->trans("CardProduct0"));

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
  print '<input type="hidden" name="catid" value="'.$_REQUEST["catid"].'">'."\n";

  if ($_GET["type"]==0) { $title=$langs->trans("NewProduct"); }
  if ($_GET["type"]==1) { $title=$langs->trans("NewService"); }
  print_fiche_titre($title);
      
  print '<table class="border" width="100%">';

  if ($mesg) print $mesg;

  if ($conf->categorie->enabled)
    {		  
      print '<tr><td>'.$langs->trans("Categorie");
      print '</td><td>';

      if (isset($_REQUEST["catid"]))
	{
	  $c = new Categorie ($db, $_REQUEST["catid"]);
	  $ways = $c->print_all_ways(' &gt; ','fourn/product/liste.php');
	  print $ways[0]."<br />\n";
	}
      print '</td></tr>';
    }

  print '<tr><td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$product->ref.'">';
  if ($_error == 1)
    {
      print $langs->trans("RefAlreadyExists");
    }
  print '</td></tr>';
  print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';
 
  if ($_GET["type"] == 0 && $conf->stock->enabled)
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
  
  print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
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

      if ( $product->id > 0 )
	{ 

	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {
	      /*
	       *  En mode visu
	       */
	      
	      $h=0;
          
	      $head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Card");
	      $hselected = $h;
	      $h++;
	      
	      if($product->type == 0)
		{
		  if ($conf->stock->enabled)
		    {
		      $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
		      $head[$h][1] = $langs->trans("Stock");
		      $h++;
		    }

		}
	      
	      $head[$h][0] = DOL_URL_ROOT."/fourn/product/photos.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Photos");	      
	      $h++;

	      //Affichage onglet Catégories
	      if ($conf->categorie->enabled){
		$head[$h][0] = DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id;
		$head[$h][1] = $langs->trans('Categories');
		$h++;
	      }

	      $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("CommercialCard");
	      $h++;

	      dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

	      if ($mesg) print($mesg);
	      
	      print '<table class="border" width="100%">';

	      if ($conf->categorie->enabled)
	      	{		  
		  print '<tr id="ways">';
		  print '<td colspan="3">';
		  $cat = new Categorie ($db);
		  $way = $cat->print_primary_way($product->id," &gt; ",'fourn/product/liste.php');
		  if ($way == "")
		    {
		      print "Ce produit n'appartient à aucune catégorie";
		    }
		  else
		    {
		      print $langs->trans("Categorie")." : ";
		      print $way;	
		    }
		  print '</td>';
		  print '</tr>';
		}
	      
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

	      $nblignefour=2;
	      if ($product->type == 0 && $conf->stock->enabled) $nblignefour++;
	      if ($product->type == 1) $nblignefour++;
		
	      print '<td valign="middle" align="center" rowspan="'.$nblignefour.'">';
	      $product->show_photo($conf->produit->dir_output,1);
	      print '</td></tr>';

          // Description
	      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';

          // Stock
	      if ($product->type == 0 && $conf->stock->enabled)
		{
		  print '<tr><td><a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$product->id.'">'.$langs->trans("Stock").'</a></td>';
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

	      // Duration
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
	      	      

          // Liste des fournisseurs
	      print '<table class="noborder" width="100%">';
	      print '<tr class="liste_titre"><td>';
	      print $langs->trans("Suppliers").'</td>';
	      print '<td>'.$langs->trans("Ref").'</td>';
	      print '<td align="right">'.$langs->trans("BuiingPrice").'</td>';
	      print '<td align="center">'.$langs->trans("Quantity").'</td>';
	      print '</tr>';

	      $sql = "SELECT s.nom, s.idp, pf.ref_fourn, pfp.price, pfp.quantity";
	      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur as pf";
	      $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON s.idp = pfp.fk_soc";
	      $sql .= " AND pfp.fk_product =".$product->id; 
	      $sql .= " WHERE pf.fk_soc = s.idp AND pf.fk_product = ".$product->id;
	      $sql .= " ORDER BY pfp.price ASC, lower(s.nom)";
	      
	      if ( $db->query($sql) )
		{
		  $num_fournisseur = $db->num_rows($resql);
		  $i = 0;
		  $var=True;      
		  while ($i < $num_fournisseur)
		    {
		      $objp = $db->fetch_object($resql);
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		      print '<td>';
		      print '<a href="fourn.php?id='.$product->id.'&amp;id_fourn='.$objp->idp.'">';
		      print img_edit($langs->trans("Edit"));
		      print '&nbsp;<a href="fourn.php?id='.$product->id.'&amp;id_fourn='.$objp->idp.'">';
		      print $objp->ref_fourn.'</a></td>';

		      print '<td align="right">';
		      print price($objp->price);
		      print '</td>';
		      print '<td align="center">'.$objp->quantity.'</td></tr>';
		      $i++;
		    }
		  $db->free();
		}
	      print '</table>';
	      print "<br></div>\n";


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
		  
		  $sql = "SELECT s.idp, s.nom, s.ville";
		  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s WHERE s.fournisseur=1";	     
		  $sql .= " ORDER BY lower(s.nom)";
		  
		  $resql = $db->query($sql);
		  if ($resql)
		    {
		      $num = $db->num_rows($resql);
		      $i = 0;		  		  
		      while ($i < $num)
			{
			  $obj = $db->fetch_object($resql);
			  print '<option value="'.$obj->idp.'">'.$obj->nom . ($obj->ville?" ($obj->ville)":"");
			  $i++;
			}
		      $db->free($resql);
		    }
		  print '</select></td></tr><tr><td>'.$langs->trans("SupplierRef").'</td>';
		  print '<td><input name="ref_fourn" size="25" value=""></td></tr>';
		  print '<tr><td colspan="2" align="center">';
		  print '<input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
		  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
		  print '</table>';
		  print '</form>';
		}	      
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
	      
	      print '<a class="butAction" href="fiche.php?id='.$product->id.'&amp;action=ajout_fourn">'.$langs->trans("AddSupplier").'</a>';
	      
	      if ($product->type == 0 && $user->rights->produit->commander && $num_fournisseur == 1)
		{
		  print '<a class="tabAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
		  print $langs->trans("Order").'</a>';
		}
	      
	      if ( $user->rights->produit->creer)
		{
		  print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';
		}
	      
	      if ($product->type == 0 && $conf->stock->enabled)
		{
		  print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$product->id.'&amp;action=correction">'.$langs->trans("CorrectStock").'</a>';
		}
	    }
	  
	  print "\n</div>\n";
	  	 	  
	}
      else
	{
	  print $langs->trans("BadId");
	}                  
    }
  else
    {
      print $langs->trans("BadId");
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
