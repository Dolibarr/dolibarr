<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Régis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
   \file       htdocs/product/fiche.php
   \ingroup    product
   \brief      Page de la fiche produit
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

$langs->load("bills");

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
    exit;
}


// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->produit->creer)
{
  if ($_POST["canvas"] <> '' && file_exists('canvas/product.'.$_POST["canvas"].'.class.php') )
    {
      $class = 'Product'.ucfirst($_POST["canvas"]);
      include_once('canvas/product.'.$_POST["canvas"].'.class.php');
      $product = new $class($db);
    }
  else
    {
      $product = new Product($db);
    }

    $product->ref                = $_POST["ref"];
    $product->libelle            = $_POST["libelle"];
    $product->price              = $_POST["price"];
    $product->tva_tx             = $_POST["tva_tx"];
    $product->type               = $_POST["type"];
    $product->status             = $_POST["statut"];
    $product->description        = $_POST["desc"];
    $product->note               = $_POST["note"];
    $product->duration_value     = $_POST["duration_value"];
    $product->duration_unit      = $_POST["duration_unit"];
    $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
    $product->canvas             = $_POST["canvas"];
    // MultiPrix
    if($conf->global->PRODUIT_MULTIPRICES == 1)
      {
	for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	  {
	    if($_POST["price_".$i])
	      $product->multiprices["$i"]=ereg_replace(" ","",$_POST["price_".$i]);
	    else
	      $product->multiprices["$i"] = "";
	  }
      }
    
    if ( $value != $current_lang ) $e_product = $product;
    
    // Produit spécifique    
    if ($product->canvas <> '' && file_exists('canvas/product.'.$product->canvas.'.class.php') )
      {
	$id = $product->CreateCanvas($user, $_POST);
      }
    else
      {
	$id = $product->create($user);
      }
    
    if ($id > 0)
      {
        Header("Location: fiche.php?id=$id");
        exit;
      }
    else
      {
    	$mesg='<div class="error">'.$product->error.'</div>';
        $_GET["action"] = "create";
        $_GET["type"] = $_POST["type"];
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
      if ( isset( $_POST["price"] ) )
        $product->price              = $_POST["price"];
      $product->tva_tx             = $_POST["tva_tx"];
      $product->description        = $_POST["desc"];
      $product->note               = $_POST["note"];
      $product->status             = $_POST["statut"];
      $product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
      $product->duration_value     = $_POST["duration_value"];
      $product->duration_unit      = $_POST["duration_unit"];
      $product->canvas             = $_POST["canvas"];
      
      if ($product->check())
        {
	  if ($product->update($product->id, $user) > 0)
            {
	      $_GET["action"] = '';
	      $_GET["id"] = $_POST["id"];
            }
	  else
            {
	      $_GET["action"] = 'edit';
	      $_GET["id"] = $_POST["id"];
	      $mesg = $product->error;
            }
        }
        else
        {
            $_GET["action"] = 'edit';
            $_GET["id"] = $_POST["id"];
            $mesg = $langs->trans("ErrorProductBadRefOrLabel");
        }


      // Produit spécifique
      if ($product->canvas <> '' && file_exists('canvas/product.'.$product->canvas.'.class.php') )
	{
	  $class = 'Product'.ucfirst($product->canvas);
	  include_once('canvas/product.'.$product->canvas.'.class.php');
	  	  
	  $product = new $class($db);
	  if ($product->FetchCanvas($_POST["id"]))
	    {
	      $product->UpdateCanvas($_POST);
	    }
	}      
    }
}

// clone d'un produit
if ($_GET["action"] == 'clone' && $user->rights->produit->creer)
{
    $db->begin();

    $product = new Product($db);
    $originalId = $_GET["id"];
    if ($product->fetch($_GET["id"]) > 0)
    {
        $product->ref = "Clone ".$product->ref;
        $product->status = 0;
        $product->id = null;

        if ($product->check())
        {
            $id = $product->create($user);
            if ($id > 0)
            {
                // $product->clone_fournisseurs($originalId, $id);

                $db->commit();

                Header("Location: fiche.php?id=$id");
                $db->close();
                exit;
            }
            else	if ($id == -3)
            {
                $db->rollback();

                $_error = 1;
                $_GET["action"] = "";
                dolibarr_print_error($product->db);
            }
            else
            {
                $db->rollback();

                dolibarr_print_error($product->db);
            }
        }
    }
    else
    {
        $db->rollback();

        dolibarr_print_error($product->db);
    }
}

