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
 * Script de facturation
 * Emets les factures compta en partant des factures téléphonique
 *
 */

/**
   \file       htdocs/telephonie/script/facturation-emission.php
   \ingroup    telephonie
   \brief      Emission des factures
   \version    $Revision$
*/


require ("../../master.inc.php");

$opt = getopt("l:c:");

$limit = $opt['l'];
$optcontrat = $opt['c'];
/*
if (strlen($limit) == 0 && strlen($optcontrat) == 0)
{
  print "Usage :\n  php facturation-emission.php -l <limit>\n";
  exit;
}
*/
require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once (DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");


$error = 0;

$datetime = time();
$datetimeprev = $datetime; // Date du prélèvement

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

/*
 * Lecture du batch
 *
 */
$lignes = 0;
$sql = "SELECT ligne FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
$sql .= " WHERE statut = 3";

$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);      
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      
      $sqla = "SELECT count(*)";
      $sqla .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
      $sqla .= " WHERE ligne = '".$row[0]."'";
      $sqla .= " AND date_format(date,'%Y%m') ='".$year.$month."'";

      $resqla = $db->query($sqla);
      
      if ( $resqla )
	{

	  $rowa = $db->fetch_row($resqla);

	  if ($rowa[0] == 0)
	    {


	      $sqlb = "SELECT max(date)";
	      $sqlb .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
	      $sqlb .= " WHERE ligne = '".$row[0]."'";

	      $resqlb = $db->query($sqlb);
	      
	      if ( $resqlb )
		{		  
		  $rowb = $db->fetch_row($resqlb);
		}


	      print $row[0]." ".$rowb[0]."\n";
	      $lignes++;
	    }
	}

      $i++;
    }            
   
  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
}

$db->close();

?>
