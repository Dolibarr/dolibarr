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
require_once DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php";

set_time_limit(0);

$host          = CMD_PRESEL_WEB_HOST;
$user_login    = CMD_PRESEL_WEB_USER;
$user_passwd   = CMD_PRESEL_WEB_PASS;

dolibarr_syslog($GLOBALS["argv"][0]." Lecture des lignes");

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
  dolibarr_syslog($GLOBALS["argv"][0]." Erreur lecture liste des lignes");
  exit(1);
}

if (sizeof($ids) == 0)
{
  dolibarr_syslog($GLOBALS["argv"][0]. " Aucune lignes à traiter - fin");
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
  foreach($ids as $cli)
    {
      $fp = fsockopen($host, 80, $errno, $errstr, 30);
      if (!$fp)
	{
	  dolibarr_syslog("$errstr ($errno)");
	}
      else
	{
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
		  
		  dolibarr_syslog(print $ligne_numero." ".$ligne_service." / ".$ligne_presel);
		}	      	      
	    }
	  fclose($fp);

	  $situation_key = "$ligne_service / $ligne_presel";

	  $sql = "SELECT max(date_traitement), situation";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
	  $sql .= " WHERE fk_fournisseur = 4";
	  $sql .= " AND cli = '".$ligne_numero."'";
	  $sql .= " GROUP BY cli;";

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
		  if ($row[1] <> $situation_key)
		    {
		      $insert = 1;
		    }
		}
	    }
	  else
	    {
	      dolibarr_syslog("Erreur lecture etat de ligne");
	    }

	  if ($insert == 1)
	    {
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commande_retour";
	      $sql .= " (cli,mode,date_traitement,situation,fk_fournisseur) ";
	      $sql .= " VALUES ('$ligne_numero','PRESELECTION',now(),'$situation_key',4);";

	      $resql = $db->query($sql);

	      if ($resql)
		{
		  
		}
	      else
		{
		  dolibarr_syslog("Error 43");
		}
	    }

	}
    }
}

?>
