<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Resiliation de lignes par API
 *
 */
require ("../../../master.inc.php");
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
 * Lecture des lignes a r販lier
 *
 */
$sql = "SELECT s.nom, s.rowid as socid, s.address, s.cp, s.ville";
$sql .= ", l.ligne, l.statut, l.rowid";

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
$sql .= " AND f.rowid = 4 AND l.statut = 4 ORDER BY s.rowid ASC";

$resql = $db->query($sql);
$result = 1;
if ($resql)
{
  $i = 0;
  $num = $db->num_rows($resql);
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
     
      $result = ResiliationPreselection($host, $user_login, $user_passwd, $obj->ligne, $num_abo);

      if ($result == 0)
	{
	  $lint = new LigneTel($db);
	  $lint->fetch_by_id($obj->rowid);
	  if ($lint->statut == 4)
	    {
	      $lint->set_statut($user, 5);
	    }
	}
     
      $i++;
    }
}

function ResiliationPreselection($host, $user_login, $user_passwd, $ligne, $id_person)
{  
  global $verbose;
  //dol_syslog("Appel de DeletePreselection($host, $user_login, ****, $ligne, $id_person)");

  $url = "/AzurApp_websvc_b3gdb/account.asmx/UpdatePreselection?";

  $url .= "user_login=".  $user_login;
  $url .= "&user_passwd=".$user_passwd;
  $url .= "&telnum=".$ligne;
  $url .= "&okCollecte=false";
  $url .= "&okPreselection=false";

  if ($verbose > 2)
    dol_syslog("$host");

  if ($verbose > 2)
    dol_syslog("$url");

  $fp = fsockopen($host, 80, $errno, $errstr, 30);
  if (!$fp)
    {
      dol_syslog("$errstr ($errno)");
    }
  else
    {
      if ($verbose > 2)
	dol_syslog("Socket Opened send data");

      $out = "GET $url HTTP/1.1\r\n";
      $out .= "Host: $host\r\n";
      $out .= "Connection: Close\r\n\r\n";
      
      fwrite($fp, $out);
      
      if ($verbose > 2)
	dol_syslog("Data sent, waiting for response");

      $parse = 0;
      $result = "error";

      $fresult = "";
      
      while (!feof($fp))
	{
	  $line = fgets($fp, 1024);
	  
	  if ($verbose > 2)
	    dol_syslog($line);

	  if ($parse == 1)
	    {
	      preg_match('/^<string xmlns=".*">(.*)<\/string>$/', $line, $results);
	      
	      $result = $results[1];
	      //dol_syslog($line);
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
    dol_syslog("result = ".$result);

  if (substr($result,0,2) == "OK")
    {
      dol_syslog("Resiliation r贳sie ligne ".$ligne." id client ".$id_person." $result\n");
      return 0;
    }
  else
    {
      dol_syslog("Resiliation 袨ou裠ligne ".$ligne." id client ".$id_person." $result\n");

      $fp = fopen("/tmp/$ligne.delete","w");
      if ($fp)
	{
	  fwrite($fp, $fresult);
	  fclose($fp);
	}

      return -1;
    }
}

?>
