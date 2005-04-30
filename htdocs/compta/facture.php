<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
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
 *
 */

/**
	    \file       htdocs/compta/facture.php
		\ingroup    facture
		\brief      Page de création d'une facture
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('facture');
$user->getrights('banque');

if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("bills");

require_once "../facture.class.php";
require_once "../paiement.class.php";
if ($conf->projet->enabled) require_once "../project.class.php";
if ($conf->propal->enabled) require_once "../propal.class.php";
if ($conf->contrat->enabled) require_once "../contrat/contrat.class.php";
if ($conf->commande->enabled) require_once "../commande/commande.class.php";
require_once "../lib/CMailFile.class.php";




if ($_GET["socidp"]) { $socidp=$_GET["socidp"]; }
if (isset($_GET["msg"])) { $msg=urldecode($_GET["msg"]); }

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;


/*
 *
 */ 
if ($_POST["action"] == 'classin') 
{
  $facture = new Facture($db);
  $facture->fetch($_POST["facid"]);
  $facture->classin($_POST["projetid"]);
}

/*
 *
 */	
if ($_POST["action"] == 'add') 
{
  $datefacture = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]); 

  $facture = new Facture($db, $_POST["socid"]);

  $facture->number         = $_POST["facnumber"];
  $facture->date           = $datefacture;      
  $facture->note           = $_POST["note"];
  
  if ($_POST["fac_rec"] > 0)
    {
      /*
       * Facture récurrente
       */
      $facture->fac_rec = $_POST["fac_rec"];
      $facid = $facture->create($user);
    }
  else
    {
      $facture->projetid       = $_POST["projetid"];
      $facture->cond_reglement = $_POST["condid"];
      $facture->mode_reglement = $_POST["mode_reglement"];
      $facture->amount         = $_POST["amount"];
      $facture->remise         = $_POST["remise"];
      $facture->remise_percent = $_POST["remise_percent"];

      if (!$_POST["propalid"] && !$_POST["commandeid"]) 
	{      
	  for ($i = 1 ; $i <= $NBLINES ; $i++)
	    {
	      if ($_POST["idprod${i}"]) {
                $startday='';
                $endday='';
                if ($_POST["date_start${i}year"] && $_POST["date_start${i}month"] && $_POST["date_start${i}day"]) {
		  $startday=$_POST["date_start${i}year"].'-'.$_POST["date_start${i}month"].'-'.$_POST["date_start${i}day"];
		}
                if ($_POST["date_end${i}year"] && $_POST["date_end${i}month"] && $_POST["date_end${i}day"]) {
		  $endday=$_POST["date_end${i}year"].'-'.$_POST["date_end${i}month"].'-'.$_POST["date_end${i}day"];
                }
		$facture->add_product($_POST["idprod${i}"],$_POST["qty${i}"],$_POST["remise_percent${i}"],$startday,$endday);
                
	      }
	    }	  
	  $facid = $facture->create($user);
	  
	  if ($facid)
	    {
	      Header("Location: facture.php?facid=".$facid);
	    }
	}
      else
	{
	  /*
	   * Propale
	   */
	  if ($_POST["propalid"])
	    {
	      $facture->propalid = $_POST["propalid"];
	  
	      $facid = $facture->create($user);
	      
	      if ($facid)
		{
		  $prop = New Propal($db);
		  if ( $prop->fetch($_POST["propalid"]) )
		    {
		      for ($i = 0 ; $i < sizeof($prop->lignes) ; $i++)
			{
			  //			  print "<pre>DEBUG: la propale précédente en ligne " . $prop->lignes[$i]->libelle . " avait comme prix : " . $prop->lignes[$i]->price . " !</pre>\n";
			  $result = $facture->addline($facid,
						      addslashes($prop->lignes[$i]->libelle),
						      $prop->lignes[$i]->subprice,
						      $prop->lignes[$i]->qty,
						      $prop->lignes[$i]->tva_tx,
						      $prop->lignes[$i]->product_id,
						      $prop->lignes[$i]->remise_percent);
			}
		    }
		  else
		    {
		      print $langs->trans("UnknownError");
		    }
		}
	    }

	  /*
	   * Commande
	   */
	  if ($_POST["commandeid"])
	    {
	      $facture->commandeid = $_POST["commandeid"];
	      $facid = $facture->create($user);

	      if ($facid)
		{
		  $comm = New Commande($db);
		  if ( $comm->fetch($_POST["commandeid"]) )
		    {
		      $lines = $comm->fetch_lignes();
		      for ($i = 0 ; $i < sizeof($lines) ; $i++)
			{
			  $result = $facture->addline($facid,
						      addslashes($lines[$i]->description),
						      $lines[$i]->subprice,
						      $lines[$i]->qty,
						      $lines[$i]->tva_tx,
						      $lines[$i]->product_id,
						      $lines[$i]->remise_percent);
			}
		    }
		  else
		    {
		      print $langs->trans("UnknownError");
		    }
		}
	      else
		{
		  print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>";
		  print "<p>Retour à la <a href=\"propal.php?propalid=$propalid\">propale</a>";
		  dolibarr_print_error($db);
		}
	    }
	
	  if ($facid)
	    {
	      Header("Location: facture.php?facid=".$facid);
	    }
	}
    }
}

/*
 *
 */

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == "yes" && $user->rights->facture->valider)
{
  $fac = new Facture($db);
  $fac->fetch($_GET["facid"]);
  $soc = new Societe($db);
  $soc->fetch($fac->socidp);
  $result = $fac->set_valid($fac->id, $user, $soc);
  if ($result)
    {
      facture_pdf_create($db, $fac->id);
    }
}

if ($_GET["action"] == 'payed' && $user->rights->facture->paiement) 
{
  $fac = new Facture($db);
  $result = $fac->set_payed($_GET["facid"]);
}

if ($_POST["action"] == 'setremise' && $user->rights->facture->creer) 
{
  $fac = new Facture($db);
  $fac->fetch($_GET["facid"]);

  $fac->set_remise($user, $_POST["remise"]);
} 

