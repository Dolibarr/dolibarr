<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
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
   \file       htdocs/telephonie/client/facture.php
   \ingroup    telephonie/facture
   \brief      Page de visualisation d'une facture
   \version    $Revision$
*/
require("./pre.inc.php");

$user->getrights('facture');
$user->getrights('banque');

$langs->load("bills");

$warning_delay=31*24*60*60; // Delai affichage warning retard (si retard paiement facture > delai)

require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/paiement.class.php";
require_once DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php";
include_once DOL_DOCUMENT_ROOT."/contact.class.php";
include_once DOL_DOCUMENT_ROOT."/actioncomm.class.php";

if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if (isset($_GET["msg"])) { $msg=urldecode($_GET["msg"]); }

// Sécurité accés client
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
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
      $facref = sanitize_string($fac->ref);
      $file = $conf->facture->dir_output . "/" . $facref . "/" . $facref . ".pdf";

      if (is_readable($file))
        {
	  $soc = new Societe($db, $fac->socid);

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
	      $from = $_POST["fromname"] . " <" . $_POST["frommail"] .">";
	      $replyto = $_POST["replytoname"]. " <" . $_POST["replytomail"].">";
	      $message = $_POST["message"];
	      if ($_POST["action"] == 'send') {
		$subject = $langs->trans("Bill")." $fac->ref";
		$actiontypeid=9;
		$actionmsg ="Mail envoyé par ".$from." à ".$sendto.".\n";
		if ($message) {
		  $actionmsg.="Texte utilisé dans le corps du message:\n";
		  $actionmsg.=$message;
		}
		$actionmsg2="Envoi facture par mail";
	      }
	      if ($_POST["action"] == 'relance') 	{
		$subject = "Relance facture $fac->ref";
		$actiontypeid=9;
		$actionmsg="Mail envoyé par ".$from." à ".$sendto.".\n";
		if ($message) {
		  $actionmsg.="Texte utilisé dans le corps du message:\n";
		  $actionmsg.=$message;
		}
		$actionmsg2="Relance facture par mail";
	      }

	      $filepath[0] = $file;
	      $filename[0] = $fac->ref.".pdf";
	      $mimetype[0] = "application/pdf";
	      $filepath[1] = $_FILES['addedfile']['tmp_name'];
	      $filename[1] = $_FILES['addedfile']['name'];
	      $mimetype[1] = $_FILES['addedfile']['type'];

	      $dir = $conf->facture->dir_output . "/" . $fac->ref . "/";
	      $handle=opendir($dir);
	      $ifi = 2;
	      while (($dfile = readdir($handle))!==false)
		{
		  if (is_readable($dir.$dfile) && substr($dfile, -10) == 'detail.pdf')
		    {
		      $filepath[$ifi] = $conf->facture->dir_output . "/" . $fac->ref . "/" . $dfile;
		      $filename[$ifi] = $dfile;
		      $mimetype[$ifi] = "application/pdf";
		      $ifi++;
		    }
		}


	      // Envoi de la facture
	      $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc);

	      if ($mailfile->sendfile())
                {
		  $msg='<div class="ok">'.$langs->trans("MailSuccessfulySent",$from,$sendto).'.</div>';

		  // Insertion action

		  $actioncomm = new ActionComm($db);
		  $actioncomm->type_id     = $actiontypeid;
		  $actioncomm->label       = $actionmsg2;
		  $actioncomm->note        = $actionmsg;
		  $actioncomm->date        = time();
		  $actioncomm->percentage  = 100;
		  $actioncomm->contact     = new Contact($db,$sendtoid);
		  $actioncomm->societe     = new Societe($db,$fac->socid);
		  $actioncomm->user        = $user;   // User qui a fait l'action
		  $actioncomm->facid       = $fac->id;

		  $ret=$actioncomm->add($user);       // User qui saisi l'action

		  if ($ret < 0)
                    {
		      dolibarr_print_error($db);
                    }
		  else
                    {
		      // Renvoie sur la fiche
		      Header("Location: facture.php?facid=".$fac->id."&msg=".urlencode($msg));
		      exit;
                    }
                }
	      else
                {
		  $msg='<div class="error">'.$langs->trans("ErrorFailedToSendMail",$from,$sendto).' !</div>';
                }
            }
	  else
            {
	      $msg='<div class="error">'.$langs->trans("ErrorMailRecipientIsEmpty").'</div>';
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

llxHeader('',$langs->trans("Bill"),'Facture');

$html = new Form($db);

if ($_GET["facid"] > 0)
{
  if ($msg) print "$msg<br>";
  
  $fac = New Facture($db);
  if ( $fac->fetch($_GET["facid"], $user->societe_id) > 0)
    {      
      $soc = new Societe($db, $fac->socid);
      $soc->fetch($fac->socid, $user);
            
      if (!$soc->perm_read)
	{
	  print "Lecture non authorisée";
	}
      
      if ($soc->perm_read)
	{      
	  $author = new User($db);
	  $author->id = $fac->user_author;
	  $author->fetch();
	  
	  $h = 0;
	  
	  $head[$h][0] = DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$soc->id;
	  $head[$h][1] = $langs->trans("Fiche client");
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT.'/telephonie/client/factures.php?id='.$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT.'/telephonie/client/facture.php?facid='.$fac->id;
	  $head[$h][1] = $langs->trans("CardBill");
	  $hselected = $h;
	  $h++;
	  
	  dolibarr_fiche_head($head, $hselected, $langs->trans("Bill")." : $fac->ref");
	  
	  /*
	   *   Facture
	   */
	  print '<table class="border" width="100%">';
	  print '<tr><td>'.$langs->trans("Company").'</td>';
	  print '<td colspan="3">';
	  print '<b><a href="fiche.php?id='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print "<td>Conditions de réglement</td><td>" . $fac->cond_reglement ."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print "<td colspan=\"3\">".dolibarr_print_date($fac->date,"dayhourtext")."</td>\n";
	  print '<td>'.$langs->trans("DateMaxPayment").'</td><td>' . dolibarr_print_date($fac->date_lim_reglement,"dayhourtext");
	  print "</td></tr>";
	  
	  print '<tr>';
	  
      // Projet
      if ($conf->projet->enabled)
	{
	  $langs->load("projects");
	  print '<td>';
	  print '<table width="100%" class="nobordernopadding"><tr><td>';
	  print $langs->trans("Project");
	  print '</td>';
	  if ($_GET["action"] != "classer") print '<td align="right"></td>';
	  print '</tr></table>';
	  print '</td><td colspan="3">';
	  if ($_GET["action"] == "classer")
	    {
	      $html->form_project("facture.php?facid=$fac->id",$fac->fk_soc,$fac->projetid,"projetid");
	    }
	  else
	    {
	      $html->form_project("facture.php?facid=$fac->id",$fac->fk_soc,$fac->projetid,"none");
	    }
	  print "</td>";
	} else {
	  print '<td height=\"10\">&nbsp;</td><td colspan="3">&nbsp;</td>';
	}
      
      print '<td rowspan="8" colspan="2" valign="top">';
      
      /*
       * Paiements
       */
      print $langs->trans("Payments").' :<br>';
      $sql = "SELECT ".$db->pdate("datep")." as dp, pf.amount,";
      $sql.= " c.libelle as paiement_type, p.num_paiement, p.rowid";
      $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement_facture as pf";
      $sql.= " WHERE pf.fk_facture = ".$fac->id." AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid";
      $sql.= " ORDER BY dp DESC";
      
      $result = $db->query($sql);
      
      if ($result)
	{
	  $num = $db->num_rows($result);
	  $i = 0; $total = 0;
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Type").'</td>';
	  print '<td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';
	  
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object($result);
	      $var=!$var;
	      print "<tr $bc[$var]><td>";
	      print "&nbsp;".strftime("%d %B %Y",$objp->dp)."</td>\n";
	      print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
	      print '<td align="right">'.price($objp->amount)."</td><td>".$langs->trans("Currency".$conf->monnaie)."</td>\n";
	      print "</tr>";
	      $totalpaye += $objp->amount;
	      $i++;
	    }
	  
	  if ($fac->paye == 0)
	    {
	      print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AlreadyPayed")." :</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
	      print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" style=\"border: 1px solid;\">".price($fac->total_ttc)."</td><td>".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
		  
	      $resteapayer = $fac->total_ttc - $totalpaye;
		  
	      print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
	      print "<td align=\"right\" style=\"border: 1px solid;\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td>".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
	    }
	  print "</table>";
	  $db->free($result);
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
      else
	{
	  print '<td colspan="3">'.$fac->remise_percent.'%</td>';
	}
      print '</tr>';
      
      print '<tr><td height=\"10\">'.$langs->trans("AmountHT").'</td>';
      print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
      
      print '<tr><td height=\"10\">'.$langs->trans("VAT").'</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
      print '<tr><td height=\"10\">'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
      
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
	      print '<td>'.$langs->trans("Description").'</td>';
	      print '<td width="50" align="right">'.$langs->trans("VAT").'</td>';
	      print '<td width="80" align="right">'.$langs->trans("PriceUHT").'</td>';
	      print '<td width="50" align="right">'.$langs->trans("Qty").'</td>';
	      print '<td width="50" align="right">'.$langs->trans("Discount").'</td>';
	      print '<td width="80" align="right">'.$langs->trans("AmountHT").'</td>';
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
		      if ($objp->fk_product_type==1) print img_object($langs->trans("ShowService"),"service");
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

		      print '<td align="right">';
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

	      $total = $total + ($objp->qty * $objp->price);
	      $i++;
	    }

	  $db->free($resql);
	}
      else
	{
	  dolibarr_print_error($db);
	}
      /*
       * Ajouter une ligne
       */

      print "</table>\n";
      print "</div>\n";

      /*
       * Boutons actions
       */

      if ($user->societe_id == 0 && $_GET["action"] <> 'valid' && $_GET["action"] <> 'editline')
	{
	  print "<div class=\"tabsAction\">\n";

	  // Envoyer
	  if ($fac->statut == 1 && $user->rights->facture->envoyer)
	    {
	      print "  <a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=presend\">".$langs->trans("Send")."</a>\n";
	    }

	  // Envoyer une relance
	  /*
	  if ($fac->statut == 1 && price($resteapayer) > 0 && $user->rights->facture->envoyer)
	    {
	      print "  <a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?facid=$fac->id&amp;action=prerelance\">".$langs->trans("SendRemind")."</a>\n";
	    }
	  */

	  print "</div>\n";

	}

      print '<table width="100%"><tr><td width="50%" valign="top">';

      /*
       * Documents générés
       * Le fichier de facture détaillée est de la forme
       * REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
       */

      $facref = sanitize_string($fac->ref);
      $file = $conf->facture->dir_output . "/" . $facref . "/" . $facref . ".pdf";
      $relativepath = "${facref}/${facref}.pdf";

      $var=true;

      if (file_exists($file))
	{
	  print "<br>\n";
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


      /*
       *   Propales
       */
      $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
      $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $fac->id";

      $resql = $db->query($sql);
      if ($resql)
	{
	  $num = $db->num_rows($resql);
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
		  $objp = $db->fetch_object($resql);
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref.'</a></td>';
		  print "<td>".dolibarr_print_date($objp->dp)."</td>\n";
		  print '<td align="right">'.price($objp->price).'</td>';
		  print "</tr>";
		  $total = $total + $objp->price;
		  $i++;
		}
	      print "<tr class=\"liste_total\"><td>&nbsp;</td><td align=\"left\">".$langs->trans("TotalHT")."</td><td align=\"right\">".price($total)."</td></tr>\n";
	      print "</table>";
	    }
	}
      else
	{
	  dolibarr_print_error($db);
	}


      print '</td><td valign="top" width="50%">';

      /*
       * Liste des actions propres à la facture
       */
      $sql = "SELECT id, ".$db->pdate("a.datea")." as da, a.label, a.note";
      $sql .= ", u.login";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_user_author = u.rowid ";
      $sql .= " AND a.fk_action in (9,10) ";
      $sql .= " AND a.fk_soc = ".$fac->socid ;
      $sql .= " AND a.fk_facture = ".$fac->id;

      $resql = $db->query($sql);
      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  if ($num)
	    {
	      print "<br>\n";
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
		  print '<td>'.img_object($langs->trans("ShowTask"),"task").' '.$objp->id.'</td>';
		  print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
		  print '<td>'.stripslashes($objp->label).'</td>';
		  print '<td>'.$objp->login.'</td>';
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
       * Affiche formulaire mail
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
	  $formmail->withtocc=1;
	  $formmail->withtopic=$langs->trans("SendBillRef","__FACREF__");
	  $formmail->withfile=1;
	  $formmail->withbody=1;
	  // Tableau des substitutions
	  $formmail->substit["__FACREF__"]=$fac->ref;
	  // Tableau des paramètres complémentaires du post
	  $formmail->param["action"]="send";
	  $formmail->param["models"]="facture_send";
	  $formmail->param["facid"]=$fac->id;
	  $formmail->param["returnurl"]=DOL_URL_ROOT."/telephonie/client/facture.php?facid=$fac->id";

	  $formmail->show_form();

	  print '<br>';
	}

      if ($_GET["action"] == 'prerelance')
	{
	  print '<br>';
	  print_titre($langs->trans("SendReminderBillByMail"));

	  $liste[0]="&nbsp;";
	  foreach ($soc->contact_email_array() as $key=>$value)
	    {
	      $liste[$key]=$value;
	    }

	  // Créé l'objet formulaire mail
	  include_once("../html.formmail.class.php");
	  $formmail = new FormMail($db);
	  $formmail->fromname = $user->fullname;
	  $formmail->frommail = $user->email;
	  $formmail->withfrom=1;
	  $formmail->withto=$liste;
	  $formmail->withtocc=1;
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

      }
    }
  else
    {
      /* Facture non trouvée */
      print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
