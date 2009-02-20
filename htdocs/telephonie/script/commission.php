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
$space = str_repeat(" ",28);
$user = new User($db, 1);
$user->login = "Rodo";

$opt = getopt("m:");

if ($opt['m'] > 0)
{
  $datetime = mktime(10,10,10,$opt['m'],10,2005);
}

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


$dir = DOL_DATA_ROOT.'/telephonie/log/';
if (!file_exists($dir))
  create_dir($dir);

$dir = DOL_DATA_ROOT.'/telephonie/log/commission/';
if (!file_exists($dir))
  create_dir($dir);

function create_dir($dir)
{
  if (! file_exists($dir))
    {
      umask(0);
      if (! @mkdir($dir, 0755))
	{
	  die ("Erreur: Le répertoire ".$dir." n'existe pas et Dolibarr n'a pu le créer.");
	}
    }
}

if (! $db->begin()) die ;

$fp = fopen($dir."/$month.$year.log","w+");
fputs($fp,"Commissions $month/$year\n");
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
  while ($row = $db->fetch_row($resql))
    {
      array_push($distributeurs, $row[0]);
    }
  $db->free($resql);
}
else
{
  $error = 1;
  dol_syslog("Verification Erreur ".$error);
}

/********************************************************
 *
 * Calcul des avances
 *
 *
 ********************************************************/
dol_syslog("Calcul avance");
$sql = "SELECT rowid, fk_distributeur, fk_contrat, datepo, montant";
$sql .= " , avance_pourcent, rem_pour_prev, rem_pour_autr, mode_paiement";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
$sql .= " WHERE date_format(datepo, '%Y%m') = '".$year_prev.$month_prev."'";
$sql .= " AND fk_distributeur > 0";

$resql = $db->query($sql);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      
      if ($obj->mode_paiement == 'pre')
	{
	  $pourcent = $obj->rem_pour_prev;
	}
      else
	{
	  $pourcent = $obj->rem_pour_autr;
	}
      $avance_pourcent = $obj->avance_pourcent;
      
      $avance = $obj->montant * 12 * $avance_pourcent * $pourcent;
      
      $avance = round($avance  * 0.0001, 2);

      fputs($fp, "DIS : ".$obj->fk_distributeur);
      fputs($fp, " av avance po ".substr($space.$obj->rowid,-4));
      fputs($fp, " : ".substr($space.$avance,-8)."\n");

      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_avance";
      $sqli .= " (date, fk_distributeur, fk_po,fk_contrat, montant, pourcentage, avance)";
      $sqli .= " VALUES ('".$year_prev.$month_prev."'";
      $sqli .= ",".$obj->fk_distributeur.",".$obj->rowid.",".$obj->fk_contrat;
      $sqli .= ",".ereg_replace(",",".",$avance);
      $sqli .= ",".ereg_replace(",",".",$pourcent);
      $sqli .= ",1)";
      
      if (! $db->query($sqli))
	{
	  $error = 2;
	  dol_syslog("Calcul avance Erreur ");
	  dol_syslog($db->error());
	  dol_syslog("$sqli");
	}
      
      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 3;
  dol_syslog("Erreur ".$db->error());
}
 

/********************************************************
 *
 * Calculs des commissions basées sur les consommations
 *
 *
 *********************************************************/

$sql = "SELECT p.rowid,  p.fk_contrat,  p.fk_distributeur";
$sql .= " , p.avance_pourcent, p.rem_pour_prev, p.rem_pour_autr";
$sql .= " , p.avance_duree, p.mode_paiement";
$sql .= " , date_format(p.datepo + INTERVAL p.avance_duree MONTH, '%Y%m') as date_regul";
$sql .= " , f.cout_vente,l.rowid as ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as f";

$sql .= " WHERE p.fk_contrat = c.rowid";
$sql .= " AND l.fk_contrat = c.rowid";
$sql .= " AND f.fk_ligne = l.rowid";
$sql .= " AND date_format(f.date, '%Y%m') = '".$year_prev.$month_prev."'";
$sql .= " AND date_format(p.datepo, '%Y%m') <= '".$year_prev.$month_prev."'";
$sql .= " AND fk_distributeur > 0";