/*
 * Suppression d'un produit/service pas encore affecté
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->produit->supprimer)
{
  $product = new Product($db);
  $product->fetch($_GET["id"]);
  $result = $product->delete($_GET["id"]);
  
  if ($result == 0)
    {
      llxHeader();
      print '<div class="ok">'.$langs->trans("ProductDeleted",$product->ref).'</div>';
      llxFooter();
      exit ;
    }
  else
    {
      $reload = 0;
      $_GET["action"]='';
    }
}


/*
 * Ajout du produit dans une propal
 */
if ($_POST["action"] == 'addinpropal')
{
  $propal = New Propal($db);
  $result=$propal->fetch($_POST["propalid"]);
  if ($result <= 0)
    {
      dolibarr_print_error($db,$propal->error);
      exit;
    }
  
  $soc = new Societe($db);
  $soc->fetch($propal->socid,$user);

  $prod = new Product($db, $_GET['id']);
  $result=$prod->fetch($_GET['id']);
  if ($result <= 0)
    {
      dolibarr_print_error($db,$prod->error);
      exit;
    }
  
    // multiprix
    if ($conf->global->PRODUIT_MULTIPRICES == 1)
      {
	$pu = $prod->multiprices[$soc->price_level];
      }
    else
      {
    	$pu=$prod->price;
      }
    
    $desc = $prod->description;
    $tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);
    
    $result = $propal->addline($propal->id,
                               $desc,
                               $pu,
                               $_POST["qty"],
                               $tva_tx,
                               $prod->id,
                               $_POST["remise_percent"]);
    if ($result > 0)
      {
	Header("Location: ../comm/propal.php?propalid=".$propal->id);
	return;
      }
    
    $mesg = $langs->trans("ErrorUnknown").": $result";
}

/*
 * Ajout du produit dans une commande
 */
if ($_POST["action"] == 'addincommande')
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);
  
  $commande = New Commande($db);
  $commande->fetch($_POST["commandeid"]);
  
  $soc = new Societe($db);
  $soc->fetch($commande->socid,$user);
  
  // multiprix
  if ($conf->global->PRODUIT_MULTIPRICES == 1)
    {
      $pu = $product->multiprices[$soc->price_level];
    }
  else
    {
      $pu=$product->price;
    }
  
  $tva_tx = get_default_tva($mysoc,$soc,$product->tva_tx);
  
  $result =  $commande->addline($commande->id,
				$product->description,
				$pu,
				$_POST["qty"],
				$tva_tx,
				$product->id,
				$_POST["remise_percent"]);
  
  Header("Location: ../commande/fiche.php?id=".$commande->id);
  exit;
}

/*
 * Ajout du produit dans une facture
 */
if ($_POST["action"] == 'addinfacture' && $user->rights->facture->creer)
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);
  
  $facture = New Facture($db);
  $facture->fetch($_POST["factureid"]);
  
  $soc = new Societe($db);
  $soc->fetch($facture->socid,$user);
  
  // multiprix
  if ($conf->global->PRODUIT_MULTIPRICES == 1)
    {
      $pu = $product->multiprices[$soc->price_level];
    }
  else
    {
      $pu=$product->price;
    }
  
  $tva_tx = get_default_tva($mysoc,$soc,$product->tva_tx);
  
  $facture->addline($facture->id,
		    $product->description,
		    $pu,
		    $_POST["qty"],
		    $tva_tx,
		    $product->id,
		    $_POST["remise_percent"]);
  
  Header("Location: ../compta/facture.php?facid=".$facture->id);
  exit;
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
  Header("Location: fiche.php?id=".$_POST["id"]);
  exit;
}

$html = new Form($db);

