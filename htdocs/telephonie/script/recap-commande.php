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
 * Génére un tableur des lignes commandées dans le mois
 *
 *
 */
require "../../master.inc.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php";
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php";
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php";

define ('COMMANDETABLEUR_NOEMAIL', -3);

$opt = getopt("e:");

$email = $opt['e'];

$date = time();
$date_now = $date;

Generate($date, $db, $date_now, $email);

if (strftime("%d", $date_now) < 7)
{
  $date_prev = $date - (86400 * 9);
  Generate($date_prev, $db, $date_now, $email);
}

/*
 *
 *
 */

function Generate($date_gen, $db, $date_now, $email)
{
  $datef = strftime("%Y-%m", $date_gen);

  $shortname = "recap-".$datef.".xls";
  $fname = DOL_DATA_ROOT ."/telephonie/ligne/commande/".$shortname;
  
  $ligne = new LigneTel($db);
  
  $workbook = &new writeexcel_workbook($fname);
  
  $worksheet = &$workbook->addworksheet();
  
  $worksheet->write(0, 0,  "Récapitulatif des commandes du mois de ".strftime("%B %Y",$date_gen)." (généré le ".strftime("%d %B %Y %HH%M", $date_now) . ")");
  
  $worksheet->set_column('A:A', 12);
  $worksheet->set_column('B:B', 42);
  $worksheet->set_column('C:C', 15);
  $worksheet->set_column('D:D', 14);
  $worksheet->set_column('E:E', 28);

  $formatcc =& $workbook->addformat();
  $formatcc->set_align('center');
  $formatcc->set_align('vcenter');

  $format[2] =& $workbook->addformat();
  $format[2]->set_align('center');
  $format[2]->set_align('vcenter');
  $format[2]->set_color('blue');

  $format[3] =& $workbook->addformat();
  $format[3]->set_align('center');
  $format[3]->set_align('vcenter');
  $format[3]->set_color('green');

  $format[4] =& $workbook->addformat();
  $format[4]->set_align('center');
  $format[4]->set_align('vcenter');
  $format[4]->set_color('pink');

  $format[5] =& $workbook->addformat();
  $format[5]->set_align('center');
  $format[5]->set_align('vcenter');
  $format[5]->set_color('orange');

  $format[6] =& $workbook->addformat();
  $format[6]->set_align('center');
  $format[6]->set_align('vcenter');
  $format[6]->set_color('red');
  $format[6]->set_bold();

  $format[7] =& $workbook->addformat();
  $format[7]->set_align('center');
  $format[7]->set_align('vcenter');
  $format[7]->set_color('red');
  $format[7]->set_bold();

  $format_left[2] =& $workbook->addformat();
  $format_left[2]->set_align('vcenter');
  $format_left[2]->set_color('blue');

  $format_left[3] =& $workbook->addformat();
  $format_left[3]->set_align('vcenter');
  $format_left[3]->set_color('green');

  $format_left[4] =& $workbook->addformat();
  $format_left[4]->set_align('vcenter');
  $format_left[4]->set_color('pink');

  $format_left[5] =& $workbook->addformat();
  $format_left[5]->set_align('vcenter');
  $format_left[5]->set_color('orange');

  $format_left[6] =& $workbook->addformat();
  $format_left[6]->set_align('vcenter');
  $format_left[6]->set_color('red');
  $format_left[6]->set_bold();

  $format_left[7] =& $workbook->addformat();
  $format_left[7]->set_align('vcenter');
  $format_left[7]->set_color('red');
  $format_left[7]->set_bold();


  $formatccb =& $workbook->addformat();
  $formatccb->set_align('center');
  $formatccb->set_align('vcenter');
  $formatccb->set_bold();
  
  $formatccbr =& $workbook->addformat();
  $formatccbr->set_align('center');
  $formatccbr->set_align('vcenter');
  $formatccbr->set_color('red');
  $formatccbr->set_bold();

  $formatc =& $workbook->addformat();
  $formatc->set_align('vcenter');

  $formatcb =& $workbook->addformat();
  $formatcb->set_align('vcenter');
  $formatcb->set_bold();

  $i = 0;

  $ligneids = array();

  $sqlall = "SELECT s.code_client, s.nom, s.rowid as socid, l.ligne, f.nom as fournisseur, l.statut, l.rowid";
  $sqlall .= " , comm.name, comm.firstname, l.remise";
  $sqlall .= " , ".$db->pdate("l.date_commande")." as date_commande";
  $sqlall .= " FROM ".MAIN_DB_PREFIX."societe as s";
  $sqlall .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sqlall .= " , ".MAIN_DB_PREFIX."societe as r";
  $sqlall .= " , ".MAIN_DB_PREFIX."user as comm";
  $sqlall .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
  $sqlall .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
  $sqlall .= " AND l.fk_soc_facture = r.rowid ";
  $sqlall .= " AND l.fk_commercial = comm.rowid ";
  $sqlall .= " AND date_format(l.date_commande,'%Y%m') = '".strftime("%Y%m", $date_gen)."'";
  /*
   *
   */

  $sql = $sqlall;

  $sql .= " ORDER BY l.date_commande DESC";

  $result = $db->query($sql);

  if ($result)
    {
      $num = $db->num_rows();
  
      $worksheet->write(1, 0,  "Code", $formatc);
      $worksheet->write(1, 1,  "Client", $formatc);
      $worksheet->write(1, 2,  "Numéro", $formatcc);
      $worksheet->write(1, 3,  "Date commande", $formatcc);
      $worksheet->write(1, 4,  "Statut actuel", $formatcc);
  
      while ($i < $num)
	{
	  $obj = $db->fetch_object();	
      
	  $j = $i + 3;
	  
	  $soc = new Societe($db);
	  $soc->fetch($obj->socid);
      
	  $worksheet->write($j, 0,  $obj->code_client, $format_left[$obj->statut]);

	  $worksheet->write($j, 1,  $obj->nom, $format_left[$obj->statut]);
      
	  $worksheet->write_string($j, 2,  "$obj->ligne", $format[$obj->statut]);
      
	  $worksheet->write($j, 3,  strftime("%d/%m/%y",$obj->date_commande), $format[$obj->statut]);

	  $worksheet->write($j, 4,  $ligne->statuts[$obj->statut], $format[$obj->statut]);
  
	  $i++;
	}
	
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }

  /*
   *
   *
   */

  $workbook->close();
  /*
   *
   */
  if ($date_gen == $date_now && strlen($email))
    {
      MailFile($fname, $shortname, $email);
    }
  
}



function MailFile($filename, $shortname, $to)
{
  $subject = "Recapitulatif mensuel des commandes";

  $sendto = $to;

  $from = TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;
  
  $message = "Bonjour,\n\nVeuillez trouver ci-joint le dernier récapitulatif des commandes.\n\n";
  $message .= "\n\nCordialement,\n";
  
  $mailfile = new DolibarrMail($subject,
			       $sendto,
			       $from,
			       $message);

  $mailfile->PrepareFile(array($filename),
			 array("application/msexcel"),
			 array($shortname));
  
  if ( $mailfile->sendfile() )
    {
      return 0;
    }  
}
