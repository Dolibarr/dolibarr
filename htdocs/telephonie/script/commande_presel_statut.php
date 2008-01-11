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
 * Recupération des fichiers CDR
 *
 */
require ("../../master.inc.php");
_log($GLOBALS["argv"][0]." Start", LOG_NOTICE);
require_once DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php";
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");

set_time_limit(0);

$host          = CMD_PRESEL_WEB_HOST;
$user_login    = CMD_PRESEL_WEB_USER;
$user_passwd   = CMD_PRESEL_WEB_PASS;

_log($GLOBALS["argv"][0]." Lecture des lignes", LOG_NOTICE);

$sql = "SELECT rowid,ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
$sql .= " WHERE fk_fournisseur = 4";
$sql .= " AND statut = 2;";

$resql = $db->query($sql);

if ($resql)
{
  $ids = array();
  while ($row = $db->fetch_row($resql))
    {
      array_push($ids, $row[1]);
    }
}
else
{
  _log($GLOBALS["argv"][0]." Erreur lecture liste des lignes", LOG_ERR);
  exit(1);
}

if (sizeof($ids) == 0)
{
  _log($GLOBALS["argv"][0]. " Aucune lignes à traiter - fin", LOG_NOTICE);
  exit(0);
}

$lignes = array();
for ($i = 0 ; $i < 10 ; $i++)
  $lignes[$i] = array();

foreach ($ids as $id)
{
  $idx = substr($id, -1);
  array_push($lignes[$idx], $id);
}

$childrenTotal = 10;
$childrenNow = 0;

while ( $childrenNow < $childrenTotal )
{
  $pid = pcntl_fork();
  
  if ( $pid == -1 )
    {
      die( "error\n" );
    }
  elseif ( $pid  )
    {
      // Père
      $childrenNow++;
    }
  else
    {
      if (sizeof($lignes[$childrenNow]))
	{
	  // Fils
	  GetPreselection_byRef($db, $host, $user_login, $user_passwd, $lignes[$childrenNow]);
	}
      exit(0);
    }
}

/*
 * Fonctions
 *
 */