if ($_POST["action"] == 'addligne' && $user->rights->facture->creer) 
{
  $fac = new Facture($db);
  $fac->fetch($_POST["facid"]);
  $datestart='';
  $dateend='';
  if ($_POST["date_startyear"] && $_POST["date_startmonth"] && $_POST["date_startday"]) {
    $datestart=$_POST["date_startyear"].'-'.$_POST["date_startmonth"].'-'.$_POST["date_startday"];
  }
  if ($_POST["date_endyear"] && $_POST["date_endmonth"] && $_POST["date_endday"]) {
    $dateend=$_POST["date_endyear"].'-'.$_POST["date_endmonth"].'-'.$_POST["date_endday"];
  }
  $result = $fac->addline($_POST["facid"],
			  $_POST["desc"],
			  $_POST["pu"],
			  $_POST["qty"],
			  $_POST["tva_tx"],
			  0,
			  $_POST["remise_percent"],
			  $datestart,
			  $dateend
			  );

  $_GET["facid"]=$_POST["facid"];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST["action"] == 'updateligne' && $user->rights->facture->creer && $_POST["save"] == $langs->trans("Save")) 
{
  $fac = new Facture($db,"",$_POST["facid"]);
  $fac->fetch($_POST["facid"]);
  $datestart='';
  $dateend='';
  if ($_POST["date_startyear"] && $_POST["date_startmonth"] && $_POST["date_startday"]) {
    $datestart=$_POST["date_startyear"].'-'.$_POST["date_startmonth"].'-'.$_POST["date_startday"];
  }
  if ($_POST["date_endyear"] && $_POST["date_endmonth"] && $_POST["date_endday"]) {
    $dateend=$_POST["date_endyear"].'-'.$_POST["date_endmonth"].'-'.$_POST["date_endday"];
  }

  $result = $fac->updateline($_POST["rowid"],
			     $_POST["desc"],
			     $_POST["price"],
			     $_POST["qty"],
			     $_POST["remise_percent"],
			     $datestart,
			     $dateend
			     );

  $_GET["facid"]=$_POST["facid"];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST["action"] == 'updateligne' && $user->rights->facture->creer && $_POST["cancel"] == $langs->trans("Cancel")) 
{
  Header("Location: facture.php?facid=".$_POST["facid"]);   // Pour réaffichage de la fiche en cours d'édition
}

if ($_GET["action"] == 'deleteline' && $user->rights->facture->creer) 
{
  $fac = new Facture($db,"",$_GET["facid"]);
  $fac->fetch($_GET["facid"]);
  $result = $fac->deleteline($_GET["rowid"]);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  if ($user->rights->facture->supprimer ) 
    {
      $fac = new Facture($db);
      $fac->delete($_GET["facid"]);
      $_GET["facid"] = 0 ;
      Header("Location: facture.php");
    }
}

if ($_POST["action"] == 'confirm_canceled' && $_POST["confirm"] == yes)
{
  if ($user->rights->facture->supprimer ) 
    {
      $fac = new Facture($db);
      $result = $fac->set_canceled($_GET["facid"]);
      $_GET["facid"] = 0 ;
      Header("Location: facture.php");
    }
}

/* 
 * Ordonnancement des lignes
 */

if ($_GET["action"] == 'up' && $user->rights->facture->creer) 
{
  $fac = new Facture($db,"",$_GET["facid"]);
  $fac->line_up($_GET["rowid"]);
}

if ($_GET["action"] == 'down' && $user->rights->facture->creer) 
{
  $fac = new Facture($db,"",$_GET["facid"]);
  $fac->line_down($_GET["rowid"]);
}

/*
 * Action envoi de mail
 */
if ($_POST["action"] == 'send' || $_POST["action"] == 'relance')
{
  $langs->load("mails");
    
  $fac = new Facture($db,"",$_POST["facid"]);
  if ( $fac->fetch($_POST["facid"]) )
    {
			$forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
			$facref = str_replace($forbidden_chars,"_",$fac->ref);
			$file = $conf->facture->dir_output . "/" . $facref . "/" . $facref . ".pdf";
      
      if (is_readable($file))
	{
      $soc = new Societe($db, $fac->socidp);

	  if ($_POST["sendto"]) {
	    // Le destinataire a été fourni via le champ libre
	    $sendto = $_POST["sendto"];
	    $sendtoid = 0;
	  }
	  elseif ($_POST["receiver"]) {
	    // Le destinataire a été fourni via la liste déroulante
	    $sendto = $soc->contact_get_email($_POST["receiver"]);
	    $sendtoid = $_POST["receiver"];
	  }
	  
	  if (strlen($sendto))
	    {	  
	      if ($_POST["action"] == 'send') {
		$subject = $langs->trans("Bill")." $fac->ref";
		$actioncode=9;
		$actionmsg="Envoyée à $sendto";
		$actionmsg2="Envoi Facture par mail";
	      }
	      if ($_POST["action"] == 'relance') 	{
		$subject = "Relance facture $fac->ref";
		$actioncode=10;
		$actionmsg="Relance envoyée à $sendto";
		$actionmsg2="Relance Facture par mail";
	      }
	      $message = $_POST["message"];
	      $from = $_POST["fromname"] . " <" . $_POST["frommail"] .">";
	      $replyto = $_POST["replytoname"]. " <" . $_POST["replytomail"].">";

	      $filepath[0] = $file;
	      $filename[0] = $fac->ref.".pdf";
	      $mimetype[0] = "application/pdf";
	      $filepath[1] = $_FILES['addedfile']['tmp_name'];
	      $filename[1] = $_FILES['addedfile']['name'];
	      $mimetype[1] = $_FILES['addedfile']['type'];
	      
	      // Envoi de la facture
	      $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath, $mimetype,$filename,$sendtocc);

	      if ( $mailfile->sendfile() )
		{		  
          $msg='<div class="ok">'.$langs->trans("MailSuccessfulySent",$from,$sendto).'.</div>';

		  $sendto = htmlentities($sendto);
			  
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), '$actioncode' ,'$fac->socidp' ,'$actionmsg','$fac->id','$sendtoid','$user->id', '$actionmsg2',100);";
	
		  if (! $db->query($sql) )
		    {
		      dolibarr_print_error($db);
		    }
		  else
		    {
		      // Renvoie sur la page de la facture
		      Header("Location: facture.php?facid=".$fac->id."&msg=".urlencode($msg));
		    } 	      	      
		}
	      else
		{
		  $msg='<div class="error">'.$langs->trans("ErrorFailedToSendMail",$from,$sendto).' !</div>';
		}	  
	    }
	  else
	    {
		  $msg='<div class="error">'.$langs->trans("ErrorMailRecipientIsEmpty").' !</div>';
	      dolibarr_syslog("Le mail du destinataire est vide");
	    }

	}
      else
	{
	  dolibarr_syslog("Impossible de lire :".$file);
	}
    }
  else
    {
      dolibarr_syslog("Impossible de lire les données de la facture. Le fichier facture n'a peut-être pas été généré.");
    }
}

/*
 * Générer ou regénérer le PDF
 */
if ($_GET["action"] == 'pdf')
{
  // Generation de la facture définie dans /includes/modules/facture/modules_facture.php
  // Génère également le fichier meta dans le m$eme répertoire (pour faciliter les recherches et indexation)
  facture_pdf_create($db, $_GET["facid"]);
} 


llxHeader('',$langs->trans("Bill"),'Facture');

$html = new Form($db);


/*********************************************************************
 *
 * Mode creation
 *
 ************************************************************************/
