<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
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

/**
        \file       htdocs/fourn/facture/fiche.php
        \ingroup    facture
        \brief      Page des la fiche facture fournisseur
        \version    $Revision$
*/

require("./pre.inc.php");
require("./paiementfourn.class.php");

$langs->load("bills");
$langs->load("suppliers");
$langs->load("companies");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


$html = new Form($db);
$mesg='';
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->fournisseur->facture->valider)
{
  $facturefourn=new FactureFournisseur($db);
  $facturefourn->fetch($_GET["facid"]);
  
  $facturefourn->set_valid($user->id);

  Header("Location: fiche.php?facid=".$_GET["facid"]);
  exit;
}


if ($_GET["action"] == 'payed')
{
  $facturefourn=new FactureFournisseur($db);
  $facturefourn->fetch($_GET["facid"]);
  
  $facturefourn->set_payed($user->id);
}

if($_GET["action"] == 'deletepaiement')
{
  $facfou = new FactureFournisseur($db);
  $facfou->fetch($_GET["facid"]);
  if ($facfou->statut == 1 && $facfou->paye == 0 && $user->societe_id == 0)
    {
      $paiementfourn = new PaiementFourn($db);
      $paiementfourn->delete($_GET["paiement_id"]);
    }
}

if ($_POST["action"] == 'modif_libelle')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set libelle = '$form_libelle' WHERE rowid = ".$_GET["facid"]." ;";
  $result = $db->query( $sql);
}