function GetPreselection_byRef($db, $host, $user_login, $user_passwd, $ids)
{  

  $user = new User($db);
  $user->id = 1;

  foreach($ids as $cli)
    {
      _log("$cli Debut Traitement ligne", LOG_NOTICE);

      $fp = @fsockopen($host, 80, $errno, $errstr, 30);
      if (!$fp)
	{
	  _log("Impossible de se connecter au server $errstr ($errno)", LOG_ERR);
	}
      else
	{
	  $ligne_numero = "";
	  $ligne_service = "";
	  $ligne_presel = "";

	  //GetPreselection_byRef  
	  $url = "/AzurApp_websvc_b3gdb/account.asmx/GetPreselection_byRef?";

	  $url .= "user_login=".  $user_login;
	  $url .= "&user_passwd=".$user_passwd;
	  $url .= "&telnum=".$cli;

	  $out = "GET $url HTTP/1.1\r\n";
	  $out .= "Host: $host\r\n";
	  $out .= "Connection: Close\r\n\r\n";
	  
	  fwrite($fp, $out);
	  
	  while (!feof($fp))
	    {
	      $line = fgets($fp, 1024);

	      if (preg_match("/<Preselection .* \/>/",$line))
		{	      
		  $results = split(" ",trim($line));
		  //print_r($results);
		  
		  $array = array();
		  preg_match('/telnum="([0123456789]*)"/', $line, $array);
		  $ligne_numero = $array[1];
		  
		  $array = array();
		  preg_match('/Service_Statut="([\S]*)"/i', $line, $array);
		  $ligne_service = $array[1];
		  
		  $array = array();
		  preg_match('/PreSelection_Statut="([\S]*)"/i', $line, $array);
		  $ligne_presel = $array[1];
		  
		  _log($ligne_numero." ".$ligne_service." / ".$ligne_presel,LOG_NOTICE);
		}

	      if (preg_match("/<Error .* \/>/",$line))
		{	      
		  $array = array();
		  preg_match('/libelle="(.*)" xmlns:d4p1/', $line, $array);
		  _log($cli . " ErreurAPI ".$array[1], LOG_ERR);
		}
	    }
	  fclose($fp);

	  if ($ligne_numero)// && $ligne_service && $ligne_presel)
	    {
	      $situation_key = "$ligne_service / $ligne_presel";
	  
	      $sql = "SELECT date_traitement, situation";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
	      $sql .= " WHERE fk_fournisseur = 4";
	      $sql .= " AND cli = '".$ligne_numero."'";
	      $sql .= " ORDER BY date_traitement DESC LIMIT 1;";
	      
	      $resql = $db->query($sql);
	      
	      if ($resql)
		{
		  $num = $db->num_rows($resql);
		  $insert = 0;
		  if ($num == 0)
		    {
		      $insert = 1;
		    }
		  else
		    {
		      $row = $db->fetch_row($resql);
		      if (trim($row[1]) <> trim($situation_key))
			{
			  $insert = 1;
			}
		    }
		}
	      else
		{
		  _log("$cli lecture etat de ligne ERREUR", LOG_ERR);
		}
	      
	      _log("$cli log etat de la ligne", LOG_NOTICE);
	      if ($insert == 1)
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commande_retour";

		  if ($situation_key == 'TRAITE_OK / EN_COURS')
		    {
		      $sql .= " (cli,mode,date_traitement,situation,fk_fournisseur,traite) ";
		      $sql .= " VALUES ('$ligne_numero','PRESELECTION',now(),'$situation_key',4,1);";
		    }
		  elseif ($situation_key == 'ATTENTE / EN_COURS')
		    {
		      $sql .= " (cli,mode,date_traitement,situation,fk_fournisseur,traite) ";
		      $sql .= " VALUES ('$ligne_numero','PRESELECTION',now(),'$situation_key',4,1);";
		    }
		  elseif ($situation_key == 'TRAITE_OK / TRAITE_OK')
		    {
		      $sql .= " (cli,mode,date_traitement,situation,fk_fournisseur,traite) ";
		      $sql .= " VALUES ('$ligne_numero','PRESELECTION',now(),'$situation_key',4,1);";
		    }
		  else
		    {
		      $sql .= " (cli,mode,date_traitement,situation,fk_fournisseur) ";
		      $sql .= " VALUES ('$ligne_numero','PRESELECTION',now(),'$situation_key',4);";
		    }

		  $resql = $db->query($sql);
		  		  
		  if ($resql)
		    {
		      _log("$cli log etat de la ligne SUCCESS", LOG_NOTICE);

		      if ($situation_key == 'TRAITE_OK / TRAITE_OK')
			{
			  $ligne = new LigneTel($db);
  
			  if ($ligne->fetch($cli) == 1)
			    {
			      if ($ligne->statut == 2)
				{
				  $statut = 3;
				  $date_mise_service = strftime(time());
				  $datea = $db->idate($date_mise_service);
				  
				  if ($ligne->set_statut($user, $statut, $datea) <> 0)
				    {
				      $error++;
				      print "ERROR\n";
				    }
				}
			    }
			  else
			    {
			      print "Erreur de lecture\n";
			    }
			}

		      if ($situation_key == 'TRAITE_OK / ATTENTE')
			{
			  $ligne = new LigneTel($db);
  
			  if ($ligne->fetch($cli) == 1)
			    {
			      if ($ligne->statut == 2)
				{
				  $statut = 7;
				  $date_mise_service = strftime(time());
				  $datea = $db->idate($date_mise_service);
				  
				  if ($ligne->set_statut($user, $statut, $datea) <> 0)
				    {
				      $error++;
				      print "ERROR\n";
				    }
				}
			    }
			  else
			    {
			      print "Erreur de lecture\n";
			    }
			}

		    }
		  else
		    {
		      _log("$cli log etat de la ligne ERREUR", LOG_ERR);
		    }
		}
	      else
		{
		  _log("$cli log etat de la ligne IDENTIQUE", LOG_NOTICE);
		}
	    }
	  else
	    {
	      _log("$cli ERREUR impossible de récupérer les infos", LOG_ERR);
	    }
	  _log("$cli Fin Traitement ligne", LOG_NOTICE);
	}
    }
}

function _log($message, $level)
{

  if ($level == LOG_ERR)
    {
      openlog("dolibarr", LOG_PID | LOG_PERROR, LOG_LOCAL3);
    }
  else
    {
      openlog("dolibarr", LOG_PID, LOG_LOCAL3);
    }
  syslog($level, $message);

  closelog();
}

?>
