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
 * Prépare les factures à imprimer
 */

/**
   \file       htdocs/telephonie/script/facturation-emission.php
   \ingroup    telephonie
   \brief      Emission des factures
   \version    $Revision$
*/

require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/pdf/pdfdetail_papier.modules.php");
require_once (DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once (DOL_DOCUMENT_ROOT."/includes/modules/facture/pdf_ibreizh.modules.php");

$error = 0;
$total_feuilles = 0;
$pages = 0;
$pages_facture = 0;
$opt = getopt("m:");

if ($opt['m'] > 0)
{
  $datetime = mktime(10,10,10,$opt['m'],10,2005);
}
else
{
  $datetime = time();
}
$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

$user = new User($db, 1);

$month = strftime("%m", $datetime);
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $month = "12";
  $year = $year - 1;
}
else
{
  $month = substr("00".($month - 1), -2) ;
}

$sql = "SELECT distinct(f.fk_facture), ff.facnumber ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service as cs";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."facture as ff";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE l.fk_contrat = cs.fk_contrat";
$sql .= " AND f.fk_ligne = l.rowid";
$sql .= " AND f.fk_facture = ff.rowid";
$sql .= " AND date_format(f.date,'%m%Y') = '".$month.$year."'";

$resql = $db->query($sql);
  
dolibarr_syslog("Impression des factures de ".$month.$year);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  dolibarr_syslog("$num factures a imprimer");

  $pdf = new FPDI_Protection('P','mm','A4');
                 		
		           // Protection et encryption du pdf
               if ($conf->global->PDF_SECURITY_ENCRYPTION)
               {
     	           $pdfrights = array('print'); // Ne permet que l'impression du document
    	           $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
     	           $pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
     	           $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
               }

  $pdf->Open();
  $pdf->SetMargins(10, 10, 10);
  $pdf->SetAutoPageBreak(1,0);
  $file = "/tmp/$year-$month-fac.pdf";

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      //print "$i/$num ".$row[1]." ".$row[0]."\n";

      $xx = new pdf_ibreizh($db);

      $pql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."prelevement_facture";
      $pql .= " WHERE fk_facture = ".$row[0];
      $repql = $db->query($pql);
      if ( $repql )
	{
	  $pow = $db->fetch_row($repql);
	  $db->free($repql);	  
	}

      if ($pow[0] > 0)
	{
	  $xx->message = "Cette facture sera prélevée sur votre compte bancaire.";
	}



      $xx->_write_pdf_file($row[0], &$pdf, 1);

      $feuilles = 0;
      $feuilles = $feuilles + $xx->pages;
      $pages = $pages + $xx->pages;
      $pages_facture = $pages_facture + $xx->pages;

      $fql = "SELECT rowid, ligne";
      $fql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
      $fql .= " WHERE f.fk_facture = ".$row[0];
      
      $refql = $db->query($fql);

      if ( $refql )
	{
	  while ($fow = $db->fetch_row($refql))
	    {
	      $obj_factel = new FactureTel($db);
	      $obj_factel->fetch($fow[0]);
	      $ligne_id = $fow[1];
	      $yy = new pdfdetail_papier ($db, $ligne_id, $year, $month, $obj_factel);
	      $yy->_write_pdf_file($obj_factel, $ligne_id, $pdf, 1);
	      $pages = $pages + $yy->pages;
	      $feuilles = $feuilles + $yy->pages;
	    }
	}
      $total_feuilles = $total_feuilles + ceil($feuilles / 2);
      $i++;
    }

  $pdf->Close();	      
  $pdf->Output($file);
  dolibarr_syslog("Generation de ".$pages_facture." envois");
  dolibarr_syslog("Generation de ".$pages." pages");
  dolibarr_syslog("Generation de ".$total_feuilles." feuilles");
  dolibarr_syslog("Ecriture de : ".$file);
  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
  dolibarr_syslog($db->error());
}

$db->close();

/*
 * Ancienne méthode
 *
 */

exit;

$sql = "SELECT distinct(f.fk_facture), ff.facnumber ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service as cs";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."facture as ff";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE l.fk_contrat = cs.fk_contrat";
$sql .= " AND f.fk_ligne = l.rowid";
$sql .= " AND f.fk_facture = ff.rowid";
$sql .= " AND date_format(f.date,'%m%Y') = '".$month.$year."'";

$resql = $db->query($sql);
  
dolibarr_syslog("Impression des factures de ".$month.$year);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  dolibarr_syslog("$num factures a imprimer");

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $file = DOL_DATA_ROOT."/facture/".$row[1]."/".$row[1].".pdf";

      if (! copy($file,"/tmp/facture/".$row[1].".pdf"))
	{
	  dolibarr_syslog("Error copy $file");
	}

      $i++;
    }

  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
  dolibarr_syslog($db->error());
}

$db->close();

?>
