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
 * Calcul des commissions des distributeurs
 */

require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/distributeurtel.class.php");

$error = 0;
$nbcommit = 0;
$datetime = time();

$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

/*
 * On facture les communications du mois précédent
 */

$month = strftime("%m", $datetime);
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $month_prev = "12";
  $year_prev = $year - 1;
}
else
{
  $month_prev = $month - 1;
  $year_prev = $year ;
}

$month_prev = substr("00".$month_prev, -2) ;


/********************************************************
 *
 * Verification des données
 *
 *
 *********************************************************/

$distributeurs = array();

$sql = "SELECT distinct fk_distributeur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
$sql .= " WHERE fk_distributeur <> 0";
  
$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      array_push($distributeurs, $row[0]);
      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
}




foreach ($distributeurs as $distributeur_id)
{
  $distributeur = new DistributeurTelephonie($db);
  $distributeur->fetch($distributeur_id);

  dolibarr_syslog($distributeur->nom);
  dolibarr_syslog($month_prev."-".$year_prev);

  /********************************************************
   *
   * Calcul des avances
   *
   *
   *********************************************************/
  
  $sql = "SELECT fk_distributeur, fk_contrat, datepo, montant";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
  $sql .= " WHERE fk_distributeur = ".$distributeur->id; 
  $sql .= " AND date_format(datepo, '%Y%m') = '".$year_prev.$month_prev."';";
  
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($resql);
	  
	  $pourcent = $distributeur->remun_pourcent_prev;

	  $avance = $obj->montant * $pourcent * 0.01;

	  $avance = round($avance * $distributeur->remun_avance * 0.01, 2);

	  
	  $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_detail";
	  $sqli .= " (date, fk_distributeur, fk_contrat, montant, pourcentage, avance)";
	  $sqli .= " VALUES ('".$year.$month."'";
	  $sqli .= ",".$distributeur->id.",".$obj->fk_contrat;
	  $sqli .= ",".ereg_replace(",",".",$avance);
	  $sqli .= ",".ereg_replace(",",".",$pourcent);
	  $sqli .= ",1)";

	  $resqli = $db->query($sqli);

	  $i++;
	}
      $db->free($resql);
    }
  else
    {
      $error = 1;
      dolibarr_syslog("Erreur ".$error);
    }
  

  /********************************************************
   *
   * Somme des commissions
   *
   *
   *********************************************************/
  
  $sql = "SELECT sum(montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_detail";
  $sql .= " WHERE fk_distributeur = ".$distributeur->id; 
  $sql .= " AND date = '".$year.$month."';";

  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  
	  $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission";
	  $sqli .= " (date, fk_distributeur, montant)";
	  $sqli .= " VALUES ('".$year.$month."'";
	  $sqli .= ",".$distributeur->id;
	  $sqli .= ",".ereg_replace(",",".",$row[0]).")";

	  $resqli = $db->query($sqli);

	  dolibarr_syslog($row[0]);

	  $i++;
	}
      $db->free($resql);
    }
  else
    {
      $error = 10;
      dolibarr_syslog("Erreur ".$error);
    }

}

?>
