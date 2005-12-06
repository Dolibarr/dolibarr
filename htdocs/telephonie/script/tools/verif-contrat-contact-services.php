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
 * Vérifie que les contrats qui n'ont pas de contact facture correct
 * ont au moins un services d'envoi courrier.
 *
 */
require ("../../../master.inc.php");
/*
 *
 */

$sql  = "SELECT rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat";

$resql = $db->query($sql) ;

if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      $sqlc  = "SELECT count(*)";
      $sqlc .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_contact_facture";
      $sqlc .= " WHERE fk_contrat =".$row[0];

      $resqlc = $db->query($sqlc) ;
      if ( $resqlc )
	{
	  $rowc = $db->fetch_row($resqlc);
	  if ($rowc[0] == 0)
	    {
	      $sqls  = "SELECT count(*)";
	      $sqls .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service";
	      $sqls .= " WHERE fk_contrat =".$row[0];
	      $sqls .= " AND fk_service in (1,2);";

	      $resqls = $db->query($sqls) ;
	      if ( $resqls )
		{
		  $rows = $db->fetch_row($resqls);
		  if ($rows[0] == 0)
		    {
		      $sqll  = "SELECT count(*)";
		      $sqll .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
		      $sqll .= " WHERE fk_contrat =".$row[0];
		      $sqll .= " AND statut = 3;";
		      
		      $resqll = $db->query($sqll) ;
		      if ( $resqll )
			{
			  $rowl = $db->fetch_row($resqll);
			  if ($rowl[0] > 0)
			    {
			      print "Contrat ".$row[0]." sans contact ni envoi courrier\n";
			    }
			}
		    }
		}
	    }
	}
      else
	{
	  print $db->error();
	}
      
    }
}
else
{
  print $db->error();
}

$db->close();
?>