if ($_POST["action"] == 'update')
{
  $datefacture = $db->idate(mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"])); 

  $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set ";
  $sql .= " facnumber='".trim($_POST["facnumber"])."'";
  $sql .= ", libelle='".trim($_POST["libelle"])."'";
  $sql .= ", note='".$_POST["note"]."'";
  $sql .= ", datef = '$datefacture'";
  $sql .= " WHERE rowid = ".$_GET['facid']." ;";
  $result = $db->query( $sql);
}
/*
 * Action création
 */
if ($_POST["action"] == 'add' && $user->rights->fournisseur->facture->creer)
{
    if ($_POST["facnumber"])
    {
        $datefacture = mktime(12,0,0,
        $_POST["remonth"],
        $_POST["reday"],
        $_POST["reyear"]);
        $tva = 0;
        $tva = ($_POST["tva_taux"] * $_POST["amount"]) / 100 ;
        $remise = 0;
        $total = $tva + $_POST["amount"] ;

        $db->begin();

        // Creation facture
        $facfou = new FactureFournisseur($db);

        $facfou->number  = $_POST["facnumber"];
        $facfou->socid   = $_POST["socidp"];
        $facfou->libelle = $_POST["libelle"];
        $facfou->date    = $datefacture;
        $facfou->note    = $_POST["note"];

        $facid = $facfou->create($user);

        // Ajout des lignes de factures
        if ($facid > 0)
        {
            for ($i = 1 ; $i < 9 ; $i++)
            {
                $label = "label$i";
                $amount = "amount$i";
                $amountttc = "amountttc$i";
                $tauxtva = "tauxtva$i";
                $qty = "qty$i";

                if (strlen($_POST[$label]) > 0 && !empty($_POST[$amount]))
                {
                    $atleastoneline=1;
                    $ret=$facfou->addline($_POST["$label"], $_POST["$amount"], $_POST["$tauxtva"], $_POST["$qty"], 1);
                    if ($ret < 0) $nberror++;
                }
                else if (strlen($_POST[$label]) > 0 && empty($_POST[$amount]))
                {
                    $ht = $_POST[$amountttc] / (1 + ($_POST[$tauxtva] / 100));
                    $atleastoneline=1;
                    $ret=$facfou->addline($_POST[$label], $ht, $_POST[$tauxtva], $_POST[$qty], 1);
                    if ($ret < 0) $nberror++;
                }
            }
            if ($nberror)
            {
                $db->rollback();
                $mesg='<div class="error">'.$facfou->error.'</div>';
                $_GET["action"]='create';
            }
            else 
            {
                $db->commit();
                header("Location: fiche.php?facid=$facid");
                exit;
            }
        }
        else
        {
            $db->rollback();
            $mesg='<div class="error">'.$facfou->error.'</div>';
            $_GET["action"]='create';
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Ref")).'</div>';
        $_GET["action"]='create';
    }
}

if ($_GET["action"] == 'del_ligne')
{
  $facfou = new FactureFournisseur($db,"",$_GET["facid"]);

  $facfou->deleteline($_GET["ligne_id"]);

  $_GET["action"] = "edit";
}

if ($_GET["action"] == 'add_ligne')
{
  $facfou = new FactureFournisseur($db,"", $_GET["facid"]);

  if (strlen($_POST["label"]) > 0 && $_POST["amount"] > 0)
    $facfou->addline($_POST["label"], $_POST["amount"], $_POST["tauxtva"], $_POST["qty"]);
  else
  {
    $ht = $_POST['amountttc'] / (1 + ($_POST['tauxtva'] / 100));
    $facfou->addline($_POST["label"], $ht, $_POST["tauxtva"], $_POST["qty"]);
  }
  $_GET["action"] = "edit";
}



/*
 *
 * Fiche facture en mode creation
 *
 */

if ($_GET["action"] == 'create' or $_GET["action"] == 'copy')
{

  llxHeader();
  
  print_titre($langs->trans("NewBill"));

  if ($mesg) { print "$mesg<br>"; }

  if ($_GET["action"] == 'copy')
    {
      $fac_ori = new FactureFournisseur($db);
      $fac_ori->fetch($_GET["facid"]);
    }
      
  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" width="100%">';
  print '<tr><td>'.$langs->trans("Company").'</td>';

  print '<td><select name="socidp">';

  $sql = "SELECT s.nom, s.prefix_comm, s.idp";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE s.fournisseur = 1";
  if ($_GET["socid"] > 0 )
    {
      $sql .= " AND s.idp =".$_GET["socid"];
    }


  $sql .= " ORDER BY s.nom ASC";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  print '<option value="'.$obj->idp;

	  if ($_GET["socid"] == $obj->idp)
	    {
	      print '" selected>'.$obj->nom.'</option>';
	    }
	  else
	    {
	      print '">'.$obj->nom.'</option>';
	    }
	  $i++;
	}
    }
  print '</select></td>';
  print '<td>'.$langs->trans("Comments").'</td></tr>';

  print '<tr><td>'.$langs->trans("Ref").'</td><td><input name="facnumber" type="text"></td>';

  print '<td rowspan="4" valign="top"><textarea name="note" wrap="soft" cols="30" rows="6"></textarea></td></tr>';
  if ($_GET["action"] == 'copy')
    {
      print '<tr><td>'.$langs->trans("Label").'</td><td><input size="30" name="libelle" value="'.$fac_ori->libelle.'" type="text"></td></tr>';
    }
  else
    {
      print '<tr><td>'.$langs->trans("Label").'</td><td><input size="30" name="libelle" type="text"></td></tr>';
    }
  print '<tr><td>'.$langs->trans("Date").'</td><td>';
  $html->select_date();
  print '</td></tr>';
  
  print '<tr><td>'.$langs->trans("Author").'</td><td>'.$user->fullname.'</td></tr>';
  print "</table><br>";

  print '<table class="border" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>&nbsp;</td><td>'.$langs->trans("Label").'</td>';
  print '<td align="center">'.$langs->trans("PriceUHT").'</td>';
  print '<td align="center">'.$langs->trans("Qty").'</td>';
  print '<td align="center">'.$langs->trans("VATRate").'</td>';
  print '<td align="center">'.$langs->trans("PriceUTTC").'</td>';
  print '</tr>';

  for ($i = 1 ; $i < 9 ; $i++)
    {
      if ($_GET["action"] == 'copy')
	{
	  $value_label = $fac_ori->lignes[$i-1][0];
	  $value_pu = $fac_ori->lignes[$i-1][1];
	  $value_qty = $fac_ori->lignes[$i-1][3];
	}
      else
	{
	  $value_qty = "1";
	}
      print '<tr><td>'.$i.'</td>';
      print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
      print '<td align="center"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
      print '<td align="center"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td>';
      print '<td align="center">';
      $html->select_tva("tauxtva".$i);
      print '</td>';
      print '<td align="center"><input type="text" size="8" name="amountttc'.$i.'" value=""></td></tr>';
    }

  print "</table>";
  print '<p align="center"><input type="submit" value="'.$langs->trans("Save").'"></p>';
  print "</form>";
  
}
else
{
  /*
   * Fiche facture en mode visu ou edition
   *
   */
  if ($_GET["facid"] > 0)
    {

      $fac = new FactureFournisseur($db);
      $fac->fetch($_GET["facid"]);

      $societe = new Fournisseur($db);

      if ( $societe->fetch($fac->socidp) )
	{
	  $addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$fac->socidp;
	  $addons[0][1] = $societe->nom;
	}
      llxHeader('','', $addons);

      if ($mesg) { print "<br>$mesg<br>"; }

      if ($_GET["action"] == "edit")
	{

	  print_titre($langs->trans("Bill").': '.$fac->ref);
	  
	  print '<form action="fiche.php?facid='.$fac->id.'" method="post">';
	  print '<input type="hidden" name="action" value="update">';
      
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Company").'</td>';
      
	  print '<td width="20%">'.stripslashes($fac->socnom).'</td>';
	  print '<td width="60%" valign="top">'.$langs->trans("Comments").'</tr>';
      
	  print '<tr><td valign="top">'.$langs->trans("Ref").'</td><td valign="top">';
	  print '<input name="facnumber" type="text" value="'.$fac->ref.'"></td>';
      
	  print '<td rowspan="8" width="60%" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="10">';
	  print stripslashes($fac->note);
	  print '</textarea></td></tr>';
	  
	  print '<tr><td valign="top">'.$langs->trans("Label").'</td><td>';
	  print '<input size="30" name="libelle" type="text" value="'.stripslashes($fac->libelle).'"></td></tr>';      

	  print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	  print '<td valign="top">'.price($fac->total_ht).'</td></tr>';      

	  print '<tr><td>'.$langs->trans("AmountTTC").'</td>';
	  print '<td valign="top">'.price($fac->total_ttc).'</td></tr>';      

	  print '<tr><td>'.$langs->trans("Date").'</td><td>';
	  $html->select_date($fac->datep);
	  print "</td></tr>";
	  
	  $authorfullname="&nbsp;";
	  if ($fac->author)
	    {
	      $author = new User($db, $fac->author);
	      $author->fetch('');
	      $authorfullname=$author->fullname;
	    }
	  print "<tr><td>".$langs->trans("Author")."</td><td>$authorfullname</td></tr>";
	  print '<tr><td>'.$langs->trans("Status").'</td><td>'.$fac->LibStatut($fac->paye,$fac->statut)."</td></tr>";
	  print "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"".$langs->trans("Save")."\"></td></tr>";
	  print "</table>";
	  print "</form>";
	  
	  /*
	   * Lignes
	   *
	   */	  
	  print '<br>';
	  
	  print "<form action=\"fiche.php?facid=$fac->id&amp;action=add_ligne\" method=\"post\">";
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td>';
	  print '<td align="center">'.$langs->trans("PriceUHT").'</td>';
	  print '<td align="center">'.$langs->trans("PriceUTTC").'</td>';
	  print '<td align="center">'.$langs->trans("Qty").'</td>';
	  print '<td align="center">'.$langs->trans("TotalHT").'</td>';
	  print '<td align="center">'.$langs->trans("VATRate").'</td>';
	  print '<td align="center">'.$langs->trans("VAT").'</td>';
	  print '<td align="right">'.$langs->trans("TotalTTC").'</td><td>&nbsp;</td></tr>';
	  for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	    {
	      print "<tr $bc[1]>".'<td>'.$fac->lignes[$i][0]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1])."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1] * (1+($fac->lignes[$i][2]/100)))."</td>";
	      print '<td align="center">'.$fac->lignes[$i][3]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][4])."</td>";
	      print '<td align="center">'.$fac->lignes[$i][2]."</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][5])."</td>";  
	      print '<td align="right">'.price($fac->lignes[$i][6])."</td>";
	      print '<td align="center">';
	      print '<a href="fiche.php?facid='.$fac->id.'&amp;action=del_ligne&amp;ligne_id='.$fac->lignes[$i][7].'">'.img_delete().'</a></td>';
	      print '</tr>';
	    }
	  
	  /* Nouvelle ligne */
	  print "<tr $bc[1]>";
	  print '<td>';
	  print '<input size="30" name="label" type="text">';
	  print '</td>';
	  print '<td align="center">';
	  print '<input size="8" name="amount" type="text">';
	  print '</td>';
	  print '<td align="center">';
	  print '<input size="8" name="amountttc" type="text">';
	  print '</td>';
	  print '<td align="center">';
	  print '<input size="2" name="qty" type="text" value="1">';
	  print '</td>';
	  print '<td align="center">-</td>';
	  print '<td align="center">';
	  $html->select_tva("tauxtva");
	  print '</td><td align="center" colspan="2">';
	  print '&nbsp;';
	  print '</td><td align="center"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	  print "</table>";
	  print "</form>";
	}
      else
	{
	  /*
	   *
	   */
	  $h=0;
	  
	  $head[$h][0] = "fiche.php?facid=".$fac->id;
	  $head[$h][1] = $langs->trans("SupplierBill").': '.$fac->ref;
	  $hselected = $h;
	  $h++;
	  
	  dolibarr_fiche_head($head, $hselected);


	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      $html->form_confirm("fiche.php?facid=$fac->id",$langs->trans("ValidateBill"),$langs->trans("ConfirmValidateBill"),"confirm_valid");
	      print '<br />';
	    }
	  
	  print "<table border=\"0\" width=\"100%\">";
	  print '<tr><td width="50%" valign="top">';

	  /*
	   *   Facture
	   */
	  print '<table class="border" width="100%">';
	  print "<tr><td>".$langs->trans("Company")."</td><td colspan=\"2\"><b><a href=\"../fiche.php?socid=$fac->socidp\">$fac->socnom</a></b></td>";
  	  print "<td align=\"right\"><a href=\"index.php?socid=$fac->socidp\">".$langs->trans("OtherBills")."</a></td>\n";
	  print "</tr>";

	  print '<tr><td>'.$langs->trans("Date")."</td><td colspan=\"3\">";
	  print dolibarr_print_date($fac->datep,"%A %e %B %Y")."</td></tr>\n";
	  print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">';
	  print $fac->libelle;
	  print '</td>';
      print '</tr>';
    
	  $authorfullname="&nbsp;";
	  if ($fac->author)
	    {
	      $author = new User($db, $fac->author);
	      $author->fetch('');
	      $authorfullname=$author->fullname;
	    }
	  print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"3\">$authorfullname</td>";
	  print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$fac->LibStatut($fac->paye,$fac->statut)."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans("TotalHT").'</td><td align="center"><b>'.price($fac->total_ht)."</b></td>";
	  print '<td align="right">'.$langs->trans("VAT").'</td><td align="center">'.price($fac->total_tva)."</td></tr>";
	  print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="3" align="center">'.price($fac->total_ttc)."</td></tr>";
	  if (strlen($fac->note))
	    {
	      print '<tr><td>'.$langs->trans("Comments").'</td><td colspan="3">';
	      print nl2br(stripslashes($fac->note));
	      print '</td></tr>';
	    }
	  print "</table>";
	      
	  print "</td><td valign=\"top\">";
	  	  

	  /*
	   * Paiements
	   */

      print '<table class="border" width="100%">';
      print '<tr><td>';

	  $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p, ".MAIN_DB_PREFIX."c_paiement as c ";
	  $sql .= " WHERE p.fk_facture_fourn = ".$fac->id." AND p.fk_paiement = c.id";

	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows($result);
	      $i = 0; $total = 0;

          print '<table class="noborder" width="100%">';
	      print '<tr><td colspan="2">'.$langs->trans("Payments").' :</td></tr>';
	      print "<tr class=\"liste_titre\">";
	      print '<td>'.$langs->trans("Date").'</td>';
	      print '<td>'.$langs->trans("Type").'</td>';

	      if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
		{
		  $tdsup=' colspan="2"';
		}
	      print "<td align=\"right\">".$langs->trans("AmountTTC")."</td><td$tdsup>&nbsp;</td>";
	      print "</tr>\n";
	      
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object($result);
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print "<td>".img_object($langs->trans("Payment"),"payment").' '.dolibarr_print_date($objp->dp)."</td>\n";
		  print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
		  print "<td align=\"right\">".price($objp->amount)."</td><td>".$conf->monnaie."</td>\n";

		  if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
		    {
		      print '<td align="center">';
		      print '<a href="fiche.php?facid='.$fac->id.'&amp;action=deletepaiement&amp;paiement_id='.$objp->rowid.'">';
		      print img_delete();
		      print '</a></td>';
		    }

		  print "</tr>";
		  $total = $total + $objp->amount;
		  $i++;
		}
	      print "<tr $bc[1]><td colspan=\"2\" align=\"right\">".$langs->trans("Total")." :</td><td align=\"right\"><b>".price($total)."</b></td><td$tdsup>".$conf->monnaie."</td></tr>\n";
	      

	      if ($fac->statut > 0)
		{
		  $resteapayer = abs($fac->total_ttc - $total);	      
		  print "<tr $bc[1]><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
		  print '<td align="right"><b>'.price($resteapayer)."</b></td><td$tdsup>".$conf->monnaie."</td>";
		  print "</tr>\n";
		}
	      
	      print "</table>";
	      $db->free();
	    } 
	  else
	    {
	      dolibarr_print_error($db);
	    }
	  print "</td></tr>";	  
	  print "</table>";
	  


	  print "</td></tr>";	  
	  print "</table>";


	  /*
	   * Lignes
	   *
	   */	  
	  print '<p><table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td>';
	  print '<td align="center">'.$langs->trans("PriceUHT").'</td>';
	  print '<td align="center">'.$langs->trans("Qty").'</td>';
	  print '<td align="center">'.$langs->trans("TotalHT").'</td>';
	  print '<td align="center">'.$langs->trans("VATRate").'</td>';
	  print '<td align="center">'.$langs->trans("VAT").'</td>';
	  print '<td align="right">'.$langs->trans("TotalTTC").'</td></tr>';
	  $var=1;
	  for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	    {
	      $var=!$var;
	      print "<tr $bc[$var]>".'<td>'.$fac->lignes[$i][0]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1])."</td>";
	      print '<td align="center">'.$fac->lignes[$i][3]."</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][4])."</td>";  
	      print '<td align="center">'.$fac->lignes[$i][2]." %</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][5])."</td>";  
	      print '<td align="right">'.price($fac->lignes[$i][6])."</td>";  

	      print '</tr>';
	    }
	  print "</table><br>";
	  
      print "</div>\n";
	}


      /*
       * Boutons actions
       */

      print "<div class=\"tabsAction\">\n";
  
      if ($fac->statut == 0 && $user->societe_id == 0)    
	{
	  if ($_GET["action"] == "edit")
	    {
	      print '<a class="butAction" href="fiche.php?facid='.$fac->id.'">'.$langs->trans("Cancel").'</a>';
	    }
	  else
	    {
	      print '<a class="butAction" href="fiche.php?facid='.$fac->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
	    }
	}
      
      if ($fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
	{
	  print '<a class="butAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans("DoPaiement").'</a>';
	}
      
      if ($fac->statut == 1 && price($resteapayer) <= 0 && $fac->paye == 0  && $user->societe_id == 0)
	{
	  print "<a class=\"butAction\" href=\"fiche.php?facid=$fac->id&amp;action=payed\">".$langs->trans('ClassifyPayed')."</a>";
	}
      
      if ($fac->statut == 0 && $user->rights->fournisseur->facture->valider)
	{
	  if ($_GET["action"] <> "edit")
	    print "<a class=\"butAction\" href=\"fiche.php?facid=$fac->id&amp;action=valid\">".$langs->trans('Valid')."</a>";
	}
      else

      if ($user->rights->fournisseur->facture->creer)
	{
	  print "<a class=\"butAction\" href=\"fiche.php?facid=$fac->id&amp;action=copy&amp;socid=$fac->socidp\">".$langs->trans('Copy')."</a>";
	}
      
      if ($_GET["action"] != "edit" && $fac->statut == 0 && $user->rights->fournisseur->facture->creer)
	{
	      print '<a class="butActionDelete" href="index.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	}
      
      print "</div>";
      
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
