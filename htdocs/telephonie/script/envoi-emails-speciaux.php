<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Envoie les factures par emails pour un client special
 * Scrip non générique utilisé par Rodolphe pour un besoin spécifique
 *
 */

require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/bon-prelevement.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/pdfdetail_ibreizh.modules.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");

dolibarr_syslog("Debut envoie de mail");

$clientid = 52;
$contactid = 151;

$year = "2005";
$month = "02";

$emails = array();
$factures_a_mailer = array();
$factures_lignes = array();

/*
 * Lecture de l'email
 */
$sql = "SELECT sc.email FROM ";     
$sql .= MAIN_DB_PREFIX."socpeople as sc";
$sql .= " WHERE sc.rowid = ".$contactid;

$resql = $db->query($sql);

if ($resql)
{
  $row = $db->fetch_row($resql);
  dolibarr_syslog($row[0]);

  array_push($emails, $row[0]);

  $db->free($resql);
}
else
{
  print $db->error();
}
/*
 * Lecture des factures
 *
 */
$sql = "SELECT distinct(f.fk_facture) FROM ";     
$sql .= MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";

$sql .= " WHERE s.rowid = l.fk_client_comm";
$sql .= " AND f.fk_facture IS NOT NULL";
$sql .= " AND l.rowid = f.fk_ligne";
$sql .= " AND s.rowid = ".$clientid;
$sql .= " AND date_format(date,'%Y%m') = ".$year.$month;
$sql .= " ORDER BY f.fk_facture ASC";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  dolibarr_syslog($num . " Factures");

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $factures_a_mailer[$i] = $row[0];

      $i++;
    }

}
else
{
  print $db->error();
}

/*
 * Association lignes / factures
 *
 */
$sql = "SELECT f.fk_facture, f.fk_ligne FROM ";     
$sql .= MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";

$sql .= " WHERE s.rowid = l.fk_client_comm";
$sql .= " AND f.fk_facture IS NOT NULL";
$sql .= " AND l.rowid = f.fk_ligne";
$sql .= " AND s.rowid = ".$clientid;
$sql .= " AND date_format(date,'%Y%m') = ".$year.$month;
$sql .= " ORDER BY f.fk_facture ASC";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  dolibarr_syslog($num . " Factures");

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $factures_lignes[$row[0]] = $row[1];
      $i++;
    }
}
else
{
  print $db->error();
}



/*
 *
 */

if (sizeof($factures_a_mailer) > 0)
{
  for ($i = 0 ; $i < sizeof($factures_a_mailer) ; $i++)
    {
      $fact = new Facture($db);

      dolibarr_syslog("Facture ".$factures_a_mailer[$i]);
      dolibarr_syslog("ligne ".$factures_lignes[$factures_a_mailer[$i]]);

      if ($fact->fetch($factures_a_mailer[$i]) == 1)
	{

	  $ligne = new LigneTel($db);
	  $ligne->fetch_by_id($factures_lignes[$factures_a_mailer[$i]]);

	  if (sizeof($emails > 0))
	    {
	      $sendto = "";
	      for ($k = 0 ; $k < sizeof($emails) ; $k++)
		{
		  $sendto .= html_entity_decode($emails[$k]) . ",";
		}
	      $sendto = substr($sendto,0,strlen($sendto) - 1);
	      
	      dolibarr_syslog("Envoi email à ".html_entity_decode($sendto) );
	      
	      
	      $subject = "Facture ibreizh ";
	      $subject .= "(".$ligne->code_analytique.") ";
	      $subject .= "$fact->ref";
	      
	      $from = TELEPHONIE_EMAIL_FACTURATION_EMAIL;
	      
	      $message = "Bonjour,\n\n";
	      $message .= "Code Agence : ".$ligne->code_analytique."\n";
	      $message .= "Veuillez trouver ci-joint notre facture numéro $fact->ref du ".strftime("%d/%m/%Y",$fact->date).".";
	      
	      $message .= "\nEgalement joint à ce mail le détails de vos communications.";
	      
	      $message .= "\n\nCordialement,";
	      $message .= "\n\n--";
	      $message .= "\niBreizh";
	      $message .= "\n106 Avenue de la Marne 56000 Vannes";
	      $message .= "\nTél : 0811 60 23 13";
	      $message .= "\nFax : 02 97 46 80 19";
	      
	      
	      $mailfile = new DolibarrMail($subject,
					   $sendto,
					   $from,
					   $message);
	      
	      $mailfile->addr_bcc = TELEPHONIE_EMAIL_FACTURATION_EMAIL;
	      
	      $arr_file = array();	      
	      $arr_name = array();
	      $arr_mime = array();
	      
	      $facfile = FAC_OUTPUTDIR . "/" . $fact->ref . "/" . $fact->ref . ".pdf";
	      
	      /*
	       * Join la facture
	       */
	      array_push($arr_file, $facfile);
	      array_push($arr_mime, "application/pdf");
	      array_push($arr_name, $fact->ref.".pdf");
	      
	      
	      
	      $dir = FAC_OUTPUTDIR . "/" . $fact->ref . "/";
	      
	      $handle=opendir(FAC_OUTPUTDIR . "/" . $fact->ref . "/");
	      /*
	       * Joint les détails
	       *
	       */
	      while (($file = readdir($handle))!==false)
		{
		  if (is_readable($dir.$file) && substr($file, -11) == '-detail.pdf')
		    {
		      array_push($arr_file, $dir.$file);
		      array_push($arr_mime, "application/pdf");
		      array_push($arr_name, $file);		  
		    }
		}
	      
	      $mailfile->PrepareFile($arr_file, $arr_mime, $arr_name);
	      
	      if ( $mailfile->sendfile() )
		{
		  
		  for ($kj = 0 ; $kj < sizeof($emails) ; $kj++)
		    {
		      $sendtoid = $contactid;
		      
		      $sendtox = $emails[$kj];
		      
		      $actioncode=9;
		      $actionmsg="Envoyée à $sendtox";
		      $actionmsg2="Envoi Facture par mail";
		      
		      $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label, percent) VALUES (now(), '$actioncode' ,'$fact->socid' ,'$actionmsg','$fact->id','$sendtoid','$user->id', '$actionmsg2',100);";
		      
		      if (! $db->query($sql) )
			{
			  print $db->error();
			}
		      else
			{
			  //print "TOTO".$sendto. " ". $sendtoid ." \n";
			}
		      
		    }
		  
		}
	    }
	  else
	    {
	      print  "Aucun email trouvé\n";
	      dolibarr_syslog("import.php aucun email trouvé");
	    }
	}
    }
}


?>