$resql = $db->query($sql);
//print $sql;
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
  
      if ($obj->mode_paiement == 'pre')
	{
	  $pourcent = $obj->rem_pour_prev;
	}
      else
	{
	  $pourcent = $obj->rem_pour_autr;
	}

      $comm = round($obj->cout_vente * $pourcent * 0.01, 2) ;
     
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_conso";
      $sqli .= " (date, fk_distributeur, fk_contrat, fk_ligne, montant, pourcentage, avance)";
      $sqli .= " VALUES ('".$year_prev.$month_prev."'";
      $sqli .= ",".$obj->fk_distributeur.",".$obj->fk_contrat.",".$obj->ligne;
      $sqli .= ",".ereg_replace(",",".",$comm);
      $sqli .= ",".ereg_replace(",",".",$pourcent);

      if ($obj->date_regul < $year_prev.$month_prev)
	{
	  $sqli .= ",0)";
	  $avan = 0;
	}
      else
	{
	  $sqli .= ",1)";
	  $avan = 1;
	}

      fputs($fp, "DIS : ".$obj->fk_distributeur);
      fputs($fp, " CON : ".$obj->fk_contrat);
      fputs($fp, " REM : ".$pourcent."%");
      fputs($fp, " conso : $comm avance $avan\n");
      
      if (! $db->query($sqli))
	{
	  $error = 4;
	  dol_syslog("Calcul conso Erreur");
	  dol_syslog($db->error());
	  dol_syslog("$sqli");
	}

      //dol_syslog("Conso po : ".$obj->rowid . " ".$comm);
              
      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 5;
  dol_syslog("Erreur ".$db->error());
}

/********************************************************
 *
 * Régulation sur contrats annulés
 *
 *
 *********************************************************/


/********************************************************
 *
 * Régulation des avances
 *
 *
 *********************************************************/
$distri_av = array();

$sql = "SELECT distinct fk_distributeur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_avance";
$sql .= " WHERE fk_distributeur <> 0";
  
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      array_push($distri_av, $row[0]);
    }
  $db->free($resql);
}
else
{
  $error = 6;
  dol_syslog("Erreur regul avances ".$error);
}

$avan_regul = array();
$comm_regul = array();

