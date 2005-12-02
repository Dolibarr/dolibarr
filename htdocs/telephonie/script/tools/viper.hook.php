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
 * Ce script se veut plus un squelette pour effectuer des opérations sur la base
 * qu'un réel scrip de production.
 *
 * Recalcul le montant d'une facture lors d'une erreur de tarif
 *
 */

require ("../../../master.inc.php");

$title = "DEVEL TITLE";
$desc = "Developpements en cours";
$user_id = 587;

if ($db->begin())
{
  $result = 1;
  $sql = "UPDATE vtiger.crmentity_seq set id=LAST_INSERT_ID(id+1)";
  $resql = $db->query($sql);
  if ( $resql )
    {
      $result = 0;
    }
  else
    {
      print "Error 1\n";
    }

  $sql = "SELECT id from vtiger.crmentity_seq;";
  
  if ($result == 0)
    {
      $result = 1;
      $resql = $db->query($sql);  
      if ( $resql )
	{
	  if ($row = $db->fetch_row($resql))
	    {
	      $tid = $row[0];
	      $result = 0;
	    }
	  else
	    {
	      print "Error 2\n";
	    }
	}
      else
	{
	  print "Error 3\n";
	}
    }

  if ($result == 0 && $tid > 0)
    {
      $sql = "INSERT INTO vtiger.troubletickets (ticketid, parent_id, priority, product_id, severity, status, category, update_log, title, description, solution) values ";
      $sql .= " (".$tid.", '', 'Low', '', 'Feature', 'Open', 'Big Problem', 'Friday 02nd December 2005 01:12:42 PM by rodo--//--Ticket created. Assigned to rodo--//--', '".$title."', '".$desc."', '')";
      
      $result = 1;
      $resql = $db->query($sql);
      if ( $resql )
	{
	  $result = 0;
	}
      else
	{
	  print $db->error()."\n";
	  print "$sql\n";
	}
    }

  $sql = "INSERT INTO vtiger.crmentity ";
  $sql .= " (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) ";
  $sql .= " VALUES ('".$tid."','$user_id','$user_id','HelpDesk','".$desc."',now(),now())";

  if ($result == 0 && $tid > 0)
    {
      $resql = $db->query($sql);
      $result = 1;
      if ( $resql )
	{
	  $result = 0;
	}
      else
	{
	  print $db->error()."\n";
	  print "$sql\n";
	}
    }
  
  if ($result == 0 && $tid > 0)
    {
      $sql = "INSERT INTO vtiger.ticketcf (ticketid) values ($tid)";
      $resql = $db->query($sql);
      $result = 1;
      if ( $resql )
	{
	  $result = 0;
	}
    }

  if ($result == 0 )
    {
      $sql = "INSERT INTO vtiger.ticketcomments (ticketid, comments) values ($tid, '')";
      $resql = $db->query($sql);
      $result = 1;
      if ( $resql )
	{
	  $result = 0;
	}
    }
  
  if ($result == 0 )
    {
      $sql = "DELETE from vtiger.tracker WHERE user_id='$user_id' and item_id='$tid'";
      $resql = $db->query($sql);
      $result = 1;
      if ( $resql )
	{
	  $result = 0;
	}
    }

  if ($result == 0 )
    {
      $sql = "INSERT INTO vtiger.tracker ";
      $sql .= " (user_id, module_name, item_id, item_summary) values ";
      $sql .= " ('".$user_id."', 'HelpDesk', '".$tid."', 'Test pour dev')";
      
      $resql = $db->query($sql);
      $result = 1;
      if ( $resql )
	{
	  $result = 0;
	}
    }

  if ($result == 0)
    {
      $db->commit();
      print "COMMIT $tid\n";
    }
  else
    {
      $db->rollback();
      print "ROLLBACK $tid\n";
    }

}


