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
require("../facture.class.php3");
require("../lib/CMailFile.class.php3");

llxHeader();

$db = new Db();

$yn[1] = "oui";
$yn[0] = "<b>non</b>";

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}
/*
 *
 */	
if ($action == 'valid') 
{
  $fac = new Facture($db);
  $result = $fac->set_valid($facid, $user->id);
  if ($result)
    {
     facture_pdf_create($db, $facid);
    }
}

if ($action == 'payed') 
{
  $fac = new Facture($db);
  $result = $fac->set_payed($facid);
}

if ($action == 'addligne') 
{
  $fac = new Facture($db);
  $result = $fac->addline($facid,$HTTP_POST_VARS["desc"],$HTTP_POST_VARS["pu"],$HTTP_POST_VARS["qty"]);
}

if ($action == 'updateligne') 
{
  $fac = new Facture($db,"",$facid);
  $result = $fac->updateline($rowid,$HTTP_POST_VARS["desc"],$HTTP_POST_VARS["price"],$HTTP_POST_VARS["qty"]);
}

if ($action == 'deleteline') 
{
  $fac = new Facture($db,"",$facid);
  $fac->id = $facid;
  $result = $fac->deleteline($rowid);
}

if ($action == 'delete') 
{
  $fac = new Facture($db);
  $fac->delete($facid);
  $facid = 0 ;
}

if ($action == 'add') 
{
  $datefacture = $db->idate(mktime(12, 0 , 0, $remonth, $reday, $reyear)); 
  
  if (! $propalid) 
    {
      $facture = new Facture($db, $socid);
      $facture->number = $facnumber;
      $facture->date = $datefacture;
      
      $facture->note = $note;
      $facture->amount = $amount;
      $facture->remise = $remise;
      
      $facture->create($user->id, $statut, $note);

    }
  else
    {
      $facture = new Facture($db, $socid);

      $facture->number   = $facnumber;
      $facture->date     = $datefacture;      
      $facture->note     = $note;
      $facture->amount   = $amount;
      $facture->remise   = $remise;
      $facture->propalid = $propalid;

      if (! $facture->create($user->id) )
	{
	  print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>";
	  print "<p>Retour à la <a href=\"propal.php3?propalid=$propalid\">propal</a>";
	  print $db->error();
	}
    }
  $facid = $facid;
  $action = '';  
}
/*
 *
 */

if ($action == 'send')
{
  $fac = new Facture($db,"",$facid);
  $fac->fetch($facid);

  $soc = new Societe($db, $fac->socidp);

  $file = $conf->facture->outputdir . "/" . $fac->ref . "/" . $fac->ref . ".pdf";

  if (file_exists($file))
    {

      $sendto = $soc->contact_get_email($HTTP_POST_VARS["destinataire"]);
      $sendtoid = $HTTP_POST_VARS["destinataire"];

      if (strlen($sendto))
	{
	  
	  $subject = "Facture $fac->ref";
	  $message = "Veuillez trouver ci-joint la facture $fac->ref\n\nCordialement\n\n";
	  $filename = "$fac->ref.pdf";
	  
	  $replyto = $HTTP_POST_VARS["replytoname"] . " <".$HTTP_POST_VARS["replytomail"] .">";
	  
	  $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$file, "application/pdf", $filename);
	  
	  if ( $mailfile->sendfile() )
	    {

	      $sendto = htmlentities($sendto);
	      
	      $sql = "INSERT INTO actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label) VALUES (now(), 9 ,$fac->socidp ,'Envoyée à $sendto',$fac->id, $sendtoid, $user->id, 'Envoi Facture par mail');";

	      if (! $db->query($sql) )
		{
		  print $db->error();
		  print "<p>$sql</p>";
		}	      	      	      
	    }
	  else
	    {
	      print "<b>!! erreur d'envoi<br>$sendto<br>$replyto<br>$filename";
	    }	  
	}
      else
	{
	  print "Can't get email $sendto";
	}
    }
}