/*
 * Action création du produit
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer)
{
  $product = new Product($db);
  
  if ($_error == 1)
    {
      $product = $e_product;
    }
  
  llxHeader("","",$langs->trans("CardProduct".$product->type));

  if ($mesg) print "$mesg\n";
 
  if ($_GET["canvas"] == '')
    {
      print '<form action="fiche.php" method="post">';
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";
      
      if ($_GET["type"]==0) { $title=$langs->trans("NewProduct"); }
      if ($_GET["type"]==1) { $title=$langs->trans("NewService"); }
      print_fiche_titre($title);
      
      print '<table class="border" width="100%">';
      print '<tr>';
      print '<td width="20%">'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$product->ref.'">';
      if ($_error == 1)
	{
	  print $langs->trans("RefAlreadyExists");
	}
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';
      
      if($conf->global->PRODUIT_MULTIPRICES == 1)
	{
	  print '<tr><td>'.$langs->trans("SellingPrice").' 1</td><td><input name="price" size="10" value="'.$product->price.'"></td></tr>';
	  for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	    {
	      print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td><td><input name="price_'.$i.'" size="10" value="'.$product->multiprices["$i"].'"></td></tr>';
	    }
	}
      // PRIX
      else
	{
	  print '<tr><td>'.$langs->trans("SellingPrice").'</td><td><input name="price" size="10" value="'.$product->price.'"></td></tr>';
	}
      
      print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
      print $html->select_tva("tva_tx",$conf->defaulttx,$mysoc,'');
      print '</td></tr>';
      
      print '<tr><td>'.$langs->trans("Status").'</td><td>';
      print '<select class="flat" name="statut">';
      print '<option value="1">'.$langs->trans("OnSell").'</option>';
      print '<option value="0" selected="true">'.$langs->trans("NotOnSell").'</option>';
      print '</select>';
      print '</td></tr>';
      
      if ($_GET["type"] == 0 && $conf->stock->enabled)
	{
	  print '<tr><td>Seuil stock</td><td>';
	  print '<input name="seuil_stock_alerte" size="4" value="0">';
	  print '</td></tr>';
	}
      else
	{
	  print '<input name="seuil_stock_alerte" type="hidden" value="0">';
	}
      
      // Description (utilisé dans facture, propale...)
      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
      
      if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
	{
	  require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	  $doleditor=new DolEditor('desc','',160,'dolibarr_notes','',false);
	  $doleditor->Create();
	}
      else
	{
	  print '<textarea name="desc" rows="4" cols="90">';
	  print '</textarea>';
	}
      
      print "</td></tr>";
      
      if ($_GET["type"] == 1)
	{
	  print '<tr><td>'.$langs->trans("Duration").'</td><td><input name="duration_value" size="6" maxlength="5" value="'.$product->duree.'"> &nbsp;';
	  print '<input name="duration_unit" type="radio" value="d">'.$langs->trans("Day").'&nbsp;';
	  print '<input name="duration_unit" type="radio" value="w">'.$langs->trans("Week").'&nbsp;';
	  print '<input name="duration_unit" type="radio" value="m">'.$langs->trans("Month").'&nbsp;';
	  print '<input name="duration_unit" type="radio" value="y">'.$langs->trans("Year").'&nbsp;';
	  print '</td></tr>';
	}
      
      // Note (invisible sur facture, propales...)
      print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';
      if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
	{
	  require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	  $doleditor=new DolEditor('note','',200,'dolibarr_notes','',false);
	  $doleditor->Create();
	}
      else
	{
	  print '<textarea name="note" rows="8" cols="70">';
	  print '</textarea>';
	}
      print "</td></tr>";
      
      print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
      print '</table>';
      print '</form>';
    }
  else
    {
      //RODO
      $smarty->template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/';
      $smarty->display('fiche-create.tpl');
    }
}

/**
 * Fiche produit
 */
