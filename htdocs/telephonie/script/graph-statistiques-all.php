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
 * Generation des graphiques clients
 *
 *
 */
require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/ProcessGraphClients.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/ProcessGraphContrats.class.php");

$childrenTotal = 10;
$childrenNow = 0;
$clientPerChild = 0;
$contratPerChild = 0;

$sql = "SELECT max(s.idp)";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";

if ($db->query($sql))
{
  $row = $db->fetch_row();
  $clientPerChild =  ceil($row[0] / $childrenTotal);
  $db->free();
}

$sql = "SELECT max(rowid)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat";

if ($db->query($sql))
{
  $row = $db->fetch_row();
  $contratPerChild =  ceil($row[0] / $childrenTotal);
  $db->free();
}



while ( $childrenNow < (2 * $childrenTotal) )
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
      if ($childrenNow < 10)
	{
	  $process = new ProcessGraphClients( $childrenNow, $clientPerChild );
	  $process->go();
	}
      if ($childrenNow >= 10)
	{
	  $process = new ProcessGraphContrats( ($childrenNow - 10), $contratPerChild );
	  $process->go();
	}
      die();
    }  
}

?>
