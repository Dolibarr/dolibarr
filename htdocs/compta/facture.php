<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	    \file       htdocs/compta/facture.php
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

require("../facture.class.php");
require("../lib/CMailFile.class.php");
require("../paiement.class.php");
require("../project.class.php");
require("../propal.class.php");
require("../contrat/contrat.class.php");
require("../commande/commande.class.php");

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
		      print "Erreur";
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
		      print "Erreur";
		    }
		}
	      else
		{
		  print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>";
		  print "<p>Retour à la <a href=\"propal.php?propalid=$propalid\">propale</a>";
		  print $db->error();
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

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->facture->valider)
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
  $fac->fetch($facid);

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

if ($_POST["action"] == 'updateligne' && $user->rights->facture->creer) 
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
 *
 */
if ($_POST["action"] == 'send' || $_POST["action"] == 'relance')
{
  $fac = new Facture($db,"",$_POST["facid"]);
  if ( $fac->fetch($_POST["facid"]) )
    {
			$forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
			$facref = str_replace($forbidden_chars,"_",$fac->ref);
			$file = FAC_OUTPUTDIR . "/" . $facref . "/" . $facref . ".pdf";
      
      if (is_readable($file))
	{
	  
	  if ($_POST["sendto"]) {
	    // Le destinataire a été fourni via le champ libre
	    $sendto = $_POST["sendto"];
	    $sendtoid = 0;
	  }
	  elseif ($_POST["receiver"]) {
	    // Le destinataire a été fourni via la liste déroulante
	    $soc = new Societe($db, $fac->socidp);
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
	      $replyto = $_POST["replytoname"] . " <".$_POST["replytomail"] .">";

	      $filepath[0] = $file;
	      $filename[0] = $fac->ref.".pdf";
	      $mimetype[0] = "application/pdf";
	      $filepath[1] = $_FILES['addedfile']['tmp_name'];
	      $filename[1] = $_FILES['addedfile']['name'];
	      $mimetype[1] = $_FILES['addedfile']['type'];
	      $replyto = $_POST["replytoname"]. " <".$_POST["replytomail"].">";
	      
	      // Envoi de la facture
	      $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath, $mimetype,$filename,$sendtocc);

	      if ( $mailfile->sendfile() )
		{		  
		  $sendto = htmlentities($sendto);
			  
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), '$actioncode' ,'$fac->socidp' ,'$actionmsg','$fac->id','$sendtoid','$user->id', '$actionmsg2',100);";
	
		  if (! $db->query($sql) )
		    {
		      dolibarr_print_error($db->error());
		    }
		  else
		    {
		      // Renvoie sur la page de la facture
		      Header("Location: facture.php?facid=".$fac->id);
		    } 	      	      
		}
	      else
		{
		  print $langs->trans("ErrorFailedToSendMail",$replyto,$sendto);
		}	  
	    }
	  else
	    {
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
  
  print_titre("Emettre une facture");

  if ($propalid)
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
      $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";      
      $sql .= " AND p.rowid = $propalid";
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
       
	  print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="socid" value="'.$obj->idp.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="0">';

	  print '<table class="border" width="100%">';
	  
	  print '<tr><td>'.$langs->trans("Customer").' :</td><td>'.$soc->nom.'</td>';
	  print '<td class="border">'.$langs->trans("Comment").'</td></tr>';

	  print '<tr><td>'.$langs->trans("Author").' :</td><td>'.$user->fullname.'</td>';
	  
	  print '<td rowspan="6" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="8"></textarea></td></tr>';	
	  
	  print '<tr><td>'.$langs->trans("Date").' :</td><td>';
	
	  print_date_select(time());

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
	  
      if ($conf->projet->enabled) {
          $langs->load("projects");
    	  print '<tr><td>'.$langs->trans("Project").' :</td><td>';
    	  $proj = new Project($db);
    	  $html->select_array("projetid",$proj->liste_array($socidp));
    	  print "</td></tr>";
      }
      	  
	  if ($_GET["propalid"] > 0)
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
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($obj->total)."</td></tr>";
	    }
	  elseif ($_GET["commandeid"] > 0)
	    {
	      print '<input type="hidden" name="commandeid" value="'.$commande->id.'">';
	      print '<tr><td>Commande</td><td colspan="2">'.$commande->ref.'</td></tr>';
	      print '<tr><td>Montant HT</td><td colspan="2">'.price($commande->total_ht).'</td></tr>';
	      print '<tr><td>TVA</td><td colspan="2">'.price($commande->total_tva)."</td></tr>";
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($commande->total_ttc)."</td></tr>";
	    }
	  else
	    {
	      print '<tr><td colspan="3">&nbsp;</td></tr>';
	      print '<tr><td colspan="3">';
	      /*
	       *
	       * Liste des elements
	       *
	       */
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
	      $sql .= " WHERE envente = 1";
	      $sql .= " ORDER BY p.nbvente DESC LIMIT ".$conf->liste_limit;
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" selected></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			{
			  $objp = $db->fetch_object();
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
	      	      
	      print '<table class="noborder" cellspacing="0" cellpadding="2">';
	      print '<tr><td>Services/Produits prédéfinis</td><td>Quan.</td><td>Remise</td><td> &nbsp; &nbsp; </td>';
	      if ($conf->service->enabled) {
		print '<td>Si produit de type service à durée limitée</td></tr>';
	      }
	      for ($i = 1 ; $i <= $NBLINES ; $i++)
		{
		  print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
		  print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
		  print '<td><input type="text" size="4" name="remise_percent'.$i.'" value="0">%</td>';
		  print '<td>&nbsp;</td>';
		  // Si le module service est actif, on propose des dates de début et fin à la ligne
		  if ($conf->service->enabled) {
		    print '<td>';
		    print 'Du ';
		    print $html->select_date('',"date_start$i",0,0,1);
		    print ' au ';
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
		      print '<tr><td colspan="3">Factures récurrentes : <select name="fac_rec">';
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
		  print "$sql";
		}
	    }
	  /*
	   *
	   */	  
	  print '<tr><td colspan="3" align="center"><input type="submit" name="bouton" value="Créer brouillon"></td></tr>';
	  print "</form>\n";
	  print "</table>\n";

	  if ($_GET["propalid"])
	    {
	      /*
	       * Produits
	       */
	      print_titre("Produits");
	      
	      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>Produit</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">Remise</td><td align="center">Qté.</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
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
		      print '<td align="center">'.$objp->remise_percent.' %</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      $sql = "SELECT pt.rowid, pt.description as product,  pt.price, pt.qty, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt  WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
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
		      print '<td align="center">'.$objp->remise_percent.' %</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      else
		{
		  print $sql;
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
	      
	      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>Produit</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">Remise</td><td align="center">Qté.</td></tr>';
	      
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
		      print '<td align="center">'.$objp->remise_percent.' %</td>';
		      print '<td align="center">'.$objp->qty.'</td></tr>';
		      $i++;
		    }
		}
	      else
		{
		  print $sql;
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
        $head[$h][1] = $langs->trans("Apercu");
        $h++;      
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
	      $html->form_confirm($_SERVER["PHP_SELF"]."?facid=$fac->id","Supprimer la facture","Etes-vous sûr de vouloir supprimer cette facture ?","confirm_delete");
	    }

	  /*
	   * Confirmation du classement abandonné
	   *
	   */
	  if ($_GET["action"] == 'canceled')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"]."?facid=$fac->id","Classer la facture à l'état 'Abandonnée'","La totalité du paiement de cette facture n'a pas été réalisée. Etes-vous sûr de vouloir abandonner définitivement cette facture ?","confirm_canceled");
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      $numfa = facture_get_num($soc);
	      $html->form_confirm("facture.php?facid=$fac->id","Valider la facture sous la référence no ".$numfa,"Etes-vous sûr de vouloir valider cette facture avec la référence no $numfa ?","confirm_valid");
	    }

	  /*
	   *   Facture
	   */
	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print '<tr><td>'.$langs->trans("Customer").'</td>';
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
	      print '<tr class="liste_titre"><td>Date</td><td>'.$langs->trans("Type").'</td>';
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
		  print '<td align="right">'.price($objp->amount)."</td><td>".MAIN_MONNAIE."</td>\n";
		  print "</tr>";
		  $totalpaye += $objp->amount;
		  $i++;
		}

	      if ($fac->paye == 0)
		{
		  print "<tr><td colspan=\"2\" align=\"right\">Total déjà payé:</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>".MAIN_MONNAIE."</td></tr>\n";
		  print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" style=\"border: 1px solid;\">".price($fac->total_ttc)."</td><td>".MAIN_MONNAIE."</td></tr>\n";
		
		  $resteapayer = $fac->total_ttc - $totalpaye;

		  print "<tr><td colspan=\"2\" align=\"right\">Reste à payer :</td>";
		  print "<td align=\"right\" style=\"border: 1px solid;\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td>".MAIN_MONNAIE."</td></tr>\n";
		}
	      print "</table>";
	      $db->free();
	    } else {
	      print $db->error();
	    }
	
	  print "</td></tr>";
	
	  print "<tr><td height=\"10\">".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td>";
  
	  print '<tr><td height=\"10\">Remise globale</td>';
	  print '<td align="right" colspan="2">'.$fac->remise_percent.'</td>';
	  print '<td>%</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
	  print '<td>'.MAIN_MONNAIE.' HT</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("VAT").'</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
	  print '<td>'.MAIN_MONNAIE.'</td></tr>';
	  print '<tr><td height=\"10\">'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
	  print '<td>'.MAIN_MONNAIE.' TTC</td></tr>';
	  print '<tr><td height=\"10\">'.$langs->trans("Status").'</td><td align="left" colspan="3">'.($fac->get_libstatut()).'</td></tr>';
	  if ($fac->note)
	    {
	      print '<tr><td colspan="4">'.$langs->trans("Note").' : '.nl2br($fac->note)."</td></tr>";
	    }
	  else {
	    print '<tr><td colspan="4">&nbsp;</td></tr>';
	  }

	  print "</table><br>";

	  if ($fac->brouillon == 1 && $user->rights->facture->creer) 
	    {
	      print '<form action="facture.php?facid='.$fac->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	      print '<table class="border" cellpadding="3" cellspacing="0"><tr><td>Remise</td><td align="right">';
	      print '<input type="text" name="remise" size="3" value="'.$fac->remise_percent.'">%';
	      print '<input type="submit" value="'.$langs->trans("Save").'">';
	      print '</td></tr></table></form>';
	    }

	  /*
	   * Lignes de factures
	   *
	   */
	  $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent, l.subprice, ".$db->pdate("l.date_start")." as date_start, ".$db->pdate("l.date_end")." as date_end ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l WHERE l.fk_facture = $fac->id ORDER BY l.rowid";

	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num_lignes = $db->num_rows();
	      $i = 0; $total = 0;
	    
	      echo '<table class="noborder" width="100%">';
	      if ($num_lignes)
		{
		  print "<tr class=\"liste_titre\">";
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
		  print '<td width="12%" align="right">P.U. HT</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Quantity").'</td>';
		  print '<td width="8%" align="right">Remise</td>';
		  print '<td width="10%" align="right">'.$langs->trans("AmountHT").'</td>';
		  print '<td>&nbsp;</td><td>&nbsp;</td>';
		  print "</tr>\n";
		}
	      $var=True;
	      while ($i < $num_lignes)
		{
		  $objp = $db->fetch_object();
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  if ($objp->fk_product > 0)
		    {
		      print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a>';
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

		  print '<td align="right">'.$objp->tva_taux.' %</td>';
		  print '<td align="right">'.price($objp->subprice)."</td>\n";
		  print '<td align="right">'.$objp->qty.'</td>';
		  if ($objp->remise_percent > 0)
		    {
		      print '<td align="right">'.$objp->remise_percent." %</td>\n";
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
		    }
		  else
		    {
		      print '<td>&nbsp;</td><td>&nbsp;</td>';
		    }
		  print "</tr>";
	  
		  // Update ligne de facture
		  if ($_GET["action"] == 'editline' && $_GET["rowid"] == $objp->rowid)
		    {
		      print '<form action="facture.php" method="post">';
		      print '<input type="hidden" name="action" value="updateligne">';
		      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
		      print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
		      print "<tr $bc[$var]>";
		      print '<td><textarea name="desc" cols="60" rows="2">'.stripslashes($objp->description).'</textarea></td>';
		      print '<td align="right">';
		      //print $html->select_tva("tva_tx",$objp->tva_taux);
		      print "$objp->tva_taux %";    // Taux tva dépend du produit, donc on ne doit pas pouvoir le changer ici
		      print '</td>';
		      print '<td align="right"><input size="8" type="text" name="price" value="'.price($objp->subprice).'"></td>';
		      print '<td align="right"><input size="4" type="text" name="qty" value="'.$objp->qty.'"></td>';
		      print '<td align="right"><input size="3" type="text" name="remise_percent" value="'.$objp->remise_percent.'">&nbsp;%</td>';
		      print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Save").'"></td>';
		      print '</tr>' . "\n";
		      if ($conf->service->enabled) {
			print "<tr $bc[$var]>";
			print '<td colspan="8">Si produit de type service à durée limitée: Du ';
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
	      //	    print "</table>";
	    } 
	  else
	    {
	      dolibarr_print_error($db->error());
	    }
	
	  /*
	   * Ajouter une ligne
	   *
	   */
	  if ($fac->statut == 0 && $user->rights->facture->creer) 
	    {

	      print "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	      print "<tr class=\"liste_titre\">";
	      print '<td width="54%">'.$langs->trans("Description").'</td>';
	      print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
	      print '<td width="12%" align="right">P.U. HT</TD>';
	      print '<td width="8%" align="right">'.$langs->trans("Quantity").'</td>';
	      print '<td width="8%" align="right">Remise</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print "</tr>\n";
	      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	      print '<input type="hidden" name="action" value="addligne">';
	      print '<tr><td><textarea name="desc" cols="60" rows="2"></textarea></td>';
	      print '<td align="right">';
	      print $html->select_tva("tva_tx",$conf->defaulttx);
	      print '</td>';
	      print '<td align="right"><input type="text" name="pu" size="8"></td>';
	      print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
	      print '<td align="right"><input type="text" name="remise_percent" size="4" value="0">&nbsp;%</td>';
	      print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	      if ($conf->service->enabled) {
		print '<tr>';
		print '<td colspan="8">Si produit de type service à durée limitée: Du ';
		print $html->select_date('',"date_start",0,0,1);
    		print ' au ';
    		print $html->select_date('',"date_end",0,0,1);
    		print '</td>';
	      }
	      print '</tr>';
	      print "</form>";
	    }
	  print "</table><br>\n";

	  /*
	   * Fin Ajout ligne
	   *
	   */
	  print '</div>';
	  if ($user->societe_id == 0 && $_GET["action"] <> 'valid')
	    {
	      print "<div class=\"tabsAction\">\n";

	      // Valider
	      if ($fac->statut == 0 && $num_lignes > 0) 
		{
		  if ($user->rights->facture->valider)
		    {
		      print '<a class="tabAction" href="facture.php?facid='.$fac->id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
		    }
		}
	      else
		{
		  // Générer
		  if ($fac->statut == 1 && ($fac->paye == 0 || $user->admin) && $user->rights->facture->creer)
		    {
		      if ($fac->paye == 0)
			{
			  print "<a class=\"tabAction\" href=\"facture.php?facid=$fac->id&amp;action=pdf\">".$langs->trans("BuildPDF")."</a>";
			}
		      else
			{
			  print "<a class=\"tabAction\" href=\"facture.php?facid=$fac->id&amp;action=pdf\">".$langs->trans("RebuildPDF")."</a>";
			}		  	
		    }
		}

	      // Supprimer
	      if ($fac->statut == 0 && $user->rights->facture->supprimer)
		{
		  print "<a class=\"tabAction\" href=\"facture.php?facid=$fac->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
		} 

	      // Envoyer
	      if ($fac->statut == 1 && $user->rights->facture->envoyer)
		{
		  print "<a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=presend\">".$langs->trans("Send")."</a>";
		}
	    
	      // Envoyer une relance
	      if ($fac->statut == 1 && price($resteapayer) > 0 && $user->rights->facture->envoyer) 
		{
		  print "<a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=prerelance\">".$langs->trans("SendRemind")."</a>";
		}

	      // Emettre paiement 
	      if ($fac->statut == 1 && price($resteapayer) > 0 && $user->rights->facture->paiement)
		{
		  print "<a class=\"tabAction\" href=\"paiement.php?facid=".$fac->id."&amp;action=create\">".$langs->trans("DoPaiement")."</a>";
		}
	    
	      // Classer 'payé'
	      if ($fac->statut == 1 && price($resteapayer) <= 0 
		  && $fac->paye == 0 && $user->rights->facture->paiement)
		{
		  print "<a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=payed\">".$langs->trans("ClassifyPayed")."</a>";
		}
	    
	      // Classer 'abandonnée' (possible si validée et pas encore classer payée)
	      if ($fac->statut == 1 && $fac->paye == 0 && $user->rights->facture->paiement)
		{
		  print "<a class=\"tabAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=canceled\">".$langs->trans("ClassifyCanceled")."</a>";
		}

	      // Récurrente
	      if (! defined("FACTURE_DISABLE_RECUR")) 	// Possibilité de désactiver les factures récurrentes
		{
		  if ($fac->statut > 0)
		    {
		      print '<a class="tabAction" href="facture/fiche-rec.php?facid='.$fac->id.'&amp;action=create">Récurrente</a>';
		    }
		}

	      print "</div>";

	    }
	  print "<p>\n";

	  /*
	   * Documents générés
	   *
	   */

	  $forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
	  $facref = str_replace($forbidden_chars,"_",$fac->ref);
	  $file = FAC_OUTPUTDIR . "/" . $facref . "/" . $facref . ".pdf";
       
	  print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";

	  if (file_exists($file))
	    {
	      $encfile = urlencode($file);
	      print_titre("Documents");
	      print '<table class="border" width="100%">';
	    
	      print "<tr $bc[0]><td>Facture PDF</td>";

	      print '<td><a href="'.DOL_URL_ROOT . '/document.php?file='.$encfile.'">'.$fac->ref.'.pdf</a></td>';

	      print '<td align="right">'.filesize($file). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	      print '</tr>';
	     

	      $dir = FAC_OUTPUTDIR . "/" . $facref . "/";
	      $handle=opendir($dir);

	      while (($file = readdir($handle))!==false)
		{
		  if (is_readable($dir.$file) && substr($file, -10) == 'detail.pdf')
		    {
		      $encfile = urlencode($dir.$file);
		      print "<tr $bc[0]><td>Facture détaillée</td>";
		      
		      print '<td><a href="'.DOL_URL_ROOT . '/document.php?file='.$encfile.'">'.$fac->ref.'-detail.pdf</a></td>';		  
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
	  $sql = "SELECT id, ".$db->pdate("a.datea")." as da,  a.note, code ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."user as u ";
	  $sql .= " WHERE a.fk_user_author = u.rowid ";
	  $sql .= " AND a.fk_action in (9,10) ";
	  $sql .= " AND a.fk_soc = ".$fac->socidp ;
	  $sql .= " AND a.fk_facture = ".$fac->id;
    
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
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
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.$objp->id.'</a></td>';
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
	   *
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
	   *
	   *
	   */
	  if ($_GET["action"] == 'presend')
	    {
	      print_titre("Envoyer la facture par mail");

	      $langs->load("other");
	      
	      $replytoname = $user->fullname;
	      $from_name = $replytoname;

	      $replytomail = $user->email;
	      $from_mail = $replytomail;
	    
	      $form = new Form($db);

	      print "<form method=\"post\" ENCTYPE=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\">\n";
	      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	      print '<input type="hidden" name="action" value="send">';
	      print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	      print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	      print "<br>\n";
	    
	      // From
	      print "<table class=\"border\" width=\"100%\">\n";
	      print "  <tr><td width=\"180\">".$langs->trans("MailFrom")."</td><td>$from_name".($from_mail?" &lt;$from_mail&gt;":"")."</td></tr>\n";
	      print "  <tr><td>".$langs->trans("MailReply")."</td><td>$replytoname".($replytomail?" &lt;$replytomail&gt;":"");
	      print "</td></tr>\n";
          
	      // To
	      print '  <tr><td width=\"180\">'.$langs->trans("MailTo").'</td><td>';
	      $form->select_array("receiver",$soc->contact_email_array());
	      print " ".$langs->trans("or")." <input size=\"30\" name=\"sendto\" value=\"$fac->email\"></td></tr>\n";

	      // CC
	      print '  <tr><td width=\"180\">'.$langs->trans("MailCC").'</td><td>';
	      print "<input size=\"30\" name=\"sendtocc\"></td></tr>\n";

	      // File
	      print "  <tr><td valign=\"top\">" . $langs->trans("MailFile"). "</td><td><input type=\"file\" name=\"addedfile\" size=\"40\" maxlength=\"80\"></td></tr>\n";

	      print "</table>";

	      // Topic + Message
	      $defaultmessage="Veuillez trouver ci-joint la facture $fac->ref\n\nCordialement\n\n";
	      $form->mail_topicmessagefile(0,1,0,$defaultmessage);
	    
	      print "<br><center><input class=\"flat\" type=\"submit\" value=\"".$langs->trans("Send")."\"></center>\n";

	      print "</form>\n";
	    }

	  if ($_GET["action"] == 'prerelance')
	    {
	      print_titre("Envoyer une relance par mail");

	      $langs->load("other");

	      $replytoname = $user->fullname;
	      $from_name = $replytoname;

	      $replytomail = $user->email;
	      $from_mail = $replytomail;
	    
	      $form = new Form($db);	    

	      print "<form method=\"post\" ENCTYPE=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\">\n";
	      print '<input type="hidden" name="facid" value="'.$fac->id.'">';
	      print '<input type="hidden" name="action" value="relance">';
	      print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	      print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	      print '<br>';
	    
	      // From
	      print "<table class=\"border\" width=\"100%\">\n";
	      print "  <tr><td width=\"180\">".$langs->trans("MailFrom")."</td><td>$from_name".($from_mail?" &lt;$from_mail&gt;":"")."</td></tr>\n";
	      print "  <tr><td>".$langs->trans("MailReply")."</td><td>$replytoname".($replytomail?" &lt;$replytomail&gt;":"");
	      print "</td></tr>\n";
          
	      // To
	      print '  <tr><td width=\"180\">'.$langs->trans("MailTo").'</td><td>';
	      $form->select_array("receiver",$soc->contact_email_array());
	      print " ".$langs->trans("or")." <input size=\"30\" name=\"sendto\" value=\"$fac->email\"></td></tr>\n";

	      // CC
	      print '  <tr><td width=\"180\">'.$langs->trans("MailCC").'</td><td>';
	      print "<input size=\"30\" name=\"sendtocc\"></td></tr>\n";

	      // File
	      print "  <tr><td valign=\"top\">" . $langs->trans("MailFile"). "</td><td><input type=\"file\" name=\"addedfile\" size=\"40\" maxlength=\"80\"></td></tr>\n";

	      print "</table>";

	      // Topic + Message
	      $defaultmessage="Nous apportons à votre connaissance que la facture ".$fac->ref." ne semble toujours pas avoir été réglée. La voici donc, pour rappel, en pièce jointe.\n\nCordialement\n\n";
	      $form->mail_topicmessagefile(0,1,0,$defaultmessage);
	    
	      print "<br><center><input class=\"flat\" type=\"submit\" value=\"".$langs->trans("Send")."\"></center>\n";

	      print "</form\n";	
	    }
	
	  /*
	   *   Propales
	   */
	
	  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $fac->id";
  
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
		  print "<tr><td align=\"right\" colspan=\"3\">".$langs->trans("TotalHT").": <b>".price($total)."</b> ".MAIN_MONNAIE."</td></tr>\n";
		  print "</table>";
		}
	    } else {
	      dolibarr_print_error($db);
	    }	
	}
      else
	{
	  /* Facture non trouvée */
	  print "Facture inexistante";
	}
    } else {
      /***************************************************************************
       *                                                                         *
       *                      Mode Liste                                         *
       *                                                                         *
       ***************************************************************************/
      $page=$_GET["page"];
      $sortorder=$_GET["sortorder"];
      $sortfield=$_GET["sortfield"];
      $month=$_GET["month"];
      $year=$_GET["year"];
      
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

	  $sql = "SELECT s.nom,s.idp,f.facnumber,f.total,f.total_ttc,".$db->pdate("f.datef")." as df, f.paye as paye, f.rowid as facid, f.fk_statut, sum(pf.amount) as am";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture WHERE f.fk_soc = s.idp";
	
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
	
	  $sql .= $db->plimit($limit,$offset);
	
	  $result = $db->query($sql);
	}
      if ($result)
	{
	  $num = $db->num_rows();
	  print_barre_liste("Factures clients",$page,$_SERVER["PHP_SELF"],"&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);

	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socidp=$socidp");
	  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socidp=$socidp",'align="center"');
	  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;socidp=$socidp");
	  print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","","&amp;socidp=$socidp",'align="right"');
	  print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","","&amp;socidp=$socidp",'align="right"');
	  print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","","&amp;socidp=$socidp",'align="right"');
	  print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye","","&amp;socidp=$socidp",'align="right"');
	  print "</tr>\n";
      
	  if ($num > 0) 
	    {
	      $var=True;
	      $total=0;
	      $totalrecu=0;

	      while ($i < min($num,$limit))
		{
		  $objp = $db->fetch_object();
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

		  print '<td><a href="facture.php?facid='.$objp->facid.'">'.img_file()."</a>&nbsp;\n";
		  print '<a href="facture.php?facid='.$objp->facid.'">'.$objp->facnumber."</a></td>\n";
		
		  if ($objp->df > 0 )
		    {
		      print "<td align=\"center\">";
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
		  print '<td><a href="fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		
		  print "<td align=\"right\">".price($objp->total)."</td>";
		  print "<td align=\"right\">".price($objp->total_ttc)."</td>";
		  print "<td align=\"right\">".price($objp->am)."</td>";	
		  // Affiche statut de la facture
		  if (! $objp->paye)
		    {
		      if ($objp->fk_statut == 0)
			{
			  print '<td align="center">brouillon</td>';
			}
		      elseif ($objp->fk_statut == 3)
			{
			  print '<td align="center">abandonnée</td>';
			}
		      else
			{
			  print '<td align="center"><a class="'.$class.'" href="facture.php?filtre=paye:0,fk_statut:1">'.($objp->am?"commencé":"impayée").'</a></td>';
			}
		    }
		  else
		    {
		      print '<td align="center">payée</td>';
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
	    	print "<td colspan=3 align=\"left\">".$langs->trans("Total").": </td>";
		    print "<td align=\"right\"><b>".price($total)."</b></td>";		
	    	print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
	    	print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
	    	print '<td align="center">&nbsp;</td>';
	    	print "</tr>\n";
	      }
	    }
	
	  print "</table>";
	  $db->free();
	}
      else
	{
	  dolibarr_print_error($db);
	}    
    }
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