if ($_GET["id"] || $_GET["ref"])
{
  $product = new Product($db);

  if ($_GET["ref"])
    {
      $result = $product->fetch('',$_GET["ref"]);
      $_GET["id"] = $product->id;
    }
  elseif ($_GET["id"]) 
    {
      $result = $product->fetch($_GET["id"]);
    }
  
  // Gestion des produits specifiques
  if ($product->canvas <> '' && file_exists('canvas/product.'.$product->canvas.'.class.php') )
    {
      $class = 'Product'.ucfirst($product->canvas);
      include_once('canvas/product.'.$product->canvas.'.class.php');

      $product = new $class($db);
      
      $result = $product->FetchCanvas($_GET["id"]);
      
      $smarty->template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$product->canvas.'/';

      
      $product->assign_values($smarty);     
    }
  // END TODO RODO FINISH THIS PART
  
  llxHeader("","",$langs->trans("CardProduct".$product->type));
  
  if ( $result )
    {      
      if ($_GET["action"] <> 'edit' && $product->canvas <> '')
        {
	  /*
	   *  Smarty en mode visu
	   */
	  
	  $head=product_prepare_head($product);
	  $titre=$langs->trans("CardProduct".$product->type);
	  dolibarr_fiche_head($head, 'card', $titre);
	  print "\n<!-- CUT HERE -->\n";	  
	  // Confirmation de la suppression de la facture
	  if ($_GET["action"] == 'delete')
            {
	      $html = new Form($db);
	      $html->form_confirm("fiche.php?id=".$product->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete");
	      print "<br />\n";
            }

	  print($mesg);

	  print '<table class="border" width="100%">';	  
	  print "<tr>";
	  
	  $nblignes=6;
	  if ($product->type == 0 && $conf->stock->enabled) $nblignes++;
	  if ($product->type == 1) $nblignes++;
	  
	  // Reference
	  print '<td width="15%">'.$langs->trans("Ref").'</td><td width="85%">';
	  $product->load_previous_next_ref();
	  $previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
	  $next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
	  if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	  print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
	  if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
	  print '</td>';
	  
	  if ($product->is_photo_available($conf->produit->dir_output))
            {
	      // Photo
	      print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
	      $nbphoto=$product->show_photos($conf->produit->dir_output,1,1,0);
	      print '</td>';
            }
	  
	  print '</tr>';
	  
	  $smarty->display('fiche-view.tpl');
	  	  
	  // Statut
	  print '<tr><td>'.$langs->trans("Status").'</td><td>';
	  print $product->getLibStatut(2);
	  print '</td></tr>';
	  
	  // TVA
	  
	  print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.$product->tva_tx.'%</td></tr>';
	  	  
	  // Description
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';
	  
	  // Durée
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
	  
	  // Note
	  print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>'.nl2br($product->note).'</td></tr>';	  
	  print "</table>\n";	  
	  print "</div>\n<!-- CUT HERE -->\n";
        }
 

        if ($_GET["action"] <> 'edit' && $product->canvas == '')
        {
	  /*
	   *  En mode visu
	   */
	  
	  $head=product_prepare_head($product);
	  $titre=$langs->trans("CardProduct".$product->type);
	  dolibarr_fiche_head($head, 'card', $titre);
	  print "\n<!-- CUT HERE -->\n";	  
	  // Confirmation de la suppression de la facture
	  if ($_GET["action"] == 'delete')
            {
	      $html = new Form($db);
	      $html->form_confirm("fiche.php?id=".$product->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete");
	      print "<br />\n";
            }

	  print($mesg);
	  
	  print '<table class="border" width="100%">';	  
	  print "<tr>";
	  
	  $nblignes=6;
	  if ($product->type == 0 && $conf->stock->enabled) $nblignes++;
	  if ($product->type == 1) $nblignes++;
	  
	  // Reference
	  print '<td width="15%">'.$langs->trans("Ref").'</td><td width="85%">';
	  $product->load_previous_next_ref();
	  $previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
	  $next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
	  if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	  print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
	  if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
	  print '</td>';
	  
	  if ($product->is_photo_available($conf->produit->dir_output))
            {
	      // Photo
	      print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
	      $nbphoto=$product->show_photos($conf->produit->dir_output,1,1,0);
	      print '</td>';
            }
	  
	  print '</tr>';
	  
	  // Libelle
	  print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
	  print '</tr>';
	  
	  // MultiPrix
	  if($conf->global->PRODUIT_MULTIPRICES == 1)
	    {
	      print '<tr><td>'.$langs->trans("SellingPrice").' 1</td><td>'.price($product->price).'</td>';
	      print '</tr>';
	      for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
		{
		  print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td><td>'.price($product->multiprices["$i"]).'</td>';
		  print '</tr>';
		}
	    }
	  // Prix
	  else
	    {
	      print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td>';
	      print '</tr>';
	    }
	  
	  // Statut
	  print '<tr><td>'.$langs->trans("Status").'</td><td>';
	  print $product->getLibStatut(2);
	  print '</td></tr>';
	  
	  // TVA
	  
	  print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.$product->tva_tx.'%</td></tr>';
	  
	  // Stock
	  if ($product->type == 0 && $conf->stock->enabled)
            {
	      print '<tr><td>'.$langs->trans("Stock").'</td>';
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
	  
            // Description
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';
	  
	  // Durée
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
	  
	  // Note
	  print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>'.nl2br($product->note).'</td></tr>';
	  
	  print "</table>\n";
	  
	  print "</div>\n<!-- CUT HERE -->\n";
        }
    }
    
    /*
     * Fiche en mode edition
     */
    if ($_GET["action"] == 'edit' && $user->rights->produit->creer)
      {
        print_fiche_titre($langs->trans('Edit').' '.$types[$product->type].' : '.$product->ref, "");

        if ($mesg) {
            print '<br><div class="error">'.$mesg.'</div><br>';
        }

	if ( $product->canvas == '')
	  {
	    print "<!-- CUT HERE -->\n";
	    print "<form action=\"fiche.php\" method=\"post\">\n";
	    print '<input type="hidden" name="action" value="update">';
	    print '<input type="hidden" name="id" value="'.$product->id.'">';
	    print '<input type="hidden" name="canvas" value="'.$product->canvas.'">';
	    print '<table class="border" width="100%">';
	    print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
	    print '<td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

        print '<tr><td>'.$langs->trans("VATRate").'</td><td colspan="2">';
        print $html->select_tva("tva_tx", $product->tva_tx, $mysoc, '', $product->tva_tx);
        print '</td></tr>';
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
        print '<select class="flat" name="statut">';
        if ($product->status)
	  {
            print '<option value="1" selected="true">'.$langs->trans("OnSell").'</option>';
            print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
	  }
        else
	  {
            print '<option value="1">'.$langs->trans("OnSell").'</option>';
            print '<option value="0" selected="true">'.$langs->trans("NotOnSell").'</option>';
	  }
        print '</td></tr>';
        if ($product->type == 0 && $conf->stock->enabled)
	  {
            print "<tr>".'<td>Seuil stock</td><td colspan="2">';
            print '<input name="seuil_stock_alerte" size="4" value="'.$product->seuil_stock_alerte.'">';
            print '</td></tr>';
	  }
        else
	  {
	  print '<input name="seuil_stock_alerte" type="hidden" value="0">';
        }
	
    	// Description (utilisé dans facture, propale...)
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
        print "\n";
        if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
        {
	  require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	  $doleditor=new DolEditor('desc',$product->description,160,'dolibarr_notes','',false);
	  $doleditor->Create();
        }
        else
        {
	  print '<textarea name="desc" rows="4" cols="90">';
	  print $product->description;
	  print "</textarea>";
        }
       	print "</td></tr>";
        print "\n";

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

        // Note
        print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="2">';
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
	  {
	    require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	    $doleditor=new DolEditor('note',$product->note,200,'dolibarr_notes','',false);
	    $doleditor->Create();
	  }
        else
	  {
	    print '<textarea name="note" rows="8" cols="70">';
	    print $product->note;
	    print "</textarea>";
	  }
	print "</td></tr>";
        
        print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';
	print "<!-- CUT HERE -->\n";
	  }
	else
	  {
	    $smarty->display('fiche-edit.tpl');
	  }
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
    if ( $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';

        print '<a class="tabAction" href="fiche.php?action=clone&amp;id='.$product->id.'">'.$langs->trans("CreateCopy").'</a>';
    }

/*
    if ($product->type == 0 && $user->rights->commande->creer)
    {
    	$langs->load('orders');
        print '<a class="tabAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
        print $langs->trans("CreateCustomerOrder").'</a>';
    }

    if ($product->type == 0 && $user->rights->fournisseur->commande->creer)
    {
    	$langs->load('orders');
        print '<a class="tabAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
        print $langs->trans("CreateSupplierOrder").'</a>';
    }
*/

    $product_is_used = $product->verif_prod_use($product->id);
    if ($user->rights->produit->supprimer && ! $product_is_used)
    {
	      print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$product->id.'">'.$langs->trans("Delete").'</a>';
    }

}

print "\n</div><br>\n";


if ($_GET["id"] && $_GET["action"] == '' && $product->status)
{
	$propal = New Propal($db);

	print '<table width="100%" class="noborder">';


	// Propals
	if($conf->propal->enabled && $user->rights->propale->creer)
	{
		$langs->load("propal");

		print '<tr class="liste_titre"><td width="50%" valign="top">';
		print_titre($langs->trans("AddToMyProposals")) . '</td>';

		if ($user->rights->commercial->client->voir)
		{
			print '<td width="50%" valign="top">';
			print_titre($langs->trans("AddToOtherProposals")) . '</td>';
		}
		else
		{
			print '<td width="50%" valign="top">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de "Mes propals"
		print '<tr><td width="50%" valign="top">';

		$sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.ref,".$db->pdate("p.datep")." as dp";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
		$sql .=" WHERE p.fk_soc = s.idp AND p.fk_statut = 0 AND p.fk_user_author = ".$user->id;
		$sql .= " ORDER BY p.datec DESC, tms DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$var=true;
			$num = $db->num_rows($result);
			print '<table class="noborder" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print '<input type="hidden" name="action" value="addinpropal">';
					print "<a href=\"../comm/propal.php?propalid=$objp->propalid\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dolibarr_print_date($objp->dp,"%d %b")."</td>\n";
					print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
					print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
					print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
					print " ".$product->stock_proposition;
					print '</td><td align="right">';
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
					print '</td>';
					print '</tr>';
					print '</form>';
					$i++;
				}
			}
			else {
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOpenedPropals");
				print "</td></tr>";
			}
			print "</table>";
			$db->free($result);
		}

		print '</td>';

		if ($user->rights->commercial->client->voir)
		{
			// Liste de "Other propals"
			print '<td width="50%" valign="top">';

			$var=true;
			$otherprop = $propal->liste_array(1, ' <> '.$user->id);
			print '<table class="noborder" width="100%">';

			if (is_array($otherprop) && sizeof($otherprop))
			{
				$var=!$var;
				print '<form method="POST" action="fiche.php?id='.$product->id.'">';
				print '<tr '.$bc[$var].'><td colspan="3">';
				print '<input type="hidden" name="action" value="addinpropal">';
				print $langs->trans("OtherPropals").'</td><td>';
				$html->select_array("propalid", $otherprop);
				print '</td></tr>';
				print '<tr '.$bc[$var].'><td nowrap="nowrap" colspan="2">'.$langs->trans("Qty");
				print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
				print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
				print '</td><td align="right">';
				print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
				print '</td></tr>';
				print '</form>';
			}
			else
			{
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOtherOpenedPropals");
				print '</td></tr>';
			}
			print '</table>';

			print '</td>';
		}

		print '</tr>';
	}

	$commande = New Commande($db);


	// Commande
	if($conf->commande->enabled && $user->rights->commande->creer)
	{
		$langs->load("orders");

		print '<tr class="liste_titre"><td width="50%" valign="top">';
		print_titre($langs->trans("AddToMyOrders")) . '</td>';

		if ($user->rights->commercial->client->voir)
		{
			print '<td width="50%" valign="top">';
			print_titre($langs->trans("AddToOtherOrders")) . '</td>';
		}
		else
		{
			print '<td width="50%" valign="top"></td>';
		}

		print '</tr>';

		// Liste de "Mes commandes"
		print '<tr><td width="50%" valign="top">';
		$sql = "SELECT s.nom, s.idp, c.rowid as commandeid, c.ref,".$db->pdate("c.date_commande")." as dc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql .=" WHERE c.fk_soc = s.idp AND c.fk_statut = 0 AND c.fk_user_author = ".$user->id;
		$sql .= " ORDER BY c.date_creation DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$var=true;
			print '<table class="noborder" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objc = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print '<input type="hidden" name="action" value="addincommande">';
					print "<a href=\"../commande/fiche.php?id=$objc->commandeid\">".img_object($langs->trans("ShowOrder"),"order")." ".$objc->ref."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=$objc->idp\">".dolibarr_trunc($objc->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dolibarr_print_date($objc->dc,"%d %b")."</td>\n";
					print '<td><input type="hidden" name="commandeid" value="'.$objc->commandeid.'">';
					print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
					print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
					print " ".$product->stock_proposition;
					print '</td><td align="right">';
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
					print '</td>';
					print '</tr>';
					print '</form>';
					$i++;
				}
			}
			else
			{
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOpenedOrders");
				print '</td></tr>';
			}
			print "</table>";
			$db->free($result);
		}

		print '</td>';

		if ($user->rights->commercial->client->voir)
		{
			// Liste de "Other orders"
			print '<td width="50%" valign="top">';

			$var=true;
			$othercom = $commande->liste_array(1, ' <> '.$user->id);
			print '<table class="noborder" width="100%">';
			if (is_array($othercom) && sizeof($othercom))
			{
				$var=!$var;
				print '<form method="POST" action="fiche.php?id='.$product->id.'">';
				print '<tr '.$bc[$var].'><td colspan="3">';
				print '<input type="hidden" name="action" value="addincommande">';
				print $langs->trans("OtherOrders").'</td><td>';
				$html->select_array("commandeid", $othercom);
				print '</td></tr>';
				print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("Qty");
				print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
				print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
				print '</td><td align="right">';
				print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
				print '</td></tr>';
				print '</form>';
			}
			else
			{
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOtherOpenedOrders");
				print '</td></tr>';
			}
			print '</table>';
		}
		print '</td>';

		print '</tr>';
	}

	// Factures
	if($conf->facture->enabled && $user->rights->facture->creer)
	{


		print '<tr class="liste_titre"><td width="50%" valign="top">';
		print_titre($langs->trans("AddToMyBills"));

		if ($user->rights->commercial->client->voir)
		{
			print '</td><td width="50%" valign="top">';
			print_titre($langs->trans("AddToOtherBills"));
		}
		else
		{
			print '<td width="50%" valign="top"></td>';
		}

		print '</td></tr>';

		// Liste de Mes factures
		print '<tr><td width="50%" valign="top">';
		$sql = "SELECT s.nom, s.idp, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
		$sql .=" WHERE f.fk_soc = s.idp AND f.fk_statut = 0 AND f.fk_user_author = ".$user->id;
		$sql .= " ORDER BY f.datec DESC, f.rowid DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$var=true;
			print '<table class="noborder" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print '<input type="hidden" name="action" value="addinfacture">';
					print "<a href=\"../compta/facture.php?facid=$objp->factureid\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dolibarr_print_date($objp->df,"%d %b")."</td>\n";
					print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
					print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
					print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
					print '</td><td align="right">';
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
					print '</td>';
					print '</tr>';
					print '</form>';
					$i++;
				}
			}
			else {
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoDraftBills");
				print '</td></tr>';
			}
			print "</table>";
			$db->free($result);
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</td>';

		if ($user->rights->commercial->client->voir)
		{
			print '<td width="50%" valign="top">';

			// Liste de Autres factures
			$var=true;

			$sql = "SELECT s.nom, s.idp, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
			$sql .=" WHERE f.fk_soc = s.idp AND f.fk_statut = 0 AND f.fk_user_author <> ".$user->id;
			$sql .= " ORDER BY f.datec DESC, f.rowid DESC";

			$result=$db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$var=true;
				print '<table class="noborder" width="100%">';
				if ($num) {
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($result);

						$var=!$var;
						print '<form method="POST" action="fiche.php?id='.$product->id.'">';
						print "<tr $bc[$var]>";
						print "<td><a href=\"../compta/facture.php?facid=$objp->factureid\">$objp->facnumber</a></td>\n";
						print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></td>\n";
						print "<td colspan=\"2\">".$langs->trans("Qty");
						print '<input type="hidden" name="action" value="addinfacture">';
						print "</td>";
						print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
						print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
						print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
						print '</td><td align="right">';
						print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
						print '</td>';
						print '</tr>';
						print '</form>';
						$i++;
					}
				}
				else {
					print "<tr ".$bc[!$var]."><td>";
					print $langs->trans("NoOtherDraftBills");
					print '</td></tr>';
				}
				print "</table>";
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}
		}

		print '</td></tr>';
	}

	print '</table>';
	
	print '<br>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
