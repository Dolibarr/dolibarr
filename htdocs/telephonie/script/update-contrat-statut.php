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
 * Mise à jours des statuts des contrats
 * Script de migration de la nouvelle structure de la base
 *
 */

require ("../../master.inc.php");
require(DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");

$contrats = array();

$sql = "SELECT rowid FROM llx_telephonie_contrat;";

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
  die ("Error $sql");
}

dol_syslog("Update contrats ".sizeof($contrats));

for ($i = 0 ; $i < sizeof($contrats) ; $i++)
{  
  $numc = 0;

  $contrat = new TelephonieContrat($db);
  $contrat->id = $contrats[$i];
  $contrat->update_statut();

}
?>