foreach ($distri_av as $distributeur_id)
{
  $avan_regul[$distributeur_id] = 0;
  $comm_regul[$distributeur_id] = 0;

  $sqla = "SELECT rowid, ".$db->pdate("datepo").", avance_duree";
  $sqla .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
  $sqla .= " WHERE fk_distributeur = ".$distributeur_id;
  $sqla .= " AND date_format(datepo + INTERVAL avance_duree MONTH, '%Y%m')='".$year_prev.$month_prev."';";

  $resqla = $db->query($sqla);
  
  if ( $resqla )
    {
      $numa = $db->num_rows($resqla);
      $ia = 0;
      
      while ($ia < $numa)
	{
	  $rowa = $db->fetch_row($resqla);
 	  dol_syslog("* Regul des avances de la po " .$rowa[0] . " ".strftime("%Y%m",$rowa[1]));
	  $ia++;

	  /* Calcul des sommes avancées */
	  $sql = "SELECT a.montant, a.fk_contrat, c.statut";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_avance as a";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
	  $sql .= " WHERE a.fk_distributeur = ".$distributeur_id;
	  $sql .= " AND c.rowid = a.fk_contrat";
	  $sql .= " AND a.fk_po = ".$rowa[0];
	  
	  $resql = $db->query($sql);
	  
	  if ( $resql )
	    {
	      $num = $db->num_rows($resql);
	      dol_syslog("* Regul des avances de la po ".$rowa[0]." ".strftime("%Y%m",$rowa[1]).", $num avances");
	      $i = 0;	      
	      while ($i < $num)
		{
		  $row = $db->fetch_row($resql);
		  
		  $avan_regul[$distributeur_id] = $avan_regul[$distributeur_id] + $row[0];
		  
		  fputs($fp, "DIS : ".$distributeur_id);
		  fputs($fp, " av regul  po ".substr($space.$rowa[0],-4));
		  fputs($fp, " : ".substr($space.$row[0],-8)."\n");

		  $sqlir = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_regul";
		  $sqlir .= " (date, fk_distributeur, fk_contrat, montant, type)";
		  $sqlir .= " VALUES ('".$year_prev.$month_prev."'";
		  $sqlir .= ",'".$distributeur_id."','".$row[1];
		  $sqlir .= "','-".ereg_replace(",",".",$row[0]);
		  $sqlir .= "','avan')";
		  $resqlir = $db->query($sqlir);
		  if (!$resqlir)
		    {
		      $error = 32;
		      dol_syslog("Erreur insertion regul avances (error $error)");
		      dol_syslog($sqlir);
		    }

		  dol_syslog("* Avance ".$row[0] . " statut : ".$row[2]);
		  
		  /* Communications relatives */
		  $datup = $year_prev.$month_prev;
		  $datdo = strftime("%Y%m",$rowa[1]);
		  if ($row[2] <> 6)
		    {
		      dol_syslog("* Communications <= $datup >= $datdo ");
		      $sqlc = "SELECT sum(montant)";
		      $sqlc .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso";
		      $sqlc .= " WHERE fk_contrat = ". $row[1];
		      $sqlc .= " AND date <= '".$datup."' AND date >= '".$datdo."'";
		      
		      $resqlc = $db->query($sqlc);
		      
		      if ( $resqlc )
			{		      
			  while ($rowc = $db->fetch_row($resqlc))
			    {
			      $comm_regul[$distributeur_id] = $comm_regul[$distributeur_id] + $rowc[0];
			      dol_syslog("* Conso générée ".$rowc[0]);

			      $sqlir = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission_regul";
			      $sqlir .= " (date, fk_distributeur, fk_contrat, montant, type)";
			      $sqlir .= " VALUES ('".$year_prev.$month_prev."'";
			      $sqlir .= ",'".$distributeur_id."','".$row[1];
			      $sqlir .= "','".ereg_replace(",",".",$rowc[0]);
			      $sqlir .= "','comm')";
			      $resqlir = $db->query($sqlir);
			      if (!$resqlir)
				{
				  $error = 31;
				  dol_syslog("Erreur insertion regul avances conso (error $error)");
				  dol_syslog($sqlir);
				}
			    }
			  $db->free($resqlc);
			}
		      else
			{
			  $error = 10;
			  dol_syslog("Erreur regul avances conso ".$error);
			}
		    }
		  else
		    {
		      /*
		       * Contrats résiliés
		       *
		       */
		      fputs($fp, "DIS : ".$obj->fk_distributeur);
		      fputs($fp, " CON : ".$row[1] . " ANNULE\n");

		      $sqlc = "UPDATE ".MAIN_DB_PREFIX."telephonie_commission_conso";
		      $sqlc .= " SET annul = '".$year_prev.$month_prev."'";
		      $sqlc .= " WHERE fk_contrat = ".$row[1];
		      $sqlc .= " AND date <= '".$datup."' AND date >= '".$datdo."'";

		      $resqlc = $db->query($sqlc);

		      if (! $resqlc )
			{
			  $error = 11;
			  dol_syslog("Erreur regul avances conso ".$error);
			}
		    }
		  
		  $i++;
		}
	      $db->free($resql);
	    }
	  else
	    {
	      $error = 12;
	      dol_syslog("Erreur regul avances ".$db->error());
	    }	  	  	  	 	  
	}
    }
  else
    {
      $error = 13;
      dol_syslog("Erreur regul avances aaaa".$db->error());
      dol_syslog($sqla);
    }
}

/********************************************************
 *
 * Calcul des consos
 *
 *
 *********************************************************/
$distri_co = array();

$sql = "SELECT distinct fk_distributeur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso";
$sql .= " WHERE fk_distributeur <> 0";
  
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      array_push($distri_co, $row[0]);
    }
  $db->free($resql);
}
else
{
  $error = 14;
  dol_syslog("Erreur calcul des commission sur conso ".$error);
}

$comm_conso = array();

