#!/usr/bin/php
<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * Désactivation des CV
 *
 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') 
{
  echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer ce script en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
  exit;
}

require ("../../htdocs/master.inc.php");
require(DOL_DOCUMENT_ROOT."/lolix/cv/cv.class.php");


$error = 0;

$now = time();
$tlimit = $now - (86400 * 35); // 35 jours

$sql = "SELECT c.idp";
$sql .= " FROM lolixfr.candidat as c";
$sql .= " WHERE c.active = 1";
$sql .= " AND datea > ".$db->idate($tlimit);
$sql .= " ORDER BY c.datea ASC";
$sql .= " LIMIT 3";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  $i = 0;
  
  dolibarr_syslog("candidat-desactive: ".$num." CV ");

  while ($i < $num)
    {
      $obj = $db->fetch_object();

      dolibarr_syslog("candidat-desactive: desactivation CV ".$obj->idp);

      $cv = new Cv($db);
      $cv->id = $_GET["id"];
      $cv->fetch();
      $cv->deactivate();
      $i++;     
    }
}
else
{
  print "Error";
}
?>
