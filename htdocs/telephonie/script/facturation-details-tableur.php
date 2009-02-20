<?PHP
/* Copyright (C) 2005-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once (DOL_DOCUMENT_ROOT."/telephonie/script/facture-detail-tableur-two.class.php");

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

//loop through our arguments and see what the user selected
for ($i = 1; $i < sizeof($GLOBALS["argv"]); $i++)
{
  switch($GLOBALS["argv"][$i])
    {
    case "--month":
      $month  = $GLOBALS["argv"][$i+1];
      break;
    case "--year":
      $year  = $GLOBALS["argv"][$i+1];
      break;
    }
}

 
dol_syslog("Mois $month Année $year");  

/*
 * Lectures de différentes lignes
 */

if (!$error)
{  
  $sql = "SELECT fk_contrat as contrat";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service";
  $sql .= " WHERE fk_service = 3";
  
  $contrats = array();
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      
      $i = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  
	  $contrats[$i] = $objp->contrat;
	  
	  $i++;
	}            
      $db->free();
    }
  else
    {
      $error = 1;
      dol_syslog($db->error());
    }
}

/*
 * Traitements
 *
 */

if (!$error)
{
  foreach ($contrats as $contrat)
    {
      $facdet = new FactureDetailTableurTwo($db);
      $resg = $facdet->GenerateFile ($contrat, $year, $month);
	      
      if ($resg <> 0)
	{
	  dol_syslog("ERREUR lors de Génération du détail tableur two");
	  $error = 19;
	}

      $sql = "SELECT rowid as ligne";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
      $sql .= " WHERE fk_contrat = ".$contrat;
      
      $resql=  $db->query($sql) ;

      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);
	      
	      $contrats[$i] = $objp->contrat;

	      $facdet = new FactureDetailTableurOne($db);
	      $resg = $facdet->GenerateFile ($obj->ligne, $year, $month);
	      
	      if ($resg <> 0)
		{
		  dol_syslog("ERREUR lors de Génération du détail tableur one");
		  $error = 19;
		}
	      
	      $i++;
	    }            
	  $db->free();
	}
      else
	{
	  $error = 1;
	  dol_syslog($db->error());
	}           
    }
}

$db->close();

dol_syslog("Conso mémoire ".memory_get_usage() );
?>
