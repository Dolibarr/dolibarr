<?php
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
 * Generation des graphiques sur les lignes
 *
 *
 */
require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/ProcessGraphLignes.class.php");

$childrenTotal = 4;
$childrenNow = 0;
$clientPerChild = 0;

$sql = "SELECT max(rowid)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";

if ($db->query($sql))
{
  $row = $db->fetch_row();
  $clientPerChild =  ceil($row[0] / $childrenTotal);
  $db->free();
}

while ( $childrenNow < $childrenTotal )
{
  $pid = pcntl_fork();
  
  if ( $pid == -1 )
    {
      die( "error\n" );
    }
  elseif ( $pid == 0 )
    {
      $childrenNow++;
    }
  else
    {
      $process = new ProcessGraphLignes( $childrenNow, $clientPerChild );
      $process->go();
      die();
    }

  usleep(100000);
}
?>
