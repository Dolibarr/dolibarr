<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
	    \file       htdocs/fourn/facture/fiche.php
        \ingroup    facture
		\brief      Page des la fiche facture fournisseur
		\version    $Revision$
*/

require("./pre.inc.php");
require("./paiementfourn.class.php");
require("../../facturefourn.class.php");

$langs->load("bills");
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


if ($_GET["action"] == 'valid') 
{
  $facturefourn=new FactureFourn($db);
  $facturefourn->fetch($_GET["facid"]);
  
  $facturefourn->set_valid($user->id);
}

if ($_GET["action"] == 'payed')
{
  $facturefourn=new FactureFourn($db);
  $facturefourn->fetch($_GET["facid"]);
  
  $facturefourn->set_payed($user->id);
}

if($_GET["action"] == 'deletepaiement')
{
  $facfou = new FactureFourn($db);
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

if ($_POST["action"] == 'add')
{
  if ($_POST["facnumber"])
    {
      $datefacture = $db->idate(mktime(12, 
				       0, 
				       0, 
				       $_POST["remonth"], 
				       $_POST["reday"],
				       $_POST["reyear"])); 
      $tva = 0;
      $tva = ($_POST["tva_taux"] * $_POST["amount"]) / 100 ;
      $remise = 0;
      $total = $tva + $_POST["amount"] ;
      
      $db->begin();
      
      // Creation facture
      $facfou = new FactureFourn($db);
    
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
              $tauxtva = "tauxtva$i";
              $qty = "qty$i";
              
              if (strlen($_POST["$label"]) > 0 && $_POST["$amount"] > 0)
        	{
        	  $atleastoneline=1;
        	  $facfou->addline($_POST["$label"], $_POST["$amount"], $_POST["$tauxtva"], $_POST["$qty"], 1);
        	}
            }

          $db->commit();
	}
      else
	{
          $db->rollback();
	}

      header("Location: fiche.php?facid=$facid");
  
    }
  else 
    {
      $mesg="<div class=\"error\">Erreur: Un numéro de facture fournisseur est obligatoire.</div>";
    }
}

if ($_GET["action"] == 'del_ligne')
{
  $facfou = new FactureFourn($db,"",$_GET["facid"]);

  $facfou->deleteline($_GET["ligne_id"]);

  $_GET["action"] = "edit";
}

if ($_GET["action"] == 'add_ligne')
{
  $facfou = new FactureFourn($db,"", $_GET["facid"]);

  $facfou->addline($_POST["label"], $_POST["amount"], $_POST["tauxtva"], $_POST["qty"]);
  
  $_GET["action"] = "edit";
}


/*
 *
 */
llxHeader();

if ($mesg) { print "<br>$mesg<br>"; }


/*
 *
 * Fiche facture en mode creation
 *
 */

