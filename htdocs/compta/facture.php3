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

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

require("../facture.class.php3");
require("../lib/CMailFile.class.php3");
require("../paiement.class.php");
require("../project.class.php3");
require("../propal.class.php3");
require("./bank/account.class.php");
require("../contrat/contrat.class.php");

llxHeader();

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
if ($HTTP_POST_VARS["action"] == 'add') 
{
  $datefacture = mktime(12, 0 , 0, $remonth, $reday, $reyear); 

  $facture = new Facture($db, $socid);

  $facture->number         = $HTTP_POST_VARS["facnumber"];
  $facture->date           = $datefacture;      
  $facture->note           = $HTTP_POST_VARS["note"];
  $facture->projetid       = $HTTP_POST_VARS["projetid"];
  $facture->cond_reglement = $HTTP_POST_VARS["condid"];
  $facture->amount         = $HTTP_POST_VARS["amount"];
  $facture->remise         = $HTTP_POST_VARS["remise"];
  $facture->remise_percent = $HTTP_POST_VARS["remise_percent"];
  
  if (!$HTTP_POST_VARS["propalid"]) 
    {      
      $facture->add_product($HTTP_POST_VARS["idprod1"],$HTTP_POST_VARS["qty1"]);
      $facture->add_product($HTTP_POST_VARS["idprod2"],$HTTP_POST_VARS["qty2"]);
      $facture->add_product($HTTP_POST_VARS["idprod3"],$HTTP_POST_VARS["qty3"]);
      $facture->add_product($HTTP_POST_VARS["idprod4"],$HTTP_POST_VARS["qty4"]);
      
      $facid = $facture->create($user);
    }
  else
    {
      $facture->propalid       = $HTTP_POST_VARS["propalid"];

      $facid = $facture->create($user);

      if ($facid)
	{
	  $prop = New Propal($db);
	  if ( $prop->fetch($HTTP_POST_VARS["propalid"]) )
	    {
	      for ($i = 0 ; $i < sizeof($prop->lignes) ; $i++)
		{
		  $result = $facture->addline($facid,
					      $prop->lignes[$i]->desc,
					      $prop->lignes[$i]->price,
					      $prop->lignes[$i]->qty,
					      $prop->lignes[$i]->tva_tx,
					      $prop->lignes[$i]->product_id);
		}
	    }
	  else
	    {
	      print "Erreur";
	    }
	}
      else
	{
	  print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>";
	  print "<p>Retour à la <a href=\"propal.php3?propalid=$propalid\">propal</a>";
	  print $db->error();
	}
    }

  $action = '';  
}
/*
 *
 */
if ($action == 'add_paiement')
{
  $datepaye = $db->idate(mktime(12, 0 , 0, $HTTP_POST_VARS["remonth"], $HTTP_POST_VARS["reday"], $HTTP_POST_VARS["reyear"]));

  $paiement = new Paiement($db);

  $paiement->facid        = $HTTP_POST_VARS["facid"];
  $paiement->datepaye     = $datepaye;
  $paiement->amount       = $HTTP_POST_VARS["amount"];
  $paiement->author       = $HTTP_POST_VARS["author"];
  $paiement->paiementid   = $HTTP_POST_VARS["paiementid"];
  $paiement->num_paiement = $HTTP_POST_VARS["num_paiement"];
  $paiement->note         = $HTTP_POST_VARS["note"];

  $paiement->create();

  $action = '';

  $fac = new Facture($db);
  $fac->fetch($HTTP_POST_VARS["facid"]);

  $label = "Réglement facture ".$fac->ref;

  $acc = new Account($db, $HTTP_POST_VARS["accountid"]);
  $acc->addline($datepaye, $paiementid, $label, $amount, $num_paiement);
}
/*
 *
 */
if ($action == 'del_paiement' && $user->rights->facture->paiement)
{
  $paiement = new Paiement($db);
  $paiement->id = $paiementid;
  $paiement->delete();

  $action = '';
}
/*
 *
 */

if ($action == 'valid' && $user->rights->facture->valider)
{
  $fac = new Facture($db);
  $fac->fetch($facid);
  $result = $fac->set_valid($facid, $user);
  if ($result)
    {
     facture_pdf_create($db, $facid);
    }
}

