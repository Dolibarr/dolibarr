<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$html = new Form($db);

if ($action == 'valid') 
{
  $sql = "UPDATE llx_facture_fourn set fk_statut = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'payed')
{
  $sql = "UPDATE llx_facture_fourn set paye = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'update')
{

  $tva = ($HTTP_POST_VARS["tauxtva"] * $HTTP_POST_VARS["amount"]) / 100 ;
  $remise = 0;
  $total = $tva + $amount ;

  $datefacture = $db->idate(mktime(12, 0 , 0, $HTTP_POST_VARS["remonth"], $HTTP_POST_VARS["reday"], $HTTP_POST_VARS["reyear"])); 

  $sql = "UPDATE llx_facture_fourn set ";
  $sql .= " libelle='".$HTTP_POST_VARS["libelle"]."'";
  $sql .= ", note='".$HTTP_POST_VARS["note"]."'";
  $sql .= ", datef = '$datefacture'";
  $sql .= ", amount=".$HTTP_POST_VARS["amount"];
  $sql .= ", total = $total";
  $sql .= ", tva = $tva";
  $sql .= " WHERE rowid = $facid ;";
  $result = $db->query( $sql);

}

if ($action == 'add')
{
  $datefacture = $db->idate(mktime(12, 
				   0, 
				   0, 
				   $HTTP_POST_VARS["remonth"], 
				   $HTTP_POST_VARS["reday"],
				   $HTTP_POST_VARS["reyear"])); 
  $tva = 0;
  $tva = ($tva_taux * $amount) / 100 ;
  $remise = 0;
  $total = $tva + $amount ;
  
  $facfou = new FactureFourn($db);

  $facfou->number  = $HTTP_POST_VARS["facnumber"];
  $facfou->socid   = $HTTP_POST_VARS["socidp"];
  $facfou->libelle = $HTTP_POST_VARS["libelle"];
  $facfou->date    = $datefacture;
  $facfou->note    = $HTTP_POST_VARS["note"];

  for ($i = 1 ; $i < 5 ; $i++)
    {
      $label = "label$i";
      $amount = "amount$i"; 
      $tauxtva = "tauxtva$i";
      $qty = "qty$i";
      
      if (strlen($$label))
	{
	  // print "Ajour ligne $i " . $$label . " " . $$amount . " " . $$tauxtva ; // DEBUG
	  $facfou->add_ligne($$label, $$amount, $$tauxtva, $$qty);
	}
    }
  $facid = $facfou->create($user);

}

if ($action == 'del_ligne')
{
  $facfou = new FactureFourn($db,"",$facid);

  if ($facfou->delete_ligne($ligne_id))
    {
      $action="edit";
    }
}

if ($action == 'add_ligne')
{
  $facfou = new FactureFourn($db,"", $facid);

  $facfou->add_ligne($HTTP_POST_VARS["label"],
		     $HTTP_POST_VARS["amount"], 
		     $HTTP_POST_VARS["tauxtva"], 
		     $HTTP_POST_VARS["qty"],
		     1);
  
  $action="edit";
}


/*
 *
 */
llxHeader();
/*
 *
 * Mode creation
 *
 */

if ($action == 'create')
{
  print_titre("Saisir une facture");

  print '<form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" cellspacing="0" cellpadding="3" border="1" width="100%">';
  print '<tr><td>Société :</td>';

  print '<td><select name="socidp">';

  $sql = "SELECT s.nom, s.prefix_comm, s.idp";
  $sql .= " FROM llx_societe as s WHERE s.fournisseur = 1 ORDER BY s.nom ASC";

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      while ($i < $num)
	{
	  $obj = $db->fetch_object($i);
	  print '<option value="'.$obj->idp;

	  if ($socid == $obj->idp)
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
  print "<td>Commentaires :</td></tr>";

  print '<tr><td>Numéro :</td><td><input name="facnumber" type="text"></td>';

  print '<td rowspan="4" valign="top"><textarea name="note" wrap="soft" cols="30" rows="6"></textarea></td></tr>';

  print "<tr>".'<td>Libellé :</td><td><input size="30" name="libelle" type="text"></td></tr>';

  print "<tr>".'<td>Date :</td><td>';
  $html->select_date();
  print '</td></tr>';
  
  print '<tr><td>Auteur :</td><td>'.$user->fullname.'</td></tr>';
  print "</table><br>";

  print '<table cellspacing="0" cellpadding="3" border="1" width="100%">';
  print "<tr $bc[1]>".'<td>&nbsp;</td><td>Libellé</td><td align="center">P.U.</td><td align="center">Qty</td><td align="center">Tx TVA</td></tr>';

  print "<tr $bc[1]>".'<td>Ligne 1 :</td>';
  print '<td><input size="30" name="label1" type="text"></td>';
  print '<td align="center"><input type="text" size="8" name="amount1"></td>';
  print '<td align="center"><input type="text" size="3" name="qty1" value="1"></td><td align="center">';
  $html->select_tva("tauxtva1");
  print '</td></tr>';

  print "<tr $bc[1]>".'<td>Ligne 2 :</td>';
  print '<td><input size="30" name="label2" type="text"></td>';
  print '<td align="center"><input type="text" size="8" name="amount2"></td>';
  print '<td align="center"><input type="text" size="3" name="qty2" value="1"></td><td align="center">';
  $html->select_tva("tauxtva2");
  print '</td></tr>';

  print "<tr $bc[1]>".'<td>Ligne 3 :</td>';
  print '<td><input size="30" name="label3" type="text"></td>';
  print '<td align="center"><input type="text" size="8" name="amount3"></td>';
  print '<td align="center"><input type="text" size="3" name="qty3" value="1"></td><td align="center">';
  $html->select_tva("tauxtva3");
  print '</td></tr>';

  print "<tr $bc[1]>".'<td>Ligne 4 :</td>';
  print '<td><input size="30" name="label4" type="text"></td>';
  print '<td align="center"><input type="text" size="8" name="amount4"></td>';
  print '<td align="center"><input type="text" size="3" name="qty4" value="1"></td><td align="center">';
  $html->select_tva("tauxtva4");
  print '</td></tr>';

  print "</table>";
  print '<p align="center"><input type="submit" value="Enregistrer"></p>';
  print "</form>";
  
}
else
{
  if ($facid > 0)
    {
      $fac = new FactureFourn($db);
      $fac->fetch($facid);

      $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, f.tva, f.total, ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.note, f.libelle, f.rowid";
      $sql .= " FROM llx_societe as s,llx_facture_fourn as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

      $result = $db->query( $sql);
      
      if ($result)
	{
	  $num = $db->num_rows();
	  if ($num)
	    {
	      $obj = $db->fetch_object( $i);    
	    }
	  $db->free();
	}
      else
	{
	  print $db->error();
	}

      print_titre ('Facture : '.$obj->facnumber);

      /*
       * Edition
       *
       *
       */
      if ($action == "edit")
	{
	  print "<form action=\"$PHP_SELF?facid=$obj->rowid\" method=\"post\">";
	  print '<input type="hidden" name="action" value="update">';
    
	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print "<tr $bc[1]><td width=\"20%\">Société :</td>";
	
	  print '<td width="20%">'.stripslashes($obj->socnom);
	  print '</td>';
	  print '<td width="60%" valign="top">Commentaires :</tr>';
	
	  print "<tr $bc[1]>".'<td valign="top">Numéro :</td><td valign="top">';
	  print '<input name="facnumber" type="text" value="'.$obj->facnumber.'"></td>';
	
	  print '<td rowspan="8" width="60%" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="10">';
	  print stripslashes($obj->note);
	  print '</textarea></td></tr>';

	  print "<tr $bc[1]>".'<td valign="top">Libellé :</td><td>';
	  print '<input size="30" name="libelle" type="text" value="'.stripslashes($obj->libelle).'"></td></tr>';
    
	  print "<tr $bc[1]>".'<td>Montant HT :</td>';
	  print '<td valign="top">'.price($fac->total_ht).'</td></tr>';
        
	  print "<tr $bc[1]><td>Date :</td><td>";

	  print_date_select($obj->df);

	  print "</td></tr>";
    
	  print "<tr $bc[1]><td>Auteur :</td><td>".'&nbsp;'."</td></tr>";
	  print "<tr $bc[1]><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Enregistrer\"></td></tr>";
	  print "</table>";
	  print "</form>";

	  /*
	   * Lignes
	   *
	   */	  
	  print "<p><form action=\"$PHP_SELF?facid=$obj->rowid&amp;action=add_ligne\" method=\"post\">";
	  print '<table border="1" cellspacing="0" cellpadding="2" width="100%">';
	  print '<tr class="liste_titre"><td class="small">Libellé</td><td align="center" class="small">P.U. HT</td><td align="center" class="small">Qty</td><td align="center" class="small">Total HT</td>';
	  print '<td align="center" class="small">Taux TVA</td>';
	  print '<td align="center" class="small">TVA</td>';
	  print '<td align="right" class="small">Total TTC</td><td>&nbsp;</td></tr>';
	  for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	    {
	      print "<tr $bc[1]>".'<td>'.$fac->lignes[$i][0]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1])."</td>";
	      print '<td align="center">'.$fac->lignes[$i][3]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][4])."</td>";
	      print '<td align="center">'.$fac->lignes[$i][2]."</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][5])."</td>";  
	      print '<td align="right">'.price($fac->lignes[$i][6])."</td>";
	      print '<td align="center">[';
	      print '<a href="fiche.php?facid='.$facid.'&amp;action=del_ligne&amp;ligne_id='.$fac->lignes[$i][7].'">Supprimer</a>]</td>';
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
	  print '<input type="submit" value="Ajouter">';
	  print '</td><td>&nbsp;</td></tr>';
	  print "</table>";
	  print "</form>";
	}
      else
	{
	  /*
	   * Affichage
	   *
	   *
	   */
	  
	  print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
	  print '<tr><td width="50%" valign="top">';
	  /*
	   *   Facture
	   */
	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print "<tr $bc[1]><td>Société</td><td colspan=\"3\"><b><a href=\"../fiche.php?socid=$obj->socidp\">$obj->socnom</a></b></td>";
	  print "<td align=\"right\"><a href=\"index.php?socid=$obj->socidp\">Autres factures</a></td>\n";
	  print "</tr>";
	  print "<tr $bc[1]><td>Date</td><td colspan=\"4\">".strftime("%A %d %B %Y",$obj->df)."</td></tr>\n";
	  print "<tr $bc[1]><td>Libelle</td><td colspan=\"4\">$obj->libelle</td>";
	  print "<tr $bc[1]><td>Auteur</td><td colspan=\"4\">$fac->author&nbsp;</td>";
	  
	  print "<tr $bc[1]>".'<td>&nbsp</td><td>Total HT</td><td align="right"><b>'.price($fac->total_ht)."</b></td>";
	  print '<td align="right">TVA</td><td align="right">'.price($fac->total_tva)."</td></tr>";
	  print "<tr $bc[1]>".'<td>&nbsp</td><td>Total TTC</td><td colspan="3" align="center">'.price($fac->total_ttc)."</td></tr>";
	  if (strlen($obj->note))
	    {
	      print "<tr $bc[1]>".'<td>Commentaires</td><td colspan="4">';
	      print nl2br(stripslashes($obj->note));
	      print '</td></tr>';
	    }
	  print "</table>";
	      
	  print "</td><td valign=\"top\">";
	  	  
	  $_MONNAIE="euros";

	  /*
	   * Paiements
	   */
	  $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
	  $sql .= " FROM llx_paiementfourn as p, c_paiement as c ";
	  $sql .= " WHERE p.fk_facture_fourn = ".$fac->id." AND p.fk_paiement = c.id";
	  
	  $result = $db->query($sql);

	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;

	      echo '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	      print "<TR class=\"liste_titre\">";
	      print "<td>Date</td>";
	      print "<td>Type</td>";
	      print "<td align=\"right\">Montant</TD><td>&nbsp;</td>";
	      print "</TR>\n";
	      
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object( $i);
		  $var=!$var;
		  print "<TR $bc[$var]>";
		  print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
		  print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
		  print "<TD align=\"right\">".price($objp->amount)."</TD><td>$_MONNAIE</td>\n";
		  print "</tr>";
		  $total = $total + $objp->amount;
		  $i++;
		}
	      print "<tr $bc[1]><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>$_MONNAIE</td></tr>\n";
	      
	      $resteapayer = $fac->total_ttc - $total;
	      
	      print "<tr $bc[1]><td colspan=\"2\" align=\"right\">Reste a payer :</td>";
	      print '<td align="right"><b>'.price($resteapayer)."</b></td><td>$_MONNAIE</td></tr>\n";
	      
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
	  print '<tr class="liste_titre"><td class="small">Libellé</td><td align="center" class="small">P.U. HT</td><td align="center" class="small">Qty</td><td align="center" class="small">Total HT</td>';
	  print '<td align="center" class="small">Taux TVA</td>';
	  print '<td align="center" class="small">TVA</td>';
	  print '<td align="right" class="small">Total TTC</td></tr>';
	  for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	    {
	      print "<tr $bc[1]>".'<td>'.$fac->lignes[$i][0]."</td>";
	      print '<td align="center">'.price($fac->lignes[$i][1])."</td>";
	      print '<td align="center">'.$fac->lignes[$i][3]."</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][4])."</td>";  
	      print '<td align="center">'.$fac->lignes[$i][2]."</td>";  
	      print '<td align="center">'.price($fac->lignes[$i][5])."</td>";  
	      print '<td align="right">'.price($fac->lignes[$i][6])."</td>";  

	      print '</tr>';
	    }
	  print "</table>";
	  
	}

      /*
       * Barre de commande
       *
       *
       */

      print '<p><table id="actions" width="100%" cellspacing="0" cellpadding="4"><tr>';
  
      if ($obj->statut == 0 && $user->societe_id == 0)
	{
	  print '<td align="center" width="20%">[<a href="index.php?facid='.$facid.'&amp;action=delete">Supprimer</a>]</td>';
	}
      elseif ($obj->statut == 1 && $obj->paye == 0  && $user->societe_id == 0)
	{
	  print '<td align="center" width="20%">[<a href="paiement.php?facid='.$fac->id.'&amp;action=create">Emmettre un paiement</a>]</td>';
	}
      else
	{
	  print '<td align="center" width="20%">-</td>';
	} 

      if ($obj->statut == 0 && $user->societe_id == 0)    
	{
	  print '<td align="center" width="20%">[<a href="fiche.php?facid='.$obj->rowid.'&amp;action=edit">Editer</a>]</td>';
	}
      else
	{
	  print '<td align="center" width="20%">-</td>';
	}
 
      if ($obj->statut == 1 && abs($resteapayer == 0) && $obj->paye == 0  && $user->societe_id == 0)
	{
	  print "<td align=\"center\" width=\"20%\">[<a href=\"$PHP_SELF?facid=$facid&amp;action=payed\">Classer 'Payée'</a>]</td>";
	}
      else
	{
	  print "<td align=\"center\" width=\"20%\">-</td>";
	}

      print "<td align=\"center\" width=\"20%\">-</td>";

      if ($obj->statut == 0  && $user->societe_id == 0)
	{
	  print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"20%\">[<a href=\"$PHP_SELF?facid=$facid&amp;action=valid\">Valider</a>]</td>";
	}
      else
	{
	  print "<td align=\"center\" width=\"20%\">-</td>";
	}

      print "</tr></table>";

    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
