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

$month = "10";
$year = "2005";


$sqls  = "SELECT l.rowid,l.ligne,l.fk_commercial_suiv";
$sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sqls .= " WHERE l.rowid =f.fk_ligne AND f.cout_vente < f.fourn_montant";
$sqls .= " AND f.date ='".$year."-".$month."-01'";

$resqls = $db->query($sqls);  
if ( $resqls )
{
  while ($obj = $db->fetch_object($resqls))
    {
      $title = "Marge négative ligne ".$obj->ligne." pour $month-$year";
      $desc = "La facturation de la ligne ".$obj->ligne. " présente une marge négative en $month/$year";
      $userlid = $obj->fk_commercial_suiv;

      if ($db->begin())
	{
	  $result = -1;
	  
	  $sql = "SELECT vtiger_id from ".MAIN_DB_PREFIX."vtiger_users";
	  $sql .= " WHERE fk_user =".$userlid.";";
	  $result = 1;
	  $resql = $db->query($sql);  
	  if ( $resql )
	    {
	      if ($row = $db->fetch_row($resql))
		{
		  $user_id = $row[0];
		  $result = 0;
		}
	      else
		{
		  print "Error user id missing\n";
		}
	    }
	  else
	    {
	      print "Error 7\n";
	    }
	  
	  if ($result == 0)
	    {
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
	    }
	  
	  if ($result == 0)
	    {
	      $sql = "SELECT user_name from vtiger.users where id =".$user_id.";";
	      $result = 1;
	      $resql = $db->query($sql);  
	      if ( $resql )
		{
		  if ($row = $db->fetch_row($resql))
		    {
		      $username = $row[0];
		      $result = 0;
		    }
		  else
		    {
		      print "Error 4 Missing id for $user_id\n";
		    }
		}
	      else
		{
		  print "Error 5\n";
		}
	    }
	  
	  
	  if ($result == 0)
	    {
	      $sql = "SELECT id from vtiger.crmentity_seq;";
	      $result = 1;
	      $resql = $db->query($sql);  
	      $tid = 0;
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
	      $sql .= " (".$tid.", '', 'High', '', 'Major', 'Open', 'Big Problem','";
	      $sql .= strftime("%E %d %B %Y %H:%M:%S", time())." by dolibarr--//--Ticket created. Assigned to $username--//--'";
	      $sql .= ", '".$title."', '".$desc."', '')";
	      
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
	  
	  
	  
	  if ($result == 0 && $tid > 0)
	    {
	      $sql = "INSERT INTO vtiger.crmentity ";
	      $sql .= " (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) ";
	      $sql .= " VALUES ('".$tid."','$user_id','$user_id','HelpDesk','".$desc."',now(),now())";
	      
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
    }
}


