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
 * Commandes des lignes par API
 *
 */
require ("../../master.inc.php");
require_once DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php";

$verbose = 0;

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
  if ($argv[$i] == "-v")
    {
      $verbose = 1;
    }
  if ($argv[$i] == "-vv")
    {
      $verbose = 2;
    }
  if ($argv[$i] == "-vvv")
    {
      $verbose = 3;
    }
}


$user = new User($db);
$user->id = 1; // C'est sale je sais !

$host          = CMD_PRESEL_WEB_HOST;
$user_login    = CMD_PRESEL_WEB_USER;
$user_passwd   = CMD_PRESEL_WEB_PASS;
$user_contract = CMD_PRESEL_WEB_CONTRACT;

/*
 * Lecture des lignes a commander
 *
 */
$sql = "SELECT s.nom, s.idp as socid, s.address, s.cp, s.ville";
$sql .= ", l.ligne, l.statut, l.rowid";

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid";
$sql .= " AND f.rowid = 4 AND l.statut = 9 ORDER BY s.idp ASC";

$resql = $db->query($sql);
$result = 1;
if ($resql)
{
  $i = 0;
  $num = $db->num_rows($resql);
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);

      $num_abo = GetNumAbonne($db, $obj->socid, 4);

      if ($num_abo == 0)
	{
	  $societe_nom        = $obj->nom;
	  $societe_adresse    = $obj->address;
	  $societe_codepostal = $obj->cp;
	  $societe_ville      = $obj->ville;
	  
	  $num_abo = CreateAbonne($host, 
				  $user_login, 
				  $user_passwd, 
				  $user_contract,
				  $societe_nom,
				  $societe_adresse,
				  $societe_codepostal,
				  $societe_ville);

	  if ($num_abo > 0)
	    {
	      $result = SetNumAbonne($db, $obj->socid, $num_abo, 4);
	    }	  
	  else
	    {
	      $result = 1;
	    }
	}
      else
	{
	  $result = 0;
	}
     
      if ($result == 0)
	{
	  $result = CreatePreselection($host, $user_login, $user_passwd, $obj->ligne, $num_abo);
	}

      if ($result == 0)
	{
	  $lint = new LigneTel($db);
	  $lint->fetch_by_id($obj->rowid);
	  if ($lint->statut == 9)
	    {
	      $lint->set_statut($user, 2);
	    }
	}
     
      $i++;
    }
}

function CreatePreselection($host, $user_login, $user_passwd, $ligne, $id_person)
{  
  global $verbose;
  //dolibarr_syslog("Appel de CreatePreselection($host, $user_login, ****, $ligne, $id_person)");

  $url = "/AzurApp_websvc_b3gdb/account.asmx/CreatePreselection?";

  $url .= "user_login=".  $user_login;
  $url .= "&user_passwd=".$user_passwd;
  $url .= "&id_person=".$id_person;
  $url .= "&telnum=".$ligne;
  $url .= "&okCollecte=true";
  $url .= "&okPreselection=true";

  if ($verbose > 2)
    dolibarr_syslog("$host");

  if ($verbose > 2)
    dolibarr_syslog("$url");

  $fp = fsockopen($host, 80, $errno, $errstr, 30);
  if (!$fp)
    {
      dolibarr_syslog("$errstr ($errno)");
    }
  else
    {
      if ($verbose > 2)
	dolibarr_syslog("Socket Opened send data");

      $out = "GET $url HTTP/1.1\r\n";
      $out .= "Host: $host\r\n";
      $out .= "Connection: Close\r\n\r\n";
      
      fwrite($fp, $out);
      
      if ($verbose > 2)
	dolibarr_syslog("Data sent, waiting for response");

      $parse = 0;
      $result = "error";

      $fresult = "";
      
      while (!feof($fp))
	{
	  $line = fgets($fp, 1024);
	  
	  if ($verbose > 2)
	    dolibarr_syslog($line);

	  if ($parse == 1)
	    {
	      preg_match('/^<string xmlns=".*">(.*)<\/string>$/', $line, $results);
	      
	      $result = $results[1];
	      //dolibarr_syslog($line);
	      $parse = 0;
	    }
	  
	  if (substr($line,0,38) == '<?xml version="1.0" encoding="utf-8"?>')
	    {
	      $parse = 1;
	    }

	  $fresult .= $line;

	}
      fclose($fp);
    }
  
  if ($verbose > 1)
    dolibarr_syslog("result = ".$result);

  if (substr($result,0,2) == "OK")
    {
      dolibarr_syslog("Presel réussie ligne ".$ligne." id client ".$id_person." $result\n");
      return 0;
    }
  else
    {
      dolibarr_syslog("Presel échouée ligne ".$ligne." id client ".$id_person." $result\n");

      $fp = fopen("/tmp/$ligne.presel","w");
      if ($fp)
	{
	  fwrite($fp, $fresult);
	  fclose($fp);
	}

      return -1;
    }
}