if ($_GET["action"] == 'create') 
{
  
  print_titre($langs->trans("NewBill"));

  if ($_GET["propalid"])
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
      $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";      
      $sql .= " AND p.rowid = ".$_GET["propalid"];
    }
  else
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
      $sql .= "FROM ".MAIN_DB_PREFIX."societe as s ";
      $sql .= "WHERE s.idp = ".$_GET["socidp"];      
    }

  if ($_GET["commandeid"])
    {
      $commande = New Commande($db);
      $commande->fetch($_GET["commandeid"]);
      $societe_id = $commande->soc_id;
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object();

	  $soc = new Societe($db);
	  $soc->fetch($obj->idp);
       
	  print '<form action="facture.php" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="socid" value="'.$obj->idp.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="0">';

	  print '<table class="border" width="100%">';
	  
	  print '<tr><td>'.$langs->trans("Company").' :</td><td>'.$soc->nom.'</td>';
	  print '<td class="border">'.$langs->trans("Comments").'</td></tr>';

	  print '<tr><td>'.$langs->trans("Author").' :</td><td>'.$user->fullname.'</td>';
	  
	  print '<td rowspan="6" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="5">';
		if (is_object($commande) && !empty($commande->projet_id))
		{
			print $commande->note;
		}
	  print '</textarea></td></tr>';	
	  
	  print '<tr><td>'.$langs->trans("Date").' :</td><td>';
	  $html->select_date();
	  print '</td></tr>';

	  print '<tr><td>'.$langs->trans("Ref").' :</td><td>Provisoire</td></tr>';
	  print '<input name="facnumber" type="hidden" value="provisoire">';

	  print "<tr><td>Conditions de réglement :</td><td>";
	  $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."cond_reglement ORDER BY sortorder";
	  $result = $db->query($sql);
	  $conds=array();
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $conds[$objp->rowid]=$objp->libelle;
		  $i++;
		}
	      $db->free();
	    }
	  
	  $html->select_array("condid",$conds);
	  print "</td></tr>";
	  
	  /* Mode de réglement */

	  print "<tr><td>Mode de réglement :</td><td>";
	  $sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY libelle";
	  $result = $db->query($sql);
	  $modesregl=array();
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $modesregl[$objp->id]=$objp->libelle;
		  $i++;
		}
	      $db->free();
	    }
	  
	  $html->select_array("mode_reglement",$modesregl);
	  print "</td></tr>";

	  /* -- */

	  if ($conf->projet->enabled)
	    {
	      $langs->load("projects");
	      print '<tr><td>'.$langs->trans("Project").' :</td><td>';
	      $proj = new Project($db);
	      $html->select_array(
			"projetid",
			$proj->liste_array($socidp),
			(is_object($commande) && !empty($commande->projet_id)) ? $commande->projet_id : ''
			);
	      print "</td></tr>";
	    }
	  else
	    {
	      print "<tr><td colspan=\"2\">&nbsp;</td></tr>";
	    }
      	  
	  if ($_GET["propalid"] > 0)
	    {
	      $amount = ($obj->price);
	      print '<input type="hidden" name="amount"   value="'.$amount.'">'."\n";
	      print '<input type="hidden" name="total"    value="'.$obj->total.'">'."\n";
	      print '<input type="hidden" name="remise"   value="'.$obj->remise.'">'."\n";
	      print '<input type="hidden" name="remise_percent"   value="'.$obj->remise_percent.'">'."\n";
	      print '<input type="hidden" name="tva"      value="'.$obj->tva.'">'."\n";
	      print '<input type="hidden" name="propalid" value="'.$_GET["propalid"].'">';
	      
	      print '<tr><td>'.$langs->trans("Proposal").'</td><td colspan="2">'.$obj->ref.'</td></tr>';
	      print '<tr><td>'.$langs->trans("TotalHT").'</td><td colspan="2">'.price($amount).'</td></tr>';
	      print '<tr><td>'.$langs->trans("VAT").'</td><td colspan="2">'.price($obj->tva)."</td></tr>";
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($obj->total)."</td></tr>";
	    }
	  elseif ($_GET["commandeid"] > 0)
	    {
	      print '<input type="hidden" name="commandeid" value="'.$commande->id.'">';
	      print '<tr><td>'.$langs->trans("Order").'</td><td colspan="2">'.$commande->ref.'</td></tr>';
	      print '<tr><td>'.$langs->trans("TotalHT").'</td><td colspan="2">'.price($commande->total_ht).'</td></tr>';
	      print '<tr><td>'.$langs->trans("VAT").'</td><td colspan="2">'.price($commande->total_tva)."</td></tr>";
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($commande->total_ttc)."</td></tr>";
	    }
	  else
	    {
	      print '<tr><td colspan="3">&nbsp;</td></tr>';
	      print '<tr><td colspan="3">';
	      	      
	      print '<table class="noborder">';
	      print '<tr><td>Services/Produits prédéfinis</td><td>'.$langs->trans("Qty").'</td><td>'.$langs->trans("Discount").'</td><td> &nbsp; &nbsp; </td>';
	      if ($conf->service->enabled) {
		print '<td>Si produit de type service à durée limitée</td></tr>';
	      }
	      for ($i = 1 ; $i <= $NBLINES ; $i++)
		{
		  print '<tr><td>';
		  $html->select_produits('',"idprod$i");
		  print '</td>';
		  print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
		  print '<td><input type="text" size="4" name="remise_percent'.$i.'" value="0">%</td>';
		  print '<td>&nbsp;</td>';
		  // Si le module service est actif, on propose des dates de début et fin à la ligne
		  if ($conf->service->enabled) {
		    print '<td>';
		    print 'Du ';
		    print $html->select_date('',"date_start$i",0,0,1);
		    print '<br>au ';
		    print $html->select_date('',"date_end$i",0,0,1);
		    print '</td>';
		  }
		  print "</tr>\n";
		}	      	      

	      print '</table>';
	      print '</td></tr>';
	    }
	  /*
	   * Factures récurrentes
	   *
	   */
	  if ($_GET["propalid"] == 0 && $_GET["commandeid"] == 0)
	    {
	      $sql = "SELECT r.rowid, r.titre, r.amount FROM ".MAIN_DB_PREFIX."facture_rec as r";
	      $sql .= " WHERE r.fk_soc = ".$soc->id;
	      if ( $db->query($sql) )
		{
		  $num = $db->num_rows();	
		  $i = 0;	
		  
		  if ($num > 0)
		    {
		      print '<tr><td colspan="3">Factures récurrentes : <select class="flat" name="fac_rec">';
		      print '<option value="0" selected></option>';
		      while ($i < $num)
			{
			  $objp = $db->fetch_object();
			  print "<option value=\"$objp->rowid\">$objp->titre : $objp->amount</option>\n";
			  $i++;
			}
		      print '</select></td></tr>';
		    }
		  $db->free();
		}
	      else
		{
		  dolibarr_print_error($db);
		}
	    }
	  /*
	   *
	   */	  
	  print '<tr><td colspan="3" align="center"><input type="submit" name="bouton" value="'.$langs->trans("CreateDraft").'"></td></tr>';
	  print "</form>\n";
	  print "</table>\n";

	  if ($_GET["propalid"])
	    {
	      /*
	       * Produits
	       */
	      print_titre($langs->trans("Products"));
	      
	      print '<table class="noborder" width="100%">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Product").'</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">'.$langs->trans("Discount").'</td><td align="center">'.$langs->trans("Qty").'</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = ".$_GET["propalid"];
	      $sql .= " ORDER BY pt.rowid ASC";
	      $result = $db->query($sql);
	      if ($result) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  $var=True;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
		      print '<td>'.$objp->product.'</td>';
		      print "<td align=\"right\">".price($objp->price)."</td>";
		      print '<td align="center">'.$objp->remise_percent.'%</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      $sql = "SELECT pt.rowid, pt.description as product,  pt.price, pt.qty, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt ";
	      $sql .= " WHERE  pt.fk_propal = ".$_GET["propalid"];
	      $sql .= " AND pt.fk_product = 0";
	      $sql .= " ORDER BY pt.rowid ASC";
	      if ($db->query($sql)) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]><td>&nbsp;</td>\n";
		      print '<td>'.$objp->product.'</td>';
		      print '<td align="right">'.price($objp->price).'</td>';
		      print '<td align="center">'.$objp->remise_percent.'%</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      else
		{
		  dolibarr_print_error($db);
		}

	      print '</table>';
	    }

	  /*
	   * Produits dans la commande
	   *
	   */
	  if ($_GET["commandeid"])
	    {
	      print_titre("Produits");
	      
	      print '<table class="noborder" width="100%">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Product").'</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">'.$langs->trans("Discount").'</td><td align="center">'.$langs->trans("Qty").'</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.subprice, pt.qty, p.rowid as prodid, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as pt, ".MAIN_DB_PREFIX."product as p";
	      $sql .= " WHERE pt.fk_product = p.rowid AND pt.fk_commande = ".$commande->id;
	      $sql .= " ORDER BY pt.rowid ASC";

	      $result = $db->query($sql);
	      if ($result) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  $var=True;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
		      print '<td>'.$objp->product.'</td>';
		      print '<td align="right">'.price($objp->subprice).'</td>';
		      print '<td align="center">'.$objp->remise_percent.'%</td>';
		      print '<td align="center">'.$objp->qty.'</td></tr>';
		      $i++;
		    }
		}
	      else
		{
		  dolibarr_print_error($db);
		}

	      print '</table>';
	    }

	}
    } 
  else 
    {
      dolibarr_print_error($db);
    }
} 
else 
/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
{

  if ($_GET["facid"] > 0)
    {      
      if ($msg) {
        print "$msg<br>";
      }

      $fac = New Facture($db);
      if ( $fac->fetch($_GET["facid"], $user->societe_id) > 0)
	{

	  $soc = new Societe($db, $fac->socidp);
	  $soc->fetch($fac->socidp);

	  $author = new User($db);
	  $author->id = $fac->user_author;
	  $author->fetch();

        $h = 0;
        
        $head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
        $head[$h][1] = $langs->trans("CardBill");
        $hselected = $h;
        $h++;
        $head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
        $head[$h][1] = $langs->trans("Preview");
        $h++;      

	if ($fac->mode_reglement == 3)
	  {
	    $head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
	    $head[$h][1] = $langs->trans("StandingOrders");
	    $h++;
	  }

        $head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
        $head[$h][1] = $langs->trans("Note");
        $h++;      
        $head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
        $head[$h][1] = $langs->trans("Info");
        $h++;      
        
        dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $fac->ref");

  	  
	  /*
	   * Confirmation de la suppression de la facture
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"]."?facid=$fac->id",$langs->trans("DeleteBill"),$langs->trans("ConfirmDeleteBill"),"confirm_delete");
	      print '<br />';
	    }

	  /*
	   * Confirmation du classement abandonné
	   *
	   */
	  if ($_GET["action"] == 'canceled')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"]."?facid=$fac->id","Classer la facture à l'état 'Abandonnée'","La totalité du paiement de cette facture n'a pas été réalisée. Etes-vous sûr de vouloir abandonner définitivement cette facture ?","confirm_canceled");
	      print '<br />';
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      $numfa = facture_get_num($soc);
	      $html->form_confirm("facture.php?facid=$fac->id","Valider la facture sous la référence no ".$numfa,"Etes-vous sûr de vouloir valider cette facture avec la référence no $numfa ?","confirm_valid");
	      print '<br />';
	    }

	  /*
	   *   Facture
	   */
	  print '<table class="border" width="100%">';
	  print '<tr><td>'.$langs->trans("Company").'</td>';
	  print '<td colspan="3">';
	  print '<b><a href="fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print "<td>Conditions de réglement : " . $fac->cond_reglement ."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print "<td colspan=\"3\">".strftime("%A %d %B %Y",$fac->date)."</td>\n";
	  print "<td>Date limite de réglement : " . strftime("%d %B %Y",$fac->date_lim_reglement) ."</td></tr>";

      print '<tr>';
      if ($conf->projet->enabled) {
          $langs->load("projects");
    	  print '<td height=\"10\">'.$langs->trans("Project").'</td><td colspan="3">';
    	  if ($fac->projetid > 0)
    	    {
    	      $projet = New Project($db);
    	      $projet->fetch($fac->projetid);
    	      print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$fac->projetid.'">'.$projet->title.'</a>';
    	    }
    	  else
    	    {
    	      print '<a href="facture.php?facid='.$fac->id.'&amp;action=classer">Classer la facture</a>';
    	    }
    	  print "&nbsp;</td>";
      } else {
    	  print '<td height=\"10\">&nbsp;</td><td colspan="3">';
    	  print "&nbsp;</td>";
      }
	  print '<td rowspan="8" valign="top">';
	  	  
	  /*
	   * Paiements
	   */
	  print $langs->trans("Payments").' :<br>';
	  $sql = "SELECT ".$db->pdate("datep")." as dp, pf.amount,";
	  $sql .= "c.libelle as paiement_type, p.num_paiement, p.rowid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement_facture as pf";
	  $sql .= " WHERE pf.fk_facture = ".$fac->id." AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid";
	  $sql .= " ORDER BY dp DESC";
	
	  $result = $db->query($sql);

	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;
	      print '<table class="noborder" width="100%">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Type").'</td>';
	      print '<td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';
    
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $var=!$var;
		  print "<tr $bc[$var]><td>";
		  print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_file().'</a>';
		  print "&nbsp;".strftime("%d %B %Y",$objp->dp)."</td>\n";
		  print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
		  print '<td align="right">'.price($objp->amount)."</td><td>".$conf->monnaie."</td>\n";
		  print "</tr>";
		  $totalpaye += $objp->amount;
		  $i++;
		}

	      if ($fac->paye == 0)
		{
		  print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AlreadyPayed")." :</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>".$conf->monnaie."</td></tr>\n";
		  print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" style=\"border: 1px solid;\">".price($fac->total_ttc)."</td><td>".MAIN_MONNAIE."</td></tr>\n";
		
		  $resteapayer = $fac->total_ttc - $totalpaye;

		  print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
		  print "<td align=\"right\" style=\"border: 1px solid;\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td>".$conf->monnaie."</td></tr>\n";
		}
	      print "</table>";
	      $db->free();
	    } else {
	      dolibarr_print_error($db);
	    }
	
	  print "</td></tr>";
	
	  print "<tr><td height=\"10\">".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td></tr>";
  
  	  print '<tr><td height=\"10\">'.$langs->trans("GlobalDiscount").'</td>';
	  if ($fac->brouillon == 1 && $user->rights->facture->creer) 
	    {
	      print '<form action="facture.php?facid='.$fac->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	      print '<td colspan="3"><input type="text" name="remise" size="3" value="'.$fac->remise_percent.'">% ';
	      print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	      print '</form>';
	    }
	  else {
	      print '<td colspan="3">'.$fac->remise_percent.'%</td>';
	    }
      print '</tr>';
      
	  print '<tr><td height=\"10\">'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("VAT").'</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';
	  print '<tr><td height=\"10\">'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("Status").'</td><td align="left" colspan="3">'.($fac->getLibStatut()).'</td></tr>';

	  if ($fac->note)
	    {
	      print '<tr><td colspan="4">'.$langs->trans("Note").' : '.nl2br($fac->note)."</td></tr>";
	    }
	  else {
	    print '<tr><td colspan="4">&nbsp;</td></tr>';
	  }

	  print "</table><br>";

        /*
         * Lignes de factures
         *
         */
        $sql  = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux";
        $sql .= " , l.remise_percent, l.subprice,";
        $sql .= $db->pdate("l.date_start")." as date_start";
        $sql .= " , ".$db->pdate("l.date_end")." as date_end, ";
        $sql .= " p.fk_product_type";
        $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l ";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON l.fk_product=p.rowid";
        $sql .= " WHERE l.fk_facture = ".$fac->id;
        $sql .= " ORDER BY l.rang ASC, l.rowid";
        
        $resql = $db->query($sql);
        if ($resql)
        {
            $num_lignes = $db->num_rows($resql);
            $i = 0; $total = 0;
        
            print '<table class="noborder" width="100%">';
            if ($num_lignes)
            {
                print "<tr class=\"liste_titre\">";
                print '<td width="54%">'.$langs->trans("Description").'</td>';
                print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
                print '<td width="12%" align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td width="8%" align="right">'.$langs->trans("Qty").'</td>';
                print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
                print '<td width="10%" align="right">'.$langs->trans("AmountHT").'</td>';
                print '<td colspan="3">&nbsp;</td>';
                print "</tr>\n";
            }
            $var=True;
            while ($i < $num_lignes)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;

                // Update ligne de facture
                if ($_GET["action"] != 'editline' || $_GET["rowid"] != $objp->rowid)
                {

                print "<tr $bc[$var]>";
                if ($objp->fk_product > 0)
                {
                    print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                    if ($objp->fk_product_type) print img_object($langs->trans("ShowService"),"service");
                    else print img_object($langs->trans("ShowProduct"),"product");
                    print ' '.stripslashes(nl2br($objp->description)).'</a>';
                    if ($objp->date_start && $objp->date_end) { print " (Du ".dolibarr_print_date($objp->date_start)." au ".dolibarr_print_date($objp->date_end).")"; }
                    if ($objp->date_start && ! $objp->date_end) { print " (A partir du ".dolibarr_print_date($objp->date_start).")"; }
                    if (! $objp->date_start && $objp->date_end) { print " (Jusqu'au ".dolibarr_print_date($objp->date_end).")"; }
                    print '</td>';
                }
                else
                {
                    print "<td>".stripslashes(nl2br($objp->description));
                    if ($objp->date_start && $objp->date_end) { print " (Du ".dolibarr_print_date($objp->date_start)." au ".dolibarr_print_date($objp->date_end).")"; }
                    if ($objp->date_start && ! $objp->date_end) { print " (A partir du ".dolibarr_print_date($objp->date_start).")"; }
                    if (! $objp->date_start && $objp->date_end) { print " (Jusqu'au ".dolibarr_print_date($objp->date_end).")"; }
                    print "</td>\n";
                }
        
                print '<td align="right">'.$objp->tva_taux.'%</td>';
                print '<td align="right">'.price($objp->subprice)."</td>\n";
                print '<td align="right">'.$objp->qty.'</td>';
                if ($objp->remise_percent > 0)
                {
                    print '<td align="right">'.$objp->remise_percent."%</td>\n";
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }
                print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100)."</td>\n";
        
                // Icone d'edition et suppression
                if ($fac->statut == 0  && $user->rights->facture->creer)
                {
                    print '<td align="right"><a href="facture.php?facid='.$fac->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
                    print img_edit();
                    print '</a></td>';
                    print '<td align="right"><a href="facture.php?facid='.$fac->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
                    print img_delete();
                    print '</a></td>';
        
                    print '<td align="right" height="28">';
                    if ($i > 0)
                    {
                        print '<a href="facture.php?facid='.$fac->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
                        print img_up();
                        print '</a>';
                    }
                    if ($i < $num_lignes-1)
                    {
                        print '<a href="facture.php?facid='.$fac->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
                        print img_down();
                        print '</a></td>';
                    }
                }
                else
                {
                    print '<td colspan="3">&nbsp;</td>';
                }
                print "</tr>";
        
                }
                
                // Update ligne de facture
                if ($_GET["action"] == 'editline' && $_GET["rowid"] == $objp->rowid)
                {
                    print '<form action="facture.php" method="post">';
                    print '<input type="hidden" name="action" value="updateligne">';
                    print '<input type="hidden" name="facid" value="'.$fac->id.'">';
                    print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
                    print "<tr $bc[$var]>";
                    print '<td><textarea name="desc" cols="50" rows="2">'.stripslashes($objp->description).'</textarea></td>';
                    print '<td align="right">';
                    //print $html->select_tva("tva_tx",$objp->tva_taux);
                    print $objp->tva_taux."%";    // Taux tva dépend du produit, donc on ne doit pas pouvoir le changer ici
                    print '</td>';
                    print '<td align="right"><input size="8" type="text" name="price" value="'.price($objp->subprice).'"></td>';
                    print '<td align="right"><input size="4" type="text" name="qty" value="'.$objp->qty.'"></td>';
                    print '<td align="right"><input size="3" type="text" name="remise_percent" value="'.$objp->remise_percent.'">%</td>';
                    print '<td align="center" rowspan="2" colspan="4" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
                    print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
                    print '</tr>' . "\n";
                    if ($conf->service->enabled)
                    {
                        print "<tr $bc[$var]>";
                        print '<td colspan="5">Si produit de type service à durée limitée: Du ';
                        print $html->select_date($objp->date_start,"date_start",0,0,$objp->date_start?0:1);
                        print ' au ';
                        print $html->select_date($objp->date_end,"date_end",0,0,$objp->date_end?0:1);
                        print '</td>';
                        print '</tr>' . "\n";
                    }
                    print "</form>\n";
                }
        
                $total = $total + ($objp->qty * $objp->price);
                $i++;
            }
        
            $db->free();
        }
        else
        {
            dolibarr_print_error($db);
        }

	  /*
	   * Ajouter une ligne
	   *
	   */
	  if ($fac->statut == 0 && $user->rights->facture->creer && $_GET["action"] <> 'valid') 
	    {

	      print "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	      print "<tr class=\"liste_titre\">";
	      print '<td width="54%">'.$langs->trans("Description").'</td>';
	      print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
	      print '<td width="12%" align="right">'.$langs->trans("PriceUHT").'</td>';
	      print '<td width="8%" align="right">'.$langs->trans("Qty").'</td>';
	      print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print "</tr>\n";
	      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	      print '<input type="hidden" name="action" value="addligne">';

	      $var=!$var;

	      print '<tr '.$bc[$var].'>';
	      print '<td><textarea name="desc" cols="50" rows="2"></textarea></td>';
	      print '<td align="right">';
	      print $html->select_tva("tva_tx",$conf->defaulttx);
	      print '</td>';
	      print '<td align="right"><input type="text" name="pu" size="6"></td>';
	      print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
	      print '<td align="right" nowrap><input type="text" name="remise_percent" size="2" value="0">%</td>';
	      print '<td align="center" valign="middle" rowspan="2" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
	      if ($conf->service->enabled) {
            print '<tr '.$bc[$var].'>';
            print '<td colspan="5">Si produit de type service à durée limitée: Du ';
            print $html->select_date('',"date_start",0,0,1);
            print ' au ';
            print $html->select_date('',"date_end",0,0,1);
            print '</td>';
	      }
	      print '</tr>';
	      print "</form>";
	    }
	  print "</table>\n";


	  print "</div>\n";


	  /*
	   * Boutons actions
	   *
	   */

	  if ($user->societe_id == 0 && $_GET["action"] <> 'valid' && $_GET["action"] <> 'editline')
	    {
	      print "<div class=\"tabsAction\">\n";
	      
	      // Valider
	      if ($fac->statut == 0 && $num_lignes > 0) 
		{
		  if ($user->rights->facture->valider)
		    {
		      print "  <a class=\"tabAction\" href=\"facture.php?facid=".$fac->id."&amp;action=valid\">".$langs->trans("Valid")."</a>\n";
		    }
		}
	      else
		{
		  // Générer
		  if ($fac->statut == 1 && ($fac->paye == 0 || $user->admin) && $user->rights->facture->creer)
		    {
		      if ($fac->paye == 0)
			{
			  print "  <a class=\"tabAction\" href=\"facture.php?facid=$fac->id&amp;action=pdf\">".$langs->trans("BuildPDF")."</a>\n";
			}
		      else
			{
			  print "  <a class=\"tabAction\" href=\"facture.php?facid=$fac->id&amp;action=pdf\">".$langs->trans("RebuildPDF")."</a>\n";
			}		  	
		    }
		}

	      // Supprimer
	      if ($fac->statut == 0 && $user->rights->facture->supprimer && $_GET["action"] != 'delete')
		{
		  print "<a class=\"butDelete\" href=\"facture.php?facid=$fac->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
		} 

	      // Envoyer
	      if ($fac->statut == 1 && $user->rights->facture->envoyer)
		{
		  print "  <a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=presend\">".$langs->trans("Send")."</a>\n";
		}
	    
	      // Envoyer une relance
	      if ($fac->statut == 1 && price($resteapayer) > 0 && $user->rights->facture->envoyer) 
		{
		  print "  <a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=prerelance\">".$langs->trans("SendRemind")."</a>\n";
		}

	      // Emettre paiement 
	      if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
		{
		  print "  <a class=\"tabAction\" href=\"paiement.php?facid=".$fac->id."&amp;action=create\">".$langs->trans("DoPaiement")."</a>\n";
		}
	    
	      // Classer 'payé'
	      if ($fac->statut == 1 && price($resteapayer) <= 0 
		  && $fac->paye == 0 && $user->rights->facture->paiement)
		{
		  print "  <a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=payed\">".$langs->trans("ClassifyPayed")."</a>\n";
		}
	    
	      // Classer 'abandonnée' (possible si validée et pas encore classer payée)
	      if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
		{
		  print "  <a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=canceled\">".$langs->trans("ClassifyCanceled")."</a>\n";
		}

	      // Récurrente
	      if (! defined("FACTURE_DISABLE_RECUR")) 	// Possibilité de désactiver les factures récurrentes
		{
		  if ($fac->statut > 0)
		    {
		      print "  <a class=\"tabAction\" href=\"facture/fiche-rec.php?facid=".$fac->id."&amp;action=create\">Récurrente</a>\n";
		    }
		}

	      print "</div>\n";

	    }
	  print "<p>\n";

	  /*
	   * Documents générés
	   *
	   * Le fichier de facture détaillée est de la forme
	   * 
	   * REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
	   *
	   */

	  $forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
	  $facref = str_replace($forbidden_chars,"_",$fac->ref);
	  $file = $conf->facture->dir_output . "/" . $facref . "/" . $facref . ".pdf";

	  $relativepath = "${facref}/${facref}.pdf";
	  
	  $var=true;
      
	  print '<table width="100%"><tr><td width="50%" valign="top">';

	  if (file_exists($file))
	    {
	      print_titre("Documents");
	      print '<table class="border" width="100%">';
	    
	      print "<tr $bc[$var]><td>".$langs->trans("Bill")." PDF</td>";

	      print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepath).'">'.$fac->ref.'.pdf</a></td>';
	      print '<td align="right">'.filesize($file). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	      print '</tr>';
	     

	      $dir = $conf->facture->dir_output . "/" . $facref . "/";
	      $handle=opendir($dir);

	      while (($file = readdir($handle))!==false)
		{
		  if (is_readable($dir.$file) && substr($file, -10) == 'detail.pdf')
		    {
		      print "<tr $bc[$var]><td>Facture détaillée</td>";
		      $relativepathdetail = "${facref}/$file";
		      
		      print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepathdetail).'">'.$file.'</a></td>';		  
		      print '<td align="right">'.filesize($dir.$file). ' bytes</td>';
		      print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($dir.$file)).'</td>';
		      print '</tr>';
		    }
		}
	      print "</table>\n";
	    }

	  print '</td><td valign="top" width="50%">';

	  /*
	   * Liste des actions propres à la facture
	   *
	   */
	  $sql = "SELECT id, ".$db->pdate("a.datea")." as da, a.note, code";
	  $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."user as u ";
	  $sql .= " WHERE a.fk_user_author = u.rowid ";
	  $sql .= " AND a.fk_action in (9,10) ";
	  $sql .= " AND a.fk_soc = ".$fac->socidp ;
	  $sql .= " AND a.fk_facture = ".$fac->id;
    
	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      if ($num)
		{
		  print_titre($langs->trans("ActionsOnBill"));
    
		  $i = 0; $total = 0;
		  print '<table class="border" width="100%">';
		  print '<tr '.$bc[$var].'><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Action").'</td><td>'.$langs->trans("By").'</td></tr>';
          print "\n";
          
		  $var=True;
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object($resql);
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans("ShowTask"),"task").' '.$objp->id.'</a></td>';
		      print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
		      print '<td>'.stripslashes($objp->note).'</td>';
		      print '<td>'.$objp->code.'</td>';
		      print "</tr>\n";
		      $i++;
		    }
		  print "</table>\n";
		}
	    }
	  else
	    {
	      dolibarr_print_error($db);
	    }
	    
	  print "</td></tr></table>";
	    
	    
	  /*
	   * Choix d'un projet
	   *
	   */
	  if ($_GET["action"] == 'classer')
	    {	    
	      print "<p><form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id\">\n";
	      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	      print '<input type="hidden" name="action" value="classin">';
	      print '<table class="border">';
          
	      print '<tr><td>'.$langs->trans("Project").'</td><td>';
	    
	      $proj = new Project($db);
	      $html->select_array("projetid",$proj->liste_array($soc->id));
	    
	      print "</td></tr>";

	      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr></table></form></p>';
	    }


	  /*
	   * Affiche formulaire mail
	   *
	   */
	  if ($_GET["action"] == 'presend')
	    {
	      print '<br>';
	      print_titre($langs->trans("SendBillByMail"));

	      $liste[0]="&nbsp;";
	      foreach ($soc->contact_email_array() as $key=>$value) {
	        $liste[$key]=$value; 
	      }

	      // Créé l'objet formulaire mail
	      include_once(DOL_DOCUMENT_ROOT."/html.formmail.class.php");
	      $formmail = new FormMail($db);	    
	      $formmail->fromname = $user->fullname;
	      $formmail->frommail = $user->email;
          $formmail->withfrom=1;
          $formmail->withto=$liste;
          $formmail->withcc=1;
          $formmail->withtopic=$langs->trans("SendBillRef","__FACREF__");
          $formmail->withfile=1;
	      $formmail->withbody=1;
          // Tableau des substitutions
          $formmail->substit["__FACREF__"]=$fac->ref;
          // Tableau des paramètres complémentaires du post
          $formmail->param["action"]="send";
          $formmail->param["models"]="facture_send";
          $formmail->param["facid"]=$fac->id;
          $formmail->param["returnurl"]=DOL_URL_ROOT."/compta/facture.php?facid=$fac->id";

          $formmail->show_form();
          
          print '<br>';
	    }

	  if ($_GET["action"] == 'prerelance')
	    {
	      print '<br>';
	      print_titre($langs->trans("SendReminderBillByMail"));

	      $liste[0]="&nbsp;";
	      foreach ($soc->contact_email_array() as $key=>$value) {
	        $liste[$key]=$value; 
	      }
	      
	      // Créé l'objet formulaire mail
	      include_once("../html.formmail.class.php");
	      $formmail = new FormMail($db);	    
	      $formmail->fromname = $user->fullname;
	      $formmail->frommail = $user->email;
          $formmail->withfrom=1;
          $formmail->withto=$liste;
          $formmail->withcc=1;
          $formmail->withtopic=$langs->trans("SendReminderBillRef","__FACREF__");
          $formmail->withfile=1;
	      $formmail->withbody=1;
          // Tableau des substitutions
          $formmail->substit["__FACREF__"]=$fac->ref;
          // Tableau des paramètres complémentaires
          $formmail->param["action"]="relance";
          $formmail->param["models"]="facture_relance";
          $formmail->param["facid"]=$fac->id;
          $formmail->param["returnurl"]=DOL_URL_ROOT."/compta/facture.php?facid=$fac->id";

          $formmail->show_form();

          print '<br>';
	    }
	
	  /*
	   *   Propales
	   */
	
	  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $fac->id";
  
	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows();
	      if ($num)
		{
		  $i = 0; $total = 0;
		  print "<br>";
	      print_titre($langs->trans("RelatedCommercialProposals"));

		  print '<table class="noborder" width="100%">';
		  print '<tr class="liste_titre">';
		  print '<td>'.$langs->trans("Ref").'</td>';
		  print '<td>'.$langs->trans("Date").'</td>';
		  print '<td align="right">'.$langs->trans("Price").'</td>';
		  print "</tr>\n";
		
		  $var=True;
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print "<td><a href=\"propal.php?propalid=$objp->propalid\">$objp->ref</a></td>\n";
		      print "<td>".strftime("%d %B %Y",$objp->dp)."</td>\n";
		      print '<td align="right">'.price($objp->price).'</td>';
		      print "</tr>";
		      $total = $total + $objp->price;
		      $i++;
		    }
		  print "<tr><td align=\"right\" colspan=\"3\">".$langs->trans("TotalHT").": <b>".price($total)."</b> ".$conf->monnaie."</td></tr>\n";
		  print "</table>";
		}
	    } else {
	      dolibarr_print_error($db);
	    }	
	}
      else
	{
	  /* Facture non trouvée */
	  print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
	}
    }
  else
    {
      
      /***************************************************************************
       *                                                                         *
       *                      Mode Liste                                         *
       *                                                                         *
       ***************************************************************************/
      $page = $_GET["page"];
      $sortorder=$_GET["sortorder"];
      $sortfield=$_GET["sortfield"];
      $month=$_GET["month"];
      $year=$_GET["year"];
      
      $fac=new Facture($db);
      
      if ($page == -1)
	{
	  $page = 0 ;
	}

      if ($user->rights->facture->lire)
	{
	  $limit = $conf->liste_limit;
	  $offset = $limit * $page ;

	  if (! $sortorder) $sortorder="DESC";
	  if (! $sortfield) $sortfield="f.datef";

	  $sql = "SELECT s.nom,s.idp,f.facnumber,f.increment,f.total,f.total_ttc,".$db->pdate("f.datef")." as df, f.paye as paye, f.rowid as facid, f.fk_statut, sum(pf.amount) as am";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	  $sql .= ",".MAIN_DB_PREFIX."facture as f";
	  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
	  $sql .= " WHERE f.fk_soc = s.idp";
	
	  if ($socidp)
	    $sql .= " AND s.idp = $socidp";
	
	  if ($month > 0)
	    $sql .= " AND date_format(f.datef, '%m') = $month";
	
	  if ($_GET["filtre"])
	    {
	      $filtrearr = split(",", $_GET["filtre"]);
	      foreach ($filtrearr as $fil)
		{
		  $filt = split(":", $fil);
		  $sql .= " AND " . $filt[0] . " = " . $filt[1];
		}
	    }
	
	  if ($_GET["search_ref"])
	    {
	      $sql .= " AND f.facnumber like '%".$_GET["search_ref"]."%'";
	    }

	  if ($_GET["search_societe"])
	    {
	      $sql .= " AND s.nom like '%".$_GET["search_societe"]."%'";
	    }

	  if ($_GET["search_montant_ht"])
	    {
	      $sql .= " AND f.total = '".$_GET["search_montant_ht"]."'";
	    }

	  if ($_GET["search_montant_ttc"])
	    {
	      $sql .= " AND f.total_ttc = '".$_GET["search_montant_ttc"]."'";
	    }

	  if ($year > 0)
	    $sql .= " AND date_format(f.datef, '%Y') = $year";

	  if (strlen($_POST["sf_ref"]) > 0)
	    {
	      $sql .= " AND f.facnumber like '%".$_POST["sf_ref"] . "%'";
	    }

	  $sql .= " GROUP BY f.facnumber";   
	
	  $sql .= " ORDER BY ";
	  $listfield=split(',',$sortfield);
	  foreach ($listfield as $key => $value) {
	    $sql.="$listfield[$key] $sortorder,";
	  }
	  $sql .= " f.rowid DESC ";
	
	  $sql .= $db->plimit($limit+1,$offset);

	  $resql = $db->query($sql);

      if ($resql)
	{
	  $num = $db->num_rows();

	  if ($socidp)
	    {
	      $soc = new Societe($db);
	      $soc->fetch($socidp);
	    }
	  
	  print_barre_liste($langs->trans("BillsCustomers")." ".($socidp?" $soc->nom":""),$page,"facture.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);

	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socidp=$socidp","",$sortfield);
	  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socidp=$socidp",'align="center"',$sortfield);
	  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;socidp=$socidp","",$sortfield);
	  print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","","&amp;socidp=$socidp",'align="right"',$sortfield);
	  print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","","&amp;socidp=$socidp",'align="right"',$sortfield);
	  print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","","&amp;socidp=$socidp",'align="right"',$sortfield);
	  print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye","","&amp;socidp=$socidp",'align="right"',$sortfield);
	  print "</tr>\n";

	  // Lignes des champs de filtre
	  print '<form method="get" action="facture.php">';
	  print '<tr class="liste_titre">';
	  print '<td valign="right">';
	  print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET["search_ref"].'">';
	  print '</td><td>&nbsp;</td>';
	  print '<td align="left">';
	  print '<input class="flat" type="text" name="search_societe" value="'.$_GET["search_societe"].'">';
	  print '</td><td align="right">';
	  print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET["search_montant_ht"].'">';
	  print '</td><td align="right">';
	  print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET["search_montant_ttc"].'">';
	  print '</td><td colspan="2" align="center">';
	  print '<input type="submit" class="button" name="button_search" value="'.$langs->trans("Search").'">';
	  print '</td>';
	  print "</tr>\n";
	  print '</form>';


	  if ($num > 0) 
	    {
	      $var=True;
	      $total=0;
	      $totalrecu=0;

	      while ($i < min($num,$limit))
		{
		  $objp = $db->fetch_object($resql);
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

		  print '<td><a href="facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").'</a> ';
		  print '<a href="facture.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a>'.$objp->increment."</td>\n";
		
		  if ($objp->df > 0 )
		    {
		      print "<td align=\"center\" nowrap>";
		      $y = strftime("%Y",$objp->df);
		      $m = strftime("%m",$objp->df);
		    
		      print strftime("%d",$objp->df)."\n";
		      print ' <a href="facture.php?year='.$y.'&amp;month='.$m.'">';
		      print substr(strftime("%B",$objp->df),0,3)."</a>\n";
		      print ' <a href="facture.php?year='.$y.'">';
		      print strftime("%Y",$objp->df)."</a></TD>\n";
		    }
		  else
		    {
		      print "<td align=\"center\"><b>!!!</b></td>\n";
		    }
		  print '<td><a href="fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$objp->nom.'</a></td>';
		
		  print "<td align=\"right\">".price($objp->total)."</td>";
		  print "<td align=\"right\">".price($objp->total_ttc)."</td>";
		  print "<td align=\"right\">".price($objp->am)."</td>";	

		  // Affiche statut de la facture
		  if (! $objp->paye)
		    {
		      if ($objp->fk_statut == 0)
			{
			  print '<td align="center">'.$langs->trans("BillShortStatusDraft").'</td>';
			}
		      elseif ($objp->fk_statut == 3)
			{
			  print '<td align="center">'.$langs->trans("BillShortStatusCanceled").'</td>';
			}
		      else
			{
			  print '<td align="center"><a class="'.$class.'" href="facture.php?filtre=paye:0,fk_statut:1">'.($objp->am?$langs->trans("BillShortStatusStarted"):$langs->trans("BillShortStatusNotPayed")).'</a></td>';
			}
		    }
		  else
		    {
		      print '<td align="center">'.$langs->trans("BillShortStatusPayed").'</td>';
		    }
		
		  print "</tr>\n";
		  $total+=$objp->total;
		  $total_ttc+=$objp->total_ttc;
		  $totalrecu+=$objp->am;
		  $i++;
		}

	      if ($num <= $limit) {
		    // Print total
	    	print "<tr ".$bc[!$var].">";
	    	print "<td colspan=3 align=\"left\"><i>".$langs->trans("Total").":</i></td>";
		    print "<td align=\"right\"><i><b>".price($total)."</b></i></td>";		
	    	print "<td align=\"right\"><i><b>".price($total_ttc)."</b></i></td>";
	    	print "<td align=\"right\"><i><b>".price($totalrecu)."</b></i></td>";
	    	print '<td align="center">&nbsp;</td>';
	    	print "</tr>\n";
	      }
	    }
	
	  print "</table>";
	  $db->free($resql);
	}
      else
	{
	  dolibarr_print_error($db);
	}    

	}

    }
  
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
