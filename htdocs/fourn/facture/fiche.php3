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
require("./pre.inc.php3");

llxHeader();

$db = new Db();

$html = new Form($db);

$yn[1] = "oui";
$yn[0] = "<b>non</b>";
	
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
      
      if (strlen($$label))
	{
	  // print "Ajour ligne $i " . $$label . " " . $$amount . " " . $$tauxtva ; // DEBUG
	  $facfou->add_ligne($$label, $$amount, $$tauxtva);
	}
    }
  $facfou->create($user);

  /*

  $sql = "INSERT INTO llx_facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, amount, remise, tva, total, fk_user_author) ";
  $sql .= " VALUES ('$facnumber','$libelle', $socidp, now(), $datefacture,'$note', $amount, $remise, $tva, $total, $user->id);";
  $result = $db->query($sql);

  if ($result)
  {
  $sql = "SELECT rowid, facnumber FROM llx_facture_fourn WHERE facnumber='$facnumber';";
  $result = $db->query($sql);
  if ($result)
  {
  $objfac = $db->fetch_object( 0);
  $facid = $objfac->rowid;
  $facnumber = $objfac->facnumber;
  $action = '';
	  
  }
  }
  else
  {
  print "<p><b>Erreur : la facture n'a pas été créée !</b>$sql<br>". $db->error();
  print "<p>Retour à la <a href=\"propal.php3?propalid=$propalid\">propal</a>";
  }
  $facid = $facid;
  $action = '';
  
  */

}
/*
 *
 * Mode creation
 *
 *
 *
 */