foreach ($distri_co as $distributeur_id)
{
  $comm_conso[$distributeur_id] = 0;

  $sqla = "SELECT rowid, ".$db->pdate("datepo").", fk_contrat";
  $sqla .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre";
  $sqla .= " WHERE fk_distributeur = ".$distributeur_id;
  $sqla .= " AND date_format(datepo + INTERVAL avance_duree MONTH, '%Y%m')<'".$year_prev.$month_prev."';";

  $resqla = $db->query($sqla);
  
  if ( $resqla )
    {
      $numa = $db->num_rows($resqla);
      $ia = 0;
      
      while ($ia < $numa)
	{
	  $rowa = $db->fetch_row($resqla);
	  dol_syslog("** Calcul des consos po " .$rowa[0] . " ".strftime("%Y%m",$rowa[1]));
	  $ia++;

	  /* Communications relatives */

	  $datup = $year_prev.$month_prev;
	  
	  dol_syslog("** Communications  $datup");
		  
	  $sqlc = "SELECT sum(montant)";
	  $sqlc .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_conso";
	  $sqlc .= " WHERE fk_contrat = ". $rowa[2];
	  $sqlc .= " AND date = '".$datup."';";
	  
	  $resqlc = $db->query($sqlc);
	  
	  if ( $resqlc )
	    {
	      if ( $rowc = $db->fetch_row($resqlc) )
		{
		  $comm_conso[$distributeur_id] = $comm_conso[$distributeur_id] + $rowc[0];		  
		  dol_syslog("** Conso générée ".$rowc[0]);
		}
	      else
		{
		  $error = 151;
		  dol_syslog("Erreur regul conso");
		}
	      $db->free($resqlc);
	    }
	  else
	    {
	      $error = 15;
	      dol_syslog("Erreur regul conso");
	    }
	}	  		      
    }
  else
    {
      $error = 16;
      dol_syslog("Erreur regul conso".$db->error());
      dol_syslog($sqla);
    }
}

/********************************************************
 *
 *
 *
 ********************************************************/

foreach ($distributeurs as $distributeur_id)
{
  $distributeur = new DistributeurTelephonie($db);
  $distributeur->fetch($distributeur_id);

  dol_syslog($distributeur->nom . " : ".$month_prev."-".$year_prev);

  $amount = 0;

  $amount = $amount + $comm_regul[$distributeur_id];
  $amount = $amount - $avan_regul[$distributeur_id];
  $amount = $amount + $comm_conso[$distributeur_id];

  fputs($fp, "DIS : ".$distributeur_id);
  fputs($fp, " ".str_repeat("-",35)."\n");

  fputs($fp, "DIS : ".$distributeur_id);
  fputs($fp, " Comm Regul : ".substr($space.$comm_regul[$distributeur_id],-15)."\n");

  fputs($fp, "DIS : ".$distributeur_id);
  fputs($fp, " Comm Conso : ".substr($space.$comm_conso[$distributeur_id],-15)."\n");

  /********************************************************
   *
   * Somme des commissions
   *
   *
   *********************************************************/
  
  $sql = "SELECT sum(montant)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission_avance";
  $sql .= " WHERE fk_distributeur = ".$distributeur->id; 
  $sql .= " AND date = '".$year_prev.$month_prev."';";

  $resql = $db->query($sql);
  
  if ( $resql )
    {
      if ($row = $db->fetch_row($resql))
	{
	  fputs($fp, "DIS : ".$distributeur_id);
	  fputs($fp, " Avances    : ".substr($space.$row[0],-15)."\n");

	  fputs($fp, "DIS : ".$distributeur_id);
	  fputs($fp, " Avan Regul : ".substr($space."-".$avan_regul[$distributeur_id],-15)."\n");

	  $amount = $amount + $row[0];
	  /* commission finale */
	  $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commission";
	  $sqli .= " (date, fk_distributeur, montant)";
	  $sqli .= " VALUES ('".$year_prev.$month_prev."'";
	  $sqli .= ",".$distributeur->id;
	  $sqli .= ",".ereg_replace(",",".",$amount).")";

	  if (! $db->query($sqli))
	    {
	      $error = 17;
	      dol_syslog("Erreur insertion Commission finale");
	    }

	  dol_syslog("Commission finale ".$amount);

	  fputs($fp, "DIS : ".$distributeur_id);
	  fputs($fp, " Comm final : ".substr($space.$amount,-15)."\n");
	}
      else
	{
	  $error = 18;
	  dol_syslog("Erreur lecture avances");
	}
      $db->free($resql);
    }
  else
    {
      $error = 19;
      dol_syslog("Erreur ".$error);
    }
}

if ($error == 0)
{
  $db->commit();
  dol_syslog("Commit");
}
else
{
  $db->rollback();
  dol_syslog("Rollback", LOG_ERR);
}
dol_syslog("----------------");
fclose($fp);
?>