if ($action == 'payed' && $user->rights->facture->paiement) 
{
  $fac = new Facture($db);
  $result = $fac->set_payed($facid);
}

if ($HTTP_POST_VARS["action"] == 'setremise' && $user->rights->facture->creer) 
{
  $fac = new Facture($db);
  $fac->fetch($facid);

  $fac->set_remise($user, $HTTP_POST_VARS["remise"]);
} 


if ($action == 'addligne' && $user->rights->facture->creer) 
{
  $fac = new Facture($db);
  $fac->fetch($facid);
  $result = $fac->addline($facid,
			  $HTTP_POST_VARS["desc"],
			  $HTTP_POST_VARS["pu"],
			  $HTTP_POST_VARS["qty"],
			  $HTTP_POST_VARS["tva_tx"]);
}

if ($action == 'updateligne' && $user->rights->facture->creer) 
{
  $fac = new Facture($db,"",$facid);
  $fac->fetch($facid);
  $result = $fac->updateline($rowid,
			     $HTTP_POST_VARS["desc"],
			     $HTTP_POST_VARS["price"],
			     $HTTP_POST_VARS["qty"]);
}

if ($action == 'deleteline' && $user->rights->facture->creer) 
{
  $fac = new Facture($db,"",$facid);
  $fac->fetch($facid);
  $result = $fac->deleteline($rowid);
}

if ($action == 'delete' && $user->rights->facture->supprimer) 
{
  $fac = new Facture($db);
  $fac->delete($facid);
  $facid = 0 ;
}

/*
 *
 */
