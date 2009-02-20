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
 */

require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/distributeurtel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");

$error = 0;
$nbcommit = 0;
$datetime = time();

$user = new User($db, 1);
$user->login = "Rodo";

$sql = "SELECT rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat";
$sql .= " WHERE fk_client_comm = 52";
  
$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      print $row[0];

      $sqll = "SELECT rowid ";
      $sqll .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
      $sqll .= " WHERE fk_contrat = ".$row[0];
      $sqll .= " AND statut = 3";

      $resqll = $db->query($sqll);
      
      if ( $resqll )
	{
	  $numl = $db->num_rows($resqll);
	  $j = 0;
	  
	  while ($j < $numl)
	    {
	      $row_l = $db->fetch_row($resqll);

	      print " ".$row_l[0];
	      $lignes++;

	      $ligne = new LigneTel($db);
	      $ligne->fetch_by_id($row_l[0]);

	      if ( $ligne->transfer($user,4) == 0)
		{
		
		}
	      else
		{
		  Print "Error ligne $row_l[0]\n";
		}

	      $j++;
	    }
	}

      print "\n";

      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 1;
  dol_syslog("Verification Erreur ".$error);
}

print "lignes : $lignes\n";

?>
