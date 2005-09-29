<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Régis Houssin        <regis.houssin@cap-networks.com>
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
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

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
  $product->note           = $_POST["note"];
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
      $product->note               = $_POST["note"];
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

// clone d'un produit
if ($_GET["action"] == 'clone' && $user->rights->produit->creer)
{
    $db->begin();

    $product = new Product($db);
    $originalId = $_GET["id"];
    if ($product->fetch($_GET["id"]) > 0)
    {
        $product->ref = "Clone ".$product->ref;
        $product->envente = 0;
        $product->id = null;

        if ($product->check())
        {
            $id = $product->create($user);
            if ($id > 0)
            {
                // $product->clone_fournisseurs($originalId, $id);

                $db->commit();

                Header("Location: fiche.php?id=$id");
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

    $facture->addline($_POST["factureid"],
    addslashes($product->libelle),
    $product->price,
    $_POST["qty"],
    $product->tva_tx,
    $product->id,
    $_POST["remise_percent"]);

    Header("Location: ../compta/facture.php?facid=".$facture->id);
    exit;
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

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
    $action = '';
    Header("Location: fiche.php?id=".$_POST["id"]);
    exit;
}



llxHeader("","",$langs->trans("ProductServiceCard"));


/*
 * Action création du produit
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer)
{
    $html = new Form($db);
    $product = new Product($db);
    if ($_error == 1)
    {
        $product = $e_product;
    }

    print '<form action="fiche.php" method="post">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

    if ($_GET["type"]==0) { $title=$langs->trans("NewProduct"); }
    if ($_GET["type"]==1) { $title=$langs->trans("NewService"); }
    print_fiche_titre($title);

    print '<table class="border" width="100%">';
    print '<tr>';
    print '<td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$product->ref.'">';
    if ($_error == 1)
    {
        print $langs->trans("RefAlreadyExists");
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
    print '<option value="0" selected="true">'.$langs->trans("NotOnSell").'</option>';
    print '</td></tr>';

    if ($_GET["type"] == 0 && $conf->stick->enabled)
    {
        print "<tr>".'<td>Seuil stock</td><td colspan="2">';
        print '<input name="seuil_stock_alerte" size="4" value="0">';
        print '</td></tr>';
    }
    else
    {
        print '<input name="seuil_stock_alerte" type="hidden" value="0">';
    }

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
    print '<textarea name="desc" rows="4" cols="50">';
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

    // Note
    print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
    print '<textarea name="note" rows="8" cols="50">';
    print "</textarea></td></tr>";

    print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
    print '</table>';
    print '</form>';
}


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
                if ($user->rights->barcode->lire)
                {
                    if ($conf->barcode->enabled)
                    {
                        $head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
                        $head[$h][1] = $langs->trans("BarCode");
                        $h++;
                    }
                }
            }

            $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Photos");
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

            if ($conf->fournisseur->enabled)
            {
                $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
                $head[$h][1] = $langs->trans("Suppliers");
                $h++;
            }

            $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
            $head[$h][1] = $langs->trans('Statistics');
            $h++;

    	    //erics: pour créer des produits composés de x 'sous' produits
    	    $head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
    	    $head[$h][1] = $langs->trans('Packs');
    	    $h++;

            $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
            $head[$h][1] = $langs->trans('Referers');
            $h++;

    		$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
    		$head[$h][1] = $langs->trans('Documents');
    		$h++;

            dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

            print($mesg);
            
            print '<table class="border" width="100%">';

            print "<tr>";

            $nblignes=6;
            if ($product->type == 0 && $conf->stock->enabled) $nblignes++;
            if ($product->type == 1) $nblignes++;

            // Reference
            print '<td width="15%">'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';

            if ($product->is_photo_available($conf->produit->dir_output))
            {
                // Photo
                print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
                $nbphoto=$product->show_photo($conf->produit->dir_output,1);
                print '</td>';
            }
            
            print '</tr>';

            // Libellé
            print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
            print '</tr>';

            // Prix
            print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td>';
            print '</tr>';

            // Statut
            print '<tr><td>'.$langs->trans("Status").'</td><td>';
            if ($product->envente) print $langs->trans("OnSell");
            else print $langs->trans("NotOnSell");
            print '</td></tr>';
            
            // TVA
            $langs->load("bills");
            print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.$product->tva_tx.' %</td></tr>';

            // Stock
            if ($product->type == 0 && $conf->stock->enabled)
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

            print "</div>\n";
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
        print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="20" value="'.$product->ref.'"></td></tr>';
        print '<td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

        $langs->load("bills");
        print '<tr><td>'.$langs->trans("VATRate").'</td><td colspan="2">';
        $html = new Form($db);
        print $html->select_tva("tva_tx", $product->tva_tx);
        print '</td></tr>';
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
        print '<select name="statut">';
        if ($product->envente)
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
        
        // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
        print '<textarea name="desc" rows="4" cols="50">';
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

        // Note
        print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="2">';
        print '<textarea name="note" rows="8" cols="50">';
        print $product->note;
        print "</textarea></td></tr>";

        print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';
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
        print '<a class="tabAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
        print $langs->trans("Order").'</a>';
    }
    
    if ( $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';

        print '<a class="tabAction" href="fiche.php?action=clone&amp;id='.$product->id.'">'.$langs->trans("CreateCopy").'</a>';
    }

}

print "\n</div><br>\n";


if ($_GET["id"] && $_GET["action"] == '' && $product->envente)
{
    $htmls = new Form($db);
    $propal = New Propal($db);

    print '<table width="100%" class="noborder">';

    // Propals
    if($user->rights->propale->creer)
    {
        $langs->load("propal");
        
        print '<tr><td width="50%" valign="top">';
        print_titre($langs->trans("AddToMyProposals")) . '</td>';
        print '<td width="50%" valign="top">';
        print_titre($langs->trans("AddToOtherProposals")) . '</td>';
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
            $num = $db->num_rows($result);
            if ($num) {
                $i = 0;
                print '<table class="noborder" width="100%">';
                $var=true;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print '<form method="POST" action="fiche.php?id='.$product->id.'">';
                    print "<tr $bc[$var]>";
                    print "<td nowrap><a href=\"../comm/propal.php?propalid=$objp->propalid\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
                    print "<td>". strftime("%d %b",$objp->dp)."</td>\n";
                    print '<input type="hidden" name="action" value="addinpropal">';
                    print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("Discount");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print " ".$product->stock_proposition;
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
                print "</table>";
            }
            else {
                print $langs->trans("NoOpenedPropals");
            }
            $db->free($result);
        }

        print '</td>';

        // Liste de "Other propals"
        print '<td width="50%" valign="top">';

        $otherprop = $propal->liste_array(1, ' <> s'.$user->id);
        if (is_array($otherprop) && sizeof($otherprop))
        {
            $var=false;
            print '<form method="POST" action="fiche.php?id='.$product->id.'">';
            print '<table class="noborder" width="100%">'.$otherprop;
            print '<input type="hidden" name="action" value="addinpropal">';
            print '<tr '.$bc[$var].'><td colspan="3">'.$langs->trans("OtherPropals").'</td><td>';
            $htmls->select_array("propalid", $otherprop);
            print '</td></tr>';
            print '<tr '.$bc[$var].'><td>'. strftime("%d %b",$objp->dp)."</td><td nowrap>\n";
            print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("Discount");
            print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
            print '</td><td align="right">';
            print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
            print '</td></tr>';
            print '</table>';
            print '</form>';
        }
        else {
            print $langs->trans("NoOtherOpenedPropals");   
        }
        print '</td>';

        print '</tr>';
    }

    // Factures
    if($user->rights->facture->creer)
    {
        $langs->load("bills");

        print '<tr><td width="50%" valign="top">';
        print_titre($langs->trans("AddToMyBills"));
        print '</td><td width="50%" valign="top">';
        print_titre($langs->trans("AddToOtherBills"));
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
            if ($num) {
                $i = 0;
                print '<table class="noborder" width="100%">';
                $var=true;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
    
                    $var=!$var;
                    print '<form method="POST" action="fiche.php?id='.$product->id.'">';
                    print "<tr $bc[$var]>";
                    print "<td nowrap><a href=\"../compta/facture.php?facid=$objp->factureid\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
                    print "<td>". strftime("%d %b",$objp->df)."</td>\n";
                    print '<input type="hidden" name="action" value="addinfacture">';
                    print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("Discount");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
                print "</table>";
            }
            else {
                print $langs->trans("NoDraftBills");   
            }
            $db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '</td><td width="50%" valign="top">';

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
            if ($num) {
                $i = 0;
                print '<table class="noborder" width="100%">';
                $var=true;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
    
                    $var=!$var;
                    print '<form method="POST" action="fiche.php?id='.$product->id.'">';
                    print "<tr $bc[$var]>";
                    print "<td><a href=\"../compta/facture.php?facid=$objp->factureid\">$objp->facnumber</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></td>\n";
                    print "<td>". strftime("%d %b",$objp->df)."</td>\n";
                    print '<input type="hidden" name="action" value="addinfacture">';
                    print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("Discount");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
                print "</table>";
            }
            else {
                print $langs->trans("NoOtherDraftBills");
            }
            $db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '</td></tr>';
    }

    print '</table>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