if ($action == 'send')
{
  $fac = new Facture($db,"",$facid);
  $fac->fetch($facid);

  $soc = new Societe($db, $fac->socidp);

  $file = FAC_OUTPUTDIR . "/" . $fac->ref . "/" . $fac->ref . ".pdf";

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
	      
	      $sql = "INSERT INTO llx_actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), 9 ,$fac->socidp ,'Envoyée à $sendto',$fac->id, $sendtoid, $user->id, 'Envoi Facture par mail',100);";

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
/*
 *
 */
if ($HTTP_POST_VARS["action"] == 'relance')
{
  $fac = new Facture($db,"",$facid);
  $fac->fetch($facid);

  $fac->send_relance($HTTP_POST_VARS["destinataire"],
		     $HTTP_POST_VARS["replytoname"],
		     $HTTP_POST_VARS["replytomail"],
		     $user);
}

/*
 *
 */
if ($action == 'pdf')
{
  /*
   * Generation de la facture
   * définit dans /includes/modules/facture/modules_facture.php
   */
  print facture_pdf_create($db, $facid);
} 

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *
 *
 ************************************************************************/
if ($action == 'create') 
{
  print_titre("Emettre une facture");

  if ($propalid)
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c";
      $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";      
      $sql .= " AND p.rowid = $propalid";
    }
  else
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
      $sql .= "FROM llx_societe as s ";
      $sql .= "WHERE s.idp = $socidp";      
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object(0);

	  $soc = new Societe($db);
	  $soc->fetch($obj->idp);
       
	  $numfa = facture_get_num($soc); // définit dans includes/modules/facture
	
	  print '<form action="'.$PHP_SELF.'" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="socid" value="'.$obj->idp.'">' ."\n";
	  
	  print '<table cellspacing="0" cellpadding="3" border="1" width="100%">';
	  
	  print "<tr><td>Client :</td><td>$obj->nom</td>";
	  print "<td>Commentaire</td></tr>";
	  
	  print "<input type=\"hidden\" name=\"author\" value=\"$author\">";
	  print "<tr><td>Auteur :</td><td>".$user->fullname."</td>";
	  
	  print '<td rowspan="6" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="8"></textarea></td></tr>';	
	  
	  print "<tr><td>Date :</td><td>";
	
	  print_date_select(time());

	  print "</td></tr>";
	  print "<tr><td>Numéro :</td><td> <input name=\"facnumber\" type=\"text\" value=\"$numfa\"></td></tr>";
	  
	  print "<tr><td>Conditions de réglement :</td><td>";
	  $sql = "SELECT rowid, libelle FROM llx_cond_reglement ORDER BY sortorder";
	  $result = $db->query($sql);
	  $conds=array();
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object($i);
		  $conds[$objp->rowid]=$objp->libelle;
		  $i++;
		}
	      $db->free();
	    }
	  
	  $html->select_array("condid",$conds);
	  print "</td></tr>";
	  
	  print "<tr><td>Projet :</td><td>";
	  $proj = new Project($db);
	  $html->select_array("projetid",$proj->liste_array($socidp));
	  print "</td></tr>";
	  
	  if ($propalid > 0)
	    {
	      $amount = ($obj->price);
	      print '<input type="hidden" name="amount"   value="'.$amount.'">'."\n";
	      print '<input type="hidden" name="total"    value="'.$obj->total.'">'."\n";
	      print '<input type="hidden" name="remise"   value="'.$obj->remise.'">'."\n";
	      print '<input type="hidden" name="remise_percent"   value="'.$obj->remise_percent.'">'."\n";
	      print '<input type="hidden" name="tva"      value="'.$obj->tva.'">'."\n";
	      print '<input type="hidden" name="propalid" value="'.$propalid.'">';
	      
	      print '<tr><td>Proposition</td><td colspan="2">'.$obj->ref.'</td></tr>';
	      print '<tr><td>Montant HT</td><td colspan="2">'.price($amount).'</td></tr>';
	      print '<tr><td>TVA</td><td colspan="2">'.price($obj->tva)."</td></tr>";
	      print '<tr><td>Total TTC</td><td colspan="2">'.price($obj->total)."</td></tr>";	  
	    }	  
	  else
	    {
	      print '<tr><td colspan="2">Services/Produits</td></tr>';
	      print '<tr><td colspan="3">';
	      /*
	       *
	       * Liste des elements
	       *
	       */
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p ";
	      $sql .= " WHERE envente = 1";
	      $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" SELECTED></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			{
			  $objp = $db->fetch_object( $i);
			  $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
			  $i++;
			}
		    }
		  $db->free();
		}
	      else
		{
		  print $db->error();
		}
	      	      
	      print '<table border="0" cellspacing="0">';
	      
	      for ($i = 1 ; $i < 5 ; $i++)
		{
		  print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
		  print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td></tr>';
		}
	      	      
	      print '</table>';
	      print '</td></tr>';
	    }
	  
	  print '<tr><td colspan="3" align="center"><input type="submit" value="Créer"></td></tr>';
	  print "</form>\n";
	  print "</table>\n";

	  if ($propalid)
	    {
	      /*
	       * Produits
	       */
	      print_titre("Produits");
	      
	      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr class="liste_titre"><td>Réf</td><td>Produit</td>';
	      print '<td align="right">Prix</td><td align="center">&nbsp;</td><td align="center">Qté.</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid";
	      $sql .= " FROM llx_propaldet as pt, llx_product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
	      
	      $result = $db->query($sql);
	      if ($result) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  $var=True;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object($i);
		      $var=!$var;
		      print "<tr $bc[$var]><td>[$objp->ref]</TD>\n";
		      print '<td>'.$objp->product.'</td>';
		      print "<td align=\"right\">".price($objp->price)."</TD>";
		      print '<td>&nbsp;</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      print '</table>';
	    }	  
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
  
  if ($facid > 0)
    {
    
      $fac = New Facture($db);
      if ( $fac->fetch($facid, $user->societe_id) > 0)
	{	  
	  $soc = new Societe($db, $fac->socidp);
	  $soc->fetch($fac->socidp);
	  $author = new User($db);
	  $author->id = $fac->user_author;
	  $author->fetch();
	  
	  print_titre("Facture : ".$fac->ref);
	  
	  /*
	   *   Facture
	   */
	  print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
	  print "<tr><td>Client</td>";
	  print "<td colspan=\"3\">";
	  print '<b><a href="fiche.php3?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print "<td>Conditions de réglement : " . $fac->cond_reglement ."</td></tr>";
	  
	  print "<tr><td>Date</td>";
	  print "<td colspan=\"3\">".strftime("%A %d %B %Y",$fac->date)."</td>\n";
	  print "<td>Date limite de réglement : " . strftime("%d %B %Y",$fac->date_lim_reglement) ."</td></tr>";
	  print "<tr><td>Auteur</td><td colspan=\"3\">$author->fullname</td>";
	  
	  if ($fac->remise_percent > 0)
	    {
	      print '<td rowspan="5" valign="top">';
	    }
	  else
	    {
	      print '<td rowspan="4" valign="top">';
	    }
	  
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
	    print "<b>Paiements</b>";
	    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	    print "<TR class=\"liste_titre\">";
	    print "<td>Date</td>";
	    print "<td>Type</td>";
	    print "<td align=\"right\">Montant</TD><td>&nbsp;</td>";
	    if (! $fac->paye)
	      {
		print "<td>&nbsp;</td>";
	      }
	    print "</TR>\n";
    
	    $var=True;
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		print "<TR $bc[$var]>";
		print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
		print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
		print '<td align="right">'.price($objp->amount)."</TD><td>$_MONNAIE</td>\n";
		if (! $fac->paye && $user->rights->facture->paiement)
		  {
		    print '<td><a href="facture.php3?facid='.$facid.'&action=del_paiement&paiementid='.$objp->rowid.'">Del</a>';
		  }
		print "</tr>";
		$total = $total + $objp->amount;
		$i++;
	      }

	    if ($fac->paye == 0)
	      {
		print "<tr><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>$_MONNAIE</td></tr>\n";
		print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($fac->total_ttc)."</td><td bgcolor=\"#d0d0d0\">$_MONNAIE</td></tr>\n";
		
		$resteapayer = price($fac->total_ttc - $total);

		print "<tr><td colspan=\"2\" align=\"right\">Reste à payer :</td>";
		print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($fac->total_ttc - $total)."</b></td><td bgcolor=\"#f0f0f0\">$_MONNAIE</td></tr>\n";
	      }
	    print "</table>";
	    $db->free();
	  } else {
	    print $db->error();
	  }
	
	print "</td></tr>";
	
	print '<tr><td>Montant</td>';
	print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
	print '<td>euros HT</td></tr>';

	if ($fac->remise_percent > 0)
	  {
	    print '<tr><td>Remise</td>';
	    print '<td align="right" colspan="2">'.$fac->remise_percent.'</td>';
	    print '<td>%</td></tr>';
	  }

	print '<tr><td>TVA</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
	print '<td>euros</td></tr>';
	print '<tr><td>Total</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
	print '<td>euros TTC</td></tr>';
	if ($fac->note)
	  {
	    print '<tr><td colspan="5">Note : '.nl2br($fac->note)."</td></tr>";
	  }

	print "</table><br>";

	if ($fac->brouillon == 1)
	  {
	    print '<form action="facture.php3?facid='.$facid.'" method="post">';
	    print '<input type="hidden" name="action" value="setremise">';
	    print '<table cellpadding="3" cellspacing="0" border="1"><tr><td>Remise</td><td align="right">';
	    print '<input type="text" name="remise" size="3" value="'.$fac->remise_percent.'">%';
	    print '<input type="submit" value="Appliquer">';
	    print '</td></tr></table></form>';
	  }

	/*
	 * Lignes de factures
	 *
	 */
	
	$sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux";
	$sql .= " FROM llx_facturedet as l WHERE l.fk_facture = $facid";
	
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    $i = 0; $total = 0;
	    
	    echo '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	    if ($num)
	      {
		print "<TR class=\"liste_titre\">";
		print '<td width="62%">Description</td>';
		print '<td width="8%" align="center">Tva Tx</td>';
		print '<td width="8%" align="center">Quantité</td>';
		print '<td width="12%" align="right">Montant</TD>';
		print '<td width="10%">&nbsp;</td><td width="10%">&nbsp;</td>';
		print "</TR>\n";
	      }
	    $var=True;
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		print "<TR $bc[$var]>";
		if ($objp->fk_product)
		  {
		    print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php3?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		  }
		else
		  {
		print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
		  }
		print '<TD align="center">'.$objp->tva_taux.' %</TD>';
		print '<TD align="center">'.$objp->qty.'</TD>';
		print '<TD align="right">'.price($objp->price)."</TD>\n";
		if ($fac->statut == 0) 
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
		    print '<TD colspan="2"><textarea name="desc" cols="60" rows="3">'.stripslashes($objp->description).'</textarea></TD>';
		    print '<TD align="center"><input size="4" type="text" name="qty" value="'.$objp->qty.'"></TD>';
		    print '<TD align="right"><input size="8" type="text" name="price" value="'.price($objp->price).'"></TD>';
		    print '<td align="right" colspan="2"><input type="submit" value="Enregistrer"></td>';
		    print '</tr>' . "\n";
		    print "</form>\n";
		  }
		
		$total = $total + ($objp->qty * $objp->price);
		$i++;
	      }
	    
	    $db->free();
	    //	    print "</table>";
	  } 
	else
	  {
	    print $db->error();
	  }
	
	/*
	 * Ajouter une ligne
	 *
	 */
	if ($fac->statut == 0) 
	  {
	    print "<form action=\"$PHP_SELF?facid=$facid\" method=\"post\">";
	    //	    echo '<TABLE border="1" width="100%" cellspacing="0" cellpadding="1">';
	    print "<TR class=\"liste_titre\">";
	    print '<td width="62%">Description</td>';
	    print '<td width="8%" align="center">Tva Tx</td>';
	    print '<td width="8%" align="center">Quantité</td>';
	    print '<td width="12%" align="right">Montant</TD>';
	    print '<td>&nbsp;</td>';
	    print '<td>&nbsp;</td>';
	    print "</TR>\n";
	    print '<input type="hidden" name="action" value="addligne">';
	    print '<tr><td><textarea name="desc" cols="60" rows="3"></textarea></td>';
	    print '<td align="center">';
	    print $html->select_tva("tva_tx");
	    print '</td>';
	    print '<td align="center"><input type="text" name="qty" size="2"></td>';
	    print '<td align="right"><input type="text" name="pu" size="8"></td>';

	    print '<td align="center" colspan="3"><input type="submit" value="Ajouter"></td></tr>';

	    print "</form>";
	  }
	print "</table>";
	/*
	 * Fin Ajout ligne
	 *
	 */
	if ($user->societe_id == 0 && $fac->paye == 0)
	  {
	    print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
	
	    if ($fac->statut == 0 && $user->rights->facture->supprimer)
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=delete\">Supprimer</a>]</td>";
	      } 
	    elseif ($fac->statut == 1 && abs($resteapayer) > 0 && $user->rights->facture->envoyer) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=presend\">Envoyer</a>]</td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      } 
	    
	    if ($fac->statut == 1 && $resteapayer > 0 && $user->rights->facture->paiement)
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"paiement.php3?facid=$facid&action=create\">Emettre un paiement</a>]</td>";
	      }
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      }
	    
	    if ($fac->statut == 1 && abs($resteapayer) == 0 && $fac->paye == 0) 
	      {
		if ($user->rights->facture->paiement)
		  {
		    print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=payed\">Classer 'Payée'</a>]</td>";
		  }
		else
		  {
		    print '<td align="center" width="25%">-</td>';
		  }
	      }
	    elseif ($fac->statut == 1 && $resteapayer > 0 && $user->rights->facture->envoyer) 
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=prerelance\">Envoyer une relance</a>]</td>";
	      }
	    else
	      {
		print '<td align="center" width="25%">-</td>';
	      }
	    
	    if ($fac->statut == 0 && $fac->total_ht > 0) 
	      {
		if ($user->rights->facture->valider)
		  {
		    print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=valid\">Valider</a>]</td>";
		  }
		else
		  {
		    print '<td align="center" width="25%">-</td>';
		  }
	      }
	    elseif ($fac->statut == 1 && $fac->paye == 0)
	      {
		if ($user->rights->facture->creer)
		  {
		    print "<td align=\"center\" width=\"25%\"><a href=\"facture.php3?facid=$facid&action=pdf\">Générer la facture</a></td>";
		  }
		else
		  {
		    print '<td align="center" width="25%">-</td>';
		  }
	      }
	    else
	      {
		print '<td align="center" width="25%">-</td>';
	      }
	    print "</tr></table>";
	  }
	print "<p>\n";

	/*
	 * Documents générés
	 *
	 */

	print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
	print_titre("Documents");
	print '<table width="100%" cellspacing="0" border="1" cellpadding="3">';
	
	$file = FAC_OUTPUTDIR . "/" . $fac->facnumber . "/" . $fac->facnumber . ".pdf";
	
	if (file_exists($file))
	  {
	    print "<tr $bc[0]><td>Facture PDF</a></td>";
	    print '<td><a href="'.FAC_OUTPUT_URL."/".$fac->facnumber."/".$fac->facnumber.'.pdf">'.$fac->facnumber.'.pdf</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	  }  
	
	$file = FAC_OUTPUTDIR . "/" . $fac->facnumber . "/" . $fac->facnumber . ".ps";
	
	if (file_exists($file))
	  {
	    print "<tr $bc[0]><td>Facture Postscript</a></td>";
	    print '<td><a href="'.FAC_OUTPUT_URL."/".$fac->facnumber."/".$fac->facnumber.'.ps">'.$fac->facnumber.'.ps</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	    
	    
	  }
	
	print "</table>\n";
	print '</td><td valign="top" width="50%">';
	print_titre("Actions");
	/*
	 * Liste des actions
	 *
	 */
	$sql = "SELECT ".$db->pdate("a.datea")." as da,  a.note";
	$sql .= " FROM llx_actioncomm as a WHERE a.fk_soc = $fac->socidp AND a.fk_action in (9,10) AND a.fk_facture = $facid";
	
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    if ($num)
	      {
		$i = 0; $total = 0;
		print '<TABLE border="1" cellspacing="0" cellpadding="4" width="100%">';
		print "<TR $bc[$var]>";
		print "<td>Date</td>";
		print "<td>Action</td>";
		print "</TR>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object( $i);
		    $var=!$var;
		    print "<tr $bc[$var]>";
		    print "<td>".strftime("%d %B %Y",$objp->da)."</TD>\n";
		    print '<td>'.stripslashes($objp->note).'</TD>';
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
	print "</td></tr></table>";
	
	/*
	 *
	 *
	 */
	if ($action == 'presend')
	  {
	    $replytoname = $user->fullname;
	    $from_name = $replytoname;

	    $replytomail = $user->email;
	    $from_mail = $replytomail;
	    
	    print "<form method=\"post\" action=\"$PHP_SELF?facid=$facid&action=send\">\n";
	    print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	    print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	    
	    print "<p><b>Envoyer la facture par mail</b>";
	    print "<table cellspacing=0 border=1 cellpadding=3>";
	    print '<tr><td>Destinataire</td><td colspan="5">';
	    
	    $form = new Form($db);	    
	    $form->select_array("destinataire",$soc->contact_email_array());
	    
	    print "</td><td><input size=\"30\" name=\"sendto\" value=\"$fac->email\"></td></tr>";
	    print "<tr><td>Expéditeur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	    print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	    print "<td>$replytomail</td></tr></table>";
	    
	    print "<input type=\"submit\" value=\"Envoyer\"></form>";
	  }

	if ($action == 'prerelance')
	  {
	    $replytoname = $user->fullname;
	    $from_name = $replytoname;

	    $replytomail = $user->email;
	    $from_mail = $replytomail;
	    
	    print "<form method=\"post\" action=\"$PHP_SELF?facid=$facid\">\n";
	    print '<input type="hidden" name="action" value="relance">';
	    print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	    print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	    
	    print_titre("Envoyer une relance");
	    print "<table cellspacing=0 border=1 cellpadding=3>";
	    print '<tr><td>Destinataire</td><td colspan="5">';
	    
	    $form = new Form($db);	    
	    $form->select_array("destinataire",$soc->contact_email_array());
	    
	    print "</td><td><input size=\"30\" name=\"sendto\" value=\"$fac->email\"></td></tr>";
	    print "<tr><td>Expéditeur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	    print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	    print "<td>$replytomail</td></tr></table>";
	    
	    print "<input type=\"submit\" value=\"Envoyer\"></form>";
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
		print "<p>";
		if ($num >1)
		  {
		    print_titre("Propositions commerciales associées");
		  }
		else
		  {
		    print_titre("Proposition commerciale associée");
		  }

		print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr class="liste_titre">';
		print "<td>Numéro</td>";
		print "<td>Date</td>";
		print '<td align="right">Prix</td>';
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
    /***************************************************************************
     *                                                                         *
     *                      Mode Liste                                         *
     *                                                                         * 
     *                                                                         *
     ***************************************************************************/
    if ($page == -1)
      {
	$page = 0 ;
      }

    if ($user->rights->facture->lire)
      {
	$limit = $conf->liste_limit;
	$offset = $limit * $page ;

	if ($sortorder == "")
	  $sortorder="DESC";

	if ($sortfield == "")
	  $sortfield="f.datef";

	$sql = "SELECT s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid, f.fk_statut";
	$sql .= " FROM llx_societe as s,llx_facture as f WHERE f.fk_soc = s.idp";
	
	if ($socidp)
	  $sql .= " AND s.idp = $socidp";
	
	if ($month > 0)
	  $sql .= " AND date_format(f.datef, '%m') = $month";
	
	if ($filtre)
	  {
	    $filtrearr = split(",", $filtre);
	    foreach ($filtrearr as $fil)
	      {
		$filt = split(":", $fil);
		$sql .= " AND " . $filt[0] . " = " . $filt[1];
	      }
	  }
	
	if ($year > 0)
	  $sql .= " AND date_format(f.datef, '%Y') = $year";

	if (strlen($HTTP_POST_VARS["sf_ref"]) > 0)
	  {
	    $sql .= " AND f.facnumber like '%".$HTTP_POST_VARS["sf_ref"] . "%'";
	  }

	
	$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
	$sql .= $db->plimit($limit + 1,$offset);
	
	$result = $db->query($sql);
      }
    if ($result)
      {
	$num = $db->num_rows();
	print_barre_liste("Factures",$page,$PHP_SELF,"&socidp=$socidp",$sortfield,$sortorder,'',$num);

	$i = 0;
	print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print '<TR class="liste_titre">';
	print '<TD>Num&eacute;ro</TD>';
	print '<td>';
	print_liste_field_titre("Société",$PHP_SELF,"s.nom","","&socidp=$socidp");
	print '</td><TD align="right">';
	print_liste_field_titre("Date",$PHP_SELF,"f.datef","","&socidp=$socidp");
	print '</td><TD align="right">Montant</TD>';
	print '<td>&nbsp;</td>';
	print "</TR>\n";
      
	if ($num > 0) 
	  {
	    $var=True;
	    while ($i < min($num,$limit))
	      {
		$objp = $db->fetch_object($i);
		$var=!$var;

		print "<tr $bc[$var]>";
		if ($objp->paye)
		  {
		    $class = "normal";
		  }
		else
		  {
		    if ($objp->fk_statut == 0)
		      {
			$class = "normal";
		      }
		    else
		      {
			$class = "impayee";
		      }
		  }

		print '<td><a class="'.$class.'" href="facture.php3?facid='.$objp->facid.'">' . $objp->facnumber;
		print "</a></TD>\n";
		print '<TD><a class="'.$class.'" href="fiche.php3?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		
		if ($objp->df > 0 )
		  {
		    print "<TD align=\"right\">";
		    $y = strftime("%Y",$objp->df);
		    $m = strftime("%m",$objp->df);
		    
		    print strftime("%d",$objp->df)."\n";
		    print ' <a class="'.$class.'" href="facture.php3?year='.$y.'&month='.$m.'">';
		    print strftime("%B",$objp->df)."</a>\n";
		    print ' <a class="'.$class.' "href="facture.php3?year='.$y.'">';
		    print strftime("%Y",$objp->df)."</a></TD>\n";
		  }
		else
		  {
		    print "<TD align=\"right\"><b>!!!</b></TD>\n";
		  }
		
		print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
		
		if (! $objp->paye)
		  {
		    if ($objp->fk_statut == 0)
		      {
			print '<td align="center">brouillon</td>';
		      }
		    else
		      {
			print '<td align="center"><a href="facture.php3?filtre=paye:0,fk_statut:1">impayée</a></td>';
		      }
		  }
		else
		  {
		    print '<td>&nbsp;</td>';
		  }
		
		print "</TR>\n";
		$i++;
	      }
	  }
	
	print "</table>";
	$db->free();
      }
    else
      {
	print $db->error() . "<br>" . $sql;
      }    
  }
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
