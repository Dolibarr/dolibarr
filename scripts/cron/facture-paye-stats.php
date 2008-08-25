<?PHP
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       	scripts/cron/facture-paye-stats.php
        \ingroup    	invoice
        \brief      	Script de mise a jour de la table facture_stats de statistiques
		\deprecated		Ce script et ces tables ne sont pas utilisees.
		\version		$Id$
*/

// Test si mode CLI
$sapi_type = php_sapi_name();
$script_file=__FILE__; 
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}
 
// Recupere env dolibarr
$version='$Revision$';
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");


$error=0;
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

/*
 *
 */
$sql = "SELECT paye, count(*)";
$sql .= " FROM ".MAIN_DB_PREFIX."facture";
$sql .= " GROUP BY paye";

$resql = $db->query($sql);

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."facture_stats";
      $sqli .= " VALUES (now(),now(),'paye $row[0]',$row[1])";
     
      $resqli = $db->query($sqli);
    }
  $db->free($resql);
}

$sql = "SELECT paye, sum(total)";
$sql .= " FROM ".MAIN_DB_PREFIX."facture";
$sql .= " GROUP BY paye";

$resql = $db->query($sql);

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."facture_stats";
      $sqli .= " VALUES (now(),now(),'total $row[0]','$row[1]')";
     
      $resqli = $db->query($sqli);
    }
  $db->free($resql);
}

?>