if ($action == 'pdf')
{
  /*
   * Generation de la facture
   *
   */
  print facture_pdf_create($db, $facid); // définit dans /includes/modules/facture/modules_facture.php
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
  print_titre("Emettre une facture");

  if ($propalid) 
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM societe as s, llx_propal as p, c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
      
      $sql .= " AND p.rowid = $propalid";
    }
  else
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
      $sql .= "FROM societe as s ";
      $sql .= "WHERE s.idp = $socidp";      
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num) {
	$obj = $db->fetch_object(0);
	
	$numfa = facture_get_num(); // définit dans includes/modules/facture
	
	print "<form action=\"$PHP_SELF\" method=\"post\">";
	print "<input type=\"hidden\" name=\"action\" value=\"add\">";
	print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";
	
	print '<table cellspacing="0" border="1" width="100%">';
	
	print "<tr bgcolor=\"#e0e0e0\"><td>Client :</td><td>$obj->nom</td>";
	
	print '<td rowspan="6">';
	print '<textarea name="note" wrap="soft" cols="60" rows="15"></textarea></td></tr>';

	if ($propalid)
	  {
	    $amount = ($obj->price - $obj->remise);
	    print '<input type="hidden" name="amount"   value="'.$amount.'">';
	    print '<input type="hidden" name="total"    value="'.$obj->total.'">';
	    print '<input type="hidden" name="remise"   value="'.$obj->remise.'">';
	    print '<input type="hidden" name="tva"      value="'.$obj->tva.'">';
	    print '<input type="hidden" name="propalid" value="'.$propalid.'">';
	    
	    print "<tr><td>Propal :</td><td>$obj->ref</td></tr>";
	    print '<tr bgcolor="#e0e0e0"><td>Montant HT :</td><td>'.price($amount).'</td></tr>';
	    print "<tr bgcolor=\"#e0e0e0\"><td>TVA 19.6% :</td><td>".price($obj->tva)."</td></tr>";
	    print "<tr bgcolor=\"#e0e0e0\"><td>Total TTC :</td><td>".price($obj->total)."</td></tr>";	  
	  }
	else
	  {	    
	    print '<tr bgcolor="#e0e0e0"><td>Montant HT :</td><td>';	    
	    print '<input name="amount" type="text" value=""></td></tr>';	    
	    print '</td></tr>';
	    
	    print '<tr bgcolor="#e0e0e0"><td>Remise :</td><td>';
	    print '<input name="remise" type="text" value=""></td></tr>';
	    print '</td></tr>';
	  }
	
	print "<input type=\"hidden\" name=\"author\" value=\"$author\">";
	print "<tr><td>Auteur :</td><td>".$user->fullname."</td></tr>";
	
	print "<tr><td>Date :</td><td>";

	print_date_select(time());

	print "</td></tr>";
	print "<tr><td>Numéro :</td><td> <input name=\"facnumber\" type=\"text\" value=\"$numfa\"></td></tr>";
	
	print '<tr><td colspan="3" align="center"><input type="submit" value="Créer"></td></tr>';
	print "</form>\n";
	print "</table>\n";
	
      }
    } 
  else 
    {
      print $db->error();
    }


} 
else 
/* *************************************************************************** */
/*                                                                             */
/*                                                                             */
/*                                                                             */
/* *************************************************************************** */
{
  
  if ($facid > 0) {
    
    $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, f.total, ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.fk_user_author, f.note";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

    if ($user->societe_id > 0) 
      {
	$sql .= " AND s.idp = ".$user->societe_id;
      }

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

    if ($num > 0)
      {

	$soc = new Societe($db, $obj->socidp);

	$author = new User($db);
	$author->id = $obj->fk_user_author;
	$author->fetch();

	print_titre("Facture : ".$obj->facnumber);
  
	print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
	print "<tr><td width=\"50%\">";
	/*
	 *   Facture
	 */
	print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
	print "<tr><td>Client</td>";
	print "<td colspan=\"3\">";
	print "<b><a href=\"fiche.php3?socid=$obj->socidp\">$obj->socnom</a></b></td></tr>";
	
	print "<tr><td>Date</td>";
	print "<td colspan=\"3\">".strftime("%A %d %B %Y",$obj->df)."</td></tr>\n";
	print "<tr><td>".translate("Author")."</td><td colspan=\"3\">$author->fullname</td>";
	
	print '<tr><td>Montant</td>';
	print '<td align="right" colspan="2"><b>'.price($obj->amount).'</b></td>';
	print '<td>euros HT</td></tr>';
	print '<tr><td>TVA</td><td align="right" colspan="2">'.tva($obj->amount).'</td>';
	print '<td>euros</td></tr>';
	print '<tr><td>Total</td><td align="right" colspan="2">'.price($obj->total).'</td>';
	print '<td>euros TTC</td></tr>';
	
	print '<tr><td>Statut</td><td align="center">'.$obj->statut.'</td>';
	print "<td>".translate("Payed")."</td>";
	print "<td align=\"center\" bgcolor=\"#f0f0f0\"><b>".$yn[$obj->paye]."</b></td></tr>";
	print "</table>";
	
	print "</td><td valign=\"top\">";

	$_MONNAIE="euros";

	/*
	 * Paiements
	 */
	$sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
	$sql .= " FROM llx_paiement as p, c_paiement as c WHERE p.fk_facture = $facid AND p.fk_paiement = c.id";
	
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    $i = 0; $total = 0;
	    print "<p><b>Paiements</b>";
	    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
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

	    if ($obj->paye == 0)
	      {
		print "<tr><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>$_MONNAIE</td></tr>\n";
		print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($obj->total)."</td><td bgcolor=\"#d0d0d0\">$_MONNAIE</td></tr>\n";
		
		$resteapayer = $obj->total - $total;

		print "<tr><td colspan=\"2\" align=\"right\">Reste a payer :</td>";
		print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">$_MONNAIE</td></tr>\n";
	      }
	    print "</table>";
	    $db->free();
	  } else {
	    print $db->error();
	  }
	
	print "</td></tr>";
	print "<tr><td>Note : ".nl2br($obj->note)."</td></tr>";
	print "</table>";
	/*
	 * Lignes de factures
	 *
	 */
	
	$sql = "SELECT l.description, l.price, l.qty, l.rowid, l.tva_taux";
	$sql .= " FROM llx_facturedet as l WHERE l.fk_facture = $facid";
	
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    $i = 0; $total = 0;
	    
	    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	    print "<TR class=\"liste_titre\">";
	    print '<td width="62%">Description</td>';
	    print '<td width="8%"align="center">Tva Tx</td>';
	    print '<td width="8%"align="center">Quantité</td>';
	    print '<td width="12%" align="right">Montant</TD><td width="10%">&nbsp;</td><td width="10%">&nbsp;</td>';
	    print "</TR>\n";
	    
	    $var=True;
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		print "<TR $bc[$var]>";
		print "<TD>".stripslashes(nl2br($objp->description))."</TD>\n";
		print '<TD align="center">'.$objp->tva_taux.' %</TD>';
		print '<TD align="center">'.$objp->qty.'</TD>';
		print '<TD align="right">'.price($objp->price)."</TD>\n";
		if ($obj->statut == 0) 
		  {
		    print '<td align="right"><a href="'.$PHPSELF.'?facid='.$facid.'&action=deleteline&rowid='.$objp->rowid.'">del</a></td>';
		    print '<td align="right"><a href="'.$PHPSELF.'?facid='.$facid.'&action=editline&rowid='.$objp->rowid.'">edit</a></td>';
		  }
		else
		  {
		    print '<td>&nbsp;</td><td>&nbsp;</td>';
		  }
		print "</tr>";
	  
		if ($action == 'editline' && $rowid == $objp->rowid)
		  {
		    print "<form action=\"$PHP_SELF?facid=$facid\" method=\"post\">";
		    print '<input type="hidden" name="action" value="updateligne">';
		    print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		    print "<TR $bc[$var]>";
		    print '<TD><textarea name="desc" cols="60" rows="3">'.stripslashes($objp->description).'</textarea></TD>';
		    print '<TD align="center"><input type="text" name="qty" value="'.$objp->qty.'"></TD>';
		    print '<TD align="right"><input type="text" name="price" value="'.price($objp->price).'"></TD>';
		    print '<td colspan="2"><input type="submit"></td>';
		    print '</tr>' . "\n";
		    print "</form>\n";
		  }
		
		$total = $total + ($objp->qty * $objp->price);
		$i++;
	      }
	    
	    $db->free();
	    print "</table>";
	  } 
	else
	  {
	    print $db->error();
	  }
	
	/*
	 * Ajouter une ligne
	 *
	 */
	if ($obj->statut == 0) 
	  {
	    print "<form action=\"$PHP_SELF?facid=$facid\" method=\"post\">";
	    echo '<TABLE border="1" width="100%" cellspacing="0" cellpadding="1">';
	    print "<TR class=\"liste_titre\">";
	    print "<td>Description</td>";
	    print "<td>Quantité</td>";
	    print "<td align=\"right\">Montant</TD>";
	    print "</TR>\n";
	    print '<input type="hidden" name="action" value="addligne">';
	    print '<tr><td><textarea name="desc" cols="60" rows="3"></textarea></td>';
	    print '<td><input type="text" name="qty" size="2"></td>';
	    print '<td><input type="text" name="pu" size="8"></td>';
	    print '</tr>';       
	    print '<tr><td align="center" colspan="3"><input type="submit"></td></tr>';
	    print "</table>";
	    print "</form>";
	  }
	
	/*
	 * Fin Ajout ligne
	 *
	 */
	if ($user->societe_id == 0)
	  {
	    print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
	
	    if ($obj->statut == 0) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=delete\">Supprimer</a>]</td>";
	      } 
	    elseif ($obj->statut == 1 && $resteapayer > 0) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=presend\">Envoyer</a>]</td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      } 
	    
	    if ($obj->statut == 1 && $resteapayer > 0) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"paiement.php3?facid=$facid&action=create\">Emettre un paiement</a>]</td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      }
	    
	    if ($obj->statut == 1 && abs($resteapayer == 0) && $obj->paye == 0) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=payed\">Classer 'Payée'</a>]</td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      }
	    
	    if ($obj->statut == 0) 
	      {
		print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=valid\">Valider</a>]</td>";
	      }
	    elseif ($obj->statut == 1 && $obj->paye == 0)
	      {
		print "<td align=\"center\" width=\"25%\"><a href=\"facture.php3?facid=$facid&action=pdf\">Générer la facture</a></td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      }
	    print "</tr></table>";
	  }
	print "<p>\n";

	/*
	 * Documents générés
	 *
	 */
	print "<hr>";
	print "<table width=\"100%\" cellspacing=2><tr><td width=\"60%\" valign=\"top\">";
	print_titre("Documents générés");
	print '<table width="100%" cellspacing="0" border="1" cellpadding="3">';
	
	$file = $conf->facture->outputdir . "/" . $obj->facnumber . "/" . $obj->facnumber . ".pdf";
	
	
	if (file_exists($file))
	  {
	    print "<tr $bc[0]><td>Facture PDF</a></td>";
	    print '<td><a href="'.$conf->facture->outputurl."/".$obj->facnumber."/".$obj->facnumber.'.pdf">'.$obj->facnumber.'.pdf</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	  }  
	
	$file = $conf->facture->outputdir . "/" . $obj->facnumber . "/" . $obj->facnumber . ".ps";
	
	if (file_exists($file))
	  {
	    print "<tr $bc[0]><td>Facture Postscript</a></td>";
	    print '<td><a href="'.$conf->facture->outputurl."/".$obj->facnumber."/".$obj->facnumber.'.ps">'.$obj->facnumber.'.ps</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	    
	    
	  }
	print '<tr $bc[0]><td colspan="4">(<a href="'.$conf->facture->outputurl.'/'.$facid.'/">liste...</a>)</td></tr>';  
	
	print "</table>\n";
	print "</td>";
	print '<td valign="top" width="40%">';
	print_titre("Actions");
	/*
	 * Liste des actions
	 *
	 */
	$sql = "SELECT ".$db->pdate("a.datea")." as da,  a.note";
	$sql .= " FROM actioncomm as a WHERE a.fk_soc = $obj->socidp AND a.fk_action = 9 AND a.fk_facture = $facid";
	
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    if ($num)
	      {
		$i = 0; $total = 0;
		print '<TABLE border="1" cellspacing="0" cellpadding="4">';
		print "<TR $bc[$var]>";
		print "<td>Date</td>";
		print "<td>Action</td>";
		print "</TR>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object( $i);
		    $var=!$var;
		    print "<TR $bc[$var]>";
		    print "<TD>".strftime("%d %B %Y",$objp->da)."</TD>\n";
		    print '<TD align="right">'.stripslashes($objp->note).'</TD>';
		    print "</tr>";
		    $i++;
		  }
		print "</table>";
	      }
	  }
	else
	  {
	    print $db->error();
	  }
	
	/*
	 *
	 *
	 */
	print "</td></tr>";
	print "</table>";
	
	/*
	 *
	 *
	 */
	if ($action == 'presend')
	  {
	    $replytoname = "R. Quiedeville"; 
	    $from_name = $replytoname;
	    $replytomail = "rq@quiedeville.org"; 
	    $from_mail = $replytomail;
	    
	    print "<form method=\"post\" action=\"$PHP_SELF?facid=$facid&action=send\">\n";
	    print "<input type=\"hidden\" name=\"replytoname\" value=\"$replytoname\">\n";
	    print "<input type=\"hidden\" name=\"replytomail\" value=\"$replytomail\">\n";
	    
	    print "<p><b>Envoyer la facture par mail</b>";
	    print "<table cellspacing=0 border=1 cellpadding=3>";
	    print '<tr><td>Destinataire</td><td colspan="5">';
	    
	    $form = new Form($db);
	    
	    $form->select_array("destinataire",$soc->contact_email_array());
	    
	    print '</td>';
	    print "<td><input size=\"30\" name=\"sendto\" value=\"$obj->email\"></td></tr>";
	    print "<tr><td>Expéditeur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	    print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	    print "<td>$replytomail</td></tr>";
	    
	    print "</table>";
	    print "<input type=\"submit\" value=\"Envoyer\">";
	    print "</form>";
	  }
	
	/*
	 *   Propales
	 */
	
	$sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
	$sql .= " FROM llx_propal as p, llx_fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $facid";
  
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    if ($num)
	      {
		$i = 0; $total = 0;
		print "<p><b>Proposition(s) commerciale(s) associée(s)</b>";
		print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
		print "<TR class=\"liste_titre\">";
		print "<td>Num</td>";
		print "<td>Date</td>";
		print "<td align=\"right\">Prix</TD>";
		print "</TR>\n";
		
		$var=True;
		while ($i < $num)
		  {
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
      }
    else
      {
	/* Facture non trouvée */
	print "Facture inexistante ou accés refusé";
      }
  } else {
    /*
     *
     * Mode Liste
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
  
    $sql .= " ORDER BY f.fk_statut, f.paye, f.datef DESC, f.facnumber DESC ";
  
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
    
      $i = 0;
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print '<TR class="liste_titre">';
      print "<TD>Num&eacute;ro</TD><td>";
      print_liste_field_titre("Société",$PHP_SELF,"s.nom");
      print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";

      print "</TR>\n";
    
      if ($num > 0) {
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	
	  if ($objp->paye && !$sep) {
	    print "<tr><td colspan=\"3\" align=\"right\">";
	    print "&nbsp;</small></td>";
	    print "<td align=\"right\">Sous Total :<b> ".price($total)."</b></td></tr>";
	  
	    print '<TR class="liste_titre">';
	    print "<TD>Num&eacute;ro</TD><td>";
	    print_liste_field_titre("Société",$PHP_SELF,"s.nom");
	    print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";

	    $sep = 1 ; $j = 0;
	    $subtotal = 0;
	  }
	
	  print "<TR $bc[$var]>";
	  print "<td><a href=\"facture.php3?facid=$objp->facid\">";
	  if ($objp->paye) {
	    print $objp->facnumber;
	  } else {
	    print '<b>'.$objp->facnumber.'</b>';
	  }
	  print "</a></TD>\n";
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


	  print "</TR>\n";
	  $i++;
	  $j++;
	
	}
      }
      if ($i == 0) { $i=1; }  if ($j == 0) { $j=1; }
      print "<tr><td></td><td>$j factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
      print "<td align=\"right\">Sous Total :<b> ".price($subtotal)."</b></td></tr>";
    
      print "<tr bgcolor=\"#d0d0d0\"><td></td><td>$i factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
      print "<td align=\"right\"><b>Total <small>(euros HT)</small>: ".price($total)."</b></td></tr>";
    
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