if ($action == 'create')
{
  print_titre("Saisir une facture");

  print '<form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<table cellspacing="0" cellpadding="3" border="1" width="100%">';
  print '<tr bgcolor="#e0e0e0"><td>Société :</td>';

  print '<td><select name="socidp">';

  $sql = "SELECT s.nom, s.prefix_comm, s.idp";
  $sql .= " FROM societe as s WHERE s.fournisseur = 1 ORDER BY s.nom ASC";

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

  print '<td rowspan="6"><textarea name="note" wrap="soft" cols="30" rows="10"></textarea></td></tr>';

  print '<tr><td>Libellé :</td><td><input size="30" name="libelle" type="text"></td></tr>';

  print '<tr><td>Date :</td><td>';
  $html->select_date();
  print '</td></tr>';

  print '<tr bgcolor="#e0e0e0"><td>Montant HT :</td>';
  print '<td><input type="text" size="8" name="amount"></td></tr>';

  print '<tr bgcolor="#e0e0e0"><td>TVA :</td>';
  print '<td><select name="tva_taux">';
  print '<option value="19.6">19.6';
  print '<option value="5.5">5.5';
  print '<option value="0">0';
  print '</select></td></tr>';
      

  $author = $GLOBALS["REMOTE_USER"];
  print "<input type=\"hidden\" name=\"author\" value=\"$author\">";
  
  print "<tr><td>Auteur :</td><td>".$user->fullname."</td></tr>";

  print '<tr><td>Ligne 1 :</td>';
  print '<td><input size="30" name="label1" type="text"></td>';

  print '<td><input type="text" size="8" name="amount1">&nbsp;TVA&nbsp;';
  $html->select_tva("tauxtva1");
  print '</td></tr>';

  print '<tr><td>Ligne 2 :</td>';
  print '<td><input size="30" name="label2" type="text"></td>';

  print '<td><input type="text" size="8" name="amount2">&nbsp;TVA&nbsp;';
  $html->select_tva("tauxtva2");
  print '</td></tr>';

  print '<tr><td>Ligne 3 :</td>';
  print '<td><input size="30" name="label3" type="text"></td>';
  print '<td><input type="text" size="8" name="amount3">&nbsp;TVA&nbsp;';
  $html->select_tva("tauxtva3");
  print '</td></tr>';

  print '<tr><td>Ligne 4 :</td>';
  print '<td><input size="30" name="label4" type="text"></td>';
  print '<td><input type="text" size="8" name="amount4">&nbsp;TVA&nbsp;';
  $html->select_tva("tauxtva4");
  print '</td></tr>';

  print '<tr><td colspan="3" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>";
  print "</table>";
  
}
else
{
  if ($facid > 0)
    {
      $fac = new FactureFourn($db);
      $fac->fetch($facid);

      $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, f.tva, f.total, ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.note, f.libelle, f.rowid";
      $sql .= " FROM societe as s,llx_facture_fourn as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

      $result = $db->query( $sql);
      
      if ($result) {
	$num = $db->num_rows();
	if ($num) {
	  $obj = $db->fetch_object( $i);    
	}
	$db->free();
      } else {
	print $db->error();
      }

      /*
       * Edition
       *
       *
       */
      if ($action == "edit")
	{

	  print "<form action=\"$PHP_SELF?facid=$obj->rowid\" method=\"post\">";
	  print '<input type="hidden" name="action" value="update">';
    
	  print '<table cellspacing="0" border="1" width="100%">';
	  print "<tr bgcolor=\"#e0e0e0\"><td width=\"20%\">Société :</td>";
	
	  print '<td width="20%">'.stripslashes($obj->socnom);
	  print '</td>';
	  print '<td width="60%" valign="top">Commentaires :</tr>';
	
	  print '<tr><td valign="top">Numéro :</td><td valign="top">';
	  print $obj->facnumber .'<br>';
	  print '<input name="facnumber" type="text" value="'.$obj->facnumber.'"></td>';
	
	  print '<td rowspan="8" width="60%" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="10">';
	  print stripslashes($obj->note);
	  print '</textarea></td></tr>';

	  print '<tr><td valign="top">Libellé :</td><td>';
	  print stripslashes($obj->libelle). '<br>';
	  print '<input size="30" name="libelle" type="text" value="'.stripslashes($obj->libelle).'"></td></tr>';
    
	  print '<tr bgcolor="#e0e0e0"><td>Montant HT :</td>';
	  print '<td valign="top">'.$obj->amount.'<br><input type="text" size="8" name="amount" value="'.$obj->amount.'"></td></tr>';
    
	  print '<tr bgcolor="#e0e0e0"><td>TVA :</td>';
	  print '<td><select name="tva_taux">';
	  print '<option value="19.6">19.6';
	  print '<option value="5.5">5.5';
	  print '<option value="0">0';
	  print '</select></td></tr>';
    
	  print "<tr><td>Date :</td><td>";

	  print_date_select($obj->df);

	  print "</td></tr>";
    
	  print "<tr><td>Auteur :</td><td>".$user->fullname."</td></tr>";
	  print "<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Enregistrer\"></td></tr>";
	  print "</form>";
	  print "</table>";

	}

      /*
       * Affichage
       *
       *
       */
  
      print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
      print "<tr>";
      print '<td><div class="titre">Facture : '.$obj->facnumber.'</div></td>';
      print "<td align=\"right\"><a href=\"facture.php3?socidp=$obj->socidp\">Autres factures de $obj->socnom</a></td>\n";
      print "</tr>";
      print "<tr><td width=\"50%\">";
      /*
       *   Facture
       */
      print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
      print "<tr><td>Société</td><td colspan=\"3\"><b><a href=\"fiche.php3?socid=$obj->socidp\">$obj->socnom</a></b></td>";

      print "<tr><td>Date</td><td colspan=\"3\">".strftime("%A %d %B %Y",$obj->df)."</td></tr>\n";
      print "<tr><td>Libelle</td><td colspan=\"3\">$obj->libelle</td>";
      print '<tr><td class="small">Auteur</td><td class="small" colspan="3">'.$obj->author.'&nbsp;</td>';
  
      print '<tr><td>Total HT</td><td align="right"><b>'.price($fac->total_ht)."</b></td>";
      print '<td align="right">TVA</td><td align="right">'.price($fac->total_tva)."</td></tr>";
      print '<tr><td>Total TTC</td><td colspan="3" align="center">'.price($fac->total_ttc)."</td></tr>";

      print "<tr><td>Statut</td><td align=\"center\">$obj->statut</td>";
      print "<td>Paye</td><td align=\"center\" bgcolor=\"#f0f0f0\"><b>".$yn[$obj->paye]."</b></td>";

      print "</tr>";
      print "</table>";
  
      print "</td><td valign=\"top\">";

      print nl2br(stripslashes($obj->note));


      $_MONNAIE="euros";

      /*
       * Paiements
       */
      $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
      $sql .= " FROM llx_paiement as p, c_paiement as c WHERE p.fk_facture = $facid AND p.fk_paiement = c.id";
    
      //$result = $db->query($sql);
      $result = 0;
      if ($result) {
	$num = $db->num_rows();
	$i = 0; $total = 0;
	print "<p><b>Paiements</b>";
	echo '<TABLE border="1" width="100%" cellspacing="0" cellpadding="3">';
	print "<TR class=\"liste_titre\">";
	print "<td>Date</td>";
	print "<td>Type</td>";
	print "<td align=\"right\">Montant</TD><td>&nbsp;</td>";
	print "</TR>\n";
    
	$var=True;
	while ($i < $num) {
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
	print "<tr><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>$_MONNAIE</td></tr>\n";
	print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($obj->total)."</td><td bgcolor=\"#d0d0d0\">$_MONNAIE</td></tr>\n";

	$resteapayer = $obj->total - $total;

	print "<tr><td colspan=\"2\" align=\"right\">Reste a payer :</td>";
	print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">$_MONNAIE</td></tr>\n";

	print "</table>";
	$db->free();
      } else {
	//      print $db->error();
      }

      print "</td></tr>";

      print "</table>";
      /*
       * Lignes
       *
       */

      print '<p><table border="1" cellspacing="0" cellpadding="2" width="100%">';
      print '<tr><td class="small">Libellé</td><td align="center" class="small">P.U. HT</td><td align="center" class="small">Qty</td><td align="center" class="small">Total HT</td>';
      print '<td align="right" class="small">Total TTC</td><td align="center" class="small">TVA</td><td align="center" class="small">Taux TVA</td></tr>';
      for ($i = 0 ; $i < sizeof($fac->lignes) ; $i++)
	{
	  print '<tr><td>'.$fac->lignes[$i][0]."</td>";
	  print '<td align="right">'.$fac->lignes[$i][4]."</td>";  
	  print '<td align="center">'.$fac->lignes[$i][3]."</td>";  
	  print '<td align="right">'.$fac->lignes[$i][1]."</td>";
	  print '<td align="right">'.price($fac->lignes[$i][6])."</td>";  
	  print '<td align="right" class="small">'.price($fac->lignes[$i][5])."</td>";  
	  print '<td align="right" class="small">'.$fac->lignes[$i][2]."</td>";  
	  print '</tr>';
	}
      print "</table>";


      /*
       * Barre de commande
       *
       *
       */

      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
      if ($obj->statut == 0)
	{
	  print '<td align="center" bgcolor="#e0e0e0" width="25%">[<a href="index.php3?facid='.$facid.'&action=delete">'.translate("Delete").'</a>]</td>';
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\">-</td>";
	} 

    
      print '<td align="center" width="25%">[<a href="fiche.php3?facid='.$obj->rowid.'&action=edit">Editer</a>]</td>';
    
      if ($obj->statut == 1 && abs($resteapayer == 0) && $obj->paye == 0)
	{
	  print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=payed\">Classer 'Payée'</a>]</td>";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\">-</td>";
	}
      if ($obj->statut == 0)
	{
	  print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=valid\">Valider</a>]</td>";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\">-</td>";
	}


      print "</tr></table><p>";

      /*
       * Documents générés
       *
       */
      print "<hr>";
      print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
      print "<b>Documents associés</b><br>";
      print "<table width=\"100%\" cellspacing=0 border=1 cellpadding=3>";

      $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/facture/$obj->facnumber/$obj->facnumber.pdf";
      if (file_exists($file)) {
	print "<tr><td>Propale PDF</a></td><td><a href=\"../../doc/facture/$obj->facnumber/$obj->facnumber.pdf\">$obj->facnumber.pdf</a></td></tr>";
      }  
      $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/facture/$obj->facnumber/$obj->facnumber.ps";
      if (file_exists($file)) {
	print "<tr><td>Propale Postscript</a></td><td><a href=\"../../doc/facture/$obj->facnumber/$obj->facnumber.ps\">$obj->facnumber.ps</a></td>";
	print "</tr>";
      }
      print "<tr><td colspan=\"2\">(<a href=\"../../doc/facture/$obj->facnumber/\">liste...</a>)</td></tr>";  

      print "</table>\n</table>";
  
      /*
       * Generation de la facture
       *
       */
      if ($action == 'pdf') {
	print "<hr><b>Génération de la facture</b><br>";
	$command = "export DBI_DSN=\"dbi:mysql:dbname=lolixfr\" ";
	$command .= " ; ../../scripts/facture-tex.pl --html -vv --facture=$facid --pdf --gljroot=" . $GLOBALS["GLJ_ROOT"] ;
    
	$output = system($command);
	print "<p>command :<br><small>$command</small><br>";
	print "<p>output :<br><small>$output</small><br>";
      } 


      /*
       *   Propales
       */
  
      $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
      $sql .= " FROM llx_propal as p, llx_fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $facid";
  
      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
	if ($num) {
	  $i = 0; $total = 0;
	  print "<p><b>Proposition(s) commerciale(s) associée(s)</b>";
	  print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<TR class=\"liste_titre\">";
	  print "<td>Num</td>";
	  print "<td>Date</td>";
	  print "<td align=\"right\">Prix</TD>";
	  print "</TR>\n";
    
	  $var=True;
	  while ($i < $num) {
	    $objp = $db->fetch_object( $i);
	    $var=!$var;
	    print "<TR $bc[$var]>";
	    print "<TD><a href=\"propal.php3?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	    print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	    print '<TD align="right">'.price($objp->price).'</TD>';
	    print "</tr>";
	    $total = $total + $objp->price;
	    $i++;
	  }
	  print "<tr><td align=\"right\" colspan=\"3\">Total : <b>".price($total)."</b> $_MONNAIE HT</td></tr>\n";
	  print "</table>";
	}
      } else {
	print $db->error();
      }

    } else {
      /*
       *
       * Liste
       *
       *
       */
      print_barre_liste("Factures",$page,$PHP_SELF);

      $sql = "SELECT s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
      $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp";
  
      if ($socidp) {
	$sql .= " AND s.idp = $socidp";
      }
  
      if ($month > 0) {
	$sql .= " AND date_format(f.datef, '%m') = $month";
      }
      if ($year > 0) {
	$sql .= " AND date_format(f.datef, '%Y') = $year";
      }
  
      $sql .= " ORDER BY f.fk_statut, f.paye, f.datef DESC ";
  
      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
    
	$i = 0;
	print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print '<TR class="liste_titre">';
	print "<TD>Num&eacute;ro</TD><td>";
	print_liste_field_titre("Société",$PHP_SELF,"s.nom");
	print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
	print "<TD align=\"right\">Payé</TD>";
	print "</TR>\n";
    
	if ($num > 0) {
	  $var=True;
	  while ($i < $num) {
	    $objp = $db->fetch_object( $i);
	    $var=!$var;
	
	    if ($objp->paye && !$sep) {
	      print "<tr><td colspan=\"3\" align=\"right\">";
	      print "&nbsp;</small></td>";
	      print "<td align=\"right\">Sous Total :<b> ".price($total)."</b></td><td>euros HT</td></tr>";
	  
	      print '<TR class="liste_titre">';
	      print "<TD>Num&eacute;ro</TD><td>";
	      print_liste_field_titre("Société",$PHP_SELF,"s.nom");
	      print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
	      print "<TD align=\"right\">Payé</TD></TR>\n";
	      $sep = 1 ; $j = 0;
	      $subtotal = 0;
	    }
	
	    print "<TR $bc[$var]>";
	    print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	    print "<TD><a href=\"fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	
	    if ($objp->df > 0 ) {
	      print "<TD align=\"right\">";
	      $y = strftime("%Y",$objp->df);
	      $m = strftime("%m",$objp->df);
	  
	      print strftime("%d",$objp->df)."\n";
	      print " <a href=\"facture.php3?year=$y&month=$m\">";
	      print strftime("%B",$objp->df)."</a>\n";
	      print " <a href=\"facture.php3?year=$y\">";
	      print strftime("%Y",$objp->df)."</a></TD>\n";
	    } else {
	      print "<TD align=\"right\"><b>!!!</b></TD>\n";
	    }
	
	    print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
	
	    $total = $total + $objp->amount;
	    $subtotal = $subtotal + $objp->amount;	  
	    print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";

	    print "</TR>\n";
	    $i++;
	    $j++;
	
	  }
	}
	if ($i == 0) { $i=1; }  if ($j == 0) { $j=1; }
	print "<tr><td></td><td>$j factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
	print "<td align=\"right\">Sous Total :<b> ".price($subtotal)."</b></td><td>euros HT</td></tr>";
    
	print "<tr bgcolor=\"#d0d0d0\"><td></td><td>$i factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
	print "<td align=\"right\"><b>Total : ".price($total)."</b></td><td>euros HT</td></tr>";
    
	print "</TABLE>";
	$db->free();
      } else {
	print $db->error();
      }

    }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
