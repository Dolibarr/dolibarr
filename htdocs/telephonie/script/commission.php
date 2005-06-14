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


if (! $db->begin()) die ;


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

/********************************************************
 *
 * Calcul des avances
 *
 *
 *********************************************************/

$sql = "SELECT fk_distributeur, fk_contrat, datepo, montant";
$sql .= " , avance_pourcent, rem_pour_prev";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";

$sql .= " WHERE date_format(datepo, '%Y%m') = '".$year_prev.$month_prev."';";

$resql = $db->query($sql);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      
      $pourcent = $obj->rem_pour_prev;
      $avance_pourcent = $obj->avance_pourcent;
      
      $avance = $obj->montant * 12 * $avance_pourcent * $pourcent;
      
      $avance = round($avance  * 0.0001, 2);

      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_avance";
      $sqli .= " (date, fk_distributeur, fk_contrat, montant, pourcentage, avance)";
      $sqli .= " VALUES ('".$year.$month."'";
      $sqli .= ",".$obj->fk_distributeur.",".$obj->fk_contrat;
      $sqli .= ",".ereg_replace(",",".",$avance);
      $sqli .= ",".ereg_replace(",",".",$pourcent);
      $sqli .= ",1)";
      
      if (! $db->query($sqli))
	{
	  $error++;
	  dolibarr_syslog("Erreur ".$db->error());
	}
      
      $i++;
    }
  $db->free($resql);
}
else
{
  $error++;
  dolibarr_syslog("Erreur ".$db->error());
}
 

/********************************************************
 *
 * Somme des commissions de conso
 *
 *
 *********************************************************/

dolibarr_syslog("Conso");

$sql = "SELECT f.cout_vente, p.fk_contrat, l.rowid as ligne, p.fk_distributeur";
$sql .= " , p.avance_pourcent, p.rem_pour_prev, p.rem_pour_autr";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as f";

$sql .= " WHERE p.fk_contrat = c.rowid";

$sql .= " AND l.fk_contrat = c.rowid";

$sql .= " AND f.fk_ligne = l.rowid";

//$sql .= " AND date_format(f.date, '%Y%m') = '".$year_prev.$month_prev."'";

$resql = $db->query($sql);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  dolibarr_syslog("Conso : ".$num);
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      
      $pourcent = $obj->rem_pour_prev;
      
      $comm = round($obj->cout_vente * $pourcent * 0.01, 2) ;
      
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_conso";
      $sqli .= " (date, fk_distributeur, fk_contrat, fk_ligne, montant, pourcentage)";
      $sqli .= " VALUES ('".$year.$month."'";
      $sqli .= ",".$obj->fk_distributeur.",".$obj->fk_contrat.",".$obj->ligne;
      $sqli .= ",".ereg_replace(",",".",$comm);
      $sqli .= ",".ereg_replace(",",".",$pourcent);
      $sqli .= ")";
      
      if (! $db->query($sqli))
	{
	  dolibarr_syslog("Erreur ".$db->error());
	}
      
      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 9;
  dolibarr_syslog("Erreur ".$db->error());
}



foreach ($distributeurs as $distributeur_id)
{
  $distributeur = new DistributeurTelephonie($db);
  $distributeur->fetch($distributeur_id);

  dolibarr_syslog($distributeur->nom);
  dolibarr_syslog($month_prev."-".$year_prev);


  /********************************************************
   *
   * Somme des commissions
   *
   *
   *********************************************************/
  
  $sql = "SELECT sum(montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_avance";
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

	  if (! $db->query($sqli))
	    {
	      $error++;
	    }

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

if ($error == 0)
{
  $db->commit();
  dolibarr_syslog("Commit");
}
else
{
  $db->rollback();
  dolibarr_syslog("Rollback");
}

?>
