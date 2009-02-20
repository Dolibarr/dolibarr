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
 * Mise à jours des contacts des contrats à partir des contacts des lignes
 * Script de migration de la nouvelle structure de la base
 *
 */

require ("../../master.inc.php");

$contrats = array();

$sql = "SELECT c.rowid, cc.rowid FROM llx_telephonie_contrat as c LEFT JOIN llx_telephonie_contrat_contact_facture as cc ON cc.fk_contrat = c.rowid WHERE cc.fk_contact is null";

if ($db->query($sql))
{
  $i = 0;
  $num = $db->num_rows();

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $contrats[$i] = $row[0];
      $i++;
    }

  $db->free();
}
else
{
  print "Errir";
}

dol_syslog("Update contrats ".sizeof($contrats));

for ($i = 0 ; $i < sizeof($contrats) ; $i++)
{  
  $numc = 0;

  $sql = "SELECT distinct(c.fk_contact) FROM llx_telephonie_contact_facture as c, llx_telephonie_societe_ligne as l";
  $sql .= " WHERE l.rowid = c.fk_ligne";
  $sql .= " AND l.fk_contrat = ".$contrats[$i];


  if ($db->query($sql))
    {      
      $numc = $db->num_rows();
      
      if ($numc == 1)
	{
	  $obc = $db->fetch_object();	  
	  $idcon = $obc->fk_contact;
	}
      $db->free();
    }
  else
    {
      print "Erreur";
    }

  if ($numc == 1)
    {
      $sql = "INSERT INTO llx_telephonie_contrat_contact_facture";
      $sql .= " (fk_contrat, fk_contact) ";
      $sql .= " VALUES (".$contrats[$i].",".$idcon.")";
      
      if (!$db->query($sql))
	{
	  dol_syslog("Erreur ");
	}
      else
	{
	  dol_syslog("Update contrat ".$contrats[$i]);
	}
    }
  else
    {

      if ($numc > 0)
	{
	  print "$contrats[$i] $numc\n";
	  print $sql."\n" ;
	}
    }
}
?>