if ($_GET["action"] == 'create' or $_GET["action"] == 'copy')
{
  if ($_GET["action"] == 'copy')
    {
      $fac_ori = new FactureFourn($db);
      $fac_ori->fetch($_GET["facid"]);
    }
  print_titre("Saisir une facture fournisseur");
      
  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" width="100%">';
  print '<tr><td>Société</td>';

  print '<td><select name="socidp">';

  $sql = "SELECT s.nom, s.prefix_comm, s.idp";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s WHERE s.fournisseur = 1 ORDER BY s.nom ASC";

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
	      print '" SELECTED>'.$obj->nom.'</option>';
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
  print '<tr class="liste_titre"><td>&nbsp;</td><td>'.$langs->trans("Label").'</td><td align="center">P.U. HT</td><td align="center">'.$langs->trans("Qty").'</td><td align="center">'.$langs->trans("VATRate").'</td></tr>';

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
      print '<tr><td>Ligne '.$i.'</td>';
      print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
      print '<td align="center"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
      print '<td align="center"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td><td align="center">';
      $html->select_tva("tauxtva".$i);
      print '</td></tr>';
    }

  print "</table>";
  print '<p align="center"><input type="submit" value="'.$langs->trans("Save").'"></p>';
  print "</form>";
  
}
else
{
  /*
   * Fiche facture en mode edition
   *
   */
  if ($_GET["facid"] > 0)
    {

      $fac = new FactureFourn($db);
      $fac->fetch($_GET["facid"]);

      if ($_GET["action"] == "edit")
	{

	  print_titre($langs->trans("Bill").' : '.$fac->ref);
	  
	  print '<form action="fiche.php?facid='.$fac->id.'" method="post">';
	  print '<input type="hidden" name="action" value="update">';
      
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Company").'</td>';
      
	  print '<td width="20%">'.stripslashes($fac->socnom).'</td>';
	  print '<td width="60%" valign="top">'.$langs->trans("Comments").'</tr>';
      
	  print '<tr><td valign="top">'.$langs->trans("Ref").'</td><td valign="top">';
	  print '<input name="facnumber" type="text" value="'.$fac->ref.'"></td>';
      
	  print '<td rowspan="7" width="60%" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="10">';
	  print stripslashes($fac->note);
	  print '</textarea></td></tr>';
	  
	  print '<tr><td valign="top">'.$langs->trans("Label").'</td><td>';
	  print '<input size="30" name="libelle" type="text" value="'.stripslashes($fac->libelle).'"></td></tr>';      
	  print '<tr><td>'.$langs->trans("AmountTTC").'</td>';
	  print '<td valign="top">'.price($fac->total_ht).'</td></tr>';      
	  print '<tr><td>'.$langs->trans("Date").'</td><td>';
	  
	  print_date_select($fac->datep);
	  
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
	  print "<p><form action=\"fiche.php?facid=$fac->id&amp;action=add_ligne\" method=\"post\">";
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td><td align="center">P.U. HT</td><td align="center">'.$langs->trans("Qty").'</td><td align="center">'.$langs->trans("TotalHT").'</td>';
	  print '<td align="center">'.$langs->trans("VATRate").'</td>';
	  print '<td align="center">'.$langs->trans("VAT").'</td>';
	  print '<td align="right">'.$langs->trans("TotalTTC").'</td><td>&nbsp;</td></tr>';
	  for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	    {
	      print "<tr $bc[1]>".'<td>'.$fac->lignes[$i][0]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1])."</td>";
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
       * Fiche facture en mode edition
       *
       */
	  $h=0;
	  
	  $head[$h][0] = "fiche.php?facid=".$fac->id;
	  $head[$h][1] = $langs->trans("Bill").' : '.$fac->ref;
	  $hselected = $h;
	  $h++;
	  
	  dolibarr_fiche_head($head, $hselected);

	  print "<table border=\"0\" width=\"100%\">";
	  print '<tr><td width="50%" valign="top">';
	  /*
	   *   Facture
	   */
	  print '<table class="border" width="100%">';
	  print "<tr><td>".$langs->trans("Company")."</td><td colspan=\"3\"><b><a href=\"../fiche.php?socid=$fac->socidp\">$fac->socnom</a></b></td>";
	  print "<td align=\"right\"><a href=\"index.php?socid=$fac->socidp\">Autres factures</a></td>\n";
	  print "</tr>";
	  print '<tr><td>'.$langs->trans("Date")."</td><td colspan=\"4\">".dolibarr_print_date($fac->datep,"%A %d %B %Y")."</td></tr>\n";
	  print '<tr><td>'.$langs->trans("Label").'</td><td colspan="4">';
	  print $fac->libelle;
	  print "</td>";

	  $authorfullname="&nbsp;";
	  if ($fac->author)
	    {
	      $author = new User($db, $fac->author);
	      $author->fetch('');
	      $authorfullname=$author->fullname;
	    }
	  print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"4\">$authorfullname</td>";
	  print '<tr><td>'.$langs->trans("Status").'</td><td colspan="4">'.$fac->LibStatut($fac->paye,$fac->statut)."</td></tr>";
	  
	  print "<tr>".'<td>&nbsp</td><td>'.$langs->trans("TotalHT").'</td><td align="right"><b>'.price($fac->total_ht)."</b></td>";
	  print '<td align="right">'.$langs->trans("VAT").'</td><td align="right">'.price($fac->total_tva)."</td></tr>";
	  print "<tr>".'<td>&nbsp</td><td>'.$langs->trans("TotalTTC").'</td><td colspan="3" align="center">'.price($fac->total_ttc)."</td></tr>";
	  if (strlen($fac->note))
	    {
	      print '<tr><td>'.$langs->trans("Comments").'</td><td colspan="4">';
	      print nl2br(stripslashes($fac->note));
	      print '</td></tr>';
	    }
	  print "</table>";
	      
	  print "</td><td valign=\"top\">";
	  	  
	  /*
	   * Paiements
	   */
	  $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p, ".MAIN_DB_PREFIX."c_paiement as c ";
	  $sql .= " WHERE p.fk_facture_fourn = ".$fac->id." AND p.fk_paiement = c.id";

	  $result = $db->query($sql);

	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;

	      echo '<table class="noborder" width="100%">';
	      print '<tr><td colspan="2">'.$langs->trans("Paiements").'</td></tr>';
	      print "<tr class=\"liste_titre\">";
	      print '<td>'.$langs->trans("Date").'</td>';
	      print '<td>'.$langs->trans("Type").'</td>';

	      if ($fac->statut == 1 && $fac->paye == 0 && $user->societe_id == 0)
		{
		  $tdsup=' colspan="2"';
		}
	      print "<td align=\"right\">Montant</td><td$tdsup>&nbsp;</td>";
	      print "</tr>\n";
	      
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print "<td>".strftime("%d %B %Y",$objp->dp)."</td>\n";
		  print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
		  print "<td align=\"right\">".price($objp->amount)."</td><td>".MAIN_MONNAIE."</td>\n";

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
	      print "<tr $bc[1]><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td$tdsup>".MAIN_MONNAIE."</td></tr>\n";
	      

	      if ($fac->statut > 0)
		{
		  $resteapayer = abs($fac->total_ttc - $total);	      
		  print "<tr $bc[1]><td colspan=\"2\" align=\"right\">Reste à payer :</td>";
		  print '<td align="right"><b>'.price($resteapayer)."</b></td><td$tdsup>".MAIN_MONNAIE."</td>";
		  print "</tr>\n";
		}
	      
	      print "</table>";
	      $db->free();
	    } 
	  else
	    {
	      print $db->error();
	    }
	  
	  print "</td></tr>";	  
	  print "</table>";
	  /*
	   * Lignes
	   *
	   */	  
	  print '<p><table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td><td align="center">P.U. HT</td><td align="center">Qantité</td><td align="center">Total HT</td>';
	  print '<td align="center">Taux TVA</td>';
	  print '<td align="center">TVA</td>';
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
       * Barre de commande
       *
       *
       */

      print "<div class=\"tabsAction\">\n";
  
      if ($fac->statut == 0 && $user->societe_id == 0)
	{
	  if ($_GET["action"] != "edit")
	    {
	      print '<a class="tabAction" href="index.php?facid='.$fac->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	    }
	}
      elseif ($fac->statut == 1 && $fac->paye == 0  && $user->societe_id == 0)
	{
	  print '<a class="tabAction" href="paiement.php?facid='.$fac->id.'&amp;action=create">'.$langs->trans("DoPaiement").'</a>';
	}
      
      if ($fac->statut == 0 && $user->societe_id == 0)    
	{
	  if ($_GET["action"] == "edit")
	    {
	      print '<a class="tabAction" href="fiche.php?facid='.$fac->id.'">'.$langs->trans("Cancel").'</a>';
	    }
	  else
	    {
	      print '<a class="tabAction" href="fiche.php?facid='.$fac->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
	    }
	}
      
      if ($fac->statut == 1 && price($resteapayer) <= 0 && $fac->paye == 0  && $user->societe_id == 0)
	{
	  print "<a class=\"tabAction\" href=\"fiche.php?facid=$fac->id&amp;action=payed\">".$langs->trans('ClassifyPayed')."</a>";
	}
      
      if ($user->societe_id == 0)
	{
	  if ($fac->statut == 0)
	    {
	      if ($_GET["action"] <> "edit")
		print "<a class=\"tabAction\" href=\"fiche.php?facid=$fac->id&amp;action=valid\">".$langs->trans('Valid')."</a>";
	    }
	  else
	    {
	      print "<a class=\"tabAction\" href=\"fiche.php?facid=$fac->id&amp;action=copy&amp;socid=$fac->socidp\">".$langs->trans('Copy')."</a>";
	    }
	}
      
      print "</div>";
      
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
