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
 * Génération des détails de facture en tableur
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");
require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/script/facture-detail-tableur-one.class.php");

$error = 0;

/*
 *
 *
 */
$sql = "SELECT max(date_format(date,'%Y%m')) ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details" ;

$resql = $db->query($sql);

if ( $resql)
{

  $row = $db->fetch_row($resql);

  $year = substr($row[0],0,4);
  $month = substr($row[0],4,2);
}
else
{
  $error++;
}
  
dolibarr_syslog("Mois $month Année $year");  

/*
 * Lectures de différentes lignes
 */

if (!$error)
{
  
  $sql = "SELECT distinct(ligne) as client";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " ORDER BY ligne ASC";
  
  $clients = array();
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  
	  $clients[$i] = $objp->client;
	  
	  $i++;
	}            
      $db->free();
      print "$i lignes trouvées\n";
    }
  else
    {
      $error = 1;
      dolibarr_syslog($db->error());
    }
}

/*
 * Traitements
 *
 */

if (!$error)
{
   foreach ($clients as $client)
    {

      $facdet = new FactureDetailTableurOne($db);
      $resg = $facdet->GenerateFile ($client, $year, $month);
      
      if ($resg <> 0)
	{
	  print "- ERREUR lors de Génération du pdf détaillé\n";
	  $error = 19;
	}
    }
}

$db->close();

dolibarr_syslog("Conso mémoire ".memory_get_usage() );

?>