function GetNumAbonne($db, $socid, $fournid)
{
  //dolibarr_syslog("Appel de GetNumAbonne($socid, $fournid)");

  $sql = "SELECT fourn_id";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_fournid";
  $sql .= " WHERE fk_soc = ".$socid;
  $sql .= " AND fk_fourn = ".$fournid;

  $resql = $db->query($sql);

  if ($resql)
    {
      if ($db->num_rows($resql) > 0)
	{
	  $row = $db->fetch_row($resql);
	  return $row[0];
	}
      else
	{
	  return 0;
	}
    }
  else
    {
      dolibarr_syslog("Erreur dans GetNumAbonne($socid, $fournid)");
      return -1;
    }
}

function SetNumAbonne($db, $socid, $soc_fourn_id, $fournid)
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_fournid";
  $sql .= " (fk_soc, fourn_id, fk_fourn, datec) ";
  $sql .= " VALUES ($socid, $soc_fourn_id, $fournid, now()) ;";

  $resql = $db->query($sql);
  
  if ($resql)
    {
      return 0;
    }
  else
    {
      dolibarr_syslog("Erreur dans SetNumAbonne($socid, $soc_fourn_id, $fournid)");
      return -1;
    }
}

function CreateAbonne($host, $user_login, $user_passwd, $user_contract, $societe_nom, $societe_adresse, $societe_codepostal, $societe_ville)
{
  $result = "error";

  $civilite = 1;
  
  $url = "/AzurApp_websvc_b3gdb/account.asmx/CreateAbonne?";
  
  $url .= "user_login=".  $user_login;
  $url .= "&user_passwd=".$user_passwd;
  $url .= "&civilite=".   urlencode($civilite);
  $url .= "&id_contract=".$user_contract;
  $url .= "&firstname=".  urlencode("Societe");
  $url .= "&lastname=".   urlencode($societe_nom);
  $url .= "&adresse=".    urlencode($societe_adresse);
  $url .= "&codepostal=". urlencode($societe_codepostal);
  $url .= "&ville=".      urlencode($societe_ville);
  $url .= "&pays=".       urlencode("NULL");
  $url .= "&telnum=".     urlencode("NULL");
  
  $fp = fsockopen($host, 80, $errno, $errstr, 30);
  if (!$fp)
    {
      dolibarr_syslog("$errstr ($errno)");
    }
  else
    {
      $out = "GET $url HTTP/1.1\r\n";
      $out .= "Host: $host\r\n";
      $out .= "Connection: Close\r\n\r\n";
      
      fwrite($fp, $out);
      
      $parse = 0;
      
      while (!feof($fp))
	{
	  $line = fgets($fp, 1024);
	  
	  if ($parse == 1)
	    {
	      //print $line."\n";
	      
	      preg_match('/^<string xmlns=".*">(.*):(.*)<\/string>$/', $line, $results);
	      
	      $result = $results[1];
	      $client_id = $results[2];
	      dolibarr_syslog($line);
	    }
	  
	  if (substr($line,0,38) == '<?xml version="1.0" encoding="utf-8"?>')
	    {
	      $parse = 1;
	    }
	}
      fclose($fp);
    }
  
  dolibarr_syslog("$result:$client_id");

  if ($result == "OK")
    {
      dolibarr_syslog("Commande réussie id client ".$client_id);
      return $client_id;
    }
  else
    {
      return 0;
    }
}
?>
